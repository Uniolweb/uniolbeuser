<?php

declare(strict_types=1);

namespace Uniolweb\Uniolbeuser\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolweb\Uniolbeuser\Configuration\UniolbeuserConfiguration;

/**
 * @todo Add localization
 * @todo Move HTML generation to Fluid?
 */
class BeuserRepository
{
    protected const RESULT_TYPE_USERS = 'users';
    protected const RESULT_TYPE_GROUPS = 'groups';

    /** @var string CSS class name for hidden beusers or be_groups */
    protected const CLASS_INACTIVE = 'inactive';

    protected array $pageTree = [];

    /**
     * Containers users, groups for all pages (which have users, groups)
     * @var array<int,array<string,int[]>>
     */
    protected array $pagePermissions = [];
    protected ?array $groups = null;
    protected ?array $users = null;

    protected array $visiblePageTypes = [];

    public function __construct(
        protected ConnectionPool $connectionPool,
        protected UniolbeuserConfiguration $uniolbeuserConfiguration
    ) {
    }

    public function clearData(): void
    {
        $this->pageTree = [];
    }

    protected function initialize(): void
    {
        if ($this->initializeGroups()) {
            $this->initializeUsers();
            $this->initializeUserGroupsAssociation();
        }
    }

    /**
     * Show permission hierarchie for a page
     * - shows only rootline, no subpages
     * @deprecated Currently not used
     */
    public function generateRootlineAsHtmlForPageId(int $pageId): string
    {
        $this->initialize();

        $this->generateRootlineForPage($pageId);
        return $this->generateTreeAsHtml(0);
    }

    /**
     * Generates rootline as HTML and adds $childHtml to page $pageId.
     *
     * @param int $pageId
     * @param string $childHtml
     * @return string
     */
    public function addRootlineAsHtmlForPageId(int $pageId, string $childHtml = ''): string
    {
        $this->initialize();

        $this->generateRootlineForPage($pageId);
        return $this->generateTreeAsHtml(0, '', -1, $pageId, $childHtml);
    }

    /**
     * Generates hierarchy: only show subpages, no rootline (if $currentPage is not 0)
     */
    public function generateListAsHtml(array $rootPages = [0], int $currentPage = 0): string
    {
        $this->initialize();

        $this->initializeRootline();

        $html = '';
        foreach ($rootPages as $pageId) {
            $html .= $this->generateTreeAsHtml($pageId, '', -1, $currentPage);
        }

        return $html;
    }

    /**
     * Read BE-groups and get corresponding pages
     */
    protected function initializeGroups(): bool
    {
        if ($this->groups != null) {
            // already initialized
            return false;
        }

        // 'SELECT uid,title,db_mountpoints FROM be_groups where title like "D :: %" and not deleted order by title;';
        $queryBuidler = $this->connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuidler->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuidler->select('uid', 'title', 'db_mountpoints')
            ->from('be_groups')
            ->where(
                $queryBuidler->expr()->like('title', $queryBuidler->createNamedParameter('D :: %'))
            )
            ->addOrderBy('title')
            ->executeQuery();

        while ($group = $result->fetchAssociative()) {
            $groupUid = (int)$group['uid'];
            $this->groups[$groupUid] = $group;
            $this->initializePermissions($group);
        }
        return true;
    }

    /**
     * Read BE-Users (only with db_mountpoints or groups) and get associated pages
     */
    protected function initializeUsers(): bool
    {
        if ($this->users != null) {
            // already initialized
            return false;
        }

        // 'SELECT uid,username,realName,db_mountpoints,disable,usergroup from be_users where (db_mountpoints != "" or usergroup != "")
        // and not deleted order by disable, realName;';
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $result = $queryBuilder->select('uid', 'username', 'realName', 'db_mountpoints', 'disable', 'usergroup')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('db_mountpoints', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->neq('usergroup', $queryBuilder->createNamedParameter(''))
                )
            )
            ->addOrderBy('disable')
            ->addOrderBy('realName')
            ->executeQuery();

        while ($user = $result->fetchAssociative()) {
            $uid = (int)$user['uid'];
            if (!($user['realName'] ?? false)) {
                $user['realName'] = $user['username'];
            }
            $this->users[$uid] = $user;
            $this->initializePermissions($user, self::RESULT_TYPE_USERS);
        }
        return true;
    }

    /**
     * Create associations groups => users
     */
    protected function initializeUserGroupsAssociation(): void
    {
        foreach ($this->users as $user) {
            $groups = explode(',', $user['usergroup'] ?? '');
            foreach ($groups as $groupId) {
                $groupId = (int)$groupId;
                if (isset($this->groups[$groupId])) {
                    if (!isset($this->groups[$groupId]['be_users'])) {
                        $this->groups[$groupId]['be_users'] = [];
                    }
                    $this->groups[$groupId]['be_users'][] = $user['uid'];
                }
            }
        }
    }

    protected function initializeRootline(): void
    {
        foreach (array_keys($this->pagePermissions) as $pageId) {
            $this->generateRootlineForPage((int)$pageId);
        }
    }

    /**
     * Get permissions for group or user and add to list
     * @param array $data<string,mixed> BE-group or BE-user
     * @param string $resultType 'groups' oder 'users'
     */
    protected function initializePermissions(array $data, string $resultType = self::RESULT_TYPE_GROUPS)
    {
        $mountpoints = explode(',', $data['db_mountpoints'] ?? '');
        foreach ($mountpoints as $pageId) {
            $pageId = (int)$pageId;
            if ($pageId) {
                if (!isset($this->pagePermissions[$pageId])) {
                    $this->pagePermissions[$pageId] = [];
                    $this->pagePermissions[$pageId][self::RESULT_TYPE_GROUPS] = [];
                    $this->pagePermissions[$pageId][self::RESULT_TYPE_USERS] = [];
                }
                $this->pagePermissions[$pageId][$resultType][] = (int)$data['uid'];
            }
        }
    }

    /**
     * Returns array BE-User realNames
     */
    protected function getRealnameFromUsersAsHtmlArray(array $users): array
    {
        $names = [];
        foreach ($users as $uid) {
            $uid = (int)$uid;
            $isDisabled = (bool)($this->users[$uid]['disable'] ?? false);
            $class = $isDisabled ? self::CLASS_INACTIVE : '';
            $realName = $this->users[$uid]['realName'] ?? $this->users[$uid]['username'] ?? $uid;
            $names[] = sprintf('<span class="%s" title="%d">%s</span>', $class, $uid, $realName);
        }
        return $names;
    }

    /**
     * @todo Generating URL to page should be handled in Fluid
     */
    protected function generatePageUrl(int $pageId): string
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId(
            $pageId
        );
        return (string)$site->getRouter()->generateUri($pageId);
    }

    /**
     * Generate HTML for a single list entry
     */
    protected function generatedEntryForPageAsHtml(array $pageInfo, int $currengPage = 0): string
    {
        $pageId = (int)$pageInfo['uid'];
        $hasPermissions = isset($this->pagePermissions[$pageInfo['uid']]);

        $doktype = (int)($pageInfo['doktype'] ?? 0);
        $isHiddenPage = (bool)($pageInfo['hidden'] ?? false);

        if ($hasPermissions) {
            $class = '';
            if ($isHiddenPage) {
                $class = self::CLASS_INACTIVE;
            }

            /**
             * page title
             *
             * @todo not b element, use span
             * <span class="expandable">
             *     <span class="pagetitle"
             */
            if ($currengPage === $pageId) {
                $class .= ' current-page';
            }
            /** todo localize
             */
            $html = sprintf(
                '<b class="%s" tabindex="0" title="Auf-/zuklappen (PageID %d %s )">%s</b>',
                $class,
                (int)$pageInfo['uid'],
                ($pageInfo['hidden'] ? ' - deaktiviert' : ''),
                $pageInfo['title']
            );

            // Link to page
            $pageUrl = $this->generatePageUrl((int)$pageInfo['uid']);
            if (!$pageInfo['hidden'] && in_array($doktype, $this->uniolbeuserConfiguration->getVisiblePageTypes())) {
                $html .= ' <a href="' . $pageUrl . '" tabindex="0" title="Seite aufrufen" target="_blank">'
                    . '<i class="fa fa-external-link" aria-hidden="true"></i></a>';
            }

            // Other doktype
            if (!in_array($doktype, $this->uniolbeuserConfiguration->getVisiblePageTypes())) {
                $html .= '&nbsp; <span class="hinweis doktype"><i class="fa fa-file-o" aria-hidden="true"></i> '
                    . '</span>';
            }

            /**
             * Users and groups
             */
            $usersAsHtml = [];

            // groups
            foreach ($this->pagePermissions[$pageInfo['uid']][self::RESULT_TYPE_GROUPS] ?? [] as $group_uid) {
                $usersAsHtml[] = '<span class="begroup_container"><span class="begroup" tabindex="0" title="' . $group_uid . '"><i class="fa fa-users" aria-hidden="true"></i> '
                    . $this->groups[$group_uid]['title'] . '</span>'
                    . ' <span class="beusers">(' . implode(', ', $this->getRealnameFromUsersAsHtmlArray($this->groups[$group_uid]['be_users'] ?? [])) . ')</span></span>';
            }

            // User
            foreach ($this->pagePermissions[$pageInfo['uid']][self::RESULT_TYPE_USERS] as $groupUid) {
                $isHiddenUser = $this->users[$groupUid]['disable'];
                $class = 'beuser';
                $class .= ($isHiddenUser ? (' ' . self::CLASS_INACTIVE) : '');
                $title = (string)$groupUid;
                // todo localize
                $title .= ($isHiddenUser ? ' - deaktiviert' : '');
                $usersAsHtml[] = sprintf(
                    '<span class="%s" title="%s"><i class="fa fa-user" aria-hidden="true"></i> %s</span>',
                    $class,
                    $title,
                    $this->users[$groupUid]['realName'] ?? $this->users[$groupUid]['username'] ?? 'unknown'
                );
            }

            $html .= '&nbsp; ' . implode(', ', $usersAsHtml);
        } else {
            $class = 'zwischen';
            if ($currengPage === $pageId) {
                $class .= ' current-page';
            }
            // todo: localize
            $html = sprintf('<span class="%s" title="Auf-/zuklappen" tabindex="0">%s</span>', $class, $pageInfo['title']);
        }
        return $html;
    }

    /**
     * Create page tree as HTML
     *
     * <ul>
     *     <li><span>Current page</span>
     *         <ul>
     *             <li><span>subpage</span> ...</li>
     *             .....
     *          </ul>
     *     </li>
     * </ul>
     */
    protected function generateTreeAsHtml(
        int $pid = 0,
        string $html = '',
        int $recursionLevel=-1,
        int $currentPage = 0,
        string $addHtml = ''
    ): string {
        $sSchleife = '';
        $subPages = [];
        $recursionLevel++;

        foreach ($this->pageTree as $aSeite) {
            // Is subpage of current page? If yes, insert
            if (isset($aSeite['pid']) && $aSeite['pid'] === $pid) {
                $subPages[] = $aSeite;
            }
        }
        $aSort = [];
        foreach ($subPages as $key => $aSeite) {
            $aSort[$key] = $aSeite['sorting'];
        }
        array_multisort($aSort, SORT_ASC, $subPages);
        foreach ($subPages as $aSeite) {
            $seite = (int)$aSeite['uid'];

            if ($currentPage && $addHtml !== '' && $currentPage == $seite) {
                $sSchleife .= $addHtml;
            } else {
                $sSchleife .= '<li>' . $this->generatedEntryForPageAsHtml($aSeite, $currentPage);
                // Add subpages
                $sSchleife .= $this->generateTreeAsHtml($aSeite['uid'], '', $recursionLevel, $currentPage, $addHtml);
                $sSchleife .= '</li>' . PHP_EOL;
            }
        }
        if ($sSchleife) {
            $html .= '<ul>' . $sSchleife . '</ul>' . PHP_EOL;
        }
        if ($pid && $recursionLevel === 0 && isset($this->pageTree[$pid])) {
            // add current page
            $html = '<li>' . $this->generatedEntryForPageAsHtml($this->pageTree[$pid], $currentPage)
                . $html
                . '</li>';
            $html = '<ul>' . $html . '</ul>';
        }

        return $html;
    }

    protected function getPage(int $pageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        return $queryBuilder->select('uid', 'pid', 'title', 'hidden', 'doktype', 'sorting')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];
    }

    /**
     * Generate rootline for page $pageId (if not already )
     */
    protected function generateRootlineForPage(int $pageId, array $data = []): void
    {
        if (!isset($this->pageTree[$pageId])) {
            $row = $this->getPage($pageId);

            if (is_array($row)) {
                $this->pageTree[$pageId] = $row;
                if ($data) {
                    $this->pageTree[$pageId]['arr'] = $data;
                }

                if ($row['pid'] ?? false) {
                    $this->generateRootlineForPage((int)$row['pid']);
                }
            }
        }
    }
}
