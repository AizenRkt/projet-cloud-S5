<?php
return [
    'doc-dir' => storage_path('api-docs'),
    'doc-route' => 'docs',
    'annotations' => [
        base_path('app/Http/Controllers/Api'), // chemin vers tes Controllers annotés
    ],
    'excludes' => [],
    'generateAlways' => true,
];
