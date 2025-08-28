<?php

declare(strict_types=1);
namespace Uniolweb\Uniolbeuser\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolweb\Uniolbeuser\Configuration\UniolbeuserConfiguration;

/**
 * Fetch information about BE users.
 */
class BackendUserService
{
    public function __construct(protected UniolbeuserConfiguration $configuration)
    {
    }

    /**
     * If the $table / $field is a field with authMode set, it is checked if user is allowed to access records with
     *    the field containing $value
     * @param string $table
     * @param string $field
     * @param string $value
     * @return bool
     */
    public function isAllowedByAuthModeForCurrentUser(string $table, string $field, string $value): bool
    {
        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'] ?? [];
        if (!$fieldConfig) {
            // do not allow access if TCA not configured
            return false;
        }

        $authMode = $this->getAuthMode($table, $field);
        if (!$authMode) {
            // authMode not set: allow access
            return true;
        }
        return $this->getBackendUser()->checkAuthMode($table, $field, $value);
    }

    /**
     * @return string Return empty string, if no authMode
     */
    protected function getAuthMode(string $table, string $type): string
    {
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
        // Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
        if ($versionInformation->getMajorVersion() < 12) {
            if ($type === 'CType') {
                return $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow';
            }
            return $GLOBALS['TCA'][$table]['columns'][$type]['config']['authMode'] ?? '';
        }
        // since v12 the only valid values are 'explicitAllow'
        if ($type === 'CType') {
            $authMode = $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] ?? 'explicitAllow';
        } else {
            $authMode = $GLOBALS['TCA'][$table]['columns'][$type]['config']['authMode'] ?? '';
        }
        if ($authMode && $authMode != 'explicitAllow') {
            $authMode = 'explicitAllow';
        }
        return $authMode;
    }

    /**
     * @return int[]
     */
    public function getAllowedDbMounts(): array
    {
        $dbMounts = (int)($this->getBackendUser()->uc['pageTree_temporaryMountPoint'] ?? 0);
        if (!$dbMounts) {
            $dbMounts = array_map(intval(...), $this->getBackendUser()->returnWebmounts());

            $dbMounts = array_unique($dbMounts);
        } else {
            $dbMounts = [$dbMounts];
        }

        foreach ($dbMounts as $key => $dbMount) {
            if (in_array((int)$dbMount, $this->configuration->getIgnorePageDbMounts())) {
                unset($dbMounts[$key]);
            }
        }
        return $dbMounts;
    }

    public function getBackendUsername(): string
    {
        $beUser = $this->getBackendUser();
        if ($beUser) {
            return $beUser->user['username'] ?? '';
        }
        return '';
    }

    public function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }
}
