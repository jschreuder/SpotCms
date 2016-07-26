<?php

use Phinx\Migration\AbstractMigration;

class AddUsersAndTokens extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            CREATE TABLE users (
                user_uuid BINARY(16) NOT NULL,
                email_address VARCHAR(128) NOT NULL,
                password VARCHAR(255) NOT NULL,
                display_name VARCHAR(64) NOT NULL,
                PRIMARY KEY (user_uuid),
                UNIQUE email_address_UNQ (email_address ASC)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");

        $this->execute("
            CREATE TABLE tokens (
                token_uuid BINARY(16) NOT NULL,
                pass_code CHAR(40) NOT NULL,
                user_uuid BINARY(16) NOT NULL,
                expires DATETIME NOT NULL,
                PRIMARY KEY (token_uuid),
                INDEX user_uuid_IDX (user_uuid ASC)
            ) ENGINE=InnoDB CHARSET=utf8mb4
        ");
        $this->execute("
            ALTER TABLE tokens
                ADD CONSTRAINT user_uuid_FK
                    FOREIGN KEY (user_uuid)
                    REFERENCES users(user_uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("DROP TABLE tokens");
        $this->execute("DROP TABLE users");
    }
}
