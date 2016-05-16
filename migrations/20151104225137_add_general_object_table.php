<?php

use Phinx\Migration\AbstractMigration;

class AddGeneralObjectTable extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE objects (
                uuid BINARY(16) NOT NULL,
                type VARCHAR(100) NOT NULL COMMENT 'Contains the tablename where the UUID is stored',
                created DATETIME NOT NULL,
                updated DATETIME NOT NULL,
                PRIMARY KEY (uuid),
                INDEX type_IDX (type ASC)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE objects");
    }
}
