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

    $accountIds = $pdo->query('SELECT id FROM account')->fetchAll(PDO::FETCH_COLUMN);
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
            WHEN fm.added_by_account_id = :actor THEN 1
            WHEN f.created_by = :actor THEN 1
            ELSE 0
         END AS can_remove
         FROM family_members fm
         JOIN families f ON f.id = fm.family_id
         WHERE fm.family_id = :familyId AND fm.account_id = :memberAccountId
         LIMIT 1'
    );
    $stmt->bindValue(':actor', $actorAccountId, PDO::PARAM_INT);
    $stmt->bindValue(':familyId', $familyId, PDO::PARAM_INT);
    $stmt->bindValue(':memberAccountId', $memberAccountId, PDO::PARAM_INT);
    $stmt->execute();

    return (int) ($stmt->fetchColumn() ?? 0) === 1;
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
