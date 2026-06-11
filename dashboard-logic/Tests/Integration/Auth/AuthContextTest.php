<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use Dashboard\Database\Connection;
use Dashboard\Infrastructure\Auth\AuthContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthContext::class)]
final class AuthContextTest extends TestCase
{
    private AuthContext $authContext;
    private bool $dbAvailable = false;

    protected function setUp(): void
    {
        try {
            Connection::reset();
            Connection::get();
            $this->dbAvailable = true;
        } catch (\Throwable) {
            $this->dbAvailable = false;
        }

        $this->authContext = new AuthContext();
    }

    protected function tearDown(): void
    {
        Connection::reset();
        $_GET = [];
        $_POST = [];
    }

    // ══════════════════════════════════════════════════════════════
    // currentUser
    // ══════════════════════════════════════════════════════════════

    public function test_current_user_returns_null_without_cookie(): void
    {
        // Ensure no cookie is set
        unset($_COOKIE['project_user']);

        $user = $this->authContext->currentUser();

        self::assertNull($user, 'Without cookie, currentUser must return null');
    }

    public function test_current_user_returns_null_with_invalid_cookie(): void
    {
        $_COOKIE['project_user'] = 'invalid-base64!!!';

        $user = $this->authContext->currentUser();

        self::assertNull($user, 'Invalid base64 cookie must return null');
    }

    public function test_current_user_returns_null_with_empty_cookie(): void
    {
        $_COOKIE['project_user'] = '';

        $user = $this->authContext->currentUser();

        self::assertNull($user, 'Empty cookie must return null');
    }

    // ══════════════════════════════════════════════════════════════
    // isAuthenticated
    // ══════════════════════════════════════════════════════════════

    public function test_is_authenticated_false_without_cookie(): void
    {
        unset($_COOKIE['project_user']);

        self::assertFalse($this->authContext->isAuthenticated());
    }

    // ══════════════════════════════════════════════════════════════
    // can
    // ══════════════════════════════════════════════════════════════

    public function test_can_returns_false_for_null_user(): void
    {
        self::assertFalse(
            $this->authContext->can('users.manage', null),
            'Null user must have no permissions',
        );
    }

    public function test_can_returns_true_for_admin_user(): void
    {
        $adminUser = [
            'userID'     => 'any-uuid',
            'email'      => 'admin@test.com',
            'name'       => 'Admin',
            'level'      => 'any-level-uuid',
            'level_name' => 'admin',
            'level_type' => 0,
        ];

        self::assertTrue(
            $this->authContext->can('any.fake.permission', $adminUser),
            'Admin (type=0) must have ALL permissions',
        );
    }

    public function test_can_works_with_real_admin_user(): void
    {
        if (!$this->dbAvailable) {
            self::markTestSkipped('Database not available');
        }

        // Get a real admin from DB
        $pdo = Connection::get();
        $admin = $pdo->query("
            SELECT u.userID, u.email, u.name, u.level, l.level_name, l.level_type
            FROM USERS u
            JOIN levels l ON l.levelsID = u.level
            WHERE l.level_type = 0
            LIMIT 1
        ")->fetch();

        if (!$admin) {
            self::markTestSkipped('No admin user in database');
        }

        $admin['level_type'] = (int) $admin['level_type'];

        self::assertTrue(
            $this->authContext->can('users.manage', $admin),
            'Real admin must have users.manage permission',
        );

        self::assertTrue(
            $this->authContext->can('server.view', $admin),
            'Real admin must have server.view permission',
        );
    }

    public function test_can_falls_back_to_current_user(): void
    {
        // Without cookie, currentUser returns null → can returns false
        unset($_COOKIE['project_user']);

        self::assertFalse(
            $this->authContext->can('users.manage'),
            'Without user and without cookie, can() must return false',
        );
    }

    // ══════════════════════════════════════════════════════════════
    // redirectParam / redirectTarget
    // ══════════════════════════════════════════════════════════════

    public function test_redirect_param_from_get(): void
    {
        $_GET['redirect'] = '/dashboard';
        $_POST = [];

        self::assertSame('/dashboard', $this->authContext->redirectParam());
    }

    public function test_redirect_param_from_post(): void
    {
        $_GET = [];
        $_POST['redirect'] = '/admin';

        self::assertSame('/admin', $this->authContext->redirectParam());
    }

    public function test_redirect_param_defaults_to_empty(): void
    {
        $_GET = [];
        $_POST = [];

        self::assertSame('', $this->authContext->redirectParam());
    }

    public function test_redirect_target_uses_param_when_valid(): void
    {
        $_GET['redirect'] = '/users?tab=projects';
        $_POST = [];

        $target = $this->authContext->redirectTarget('/index.php');

        self::assertSame('/users?tab=projects', $target);
    }

    public function test_redirect_target_falls_back_to_script_name(): void
    {
        $_GET = [];
        $_POST = [];

        $target = $this->authContext->redirectTarget('/index.php');

        self::assertSame('/index.php', $target);
    }

    public function test_redirect_target_rejects_relative_paths(): void
    {
        $_GET['redirect'] = '../evil';
        $_POST = [];

        $target = $this->authContext->redirectTarget('/index.php');

        self::assertSame('/index.php', $target, 'Non-absolute redirects must be ignored');
    }
}
