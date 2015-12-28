<?php

use Phinx\Migration\AbstractMigration;

class AddFilesTable extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE files (
                file_uuid BINARY(16) NOT NULL,
                name VARCHAR(96) NOT NULL,
                path VARCHAR(192) NOT NULL,
                mime_type VARCHAR(128) NOT NULL,
                PRIMARY KEY (file_uuid),
                UNIQUE path_file_UNQ (path ASC, name ASC)
            ) ENGINE = InnoDB
        ");
        $this->execute("
            ALTER TABLE files
                ADD CONSTRAINT file_uuid_FK
                    FOREIGN KEY (file_uuid)
                    REFERENCES objects(uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE files");
    }
}
