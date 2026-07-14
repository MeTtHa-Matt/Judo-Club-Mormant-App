<?php
if (!isset($_SESSION['admin']) || (int) $_SESSION['admin'] !== 1) {
    header('Location: index.php');
    exit;
}

$defaultLinks = [
    [
        'key' => 'home_inscription',
        'description' => 'Bouton "Inscription en ligne"',
        'title' => 'Inscription en ligne',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/adhesions/formulaire-d-inscription-2024-2025',
    ],
    [
        'key' => 'home_kimonos',
        'description' => 'Bouton "Kimonos & Dossards"',
        'title' => 'Kimonos & Dossards',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/boutiques/kimonos-club-adidas-dossards-2024-2025',
    ],
    [
        'key' => 'home_vetements',
        'description' => 'Bouton "Vêtements Club"',
        'title' => 'Vêtements Club',
        'url' => 'https://market-factory.fr/judo-club-mormant/',
    ],
    [
        'key' => 'home_mon_compte',
        'description' => 'Bouton "Mon compte FFJudo"',
        'title' => 'Mon compte FFJudo',
        'url' => 'https://moncompte.ffjudo.com',
    ],
    [
        'key' => 'home_craqs',
        'description' => 'Bouton "Commander les barres"',
        'title' => 'Commander les barres',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/boutiques/barres-de-cereales-artisanales',
    ],
];

$errors = [];
$success = false;
$hasTable = true;

$storedByKey = [];
try {
    $stmt = $pdo->query('SELECT link_key, title, url, display_order FROM index_links_jcm ORDER BY display_order, id');
    $storedLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $storedLinks = [];
    $hasTable = false;
}

foreach ($storedLinks as $storedLink) {
    $storedByKey[$storedLink['link_key']] = $storedLink;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO index_links_jcm (link_key, label, title, url, display_order) VALUES (?, ?, ?, ?, ?) '
                    . 'ON DUPLICATE KEY UPDATE label = VALUES(label), title = VALUES(title), url = VALUES(url), display_order = VALUES(display_order)'
            );

            foreach ($defaultLinks as $index => $link) {
                $key = $link['key'];
                $title = $storedByKey[$key]['title'] ?? $link['title'];
                $url = trim($_POST[$key . '_url'] ?? '');

                if ($url === '') {
                    $errors[] = 'L\'URL est obligatoire pour ' . $link['description'] . '.';
                    continue;
                }

                $stmt->execute([$key, $title, $title, $url, $index + 1]);
            }

            if ($errors === []) {
                $success = true;
            }
        } catch (PDOException $e) {
            $hasTable = false;
            $errors[] = 'La table index_links_jcm n\'existe pas encore. Ajoutez le SQL présent dans db.sql.';
        }
    }
}

$linksToRender = [];
foreach ($defaultLinks as $index => $link) {
    $stored = $storedByKey[$link['key']] ?? [];
    $linksToRender[] = [
        'key' => $link['key'],
        'description' => $link['description'],
        'title' => $stored['title'] ?? $link['title'],
        'url' => $stored['url'] ?? $link['url'],
        'display_order' => $stored['display_order'] ?? ($index + 1),
    ];
}
