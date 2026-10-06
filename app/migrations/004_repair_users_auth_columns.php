<?php

class Repair_users_auth_columns
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if (!$this->_lava->dbforge->table_exists('users')) {
            throw new RuntimeException('Cannot repair authentication columns: users table does not exist.');
        }

        $columns = [
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
                'default'    => '',
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => "'admin','moderator','user'",
                'null'       => false,
                'default'    => 'user',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 1,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->_lava->dbforge->column_exists('users', $name)) {
                $this->_lava->dbforge->add_column('users', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        // Keep authentication data once users have registered through the API.
    }
}
