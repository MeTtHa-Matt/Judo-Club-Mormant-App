<?php

$defaultIndexLinks = [
    'home_inscription' => [
        'label' => 'Espace Adhésion',
        'title' => 'Inscription en ligne',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/adhesions/formulaire-d-inscription-2024-2025',
    ],
    'home_kimonos' => [
        'label' => 'Kimonos & Dossards',
        'title' => 'Kimonos & Dossards',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/boutiques/kimonos-club-adidas-dossards-2024-2025',
    ],
    'home_vetements' => [
        'label' => 'Vêtements Club',
        'title' => 'Vêtements Club',
        'url' => 'https://market-factory.fr/judo-club-mormant/',
    ],
    'home_mon_compte' => [
        'label' => 'Mon compte FFJudo',
        'title' => 'Mon compte FFJudo',
        'url' => 'https://moncompte.ffjudo.com',
    ],
    'home_craqs' => [
        'label' => 'Partenaire Les Craq\'s',
        'title' => 'Commander les barres',
        'url' => 'https://www.helloasso.com/associations/judo-club-mormant/boutiques/barres-de-cereales-artisanales',
    ],
];

$indexLinks = $defaultIndexLinks;

try {
    $stmt = $pdo->prepare('SELECT link_key, label, title, url FROM index_links_jcm ORDER BY display_order, id');
    $stmt->execute();
    $storedLinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($storedLinks as $storedLink) {
        if (isset($indexLinks[$storedLink['link_key']])) {
            $indexLinks[$storedLink['link_key']]['label'] = $storedLink['label'] ?? $indexLinks[$storedLink['link_key']]['label'];
            $indexLinks[$storedLink['link_key']]['title'] = $storedLink['title'] ?? $indexLinks[$storedLink['link_key']]['title'];
            $indexLinks[$storedLink['link_key']]['url'] = $storedLink['url'] ?? $indexLinks[$storedLink['link_key']]['url'];
        }
    }
} catch (PDOException $e) {
    $indexLinks = $defaultIndexLinks;
}

$homeInscriptionLink = $indexLinks['home_inscription'];
$homeKimonosLink = $indexLinks['home_kimonos'];
$homeVetementsLink = $indexLinks['home_vetements'];
$homeMonCompteLink = $indexLinks['home_mon_compte'];
$homeCraqsLink = $indexLinks['home_craqs'];
