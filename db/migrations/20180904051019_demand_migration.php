<?php

use Phinx\Migration\AbstractMigration;

class DemandMigration extends AbstractMigration
{

    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $exists = $this->hasTable('demands');
        if (!$exists) {
            $table = $this->table('demands');
            $table
                  ->addColumn('title', 'string', ['limit' => 50, 'null' => false])
                  ->addColumn('category_id', 'integer', ['null' => false])
                  ->addColumn('user_id', 'integer', ['null' => false])
                  ->addColumn('zipcode', 'string', ['limit' => 20, 'null' => false])
                  ->addColumn('city', 'string', ['limit' => 40, 'null' => false])
                  ->addColumn('execute_time', 'enum', ['null' => false, 'values' => [\MyHammer\Domain\Model\Entity\DemandEntity::EXECUTE_TIMES]])
                  ->addColumn('description', 'text', ['null' => false])
                  ->addColumn('created_at', 'datetime')
                  ->addColumn('updated_at', 'datetime')
                  ->create();
        }
    }
}
