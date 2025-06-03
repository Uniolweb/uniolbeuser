<?php

declare(strict_types=1);
namespace Uniolweb\Uniolbeuser\Controller\UserData;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Form properties
 *
 * !!! important: new properties must be added to fromArray() and toArray()
 */
class FormData
{
    public const DEPTH_INFINITE = 9999;

    public const DEFAULT_ACTION = 'listOverview';
    public const DEFAULT_DEPTH = self::DEPTH_INFINITE;
    public const DEFAULT_SHOW_HIDDEN = true;
    public const DEFAULT_SORT_BY = 'tstamp';
    public const DEFAULT_SORT_DIRECTION = 'ASC';
    public const DEFAULT_SHOW_INFO = false;
    public const HOW_TO_TRAVERSE_PAGES_SUBPAGES = 'subpages';
    public const HOW_TO_TRAVERSE_PAGES_ALL = 'all';
    public const HOW_TO_TRAVERSE_PAGES_ALL_MOUNTPOINTS = 'allmountpoints';
    public const DEFAULT_HOW_TO_TRAVERSE_PAGES = self::HOW_TO_TRAVERSE_PAGES_SUBPAGES;

    public const MAX_COUNT_DEFAULT = 100;

    protected string $pages = self::DEFAULT_HOW_TO_TRAVERSE_PAGES;
    protected int $depth = self::DEFAULT_DEPTH;
    protected bool $showHidden = self::DEFAULT_SHOW_HIDDEN;
    protected bool $showInfo = self::DEFAULT_SHOW_INFO;

    protected string $sortBy = self::DEFAULT_SORT_BY;
    protected string $sortDirection = self::DEFAULT_SORT_DIRECTION;

    protected string $searchString = '';

    protected int $maxcount = self::MAX_COUNT_DEFAULT;

    protected string $currentAction = self::DEFAULT_ACTION;

    public function __construct(
        int $depth = self::DEFAULT_DEPTH,
        bool $showHidden = self::DEFAULT_SHOW_HIDDEN,
        string $sortBy = self::DEFAULT_SORT_BY,
        string $sortDirection = self::DEFAULT_SORT_DIRECTION,
        string $searchString = '',
        string $pages = self::HOW_TO_TRAVERSE_PAGES_SUBPAGES,
        bool $showInfo = self::DEFAULT_SHOW_INFO,
        int $maxcount = self::MAX_COUNT_DEFAULT,
        string $currentAction = self::DEFAULT_ACTION
    ) {
        $this->setDepth($depth);
        $this->setShowHidden($showHidden);
        $this->setSortBy($sortBy);
        $this->setSortDirection($sortDirection);
        $this->setSearchString($searchString);
        $this->setPages($pages);
        $this->setShowInfo($showInfo);
        $this->setMaxcount($maxcount);
        $this->setCurrentAction($currentAction);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function fromArray(array $data): void
    {
        $this->setDepth((int)($data['depth'] ?? self::DEFAULT_DEPTH));
        $this->setShowHidden((bool)($data['showHidden'] ?? self::DEFAULT_SHOW_HIDDEN));
        $this->setSortBy($data['sortBy'] ?? self::DEFAULT_SORT_BY);
        $this->setSortDirection($data['sortDirection'] ?? self::DEFAULT_SORT_DIRECTION);
        $this->setSearchString($data['searchString'] ?? '');
        $this->setPages($data['pages'] ?? self::DEFAULT_HOW_TO_TRAVERSE_PAGES);
        $this->setShowInfo((bool)($data['showInfo'] ?? self::DEFAULT_SHOW_INFO));
        $this->setMaxcount((int)($data['maxcount'] ?? self::MAX_COUNT_DEFAULT));
        $this->setCurrentAction($data['currentAction'] ?? self::DEFAULT_ACTION);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'depth' => $this->getDepth(),
            'showHidden' => $this->isShowHidden(),
            'showInfo' => $this->isShowInfo(),
            'sortBy' => $this->getSortBy(),
            'sortDirection' => $this->getSortDirection(),
            'searchString' => $this->getSearchString(),
            'pages' => $this->getPages(),
            'maxcount' => $this->getMaxcount(),
            'currentAction' => $this->getCurrentAction(),
        ];
    }

    public function getCurrentAction(): string
    {
        return $this->currentAction;
    }

    public function setCurrentAction(string $currentAction): void
    {
        $this->currentAction = $this->normalizeAction($currentAction);
    }

    protected function normalizeAction(string $action): string
    {
        return preg_replace('#Action$#', '', $action);
    }

    /**
     * @return bool
     */
    public function isShowInfo(): bool
    {
        return $this->showInfo;
    }

    public function setShowInfo(bool $showInfo): void
    {
        $this->showInfo = $showInfo;
    }

    /**
     * @return string
     */
    public function getPages(): string
    {
        return $this->convertPagesToAllowedValues($this->pages);
    }

    public function setPages(string $pages): void
    {
        $this->pages = $this->convertPagesToAllowedValues($pages);
    }

    /**
     * Can be removed at a later point. Is used, because at the beginning, "all"
     * was used for non-admins.
     */
    protected function convertPagesToAllowedValues(string $pages): string
    {
        if ($pages === self::HOW_TO_TRAVERSE_PAGES_ALL && !$this->isAdmin()) {
            return self::HOW_TO_TRAVERSE_PAGES_ALL_MOUNTPOINTS;
        }
        if ($pages === self::HOW_TO_TRAVERSE_PAGES_ALL_MOUNTPOINTS && $this->isAdmin()) {
            return self::HOW_TO_TRAVERSE_PAGES_ALL;
        }
        if ($pages === '') {
            return self::DEFAULT_HOW_TO_TRAVERSE_PAGES;
        }
        return $pages;
    }

    /**
     * @return int
     */
    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): void
    {
        $this->depth = $depth;
    }

    public function isShowHidden(): bool
    {
        return $this->showHidden;
    }

    public function setShowHidden(bool $showHidden): void
    {
        $this->showHidden = $showHidden;
    }

    public function getSortBy(): string
    {
        return $this->sortBy;
    }

    public function setSortBy(string $sortBy): void
    {
        $this->sortBy = $sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(string $sortDirection): void
    {
        $this->sortDirection = $sortDirection;
    }

    public function getSearchString(): string
    {
        return $this->searchString;
    }

    /**
     * Convert searchString to something which can be passed to the database
     * @return string
     */
    public function getResolvedSearchString(): string
    {
        if (str_starts_with($this->searchString, 'special:')) {
            switch (substr($this->searchString, strlen('special:'))) {
                case 'external_iframe':
                    return 'regex:<iframe [^>]*src="https?://(?![^"]*(uol.de|uni-oldenburg.de)")[^"]*"';
            }
        }
        return $this->searchString;
    }

    public function setSearchString(string $searchString): void
    {
        $this->searchString = $searchString;
    }

    public function getMaxcount(): int
    {
        return $this->maxcount;
    }

    public function setMaxcount(int $maxcount): void
    {
        $this->maxcount = $maxcount;
    }

    /**
     * @param array<string,mixed> $data
     * @return FormData
     */
    public static function instantiateFromArray(array $data): FormData
    {
        $obj = new FormData();
        $obj->fromArray($data);
        return $obj;
    }

    /**
     * magic function
     *
     * @param array<string,mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->fromArray($data);
    }

    /**
     * magic function
     * @return array<string,mixed>
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function isAdmin(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }
}
