<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateApiErrorLogsTable extends AbstractMigration
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
    public function change(): void
    {
        $table = $this->table('api_error_logs');
        $table->addColumn('hotel_id', 'integer', ['null' => true])
              ->addColumn('endpoint', 'string', ['limit' => 255])
              ->addColumn('payload', 'text', ['null' => true])
              ->addColumn('response_code', 'integer', ['null' => true])
              ->addColumn('error_message', 'text', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['hotel_id'])
              ->create();
    }
}
