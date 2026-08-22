<?php

function jcm_get_tutorial_steps(string $platform): array
{
    $platform = strtolower($platform);

    $tutorials = [
        'ios' => [
            [
                'title' => 'Étape 1',
                'image' => 'img/tuto ios/1.jpeg',
                'caption' => 'Appuyez sur le bouton de partage'
            ],
            [
                'title' => 'Étape 2',
                'image' => 'img/tuto ios/2.jpeg',
                'caption' => 'Appuyer sur « En savoir plus »'
            ],
            [
                'title' => 'Étape 3',
                'image' => 'img/tuto ios/3.jpeg',
                'caption' => 'Cliquez sur " + Sur l’écran d’accueil"'
            ],
            [
                'title' => 'Étape 4',
                'image' => 'img/tuto ios/4.jpeg',
                'caption' => 'Appuyez sur "Ajouter la web app"'
            ],
        ],
        'android' => [
            [
                'title' => 'Étape 1',
                'image' => 'img/tuto android/1.jpg',
                'caption' => 'Cliquez sur les 3 petits points pour ouvrir le menu du navigateur'
            ],
            [
                'title' => 'Étape 2',
                'image' => 'img/tuto android/2.jpg',
                'caption' => 'Faire défiler jusqu’en bas pour trouver l’option d’installation'
            ],
            [
                'title' => 'Étape 3',
                'image' => 'img/tuto android/3.jpg',
                'caption' => 'Choisir l’option installer puis créer un raccourci'
            ],
            [
                'title' => 'Étape 4',
                'image' => 'img/tuto android/4.jpg',
                'caption' => 'Appuyer sur Installer pour installer la web app sur votre appareil'
            ],
        ],
    ];

    return $tutorials[$platform] ?? [];
}

function jcm_render_tutorial(string $platform): string
{
    $steps = jcm_get_tutorial_steps($platform);
    if (empty($steps)) {
        return '';
    }

    $output = '<div class="tutorial-grid">';

    foreach ($steps as $index => $step) {
        $output .= '<article class="install-step">';
        $output .= '  <div class="install-step-number">' . ($index + 1) . '</div>';
        $output .= '  <div class="install-step-card">';
        $output .= '    <div class="install-step-image-wrap">';
        $output .= '      <img src="' . htmlspecialchars($step['image'], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($step['title'] . ' - ' . $platform, ENT_QUOTES, 'UTF-8') . '" class="install-step-image">';
        $output .= '    </div>';
        $output .= '    <div class="install-step-body">';
        $output .= '      <h3 class="install-step-title">' . htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8') . '</h3>';
        $output .= '      <p class="install-step-caption">' . htmlspecialchars($step['caption'], ENT_QUOTES, 'UTF-8') . '</p>';
        $output .= '    </div>';
        $output .= '  </div>';
        $output .= '</article>';
    }

    $output .= '</div>';

    return $output;
}
