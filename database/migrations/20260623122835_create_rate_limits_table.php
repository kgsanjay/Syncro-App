<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRateLimitsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('rate_limits');
        $table->addColumn('identifier', 'string', ['limit' => 255])
              ->addColumn('hits', 'integer', ['default' => 0])
              ->addColumn('window_start', 'integer')
              ->addIndex(['identifier'], ['unique' => true])
              ->addIndex(['window_start']) // useful for cleanup
              ->create();
    }
}
