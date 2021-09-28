<?php
/**
 * Rezi Api plugin for Craft CMS 3.x
 *
 * rg
 *
 * @link      https://pluginfactory.io/
 * @copyright Copyright (c) 2018 lucajegard
 */

namespace lucajegard\reziapi\variables;

use lucajegard\reziapi\ReziApi;
use craft\web\twig\variables\Sections;
use craft\models\FieldLayout;
use craft\models\EntryType;

use Craft;

/**
 * @author    lucajegard
 * @package   ReziApi
 * @since     1
 */
class ReziApiVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */

    public function getBranch($branchId=null)
    {
        return ReziApi::$plugin->reziApiService->getBranch($branchId);
    }
    public function exampleVariable($optional = null)
    {
        $result = "And away we go to the Twig template...";
        if ($optional) {
            $result = "I'm feeling optional today...";
        }
        return $result;
    }
    public function getMapping($branchId)
    {
        return ReziApi::$plugin->reziApiService->getBranchMapping($branchId);
    }
}
