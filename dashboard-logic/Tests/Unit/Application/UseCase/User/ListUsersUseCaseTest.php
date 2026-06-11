<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase\User;

use Dashboard\Application\Repository\UserRepositoryInterface;
use Dashboard\Application\UseCase\User\ListUsersUseCase;
use Dashboard\Domain\Entity\User;
use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ListUsersUseCase::class)]
final class ListUsersUseCaseTest extends TestCase
{
    private UserRepositoryInterface $userRepo;
    private ListUsersUseCase $useCase;

    protected function setUp(): void
    {
        $this->userRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase  = new ListUsersUseCase($this->userRepo);
    }

    public function test_execute_returns_all_users(): void
    {
        $users = [
            User::create('u1', new Email('alice@example.com'), 'Alice', 'pass', 'lvl-1'),
            User::create('u2', new Email('bob@example.com'), 'Bob', 'pass', 'lvl-1'),
        ];

        $this->userRepo->method('findAll')
            ->willReturn($users);

        $result = $this->useCase->execute();

        self::assertCount(2, $result);
        self::assertSame('alice@example.com', $result[0]->email()->value());
        self::assertSame('bob@example.com', $result[1]->email()->value());
    }

    public function test_findByLevel_filters_by_level(): void
    {
        $users = [
            User::create('u1', new Email('admin@example.com'), 'Admin', 'pass', 'lvl-admin'),
        ];

        $this->userRepo->method('findByLevel')
            ->with('lvl-admin')
            ->willReturn($users);

        $result = $this->useCase->findByLevel('lvl-admin');

        self::assertCount(1, $result);
        self::assertSame('lvl-admin', $result[0]->levelId());
    }

    public function test_execute_returns_empty_array_when_no_users(): void
    {
        $this->userRepo->method('findAll')
            ->willReturn([]);

        $result = $this->useCase->execute();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }
}
