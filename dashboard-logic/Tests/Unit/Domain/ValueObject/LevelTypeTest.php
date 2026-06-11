<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use Dashboard\Domain\ValueObject\LevelType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios del Value Object LevelType.
 *
 * Verifica que solo acepte 0 (admin) o 1 (client),
 * que los métodos isAdmin/isClient funcionen,
 * y que la comparación entre tipos sea correcta.
 */
#[CoversClass(LevelType::class)]
final class LevelTypeTest extends TestCase
{
    public function test_admin_type_has_value_zero(): void
    {
        $type = new LevelType(LevelType::ADMIN);
        self::assertSame(0, $type->value());
    }

    public function test_client_type_has_value_one(): void
    {
        $type = new LevelType(LevelType::CLIENT);
        self::assertSame(1, $type->value());
    }

    public function test_isAdmin_returns_true_for_admin(): void
    {
        $type = new LevelType(LevelType::ADMIN);
        self::assertTrue($type->isAdmin());
    }

    public function test_isAdmin_returns_false_for_client(): void
    {
        $type = new LevelType(LevelType::CLIENT);
        self::assertFalse($type->isAdmin());
    }

    public function test_isClient_returns_true_for_client(): void
    {
        $type = new LevelType(LevelType::CLIENT);
        self::assertTrue($type->isClient());
    }

    public function test_isClient_returns_false_for_admin(): void
    {
        $type = new LevelType(LevelType::ADMIN);
        self::assertFalse($type->isClient());
    }

    public function test_invalid_value_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid level type');

        new LevelType(2);
    }

    public function test_negative_value_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);

        new LevelType(-1);
    }

    public function test_equals_returns_true_for_same_type(): void
    {
        $a = new LevelType(LevelType::ADMIN);
        $b = new LevelType(LevelType::ADMIN);

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_type(): void
    {
        $a = new LevelType(LevelType::ADMIN);
        $b = new LevelType(LevelType::CLIENT);

        self::assertFalse($a->equals($b));
    }

    public function test_constants_are_accessible(): void
    {
        self::assertSame(0, LevelType::ADMIN);
        self::assertSame(1, LevelType::CLIENT);
    }
}
