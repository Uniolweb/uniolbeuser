<?php

declare(strict_types=1);
namespace Uniolweb\Uniolbeuser\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class UniolbeuserConfiguration
{
    /**
     * @var array<int,int>
     */
    protected array $visiblePageTypes = [1];

    /**
     * @var array<int,int>
     */
    protected array $ignorePageDbMounts = [];

    protected bool $blurBeUsernames = false;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->inializeValues($extensionConfiguration);
    }

    protected function inializeValues(ExtensionConfiguration $extensionConfiguration): void
    {
        // extconf 'visiblePageTypes'
        $visiblePageTypes = $this->convertCsvStringToIntArray($extensionConfiguration->get('uniolbeuser', 'visiblePageTypes'));
        if ($visiblePageTypes) {
            $this->visiblePageTypes = $visiblePageTypes;
        } else {
            $this->visiblePageTypes = [1];
        }

        // extconf 'ignorePageDbMounts'
        $this->ignorePageDbMounts = $this->convertCsvStringToIntArray($extensionConfiguration->get('uniolbeuser', 'ignorePageDbMounts'));

        // extconf: 'blurBeUsernames'
        $this->blurBeUsernames = (bool)$extensionConfiguration->get('uniolbeuser', 'blurBeUsernames');
    }

    /**
     * @return array<int,int>
     */
    protected function convertCsvStringToIntArray(string $csvString, array $ignoreAsValues = []): array
    {
        $resultArray = explode(',', $csvString);

        foreach ($resultArray as $key => $value) {
            $value = (int)$value;
            if ($ignoreAsValues && in_array($value, $ignoreAsValues)) {
                unset($this->visiblePageTypes[$key]);
                continue;
            }
            $resultArray[$key] = $value;
        }
        return $resultArray;
    }

    /**
     * @return array<int,int>
     */
    public function getVisiblePageTypes(): array
    {
        return $this->visiblePageTypes;
    }

    /**
     * @return array<int,int>
     */
    public function getIgnorePageDbMounts(): array
    {
        return $this->ignorePageDbMounts;
    }

    public function isBlurBeUsernames(): bool
    {
        return $this->blurBeUsernames;
    }
}
