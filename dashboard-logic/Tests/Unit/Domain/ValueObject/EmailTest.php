<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use Dashboard\Domain\ValueObject\Email;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios del Value Object Email.
 *
 * Verifica validación de formato, normalización a minúsculas,
 * límite de caracteres, inmutabilidad y comparación entre emails.
 */
#[CoversClass(Email::class)]
final class EmailTest extends TestCase
{
    public function test_valid_email_stores_normalized_value(): void
    {
        $email = new Email('Test@Example.com');
        self::assertSame('test@example.com', $email->value());
    }

    public function test_valid_email_trims_whitespace(): void
    {
        $email = new Email('  user@example.com  ');
        self::assertSame('user@example.com', $email->value());
    }

    public function test_empty_string_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        new Email('');
    }

    public function test_whitespace_only_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);

        new Email('   ');
    }

    public function test_invalid_format_throws_domain_exception(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Invalid email format');

        new Email('not-an-email');
    }

    public function test_email_without_at_sign_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        new Email('userexample.com');
    }

    public function test_email_without_domain_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        new Email('user@');
    }

    public function test_email_too_long_throws_exception(): void
    {
        $this->expectException(\DomainException::class);

        // filter_var() tiene un límite interno de 254 chars, rechazando como formato inválido
        $local = str_repeat('a', 251);
        $email = "{$local}@b.co";
        self::assertGreaterThan(255, strlen($email));

        new Email($email);
    }

    public function test_toString_returns_email_value(): void
    {
        $email = new Email('user@example.com');
        self::assertSame('user@example.com', (string) $email);
    }

    public function test_equals_returns_true_for_same_value(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('USER@example.com'); // se normaliza a minúsculas

        self::assertTrue($a->equals($b));
    }

    public function test_equals_returns_false_for_different_value(): void
    {
        $a = new Email('alice@example.com');
        $b = new Email('bob@example.com');

        self::assertFalse($a->equals($b));
    }

    public function test_subaddressing_is_valid(): void
    {
        $email = new Email('user+tag@example.com');
        self::assertSame('user+tag@example.com', $email->value());
    }
}
