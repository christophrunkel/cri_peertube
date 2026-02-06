<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Peertube online media helper',
    'description' => 'Media helper for Peertube - an indepentent Video streaming platform. Improve your digital independendence',
    'category' => 'plugin',
    'author' => 'Christoph Runkel',
    'author_email' => 'dialog@christophrunkel.de',
    'state' => 'beta',
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
