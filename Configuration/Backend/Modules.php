<?php

declare(strict_types=1);

return [
    // Extbase module
    'web_UniolbeuserInfo' => [
        // main module
        'parent' => 'web',
        // The module position. Allowed values are before => <identifier> and after => <identifier>. To define modules on top or at the bottom, before => * and after => * can be used. Using the top and bottom values (without key) is deprecated and will be removed in upcoming versions.
        'position' => ['after' => 'web_info'],
        // Can be user (editor permissions), admin, or systemMaintainer.
        'access' => 'user',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-beuser',
        //'iconIdentifier' => 'beuser-module',
        'path' => '/module/web/UniolbeuserInfo',
        // Either array or path to locallang file
        //  The referenced file should contain the following label keys: - mlang_tabs_tab (Used as module title) - mlang_labels_tabdescr (Used as module description) - mlang_labels_tablabel (Used as module short description)
        'labels' => 'LLL:EXT:uniolbeuser/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'uniolbeuser',
        'controllerActions' => [
            \Uniolweb\Uniolbeuser\Controller\BackendModuleController::class => [
                'list',
            ],
        ],
    ],
];
