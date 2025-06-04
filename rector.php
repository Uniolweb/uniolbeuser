<?php

declare(strict_types=1);

use  Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\TYPO311\v5\FlexFormToolsArrayValueByPathRector;

/**
 * composer require --dev ssch/typo3-rector
 *
 * Updated for:
 * - TYPO3 v12
 * - latest ssch/typo3-rector (2.6.0)
 * - PHP >=8.1
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__,
    ]);

    // register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Add some general TYPO3 rules
    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);
    $rectorConfig->ruleWithConfiguration(ExtEmConfRector::class, [
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ]);

    //define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ]);

    // Define your target version which you want to support
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    // If you only want to process one/some TYPO3 extension(s), you can specify its path(s) here.
    // If you use the option --config change __DIR__ to getcwd()
    // $rectorConfig->paths([
    //    __DIR__ . '/packages/acme_demo/',
    // ]);

    // When you use rector there are rules that require some more actions like creating UpgradeWizards for outdated TCA types.
    // To fully support you we added some warnings. So watch out for them.

    // If you use importNames(), you should consider excluding some TYPO3 files.
    $rectorConfig->skip([
        // @see https://github.com/sabbelasichon/typo3-rector/issues/2536
        __DIR__ . '/**/Configuration/ExtensionBuilder/*',
        // We skip those directories on purpose as there might be node_modules or similar
        // that include typescript which would result in false positive processing
        __DIR__ . '/**/Resources/**/node_modules/*',
        __DIR__ . '/**/Resources/**/NodeModules/*',
        __DIR__ . '/**/Resources/**/BowerComponents/*',
        __DIR__ . '/**/Resources/**/bower_components/*',
        __DIR__ . '/**/Resources/**/build/*',
        __DIR__ . '/vendor/*',
        __DIR__ . '/Build/*',
        __DIR__ . '/public/*',
        __DIR__ . '/.github/*',
        __DIR__ . '/.Build/*',
        NameImportingPostRector::class => [
            //'ext_localconf.php',
            //'ext_tables.php',
            'ClassAliasMap.php',
            __DIR__ . '/**/Configuration/*.php',
            __DIR__ . '/**/Configuration/**/*.php',
        ],
        // problem with rector and FlexformTools::cleanFlexFormXML
        // see https://github.com/sabbelasichon/typo3-rector/issues/4279
        // should see changelog: https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5/Deprecation-95254-TwoFlexFormToolsMethods.html)
        // and apply changes for FlexformTools::getArrayValueByPath and FlexformTools::setArrayValueByPath
        FlexFormToolsArrayValueByPathRector::class => [
            __DIR__ . '/Classes/**/*.php',
        ]
    ]);
};
