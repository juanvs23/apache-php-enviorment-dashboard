<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la entidad User.
 *
 * Verifica creación, autenticación, cambio de datos
 * e inmutabilidad del ID.
 */
#[CoversClass(User::class)]
final class UserTest extends TestCase
{
    private const UUID = '550e8400-e29b-41d4-a716-446655440000';

    private Email $email;
    private User $user;

    protected function setUp(): void
    {
        $this->email = new Email('user@example.com');
        $this->user  = new User(
            self::UUID,
            $this->email,
            'Test User',
            password_hash('secret123', PASSWORD_BCRYPT),
            'level-1',
        );
    }

    public function test_constructor_sets_all_properties(): void
    {
        self::assertSame(self::UUID, $this->user->userId());
        self::assertSame('user@example.com', $this->user->email()->value());
        self::assertSame('Test User', $this->user->name());
        self::assertSame('level-1', $this->user->levelId());
    }

    public function test_create_factory_hashes_password(): void
    {
        $user = User::create(
            self::UUID,
            $this->email,
            'New User',
            'plainPassword',
            'level-2',
        );

        self::assertNotSame('plainPassword', $user->passwordHash());
        self::assertTrue(password_verify('plainPassword', $user->passwordHash()));
    }

    public function test_authenticate_with_correct_password_returns_true(): void
    {
        self::assertTrue($this->user->authenticate('secret123'));
    }

    public function test_authenticate_with_wrong_password_returns_false(): void
    {
        self::assertFalse($this->user->authenticate('wrong-password'));
    }

    public function test_userId_is_immutable(): void
    {
        // El ID está en readonly, no se puede cambiar por fuera
        $id = $this->user->userId();
        self::assertSame(self::UUID, $id);
    }

    public function test_changeEmail_updates_email(): void
    {
        $newEmail = new Email('new@example.com');
        $this->user->changeEmail($newEmail);

        self::assertSame('new@example.com', $this->user->email()->value());
    }

    public function test_changeName_updates_name(): void
    {
        $this->user->changeName('Updated Name');
        self::assertSame('Updated Name', $this->user->name());

        $this->user->changeName(null);
        self::assertNull($this->user->name());
    }

    public function test_changePassword_updates_hash(): void
    {
        $oldHash = $this->user->passwordHash();
        $this->user->changePassword('newPassword456');

        self::assertNotSame($oldHash, $this->user->passwordHash());
        self::assertTrue($this->user->authenticate('newPassword456'));
    }

    public function test_changeLevel_updates_levelId(): void
    {
        $this->user->changeLevel('level-99');
        self::assertSame('level-99', $this->user->levelId());
    }

    public function test_authenticate_fails_after_changePassword_with_old_password(): void
    {
        $this->user->changePassword('newPassword456');

        self::assertFalse($this->user->authenticate('secret123'));
        self::assertTrue($this->user->authenticate('newPassword456'));
    }

    public function test_name_can_be_null(): void
    {
        $user = new User(
            self::UUID,
            $this->email,
            null,
            password_hash('pwd', PASSWORD_BCRYPT),
            'level-1',
        );

        self::assertNull($user->name());
    }
}
