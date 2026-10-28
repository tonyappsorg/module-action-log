<?php
namespace Tonyapps\ActionLog\Test\Unit\Plugin;

use Magento\Backend\Model\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Magento\User\Model\User;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;
use Tonyapps\ActionLog\Plugin\AuthLoginPlugin;

class AuthLoginPluginTest extends TestCase
{
    public function testSuccessfulLoginRecordsUserAndOpensTrackedSession(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(12);
        $storage = new class($user) {
            public function __construct(private object $user) {}
            public function getUser(): object { return $this->user; }
            public function getSessionId(): string { return 'session-1'; }
        };
        $auth = $this->createMock(Auth::class);
        $auth->method('getAuthStorage')->willReturn($storage);
        $logger = $this->createMock(Logger::class);
        $logger->expects(self::once())->method('recordLogin')->with('admin', true, 12);
        $logger->expects(self::once())->method('openSession')->with(12, 'admin', 'session-1');

        $result = (new AuthLoginPlugin($logger, $helper, $this->createMock(SessionResource::class)))
            ->aroundLogin($auth, static fn() => 'ok', 'admin', 'password');

        self::assertSame('ok', $result);
    }

    public function testFailedLoginIsRecordedAndExceptionIsRethrown(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $logger = $this->createMock(Logger::class);
        $logger->expects(self::once())->method('recordLogin')->with('admin', false);
        $exception = new AuthenticationException(__('Invalid credentials.'));

        try {
            (new AuthLoginPlugin($logger, $helper, $this->createMock(SessionResource::class)))
                ->aroundLogin($this->createMock(Auth::class), static function () use ($exception) { throw $exception; }, 'admin', 'bad');
            self::fail('authentication exception should be rethrown');
        } catch (AuthenticationException $caught) {
            self::assertSame($exception, $caught);
        }
    }

    public function testLogoutTerminatesTheTrackedSession(): void
    {
        $storage = new class {
            public function getSessionId(): string { return 'session-1'; }
        };
        $auth = $this->createMock(Auth::class);
        $auth->method('getAuthStorage')->willReturn($storage);
        $sessions = $this->createMock(SessionResource::class);
        $sessions->expects(self::once())->method('terminateBySessionId')->with('session-1');

        $result = (new AuthLoginPlugin($this->createMock(Logger::class), $this->createMock(Helper::class), $sessions))
            ->aroundLogout($auth, static fn() => 'out');

        self::assertSame('out', $result);
    }
}
