<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RbacTables extends AbstractMigration
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
        // Roles table
        $roles = $this->table('roles');
        $roles->addColumn('name', 'string', ['limit' => 50])
              ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['name'], ['unique' => true])
              ->create();

        // Permissions table
        $permissions = $this->table('permissions');
        $permissions->addColumn('name', 'string', ['limit' => 50])
                    ->addColumn('description', 'string', ['limit' => 255, 'null' => true])
                    ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                    ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
                    ->addIndex(['name'], ['unique' => true])
                    ->create();

        // Role has permissions table
        $role_has_permissions = $this->table('role_has_permissions', ['id' => false, 'primary_key' => ['role_id', 'permission_id']]);
        $role_has_permissions->addColumn('role_id', 'integer')
                             ->addColumn('permission_id', 'integer')
                             ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                             ->addForeignKey('permission_id', 'permissions', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
                             ->create();

        // Add role_id to users
        $users = $this->table('users');
        $users->addColumn('role_id', 'integer', ['null' => true, 'after' => 'role'])
              ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'SET_NULL', 'update' => 'CASCADE'])
              ->update();
    }
}
