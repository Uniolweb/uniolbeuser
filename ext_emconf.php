<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'BE user BE module',
    'description' => '',
    'category' => 'module',
    'author' => 'Sybille Peters',
    'author_email' => 'sybille.peters@uni-oldenburg.de',
    'author_company' => 'Carl von Ossietzky Universität Oldenburg',
    'state' => 'stable',
    'version' => '1.0.3',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.32-12.9.99',
            'fontawesome_provider' => '1.0.2-1.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
