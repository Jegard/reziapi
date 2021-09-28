<?php
/**
 * Rezi API plugin for Craft CMS 3.x
 *
 * An integration with dezrez cloud based estate agency software
 *
 * @link      https://github.com/Jegard
 * @copyright Copyright (c) 2018 Luca Jegard
 */

namespace lucajegard\reziapi\controllers;

use lucajegard\reziapi\ReziApi;

use Craft;
use craft\web\Controller;
use lucajegard\reziapi\jobs\ReziApiTask;
use craft\elements\Entry;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Luca Jegard
 * @package   ReziApi
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something','update-branch'];

    // Public Methods
    // =========================================================================

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/rezi-api/default
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the DefaultController actionIndex() method';

        return $result;
    }

    /**
     * Handle a request going to our plugin's actionDoSomething URL,
     * e.g.: actions/rezi-api/default/do-something
     *
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'Welcome to the DefaultController actionDoSomething() method';

        return $result;
    }

    public function actionDeleteBranch()
    {
        $branchId = Craft::$app->getRequest()->getRequiredParam('branchId');
        if (ReziApi::$plugin->reziApiService->deleteBranchModel($branchId)) {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Branch deleted'));
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Error deleting branch'));
        }

        return $this->redirect('admin/rezi-api');
    }

    public function actionCreateNewBranch()
    {
        // create a new api link
        $branchName = Craft::$app->getRequest()->getRequiredParam('branchName');
        $apiKey = Craft::$app->getRequest()->getRequiredParam('apiKey');
        $sectionId = Craft::$app->getRequest()->getRequiredParam('sectionId');

        if (ReziApi::$plugin->reziApiService->createBranchModel($branchName, $apiKey, $sectionId)) {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'New branch added'));
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Error saving branch'));
        }

        return $this->redirect('admin/rezi-api');
    }

    public function actionUpdateBranchMapping()
    {
        $branchId = Craft::$app->getRequest()->getRequiredParam('branchId');
        $mapping = Craft::$app->getRequest()->getRequiredParam('mapping');
        $uniqueIdField = Craft::$app->getRequest()->getRequiredParam('uniqueIdField');

        $updateBranchMapping = ReziApi::$plugin->reziApiService->updateBranchMapping($branchId, $mapping, $uniqueIdField);
        if ($updateBranchMapping) {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Mapping updated'));
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Error updating mapping'));
        }
        return $this->redirect('admin/rezi-api');
    }

    public function actionUpdateBranch()
    {
        $branchId = Craft::$app->getRequest()->getRequiredParam('branchId');
        $branchName = Craft::$app->getRequest()->getRequiredParam('branchName');
        $mapping = ReziApi::$plugin->reziApiService->getBranchMapping($branchId);
        $sectionId = Craft::$app->getRequest()->getRequiredParam('sectionId');
        $uniqueIdField = Craft::$app->getRequest()->getRequiredParam('uniqueIdField');

        $queue = Craft::$app->getQueue();
        
        $jobId = $queue->push(new ReziApiTask([
            'criteria' => [
                'sectionId' => $sectionId,
                'branchId' => $branchId,
                'branchName' => $branchName,
                'mapping' => $mapping,
                'uniqueIdField' => $uniqueIdField
            ],
        ]));
        // ReziApi::$plugin->reziApiService->taskTest( $branchId, $mapping, $sectionId, $uniqueIdField );

        Craft::$app->getSession()->setNotice(Craft::t('rezi-api', 'Starting update task'));

        return $this->redirect('admin/rezi-api');
    }
}
