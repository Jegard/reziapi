<?php
/**
 * Rezi API plugin for Craft CMS 3.x
 *
 * An integration with dezrez cloud based estate agency software
 *
 * @link      https://github.com/Jegard
 * @copyright Copyright (c) 2018 Luca Jegard
 */

namespace lucajegard\reziapi\services;

use lucajegard\reziapi\ReziApi;

use Craft;
use craft\base\Component;
//use lucajegard\reziapi\models\RezApiModel;
use lucajegard\reziapi\records\ReziApiRecord;
use craft\elements\Entry;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\Json;

use craft\web\twig\variables\Sections;
use craft\elements\Category;

/**
 * ReziApiService Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Luca Jegard
 * @package   ReziApi
 * @since     1.0.0
 */
class ReziApiService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     ReziApi::$plugin->reziApiService->exampleService()
     *
     * @return mixed
     */
    private $_folder;
    public function getBranch($branchId = null)
    {
        // if branch id is supplied then get that specific branch else just return all branches
        if (is_null($branchId)) {
            return ReziApiRecord::find()->all();
        } else {
            return ReziApiRecord::find()->where(['id' => $branchId])->one();
        }
    }
    
    public function createBranchModel($branchName, $apiKey, $sectionId)
    {
        $branchModelRecord = new ReziApiRecord;
        $branchModelRecord->setAttribute('branchName', $branchName);
        $branchModelRecord->setAttribute('apiKey', $apiKey);
        $branchModelRecord->setAttribute('sectionId', $sectionId);
        return $branchModelRecord->save();
    }

    public function deleteBranchModel($id)
    {
        return ReziApiRecord::find()->where(['id' => $id])->one()->delete();
    }
    public function getBranchMapping($branchId)
    {
        return json_decode(ReziApiRecord::find()->where(['id' => $branchId])->one()['fieldMapping'], true);
    }
    public function updateBranchMapping($branchId, $mapping, $uniqueIdField)
    {
        $branchModelRecord = $this->getBranch($branchId);
        $branchModelRecord->setAttribute('fieldMapping', $mapping);
        $branchModelRecord->setAttribute('uniqueIdField', $uniqueIdField);
        return $branchModelRecord->save();
    }

    public function getBranchApiKey($branchId)
    {
        return $this->getBranch($branchId)->apiKey;
    }
    public function getFullDetails($propertyId, $branchId)
    {
        $key = $this->getBranchApiKey($branchId);
        
        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.dezrez.com/api/simplepropertyrole/" . $propertyId . "?APIKEY=" . $key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Content-Type: application/json",
            "Postman-Token: cd473410-1407-20c3-ca9e-6a389f713df9",
            "Rezi-Api-Version: 1.0"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        file_put_contents(__DIR__.'/info.json', json_encode($info));
        curl_close($curl);

        return array(
            'response' => $response,
            'info' => $info,
            'error' => $err
        );
    }
    public function searchReziProperties($pageNumber = 1, $branchId)
    {
        $curl = curl_init();
        $key = $this->getBranchApiKey($branchId);
        file_put_contents(__DIR__ . '/key.json', $key);
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.dezrez.com/api/simplepropertyrole/search?APIKey=" . $key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\n  MinimumPrice: 0,\n  BranchIdList:[],\n  PageNumber: ".$pageNumber.",\n PageSize: 20,\n MarketingFlags: ['ApprovedForMarketingWebsite'],\n IncludeStc: true }",
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Content-Type: application/json",
            "Postman-Token: 3db62e40-65ed-79c5-ca27-5f0681bf2e6b",
            "Rezi-Api-Version: 1.0"
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);

        curl_close($curl);
        file_put_contents(__DIR__.'/response.json', $response);
        
        return array(
            'response' => $response,
            'info' => $info,
            'error' => $err
        );
    }
    public function saveEntry($sectionId, $fields, $uniqueIdField, $roleId)
    {
        //$entryType = EntryType::find()->where(['handle' => $handle])->one();
    
        

        $entry = Entry::find()
                ->sectionId($sectionId)
                ->$uniqueIdField($roleId)
                ->status(null)
                ->all();
        if (count($entry) == 0) {
            $entry = new Entry();
            $entry->sectionId = $sectionId;
        } else {
            $entry = $entry[0];
        }

        // $entry->typeId = 1;
        $entry->authorId = 1;
    
        if (isset($fields['typeId'])) {
            $entry->typeId = $fields['typeId'];
            unset($fields['typeId']);
        }

        if (isset($fields['title'])) {
            $entry->title = $fields['title'];
            unset($fields['title']);
        }
    
        if (isset($fields['slug'])) {
            $entry->slug = $fields['slug'];
            unset($fields['slug']);
        }
    
        $entry->setFieldValues($fields);
        //$entry->enabled = true;

        if (Craft::$app->elements->saveElement($entry)) {
            return $entry;
        } else {
            throw new \Exception("Couldn't save new entry " . print_r($entry->getErrors(), true));
        }
    }
    
    public function disableAged($sectionId, $reziFullRoleId, $uniqueIdField){
        //1: Get all entries that are LIVE
        $entry = Entry::find()
            ->sectionId($sectionId)
            //Michael - start here
            ->status(null)
            ->all();
        //file_put_contents( __DIR__ . '/allEntries.json' , json_encode($entry));
        /*** Turn off OR on any according to Rezi ***/
        // 1: Get all unique (role) IDs of current craft entries
        $allReturnedUniqueID = [];
        foreach($entry as $currentEntry){
            array_push($allReturnedUniqueID, $currentEntry->getFieldValue('uniqueId'));
        }
        file_put_contents( __DIR__ . '/allProps.json' , json_encode($allReturnedUniqueID));

        //2: Get all unique IDs returned by Rezi
        $allReziUniqueId = [];
        foreach ($reziFullRoleId as $currentReziRole){
            array_push($allReziUniqueId, $currentReziRole['RoleId']);
        }
        file_put_contents( __DIR__ . '/allReziActiveProps.json' , json_encode($allReziUniqueId));

        
        $entriesUpdated = [];
        //3: Compare all IDs to list that you want live -> make all on that list live.
        foreach($entry as $currentEntry){
            if (in_array($currentEntry->getFieldValue('uniqueId'), $allReziUniqueId)){
                //Change the entry to enabled, save the entry
                $currentEntry->enabled = 1;

                if (Craft::$app->elements->saveElement($currentEntry)) {
                    array_push($entriesUpdated, $currentEntry) ;
                    
                    return $currentEntry;
                } else {
                    throw new \Exception("Couldn't save new entry " . print_r($currentEntry->getErrors(), true));
                }
            } else {
                //If its not on the list, make sure it is NOT enabled
                $currentEntry->enabled = 0;
            }    
        }          
    }
    
    public function catTest()
    {
        $entry = Entry::find()
        ->sectionId(1)
        ->status(null)
        ->first();

        $categoryGroupId = $entry['parish']->groupId;


        $category = Category::find()
        ->groupId($categoryGroupId)
        ->limit(null)
        ->all();

        $newcat = new Category();
        $newcat->groupId = 2;
        $newcat->title = 'fgdd';

        d($newcat);

        // Craft::$app->elements->saveElement($newcat);

        // d( Craft::$app->categories->saveCat );

        $sections = new Sections();
        $section = $sections->getSectionById(1);
        $entryTypes = $section->getEntryTypes();


        $entryTypeId = $entryTypes[0]->id;

        $fields = $entryTypes[0]->getFieldLayout()->getFields();
        d($entryTypes);
    }
    public function updateCraftCategories($catTitle, int $groupId)
    {
        $category = Category::find()
        ->groupId($groupId)
        ->title($catTitle)
        ->first();
        if ($category === null) {
            $newCategory = new Category();
            $newCategory->groupId = $groupId;
            $newCategory->title = $catTitle;
            if (Craft::$app->elements->saveElement($newCategory)) {
                return (int)$newCategory->id;
            }
        } else {
            return (int)$category->id;
        }
    }
    /**
     * @ TODO
     */
    public function prepareCategory($entryFields, $key, $fieldName)
    {
        if (empty(trim($fieldName))) {
            return [];
        }
        $this->reziLog('Creating Category: ' . $fieldName);
        $catGroupId = null;
        foreach ($entryFields as $entryField) {
            if ($entryField->handle == $key) {
                $catGroupId = (Craft::$app->categories->getGroupByHandle($entryField->handle)->id);
            }
        }
        return $catGroupId === null ? [] : [ $this->updateCraftCategories($fieldName, $catGroupId) ];
    }
    public function updateCraftEntry($property, $mapping, $sectionId, $uniqueIdField)
    {
        $mapping = (array) $mapping;
        $property = (array) $property;

        file_put_contents(__DIR__ . '/mapping.json', json_encode($mapping));
        file_put_contents(__DIR__ . '/property.json', json_encode($property));
        
        $sections = new Sections();
        $section = $sections->getSectionById($sectionId);
        $entryTypes = $section->getEntryTypes();
        $entryFields = [];
        $entryTypeId = $entryTypes[0]->id;
        $fields = array(
            'typeId' => $entryTypeId,
        );

        foreach ($entryTypes as $entryType) {
            $entryFields = array_merge($entryFields, $entryType->getFieldLayout()->getFields());
        }
        
        $fields[$uniqueIdField] = $property['RoleId'];
        foreach ($mapping as $key => $map) {
            //check whether we can easily find the maps value on the rezi feed

            switch ($map) {
                case 'RoleType (category)':
                    $fields[$key] = $this->prepareCategory($entryFields, $key, $property['RoleType']['DisplayName']);
                    break;
                case 'Locality (category)':
                    $fields[$key] = $this->prepareCategory($entryFields, $key, $property['Address']['Locality']);
                    break;
                case 'PropertyType (Category)':
                    $fields[$key] = $this->prepareCategory($entryFields, $key, $property['PropertyType']['DisplayName']);
                    break;
                case 'Flags (category)':
                    $flatCatIds = [];
                    foreach ($property['Flags'] as $flag) {
                        $flatCatIds = array_merge($flatCatIds, $this->prepareCategory($entryFields, $key, $flag['DisplayName']));
                    }
                    file_put_contents(__DIR__ . '/flag.json', json_encode($flatCatIds));
                    $fields[$key] = $flatCatIds;
                    break;
                case 'Descriptions->Features (Category)':
                    $featureCatIds = [];
                    $features = [];
                    //if ($desc['Name'] == 'Feature Description' || $desc['Name'] == 'Features') {  
                    foreach ($property['Descriptions'] as $desc) {
                        if ($desc['Name'] == 'Features' || $desc['Name'] == 'Feature Description') {
                            foreach ($desc['Features'] as $feature) {
                                $featureCatIds = array_merge($featureCatIds, $this->prepareCategory($entryFields, $key, $feature['Feature']));
                                array_push($features, $feature['Feature']);
                            }
                        }
                        // if ($desc['Name'] == 'Feature Description') {
                        //     foreach ($desc['Features'] as $feature) {
                        //         $featureCatIds = array_merge($featureCatIds, $this->prepareCategory($entryFields, $key, $feature['Feature']));
                        //         array_push($features, $feature['Feature']);
                        //     }
                        // }
                    }
                    //}
                    file_put_contents(__DIR__ . '/features.json', json_encode($features));
                    $fields[$key] = $featureCatIds;
                    break;
                case 'Documents':
                    $imageIds = $this->getReziDocuments($property['Documents']);
                    $fields[$key] = $imageIds;
                    break;
                case 'Images':
                    $imageIds = $this->getReziImages($property['Images']);
                    $fields[$key] = $imageIds;
                    break;
                case 'Text (Main Marketing)':
                    foreach ($property['Descriptions'] as $desc) {
                        if ($desc['Name'] == 'Main Marketing') {
                            $fields[$key] = $desc['Text'];
                        }
                    }
                    break;
                case 'Pets':
                    //$petsWelcome = 'working-';
                    foreach ($property['Descriptions'] as $desc) {

                        //file_put_contents(__DIR__ . '/fileinfo.json', json_encode($fileInfo));
                        if (isset($desc['Pairs'])){ 
                            foreach ($desc['Pairs'] as $pairs) {
                                if($pairs['Key']['SystemName'] == 'Pets'){
                                    $fields[$key] = $pairs['Value']['SystemName'];  
                                    // if ($pairs['Value']['SystemName'] == 'Yes' || $pairs['Value']['SystemName'] == 'Yes'){
                                    //     $fields[$key] = 1;
                                    // } else {
                                    //     $fields[$key] = 0;
                                    // }
                                    //$petsWelcome .= $pairs['Value']['SystemName'];
                                    //$petsWelcome .='==';

                                    //file_put_contents(__DIR__ . '/test.json', $pairs['Value']['SystemName']);
                                } 
                            }
                            
                        }
                      
                        
                    }
                    //file_put_contents(__DIR__ . '/test.text', $petsWelcome);
                    break;
                default:
                    if (isset($property[ $map ])) {
                        $fields[$key] = $property[ $map ];
                    } else {
                        $keys = explode('->', $map);
                        
                        $searchedValue = $this->searchReziMultiArray($keys, $property, 0);
                        if ($searchedValue) {
                            $fields[$key] = $searchedValue;
                        }
                    }
            }
        }

        $this->saveEntry($sectionId, $fields, $uniqueIdField, $property['RoleId']);
        // \Kint::dump( $entryTypes );
    }
    public function reziLog($message)
    {
        $file = Craft::getAlias('@storage/logs/rezi.log');
        $log = date('Y-m-d H:i:s').' '.$message."\n";
        FileHelper::writeToFile($file, $log, ['append' => true]);
    }

    public function getReziImages($filesArray)
    {
        $ids = [];
        $mimes = [];
        $fileInfo = [];
        foreach ($filesArray as $key => $node) {
            $fileInfo[] = $node;
            // first check if images is already downloaded by its title and if not thn download
            $assets = Asset::Find()
                ->title($node['Id'])
                ->all();
            if (count($assets) > 0) {
                array_push($ids, $assets[0]->id);
            } else {
                $file = $this->file_get_contents_curl($node['Url']);
                $pathinfo = pathinfo($node['Url']);
                $fileInfo[] = $pathinfo;
                $basename = $pathinfo['basename'];
                if (strpos($basename, '?v=') !== false) {
                    $basename = explode('?v=', $basename)[0];
                }
                //filesize attempt images
                $filesize = filesize($node['Url']);
                file_put_contents(__DIR__ . '/filesize.txt', ($filesize));

                file_put_contents(__DIR__ . '/fileinfo.json', json_encode($fileInfo));

                $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $basename;
                if (strpos($path, '?v=') !== false) {
                    $path = explode('?v=', $path)[0];
                }

                FileHelper::writeToFile($path, $file);
                $this->reziLog('adding filepath: ' . $path);

                $mimeType = FileHelper::getMimeType($path, null, false);
                $mimes [] = $mimeType;
                $this->reziLog($mimeType);
                file_put_contents(__DIR__ . '/mimeinfo.json', json_encode($mimes));
                if ($mimeType !== null && strpos($mimeType, 'image/') !== 0) {
                } else {
                    $asset = new Asset();
                    $asset->tempFilePath = $path;
                    $asset->setScenario(Asset::SCENARIO_CREATE);
                    $asset->filename = $basename;

                    // $asset->title = $pathinfo['filename'];

                    $asset->avoidFilenameConflicts = true;
                    $asset->setScenario(\craft\elements\Asset::SCENARIO_CREATE);
                    $folder = $this->getFolder(1);

                    $asset->newFolderId = $folder->id;
                    $asset->volumeId = $folder->volumeId;

                    

                    if (!Craft::$app->getElements()->saveElement($asset)) {
                        Craft::error('[API CALLER] Could not store image ' . Json::encode($asset->getErrors()));
                        $this->reziLog('Error saving new craft image: ' . Json::encode($asset->getErrors()));
                    //\Kint::dump($asset->getErrors());
                    } else {
                        $this->reziLog('New image: ' . $asset->title . ' saved successfully with path: ' . $basename);
                        array_push($ids, $asset->id);
                    }
                }

                //$ids.push($asset->id);
            }
        }
        return $ids;
    }

    public function getReziDocuments($filesArray)
    {
        $ids = [];

        foreach ($filesArray as $key => $node) {
            // first check if images is already downloaded by its title and if not thn download
            $assets = Asset::Find()
                ->title($node['Id'])
                ->all();
            if (count($assets) > 0) {
                array_push($ids, $assets[0]->id);
            } else {
                $file = $this->file_get_contents_curl($node['Url']);
                $pathinfo = pathinfo($node['Url']);
                $fileInfo[] = $pathinfo;
                $basename = $pathinfo['basename'];
                if (strpos($basename, '?v=') !== false) {
                    $basename = explode('?v=', $basename)[0];
                }


                file_put_contents(__DIR__ . '/brochureDocumentInfo.json', json_encode($fileInfo));

                file_put_contents(__DIR__ . '/brochureDocumentInfo.txt', $pathinfo);
                $path = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $pathinfo['basename'];
                FileHelper::writeToFile($path, $file);

                $mimeType = FileHelper::getMimeType($path, null, false);

                //if ($mimeType !== null && strpos($mimeType, 'image/') !== 0 && strpos($mimeType, 'application/pdf') !== 0) {
                if ($mimeType !== null && strpos($mimeType, 'application/pdf') !== 0) {
                } else {
                    $asset = new Asset();
                    $asset->tempFilePath = $path;
                    $asset->setScenario(Asset::SCENARIO_CREATE);
                    $asset->filename = $basename;
                    // $asset->title = $pathinfo['filename'];

                    $asset->avoidFilenameConflicts = true;
                    $asset->setScenario(\craft\elements\Asset::SCENARIO_CREATE);
                    $folder = $this->getFolder(1);

                    $asset->newFolderId = $folder->id;
                    $asset->volumeId = $folder->volumeId;

                    

                    if (!$result = Craft::$app->getElements()->saveElement($asset)) {
                        Craft::error('[API CALLER] Could not store image ' . Json::encode($asset->getErrors()));
                    //\Kint::dump($asset->getErrors());
                    } else {
                        array_push($ids, $asset->id);
                    }
                }

                //$ids.push($asset->id);
            }
        }
        return $ids;
    }

    public function getFolder($id)
    {
        if ($this->_folder === null) {
            $this->_folder = Craft::$app->getAssets()->findFolder(['id' => $id]);
        }
        return $this->_folder;
    }

    public function file_get_contents_curl($url)
    {
        $ch = curl_init();
    
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
        $data = curl_exec($ch);
        curl_close($ch);
    
        return $data;
    }

    public function searchReziMultiArray($keys, $property, $index)
    {
        // i am respresenting multidemensional key like so Address->Postcode
        // so i need to split them in order to traverse the multi dimensional rezi array
        $currentKey = $keys[ $index ];
        if (isset($property[$currentKey])) {
            if (gettype($property[$currentKey]) == 'array') {
                if (!$this->has_string_keys($property[$currentKey])) {
                    $mergedNode = [];
                    foreach ($property[$currentKey] as $node) {
                        $mergedNode = array_merge($mergedNode, $node);
                    }
                    return $this->searchReziMultiArray($keys, $mergedNode, $index+1);
                } else {
                    return $this->searchReziMultiArray($keys, $property[$currentKey], $index+1);
                }
            } else {
                return $property[$currentKey];
            }
        } else {
            return false;
        }


        // if( !isset($property[$key]) ){
        //     return false;
        // }else{
        //     if( gettype($property[$key]) == 'array' ){
        //         foreach( $property[$key] as $propNode ){
        //             return $this->searchReziMultiArray( $key, $propNode );
        //         }
        //     }else{
        //         return $property[$key];
        //     }
        // }
    }
    public function has_string_keys(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    public function taskTest($branchId, $mapping, $sectionId, $uniqueIdField)
    {
        $propIds = [];
        $props = [];
        $allPropsFound = false;
        $pageNumber = 1;

        while (!$allPropsFound) {
            $pageProps = ReziApi::$plugin->reziApiService->searchReziProperties($pageNumber, $branchId);
            $response = json_decode($pageProps['response'], true);
            
            
            if ($pageProps['info']['http_code'] == 200) {
                if ($response['CurrentCount'] != 0) {
                    foreach ($response['Collection'] as $prop) {
                        if (isset($prop['RoleId'])) {
                            // $propIds [] = $prop['RoleId'];
                            array_push($propIds, $prop['RoleId']);
                        }
                    }
                } else {
                    $allPropsFound = true;
                }
            } else {
                return false;
            }
            $pageNumber++;
        }
        
        foreach ($propIds as $key => $id) {
            $propertyRequest = ReziApi::$plugin->reziApiService->getFullDetails($id, $branchId);
            $response = json_decode($propertyRequest['response'], true);
            if ($propertyRequest['info']['http_code'] == 200) {
                $props [] = $response;
                $updateCraftEntry = ReziApi::$plugin->reziApiService->updateCraftEntry($response, $mapping, $sectionId, $uniqueIdField);
            } else {
                return false;
            }
            // $this->setProgress($queue, $key / count($propIds));
        }
    }
}
