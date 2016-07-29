<?php

use Phinx\Migration\AbstractMigration;

class LinkUsersToObjectRepository extends AbstractMigration
{
    public function up()
    {
        $this->execute("
            INSERT INTO objects (uuid, type, created, updated)
                 SELECT user_uuid AS uuid, 'users' as type, '2016-07-30 00:00:00' AS created,
                        '2016-07-30 00:00:00' AS updated
                   FROM users
        ");
        $this->execute("
            ALTER TABLE users
                ADD CONSTRAINT user_uuid_objects_FK
                    FOREIGN KEY (user_uuid)
                    REFERENCES objects(uuid)
                    ON UPDATE RESTRICT ON DELETE CASCADE
        ");
    }

    public function down()
    {
        $this->execute("
            ALTER TABLE users
                DROP FOREIGN KEY user_uuid_objects_FK
        ");
        $this->execute("
            DELETE FROM objects
                  WHERE type = 'users'
        ");
    }
}
