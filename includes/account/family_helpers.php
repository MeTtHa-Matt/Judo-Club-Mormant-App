<?php

function jcm_ensure_family_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS families (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES account(id) ON DELETE CASCADE
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS family_members (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            family_id INT NOT NULL,
            account_id INT NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT "parent",
            added_by_account_id INT NULL DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_family_account (family_id, account_id),
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
            FOREIGN KEY (account_id) REFERENCES account(id) ON DELETE CASCADE,
            FOREIGN KEY (added_by_account_id) REFERENCES account(id) ON DELETE SET NULL,
            INDEX (family_id),
            INDEX (account_id),
            INDEX (added_by_account_id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS family_link_requests (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            family_id INT NOT NULL,
            child_profile_id INT NOT NULL,
            parent_account_id INT NOT NULL,
            child_account_id INT NOT NULL,
            status VARCHAR(30) NOT NULL DEFAULT "pending_child",
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_family_link_request (child_profile_id, child_account_id),
            INDEX idx_family_link_target (child_account_id, status),
            FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
            FOREIGN KEY (child_profile_id) REFERENCES child_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (parent_account_id) REFERENCES account(id) ON DELETE CASCADE,
            FOREIGN KEY (child_account_id) REFERENCES account(id) ON DELETE CASCADE
        )'
    );

    $addedByColumn = $pdo->query("SHOW COLUMNS FROM family_members LIKE 'added_by_account_id'")->fetchAll();
    if (empty($addedByColumn)) {
        $pdo->exec('ALTER TABLE family_members ADD COLUMN added_by_account_id INT NULL AFTER role');
        $pdo->exec('ALTER TABLE family_members ADD CONSTRAINT fk_family_members_added_by FOREIGN KEY (added_by_account_id) REFERENCES account(id) ON DELETE SET NULL');
    }

    $columns = $pdo->query("SHOW COLUMNS FROM child_profiles LIKE 'family_id'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec('ALTER TABLE child_profiles ADD COLUMN family_id INT NULL AFTER account_id');
        $pdo->exec('ALTER TABLE child_profiles ADD INDEX idx_child_profiles_family (family_id)');
        $pdo->exec('ALTER TABLE child_profiles ADD CONSTRAINT fk_child_profiles_family FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE SET NULL');
    }

    $accountIds = $pdo->query(
        'SELECT a.id
         FROM account a
         WHERE NOT EXISTS (
             SELECT 1 FROM family_members fm WHERE fm.account_id = a.id
         )
         OR EXISTS (
             SELECT 1 FROM child_profiles cp
             WHERE cp.account_id = a.id AND cp.family_id IS NULL
         )'
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach ($accountIds as $accountId) {
        $familyId = jcm_get_account_family_id($pdo, (int) $accountId);
        if ($familyId === null) {
            $familyId = jcm_create_family_for_account($pdo, (int) $accountId);
        }

        $childrenStmt = $pdo->prepare('UPDATE child_profiles SET family_id = ? WHERE account_id = ? AND family_id IS NULL');
        $childrenStmt->execute([$familyId, $accountId]);
    }

    $pdo->exec(
        'UPDATE family_members fm
         SET added_by_account_id = (
             SELECT f.created_by
             FROM families f
             WHERE f.id = fm.family_id
             LIMIT 1
         )
         WHERE fm.added_by_account_id IS NULL'
    );
}

function jcm_get_account_family_id(PDO $pdo, int $accountId): ?int
{
    $stmt = $pdo->prepare(
        'SELECT family_id FROM family_members WHERE account_id = ? LIMIT 1'
    );
    $stmt->execute([$accountId]);
    $familyId = $stmt->fetchColumn();

    return $familyId === false || $familyId === null ? null : (int) $familyId;
}

function jcm_create_family_for_account(PDO $pdo, int $accountId): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO families (created_by) VALUES (?)');
        $stmt->execute([$accountId]);
        $familyId = (int) $pdo->lastInsertId();

        $memberStmt = $pdo->prepare(
            'INSERT INTO family_members (family_id, account_id, role, added_by_account_id) VALUES (?, ?, "parent", ?)'
        );
        $memberStmt->execute([$familyId, $accountId, $accountId]);

        $pdo->commit();
        return $familyId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function jcm_get_or_create_family_for_account(PDO $pdo, int $accountId): int
{
    $existingFamilyId = jcm_get_account_family_id($pdo, $accountId);
    if ($existingFamilyId !== null) {
        return $existingFamilyId;
    }

    return jcm_create_family_for_account($pdo, $accountId);
}

function jcm_get_family_members(PDO $pdo, int $familyId): array
{
    $stmt = $pdo->prepare(
        'SELECT a.id, a.firstname, a.lastname, a.email, fm.role, fm.added_by_account_id, fm.account_id AS member_account_id
         FROM family_members fm
         JOIN account a ON a.id = fm.account_id
         WHERE fm.family_id = ?
         ORDER BY a.lastname, a.firstname'
    );
    $stmt->execute([$familyId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function jcm_can_remove_family_member(PDO $pdo, int $actorAccountId, int $familyId, int $memberAccountId): bool
{
    $stmt = $pdo->prepare(
        'SELECT CASE
            WHEN fm.added_by_account_id = :addedByActor THEN 1
            WHEN f.created_by = :familyCreatorActor THEN 1
            ELSE 0
         END AS can_remove
         FROM family_members fm
         JOIN families f ON f.id = fm.family_id
         WHERE fm.family_id = :familyId AND fm.account_id = :memberAccountId
         LIMIT 1'
    );
    $stmt->bindValue(':addedByActor', $actorAccountId, PDO::PARAM_INT);
    $stmt->bindValue(':familyCreatorActor', $actorAccountId, PDO::PARAM_INT);
    $stmt->bindValue(':familyId', $familyId, PDO::PARAM_INT);
    $stmt->bindValue(':memberAccountId', $memberAccountId, PDO::PARAM_INT);
    $stmt->execute();

    return (int) ($stmt->fetchColumn() ?? 0) === 1;
}

function jcm_accounts_share_family(PDO $pdo, int $firstAccountId, int $secondAccountId): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM family_members first_member
         JOIN family_members second_member ON second_member.family_id = first_member.family_id
         WHERE first_member.account_id = ? AND second_member.account_id = ?
         LIMIT 1'
    );
    $stmt->execute([$firstAccountId, $secondAccountId]);

    return (bool) $stmt->fetchColumn();
}

function jcm_remove_family_member(PDO $pdo, int $actorAccountId, int $familyId, int $memberAccountId): bool
{
    if (!jcm_can_remove_family_member($pdo, $actorAccountId, $familyId, $memberAccountId)) {
        return false;
    }

    if ((int) $actorAccountId === (int) $memberAccountId) {
        return false;
    }

    $stmt = $pdo->prepare('DELETE FROM family_members WHERE family_id = ? AND account_id = ? LIMIT 1');
    $stmt->execute([$familyId, $memberAccountId]);

    return $stmt->rowCount() > 0;
}

function jcm_link_account_to_family(PDO $pdo, int $parentAccountId, int $targetAccountId): int
{
    if ($parentAccountId === $targetAccountId) {
        return jcm_get_or_create_family_for_account($pdo, $parentAccountId);
    }

    $sourceFamilyId = jcm_get_or_create_family_for_account($pdo, $parentAccountId);
    $targetFamilyId = jcm_get_account_family_id($pdo, $targetAccountId);

    $existingMembership = $pdo->prepare(
        'SELECT 1 FROM family_members WHERE family_id = ? AND account_id = ? LIMIT 1'
    );
    $existingMembership->execute([$sourceFamilyId, $targetAccountId]);
    if ($existingMembership->fetchColumn()) {
        return $sourceFamilyId;
    }

    if ($targetFamilyId === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO family_members (family_id, account_id, role, added_by_account_id) VALUES (?, ?, "parent", ?)'
        );
        $stmt->execute([$sourceFamilyId, $targetAccountId, $parentAccountId]);
        return $sourceFamilyId;
    }

    if ((int) $targetFamilyId === (int) $sourceFamilyId) {
        return $sourceFamilyId;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE child_profiles SET family_id = ? WHERE family_id = ?'
        );
        $stmt->execute([$sourceFamilyId, $targetFamilyId]);

        $memberStmt = $pdo->prepare(
            'UPDATE family_members SET family_id = ? WHERE family_id = ?'
        );
        $memberStmt->execute([$sourceFamilyId, $targetFamilyId]);

        $deleteStmt = $pdo->prepare('DELETE FROM families WHERE id = ?');
        $deleteStmt->execute([$targetFamilyId]);

        $pdo->commit();
        return $sourceFamilyId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function jcm_get_family_account_match_candidates(PDO $pdo, int $familyId, ?int $childId = null): array
{
    $sql = 'SELECT id, account_id, firstname, lastname
            FROM child_profiles WHERE family_id = ?';
    $params = [$familyId];
    if ($childId !== null) {
        $sql .= ' AND id = ?';
        $params[] = $childId;
    }
    $sql .= ' ORDER BY id DESC';
    $childrenStmt = $pdo->prepare($sql);
    $childrenStmt->execute($params);
    $children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($children)) {
        return [];
    }

    $accountsStmt = $pdo->prepare(
        'SELECT a.id, a.firstname, a.lastname, a.email
         FROM account a
         WHERE NOT EXISTS (
             SELECT 1 FROM family_members fm
             WHERE fm.family_id = ? AND fm.account_id = a.id
         )
         ORDER BY a.lastname, a.firstname, a.id'
    );
    $accountsStmt->execute([$familyId]);
    $accounts = $accountsStmt->fetchAll(PDO::FETCH_ASSOC);

    $requestsStmt = $pdo->prepare(
        'SELECT child_profile_id, child_account_id
         FROM family_link_requests WHERE family_id = ? AND status <> "stale"'
    );
    $requestsStmt->execute([$familyId]);
    $existingRequests = [];
    foreach ($requestsStmt->fetchAll(PDO::FETCH_ASSOC) as $request) {
        $existingRequests[(int) $request['child_profile_id'] . ':' . (int) $request['child_account_id']] = true;
    }

    $matches = [];
    foreach ($children as $child) {
        foreach ($accounts as $account) {
            if ((int) $child['account_id'] === (int) $account['id']
                || !jcm_person_names_match(
                    (string) $child['firstname'],
                    (string) $child['lastname'],
                    (string) $account['firstname'],
                    (string) $account['lastname']
                )) {
                continue;
            }

            $key = (int) $child['id'] . ':' . (int) $account['id'];
            if (isset($existingRequests[$key])) {
                continue;
            }

            $matches[] = [
                'child_id' => (int) $child['id'],
                'child_firstname' => $child['firstname'],
                'child_lastname' => $child['lastname'],
                'account_id' => (int) $account['id'],
                'account_firstname' => $account['firstname'],
                'account_lastname' => $account['lastname'],
                'email' => $account['email'],
            ];
        }
    }

    return $matches;
}

function jcm_normalize_person_name(string $name): string
{
    $normalized = mb_strtolower(trim($name), 'UTF-8');
    if (function_exists('iconv')) {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if ($transliterated !== false) {
            $normalized = $transliterated;
        }
    } else {
        $normalized = strtr($normalized, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'ae', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ò' => 'o',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'œ' => 'oe', 'ù' => 'u',
            'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss',
        ]);
    }

    return preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
}

function jcm_person_name_matches(string $profileName, string $accountName): bool
{
    $profileNormalized = jcm_normalize_person_name($profileName);
    $accountNormalized = jcm_normalize_person_name($accountName);
    if ($profileNormalized === '' || $accountNormalized === '') {
        return false;
    }
    if ($profileNormalized === $accountNormalized) {
        return true;
    }

    $longestLength = max(strlen($profileNormalized), strlen($accountNormalized));
    $shortestName = strlen($profileNormalized) <= strlen($accountNormalized)
        ? $profileNormalized
        : $accountNormalized;
    $longestName = strlen($profileNormalized) > strlen($accountNormalized)
        ? $profileNormalized
        : $accountNormalized;
    if (strlen($shortestName) >= 4 && str_contains($longestName, $shortestName)) {
        return true;
    }

    $distance = levenshtein($profileNormalized, $accountNormalized);
    $maxDistance = $longestLength <= 3 ? 0 : ($longestLength <= 6 ? 1 : 2);

    return $distance <= $maxDistance
        && ($longestLength === 0 || 1 - ($distance / $longestLength) >= 0.75);
}

function jcm_person_names_match(
    string $profileFirstname,
    string $profileLastname,
    string $accountFirstname,
    string $accountLastname
): bool {
    return jcm_person_name_matches($profileFirstname, $accountFirstname)
        && jcm_person_name_matches($profileLastname, $accountLastname);
}

function jcm_find_family_child_profile_by_name(PDO $pdo, int $familyId, string $firstname, string $lastname): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, account_id, firstname, lastname, annee_naissance, id_ceinture, Poids
         FROM child_profiles
         WHERE family_id = ?
         ORDER BY id DESC'
    );
    $stmt->execute([$familyId]);
    $matches = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $profile) {
        if (jcm_normalize_person_name((string) $profile['firstname']) === jcm_normalize_person_name($firstname)
            && jcm_normalize_person_name((string) $profile['lastname']) === jcm_normalize_person_name($lastname)) {
            $matches[] = $profile;
        }
    }

    return count($matches) === 1 ? $matches[0] : null;
}

function jcm_get_pending_family_link_request(PDO $pdo, int $accountId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT r.id, r.family_id, r.child_profile_id, r.parent_account_id,
                parent.firstname AS parent_firstname, parent.lastname AS parent_lastname, parent.email AS parent_email,
                cp.firstname AS child_firstname, cp.lastname AS child_lastname
         FROM family_link_requests r
         JOIN account parent ON parent.id = r.parent_account_id
         JOIN child_profiles cp ON cp.id = r.child_profile_id AND cp.family_id = r.family_id
         WHERE r.child_account_id = ? AND r.status = "pending_child"
           AND NOT EXISTS (
               SELECT 1 FROM family_members fm
               WHERE fm.family_id = r.family_id AND fm.account_id = r.child_account_id
           )
         ORDER BY r.created_at ASC, r.id ASC
         LIMIT 1'
    );
    $stmt->execute([$accountId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    return $request ?: null;
}
