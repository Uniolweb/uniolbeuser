<?php

declare(strict_types=1);
namespace Uniolweb\Uniolbeuser\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Uniolweb\Uniolbeuser\Configuration\UniolbeuserConfiguration;
use Uniolweb\Uniolbeuser\Controller\UserData\FormData;
use Uniolweb\Uniolbeuser\Repository\BeuserRepository;
use Uniolweb\Uniolbeuser\Service\BackendUserService;

/**
 * @todo localize text strings
 */
class BackendModuleController extends ActionController
{
    /** @var SiteLanguage[] */
    protected array $siteLanguages;

    protected string $backendModuleUserDataIdentifier = 'uniolbeuser';

    public function __construct(
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected BackendUriBuilder $backendUriBuilder,
        protected PageRenderer $pageRenderer,
        protected BackendUserService $backendUserService,
        protected BeuserRepository $beuserRepository,
        protected UniolbeuserConfiguration $configuration,
        protected Typo3Version $typo3Version
    ) {
        $this->backendModuleUserDataIdentifier = 'web_beuser';
    }

    public function listAction(?FormData $formdata = null): ResponseInterface
    {
        $action = '';
        $results = [];
        if ($formdata === null) {
            $formdata = $this->getModuleDataForm();
        } else {
            $action = $formdata->getCurrentAction();
        }
        if (!$action) {
            $action = $this->request->getControllerActionName();
            $formdata->setCurrentAction($action);
        }
        $pageId = (int)($this->request->getQueryParams()['id'] ?? 0);

        $this->saveModuleDataForm($formdata);

        if ($pageId) {
            // Show only subpages, no rootline
            $childrenAsHtml = $this->beuserRepository->generateListAsHtml([$pageId], $pageId);
            $this->beuserRepository->clearData();
            $results = $this->beuserRepository->addRootlineAsHtmlForPageId($pageId, $childrenAsHtml);
        } else {
            if ($this->isAdmin()) {
                // Show entire list for admin
                $results = $this->beuserRepository->generateListAsHtml();
                $this->addFlashMessage('Es ist aktuell keine Seite im Seitenbaum ausgewählt: alle anzeigen', '', ContextualFeedbackSeverity::INFO);
            } else {
                // show notice
                $this->addFlashMessage('Bitte wählen Sie eine Seite im Seitenbaum aus', '', ContextualFeedbackSeverity::INFO);
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('pageId', $pageId);
        $moduleTemplate->assign('blurBeUsernames', $this->configuration->isBlurBeUsernames());
        $moduleTemplate->assign('isAdmin', $this->backendUserService->isAdmin());
        $moduleTemplate->assign('action', $action);
        $moduleTemplate->assign('formdata', $formdata);
        $moduleTemplate->assign('results', $results);
        $moduleTemplate->assign('typo3MajorVersion', $this->typo3Version->getMajorVersion());
        return $moduleTemplate->renderResponse('List');
    }

    protected function saveModuleDataForm(FormData $formdata): void
    {
        if ($this->backendModuleUserDataIdentifier === '') {
            throw new \RuntimeException('Must set $this->backendModuleUserDataIdentifier in this class');
        }
        if ($formdata->getPages() === 'all' && !$this->backendUserService->isAdmin()) {
            $formdata->setPages('allmountpoints');
        }
        $moduleData = $formdata->toArray();
        $GLOBALS['BE_USER']->pushModuleData($this->backendModuleUserDataIdentifier, $moduleData);
    }

    protected function getModuleDataForm(): FormData
    {
        $result = $this->getModuleData();
        return FormData::instantiateFromArray($result);
    }

    /**
     * @return array<mixed>
     */
    protected function getModuleData(): array
    {
        if ($this->backendModuleUserDataIdentifier === '') {
            throw new \RuntimeException('Must set $this->backendModuleUserDataIdentifier in this class');
        }
        $moduleData = $GLOBALS['BE_USER']->getModuleData($this->backendModuleUserDataIdentifier);
        if (!$moduleData) {
            $moduleData = [];
        }
        return $moduleData;
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    public function isAdmin(): bool
    {
        return $this->getBackendUser()->isAdmin();
    }
}
