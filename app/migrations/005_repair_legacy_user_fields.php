<?php

class Repair_legacy_user_fields
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        foreach (['firstname', 'lastname'] as $name) {
            if ($this->_lava->dbforge->column_exists('users', $name)) {
                $this->_lava->dbforge->modify_column('users', [
                    $name => [
                        'type'       => 'VARCHAR',
                        'constraint' => 100,
                        'null'       => true,
                    ],
                ]);
            }
        }

        if ($this->_lava->dbforge->column_exists('users', 'email')) {
            $this->_lava->dbforge->modify_column('users', [
                'email' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => false,
                ],
            ]);
        }
    }

    public function down()
    {
        // Preserve user data and compatibility once registration is enabled.
    }
}
