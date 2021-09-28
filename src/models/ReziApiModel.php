<?php
/**
 * Rezi API plugin for Craft CMS 3.x
 *
 * An integration with dezrez cloud based estate agency software
 *
 * @link      https://github.com/Jegard
 * @copyright Copyright (c) 2018 Luca Jegard
 */

namespace lucajegard\reziapi\models;

use lucajegard\reziapi\ReziApi;

use Craft;
use craft\base\Model;

/**
 * ReziApiModel Model
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Luca Jegard
 * @package   ReziApi
 * @since     1.0.0
 */
class ReziApiModel extends Model
{
    public $id;
    public $sectionId;
    public $branchName;
    public $apiKey;
    public $fieldMapping;
}
