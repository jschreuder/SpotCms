<?php declare(strict_types = 1);

namespace Spot\Auth\Repository;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\User;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Repository\ObjectRepository;
use Spot\DataModel\Repository\SqlRepositoryTrait;

class UserRepository
{
    use SqlRepositoryTrait;

    /** @var  \PDO */
    private $pdo;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function __construct(\PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
    }

    public function create(User $user)
    {
        $this->pdo->beginTransaction();
        try {
            $this->objectRepository->create(User::TYPE, $user->getUuid());
            $this->executeSql('
                INSERT INTO users (user_uuid, email_address, password, display_name)
                     VALUES (:user_uuid, :email_address, :password, :display_name)
            ', [
                'user_uuid' => $user->getUuid()->getBytes(),
                'email_address' => $user->getEmailAddress()->toString(),
                'password' => $user->getPassword(),
                'display_name' => $user->getDisplayName(),
            ]);
            $this->pdo->commit();
            $user->metaDataSetInsertTimestamp(new \DateTimeImmutable());
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(User $user)
    {
        // The database constraint should cascade the delete to the page
        $this->objectRepository->delete(User::TYPE, $user->getUuid());
    }

    public function update(User $user)
    {
        $this->pdo->beginTransaction();
        try {
            $query = $this->executeSql('
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

            // When at least one of the fields changes, the rowCount will be 1 and an update occurred
            if ($query->rowCount() === 1) {
                $this->objectRepository->update(User::TYPE, $user->getUuid());
                $user->metaDataSetUpdateTimestamp(new \DateTimeImmutable());
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function getUserFromRow(array $row)
    {
        return (new User(
            Uuid::fromBytes($row['user_uuid']),
            EmailAddress::get($row['email_address']),
            $row['password'],
            $row['display_name']
            ))
            ->metaDataSetInsertTimestamp(new \DateTimeImmutable($row['created']))
            ->metaDataSetUpdateTimestamp(new \DateTimeImmutable($row['updated']));
    }

    public function getByUuid(UuidInterface $uuid)
    {
        return $this->getUserFromRow($this->getRow('
                SELECT user_uuid, email_address, password, display_name, created, updated
                  FROM users
            INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                 WHERE user_uuid = :user_uuid
        ', [
            'user_uuid' => $uuid->getBytes(),
        ]));
    }

    public function getByEmailAddress(EmailAddress $emailAddress)
    {
        return $this->getUserFromRow($this->getRow('
                SELECT user_uuid, email_address, password, display_name, created, updated
                  FROM users
            INNER JOIN objects ON (page_uuid = uuid AND type = "pages")
                 WHERE email_address = :email_address
        ', [
            'email_address' => $emailAddress->toString(),
        ]));
    }
}
