<?php

namespace spec\Spot\Auth\Repository;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Ramsey\Uuid\Uuid;
use Spot\Auth\Entity\User;
use Spot\Auth\Repository\UserRepository;
use Spot\Auth\Value\EmailAddress;
use Spot\DataModel\Repository\ObjectRepository;

/** @mixin  UserRepository */
class UserRepositorySpec extends ObjectBehavior
{
    /** @var  \PDO */
    private $pdo;

    /** @var  ObjectRepository */
    private $objectRepository;

    public function let(\PDO $pdo, ObjectRepository $objectRepository)
    {
        $this->pdo = $pdo;
        $this->objectRepository = $objectRepository;
        $this->beConstructedWith($pdo, $objectRepository);
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
        $user->metaDataSetInsertTimestamp(new Argument\Token\TypeToken(\DateTimeInterface::class))->shouldBeCalled();

        $this->pdo->beginTransaction()->shouldBeCalled();
        $this->objectRepository->create(User::TYPE, $uuid);

        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO users'))
            ->willReturn($statement);
        $statement->execute([
            'user_uuid' => $uuid->getBytes(),
            'email_address' => $email,
            'password' => $password,
            'display_name' => $displayName,
        ])->shouldBeCalled();

        $this->pdo->commit()->shouldBeCalled();

        $this->create($user);
    }

    public function it_can_rollBack_on_error_during_insert_user(User $user)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $user->getEmailAddress()->willReturn(EmailAddress::get($email = 'nute@gunray.tf'));
        $user->getPassword()->willReturn($password = password_hash('no.jedi.please', PASSWORD_BCRYPT));
        $user->getDisplayName()->willReturn($displayName = 'Nute Gunray');

        $this->pdo->beginTransaction()->shouldBeCalled();
        $this->objectRepository->create(User::TYPE, $uuid);

        $exception = new \RuntimeException();
        $this->pdo->prepare(new Argument\Token\StringContainsToken('INSERT INTO users'))
            ->willThrow($exception);

        $this->pdo->rollBack()->shouldBeCalled();
        $this->shouldThrow($exception)->duringCreate($user);
    }

    public function it_can_delete_a_user(User $user)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $this->objectRepository->delete(User::TYPE, $uuid);
        $this->delete($user);
    }

    public function it_can_update_a_user(User $user, \PDOStatement $statement)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $user->getEmailAddress()->willReturn(EmailAddress::get($email = 'nute@gunray.tf'));
        $user->getPassword()->willReturn($password = password_hash('no.jedi.please', PASSWORD_BCRYPT));
        $user->getDisplayName()->willReturn($displayName = 'Nute Gunray');
        $user->metaDataSetUpdateTimestamp(new Argument\Token\TypeToken(\DateTimeInterface::class))->shouldBeCalled();

        $this->pdo->beginTransaction()->shouldBeCalled();

        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE users'))
            ->willReturn($statement);
        $statement->execute([
            'user_uuid' => $uuid->getBytes(),
            'email_address' => $email,
            'password' => $password,
            'display_name' => $displayName,
        ])->shouldBeCalled();
        $statement->rowCount()->willReturn(1);

        $this->objectRepository->update(User::TYPE, $uuid)->shouldBeCalled();
        $this->pdo->commit()->shouldBeCalled();

        $this->update($user);
    }

    public function it_can_rollBack_on_error_during_update_user(User $user)
    {
        $user->getUuid()->willReturn($uuid = Uuid::uuid4());
        $user->getEmailAddress()->willReturn(EmailAddress::get($email = 'nute@gunray.tf'));
        $user->getPassword()->willReturn($password = password_hash('no.jedi.please', PASSWORD_BCRYPT));
        $user->getDisplayName()->willReturn($displayName = 'Nute Gunray');

        $this->pdo->beginTransaction()->shouldBeCalled();

        $exception = new \RuntimeException();
        $this->pdo->prepare(new Argument\Token\StringContainsToken('UPDATE users'))
            ->willThrow($exception);

        $this->pdo->rollBack()->shouldBeCalled();

        $this->shouldThrow($exception)->duringUpdate($user);
    }

    public function it_can_retrieve_a_user_by_uuid(\PDOStatement $statement)
    {
        $userUuid = Uuid::uuid4();
        $userRow = [
            'user_uuid' => $userUuid->getBytes(),
            'email_address' => 'leia@organa.alderaan',
            'password' => password_hash('no.siblings.as.far.as.i.know', PASSWORD_BCRYPT),
            'display_name' => 'Princess Leia',
            'created' => '1977-05-25 00:11:38',
            'updated' => '1997-01-31 00:11:38',
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
            'created' => '1977-05-25 00:11:38',
            'updated' => '1997-01-31 00:11:38',
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
