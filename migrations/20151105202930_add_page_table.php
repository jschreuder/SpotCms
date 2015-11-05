<?php

use Phinx\Migration\AbstractMigration;

class AddPageTable extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE pages (
                page_uuid BINARY(16) NOT NULL,
                title VARCHAR(512) NOT NULL,
                slug VARCHAR(48) NOT NULL,
                short_title VARCHAR(48) NOT NULL,
                parent_uuid BINARY(16) NULL DEFAULT NULL,
                sort_order INTEGER(11) NOT NULL DEFAULT 0,
                status VARCHAR(16) NOT NULL DEFAULT 'concept',
                PRIMARY KEY (page_uuid),
                UNIQUE slug_UNQ (slug ASC),
                INDEX parent_uuid_IDX (parent_uuid ASC)
            ) ENGINE = InnoDB
        ");
        $this->execute("
            ALTER TABLE pages
                ADD CONSTRAINT parent_uuid_FK
                    FOREIGN KEY (parent_uuid)
                    REFERENCES pages(page_uuid)
                    ON UPDATE RESTRICT ON DELETE RESTRICT,
                ADD CONSTRAINT page_uuid_FK
                    FOREIGN KEY (page_uuid)
                    REFERENCES objects(uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE pages");
    }
}
