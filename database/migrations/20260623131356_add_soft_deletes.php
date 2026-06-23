<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddSoftDeletes extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $tables = ['hotels', 'guests', 'reservations', 'invoices'];
        
        foreach ($tables as $tableName) {
            if ($this->hasTable($tableName)) {
                $table = $this->table($tableName);
                $table->addColumn('deleted_at', 'timestamp', ['null' => true, 'default' => null])
                      ->update();
            }
        }
    }

    public function down(): void
    {
        $tables = ['hotels', 'guests', 'reservations', 'invoices'];
        
        foreach ($tables as $tableName) {
            if ($this->hasTable($tableName)) {
                $table = $this->table($tableName);
                if ($table->hasColumn('deleted_at')) {
                    $table->removeColumn('deleted_at')
                          ->update();
                }
            }
        }
    }
}
