<?php

namespace spec\Spot\Auth\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\User;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Value\EmailAddress;

/** @mixin  UserRepository */
class UserRepositorySpec extends ObjectBehavior
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
        $this->shouldHaveType(UserRepository::class);
    }

    public function it_can_insert_a_user(User $user, \PDOStatement $statement)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $user->getEmailAddress()->willReturn(EmailAddress::get($email = 'nute@gunray.tf'));
        $user->getPassword()->willReturn($password = password_hash('no.jedi.please', PASSWORD_BCRYPT));
        $user->getDisplayName()->willReturn($displayName = 'Nute Gunray');

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO users'))
            ->willReturn($statement);
        $statement->execute([
            'user_uuid' => $uuid->getBytes(),
            'email_address' => $email,
            'password' => $password,
            'display_name' => $displayName,
        ])->shouldBeCalled();

        $this->create($user);
    }

    public function it_can_delete_a_token(User $user, \PDOStatement $statement)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());

        $this->pdo->prepare(new Argument\Token\StringContainsToken('DELETE FROM users'))
            ->willReturn($statement);
        $statement->execute(['user_uuid' => $uuid->getBytes()])->shouldBeCalled();

        $this->delete($user);
    }

    public function it_can_update_a_user(User $user, \PDOStatement $statement)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $user->getEmailAddress()->willReturn(EmailAddress::get($email = 'nute@gunray.tf'));
        $user->getPassword()->willReturn($password = password_hash('no.jedi.please', PASSWORD_BCRYPT));
        $user->getDisplayName()->willReturn($displayName = 'Nute Gunray');

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE users'))
            ->willReturn($statement);
        $statement->execute([
            'user_uuid' => $uuid->getBytes(),
            'email_address' => $email,
            'password' => $password,
            'display_name' => $displayName,
        ])->shouldBeCalled();

        $this->update($user);
    }

    public function it_can_retrieve_a_user_by_uuid(\PDOStatement $statement)
    {
        $userUuid = Uuid::uuid4();
        $userRow = [
            'user_uuid' => $userUuid->getBytes(),
            'email_address' => 'leia@organa.alderaan',
            'password' => password_hash('no.siblings.as.far.as.i.know', PASSWORD_BCRYPT),
            'display_name' => 'Princess Leia',
        ];

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM users'))
            ->willReturn($statement);
        $statement->execute(['user_uuid' => $userUuid->getBytes()])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn($userRow);

        $user = $this->getByUuid($userUuid);
        $user->shouldHaveType(User::class);
        $user->getUuid()->equals($userUuid)->shouldReturn(true);
        $user->getEmailAddress()->toString()->shouldReturn($userRow['email_address']);
        $user->getPassword()->shouldReturn($userRow['password']);
        $user->getDisplayName()->shouldReturn($userRow['display_name']);
    }

    public function it_can_retrieve_a_user_by_email(\PDOStatement $statement)
    {
        $userUuid = Uuid::uuid4();
        $userRow = [
            'user_uuid' => $userUuid->getBytes(),
            'email_address' => 'leia@organa.alderaan',
            'password' => password_hash('no.siblings.as.far.as.i.know', PASSWORD_BCRYPT),
            'display_name' => 'Princess Leia',
        ];

        $this->pdo->prepare(new Argument\Token\StringContainsToken('FROM users'))
            ->willReturn($statement);
        $statement->execute(['email_address' => $userRow['email_address']])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);
        $statement->fetch(\PDO::FETCH_ASSOC)->willReturn($userRow);

        $user = $this->getByEmailAddress(EmailAddress::get($userRow['email_address']));
        $user->shouldHaveType(User::class);
        $user->getUuid()->equals($userUuid)->shouldReturn(true);
        $user->getEmailAddress()->toString()->shouldReturn($userRow['email_address']);
        $user->getPassword()->shouldReturn($userRow['password']);
        $user->getDisplayName()->shouldReturn($userRow['display_name']);
    }
}
