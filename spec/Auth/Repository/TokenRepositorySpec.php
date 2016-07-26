<?php

namespace spec\Spot\Auth\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\Token;
use Spot\Auth\Repository\TokenRepository;

/** @mixin  TokenRepository */
class TokenRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    public function let(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->beConstructedWith($pdo);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(TokenRepository::class);
    }

    public function it_can_insert_a_token(Token $token, \PDOStatement $statement)
    {
        $token->getUuid()->willReturn($uuid = Uuid::uuid4());
        $token->getPassCode()->willReturn($passCode = bin2hex(random_bytes(20)));
        $token->getUserUuid()->willReturn($userUuid = Uuid::uuid4());
        $token->getExpires()->willReturn($expires = new \DateTimeImmutable('+42 seconds'));

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO tokens'))
            ->willReturn($statement);
        $statement->execute([
            'token_uuid' => $uuid->getBytes(),
            'pass_code' => $passCode,
            'user_uuid' => $userUuid->getBytes(),
            'expires' => $expires->format('Y-m-d H:i:s'),
        ])->shouldBeCalled();

        $this->create($token);
    }

    public function it_can_delete_a_token(Token $token, \PDOStatement $statement)
    {
        $token->getUuid()->willReturn($uuid = Uuid::uuid4());

        $this->pdo->prepare(new Argument\Token\StringContainsToken('DELETE FROM tokens'))
            ->willReturn($statement);
        $statement->execute(['token_uuid' => $uuid->getBytes()])->shouldBeCalled();

        $this->delete($token);
    }

    public function it_can_delete_expired_tokens(\PDOStatement $statement)
    {
        $this->pdo->prepare(new Argument\Token\StringContainsToken('DELETE FROM tokens'))
            ->willReturn($statement);
        $statement->execute([])->shouldBeCalled();

        $this->deleteExpired();
    }

    public function it_can_retrieve_a_user(\PDOStatement $statement)
    {
        $tokenUuid = Uuid::uuid4();
        $userUuid = Uuid::uuid4();
        $expires = new \DateTimeImmutable('+42 seconds');
        $tokenRow = [
            'token_uuid' => $tokenUuid->getBytes(),
            'pass_code' => bin2hex(random_bytes(20)),
            'user_uuid' => $userUuid->getBytes(),
            'expires' => $expires->format('Y-m-d H:i:s'),
        ];

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM tokens'))
            ->willReturn($statement);
        $statement->execute(['token_uuid' => $tokenUuid->getBytes()])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn($tokenRow);

        $token = $this->getByUuid($tokenUuid);
        $token->shouldHaveType(Token::class);
        $token->getUuid()->equals($tokenUuid)->shouldReturn(true);
        $token->getPassCode()->shouldReturn($tokenRow['pass_code']);
        $token->getUserUuid()->equals($userUuid)->shouldReturn(true);
        $token->getExpires()->format('Y-m-d H:i:s')->shouldReturn($tokenRow['expires']);
    }
}
