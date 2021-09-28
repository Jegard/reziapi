<?php
/**
 * Rezi Api plugin for Craft CMS 3.x
 *
 * erth
 *
 * @link      https://pluginfactory.io/
 * @copyright Copyright (c) 2018 lucajegard
 */

namespace lucajegard\reziapi\migrations;

use lucajegard\reziapi\ReziApi;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

/**
 * Rezi Api Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    lucajegard
 * @package   ReziApi
 * @since     43
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

    // reziapi_reziapirecord table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%reziapi_reziapirecord}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%reziapi_reziapirecord}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                // Custom columns in the table
                    'siteId' => $this->integer(),
                    'branchName' => $this->string(255)->notNull()->defaultValue(''),
                    'apiKey' => $this->string(1000)->notNull()->defaultValue(''),
                    'sectionId' => $this->integer(),
                    'fieldMapping' => $this->text(),
                    'uniqueIdField' => $this->string(255)->notNull()->defaultValue('')
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
    // reziapi_reziapirecord table
        $this->createIndex(
            $this->db->getIndexName(
                '{{%reziapi_reziapirecord}}',
                'some_field',
                true
            ),
            '{{%reziapi_reziapirecord}}',
            'some_field',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case DbConfig::DRIVER_MYSQL:
                break;
            case DbConfig::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
    // reziapi_reziapirecord table
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%reziapi_reziapirecord}}', 'siteId'),
            '{{%reziapi_reziapirecord}}',
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
    // reziapi_reziapirecord table
        $this->dropTableIfExists('{{%reziapi_reziapirecord}}');
    }
}
