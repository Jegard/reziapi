<?php
/**
 * Rezi API plugin for Craft CMS 3.x
 *
 * An integration with dezrez cloud based estate agency software
 *
 * @link      https://github.com/Jegard
 * @copyright Copyright (c) 2018 Luca Jegard
 */

namespace lucajegard\reziapi\jobs;

use lucajegard\reziapi\ReziApi;

use Craft;
use craft\queue\BaseJob;

/**
 * ReziApiTask job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use lucajegard\reziapi\jobs\ReziApiTask as ReziApiTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new ReziApiTaskJob([
 *     'description' => Craft::t('rezi-api', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Luca Jegard
 * @package   ReziApi
 * @since     1.0.0
 */
class ReziApiTask extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * Some attribute
     *
     * @var string
     */
    public $someAttribute = 'Some Default';
    public $criteria;

    // Public Methods
    // =========================================================================

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue)
    {
        // Do work here
        $propIds = [];
        $props = [];
        $allPropsFound = false;
        $pageNumber = 1;
        $branchId = $this->criteria['branchId'];
        $mapping = $this->criteria['mapping'];
        $sectionId = $this->criteria['sectionId'];
        $uniqueIdField = $this->criteria['uniqueIdField'];


        while(!$allPropsFound){
            $pageProps = ReziApi::$plugin->reziApiService->searchReziProperties($pageNumber, $branchId);
            $response = json_decode($pageProps['response'], true);
            
            
            if($pageProps['info']['http_code'] == 200){
                if($response['CurrentCount'] != 0){
                    
                    foreach( $response['Collection'] as $prop ){
                        
                        if( isset($prop['RoleId']) ){
                            // $propIds [] = $prop['RoleId'];
                            array_push( $propIds, $prop['RoleId'] );
                        }
                    }
                }else{
                    $allPropsFound = true;
                }
            }else{
                return false;
            }
            $pageNumber++;
        }
        
        foreach($propIds as $key => $id){
            $propertyRequest = ReziApi::$plugin->reziApiService->getFullDetails($id, $branchId);
            $response = json_decode($propertyRequest['response'], true);
            if($propertyRequest['info']['http_code'] == 200){
                $props [] = $response;
                $updateCraftEntry = ReziApi::$plugin->reziApiService->updateCraftEntry($response, $mapping, $sectionId, $uniqueIdField);
            }else{
                return false;
            }
            $this->setProgress($queue, $key / count($propIds));
        }
        file_put_contents( __DIR__ . '/fullresult.json' , json_encode($props) );
        ReziApi::$plugin->reziApiService->disableAged($sectionId, $props, $uniqueIdField);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('rezi-api', 'ReziApiTask');
    }
}
