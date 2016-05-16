<?php

use Phinx\Migration\AbstractMigration;

class AddConfigCollections extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE config_collections (
                config_collection_uuid BINARY(16) NOT NULL,
                type VARCHAR(96) NOT NULL,
                name VARCHAR(48) NOT NULL,
                PRIMARY KEY (config_collection_uuid),
                UNIQUE type_name_UNQ (type ASC, name ASC)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");
        $this->execute("
            ALTER TABLE config_collections
                ADD CONSTRAINT config_collection_uuid_FK
                    FOREIGN KEY (config_collection_uuid)
                    REFERENCES objects(uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");

        $this->execute("
            CREATE TABLE config_items (
                config_collection_uuid BINARY(16) NOT NULL,
                name VARCHAR(96) NOT NULL,
                value TEXT NULL,
                PRIMARY KEY (config_collection_uuid, name)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");
        $this->execute("
            ALTER TABLE config_items
                ADD CONSTRAINT config_items_collections_FK
                    FOREIGN KEY (config_collection_uuid)
                    REFERENCES config_collections(config_collection_uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE config_items");
        $this->execute("DROP TABLE config_collections");
    }
}
