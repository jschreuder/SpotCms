<?php

use Phinx\Migration\AbstractMigration;

class AddPageBlocksToPages extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE page_blocks (
                page_block_uuid BINARY(16) NOT NULL,
                page_uuid BINARY(16) NOT NULL,
                type VARCHAR(48) NOT NULL,
                parameters TEXT NULL,
                location VARCHAR(48) NOT NULL,
                sort_order INTEGER(11) NOT NULL DEFAULT 0,
                status VARCHAR(16) NOT NULL DEFAULT 'concept',
                PRIMARY KEY (page_block_uuid),
                INDEX page_uuid_IDX (page_uuid ASC),
                INDEX location_IDX (location ASC)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");
        $this->execute("
            ALTER TABLE page_blocks
                ADD CONSTRAINT pages_page_blocks_FK
                    FOREIGN KEY (page_uuid)
                    REFERENCES pages(page_uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE,
                ADD CONSTRAINT page_block_uuid_FK
                    FOREIGN KEY (page_block_uuid)
                    REFERENCES objects(uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE page_blocks");
    }
}
