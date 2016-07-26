<?php declare(strict_types = 1);

namespace Spot\Auth\Repository;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\User;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Repository\SqlRepositoryTrait;

class UserRepository
{
    use SqlRepositoryTrait;

    /** @var  \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(User $user)
    {
        $this->executeSql('
            INSERT INTO users (user_uuid, email_address, password, display_name)
                 VALUES (:user_uuid, :email_address, :password, :display_name)
        ', [
            'user_uuid' => $user->getUuid()->getBytes(),
            'email_address' => $user->getEmailAddress()->toString(),
            'password' => $user->getPassword(),
            'display_name' => $user->getDisplayName(),
        ]);
    }

    public function delete(User $user)
    {
        $this->executeSql('
            DELETE FROM users WHERE user_uuid = :user_uuid
        ', [
            'user_uuid' => $user->getUuid()->getBytes(),
        ]);
    }

    public function update(User $user)
    {
        $this->executeSql('
            UPDATE users
               SET email_address = :email_address,
                   password = :password,
                   display_name = :display_name
             WHERE user_uuid = :user_uuid
        ', [
            'user_uuid' => $user->getUuid()->getBytes(),
            'email_address' => $user->getEmailAddress()->toString(),
            'password' => $user->getPassword(),
            'display_name' => $user->getDisplayName(),
        ]);
    }

    private function getUserFromRow(array $row)
    {
        return new User(
            Uuid::fromBytes($row['user_uuid']),
            EmailAddress::get($row['email_address']),
            $row['password'],
            $row['display_name']
        );
    }

    public function getByUuid(UuidInterface $uuid)
    {
        return $this->getUserFromRow($this->getRow('
            SELECT user_uuid, email_address, password, display_name
              FROM users
             WHERE user_uuid = :user_uuid
        ', [
            'user_uuid' => $uuid->getBytes(),
        ]));
    }

    public function getByEmailAddress(EmailAddress $emailAddress)
    {
        return $this->getUserFromRow($this->getRow('
            SELECT user_uuid, email_address, password, display_name
              FROM users
             WHERE email_address = :email_address
        ', [
            'email_address' => $emailAddress->toString(),
        ]));
    }
}
