<?php

return [
    'manifest' => [
        'id' => env('PWA_ID', '/'),
        'version' => env('PWA_VERSION', '1.0.0.1'),
        'name' => env('PWA_NAME', 'finWise - Catatan Keuangan'),
        'short_name' => env('PWA_SHORT_NAME', 'finWise'),
        'description' => env('PWA_DESCRIPTION', 'Catat dan pantau keuangan pribadi.'),
        'start_url' => env('PWA_START_URL', '/dashboard'),
        'scope' => env('PWA_SCOPE', '/'),
        'display' => env('PWA_DISPLAY', 'standalone'),
        'background_color' => env('PWA_BACKGROUND_COLOR', '#F1FFF3'),
        'theme_color' => env('PWA_THEME_COLOR', '#00D09E'),
        'icons' => [[
            'src' => env('PWA_ICON_SRC', '/icons/finwise.svg'),
            'sizes' => env('PWA_ICON_SIZES', 'any'),
            'type' => env('PWA_ICON_TYPE', 'image/svg+xml'),
            'purpose' => env('PWA_ICON_PURPOSE', 'any maskable'),
        ]],
    ],
];
