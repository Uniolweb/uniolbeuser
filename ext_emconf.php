<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'BE user BE module',
    'description' => '',
    'category' => 'module',
    'author' => 'Sybille Peters',
    'author_email' => 'sybille.peters@uni-oldenburg.de',
    'author_company' => 'Carl von Ossietzky Universität Oldenburg',
    'state' => 'stable',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
            'fontawesome_provider' => '1.0.2-1.99.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
