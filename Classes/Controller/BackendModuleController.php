<?php

declare(strict_types=1);
namespace Uniolweb\Uniolbeuser\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
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
        protected BeuserRepository $beuserRepository
    ) {
        $this->backendModuleUserDataIdentifier = 'web_beuser';
    }

    public function listAction(FormData $formdata = null): ResponseInterface
    {
        $action = '';
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
        $this->view->assign('pageId', $pageId);
        $this->finalizeAction($action, $formdata);

        if ($pageId) {
            // Show only subpages, no rootline
            $childrenAsHtml = $this->beuserRepository->generateListAsHtml([$pageId], $pageId);
            $this->beuserRepository->clearData();
            $this->view->assign('results', $this->beuserRepository->addRootlineAsHtmlForPageId($pageId, $childrenAsHtml));
        } else {
            if ($this->isAdmin()) {
                // Show entire list for admin
                $this->view->assign('results', $this->beuserRepository->generateListAsHtml());
                $this->addFlashMessage('Es ist aktuell keine Seite im Seitenbaum ausgewählt: alle anzeigen', '', ContextualFeedbackSeverity::INFO);
            } else {
                // show notice
                $this->addFlashMessage('Bitte wählen Sie eine Seite im Seitenbaum aus', '', ContextualFeedbackSeverity::INFO);
            }
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Call for all actions
     */
    protected function finalizeAction(string $action, FormData $formdata): void
    {
        $this->saveModuleDataForm($formdata);
        $this->view->assign('isAdmin', $this->backendUserService->isAdmin());
        $this->view->assign('action', $action);
        $this->view->assign('formdata', $formdata);
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
