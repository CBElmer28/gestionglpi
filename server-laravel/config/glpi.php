<?php

return [
    'url'        => env('GLPI_URL', 'http://localhost:8080/api.php/v1'),
    'app_token'  => env('GLPI_APP_TOKEN', ''),
    'user_token' => env('GLPI_USER_TOKEN', ''),
    // Tipo de ítem personalizado creado en GLPI (Activos > Libros)
    // Nombre real: Glpi\CustomAsset\LibrosAsset — en URL se usa con backslashes
    'book_itemtype' => env('GLPI_BOOK_ITEMTYPE', 'Glpi\\CustomAsset\\LibrosAsset'),
    // TTL del session_token cacheado (en segundos — 50 minutos)
    'session_ttl' => 3000,
];
