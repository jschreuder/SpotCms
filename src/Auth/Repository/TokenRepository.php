<?php declare(strict_types = 1);

namespace Spot\Auth\Repository;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Spot\Auth\Entity\Token;
use Spot\DataModel\Repository\SqlRepositoryTrait;

class TokenRepository
{
    use SqlRepositoryTrait;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(Token $token)
    {
        $this->executeSql('
            INSERT INTO tokens (token_uuid, pass_code, user_uuid, expires)
                 VALUES (:token_uuid, :pass_code, :user_uuid, :expires)
        ', [
            'token_uuid' => $token->getUuid()->getBytes(),
            'pass_code' => $token->getPassCode(),
            'user_uuid' => $token->getUserUuid()->getBytes(),
            'expires' => $token->getExpires()->format('Y-m-d H:i:s'),
        ]);
    }

    public function delete(Token $token)
    {
        $this->executeSql('
            DELETE FROM tokens WHERE token_uuid = :token_uuid
        ', [
            'token_uuid' => $token->getUuid()->getBytes(),
        ]);
    }

    public function deleteExpired()
    {
        $this->executeSql('DELETE FROM tokens WHERE expires < NOW()');
    }

    private function getTokenFromRow(array $row) : Token
    {
        return new Token(
            Uuid::fromBytes($row['token_uuid']),
            $row['pass_code'],
            Uuid::fromBytes($row['user_uuid']),
            new \DateTimeImmutable($row['expires'])
        );
    }

    public function getByUuid(UuidInterface $uuid) : Token
    {
        return $this->getTokenFromRow($this->getRow('
            SELECT token_uuid, pass_code, user_uuid, expires
              FROM tokens
             WHERE token_uuid = :token_uuid
        ', [
            'token_uuid' => $uuid->getBytes()
        ]));
    }
}
