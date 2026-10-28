<?php
namespace Tonyapps\ActionLog\Test\Unit\Plugin;

use Magento\Backend\Model\Auth\Session;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;
use Tonyapps\ActionLog\Plugin\SessionGuardPlugin;

class SessionGuardPluginTest extends TestCase
{
    public function testEnabledSessionHeartbeatTouchesTheTrackedRow(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $sessions = $this->createMock(SessionResource::class);
        $sessions->expects(self::once())->method('touch')->with('session-1');
        $subject = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['getSessionId'])->getMock();
        $subject->method('getSessionId')->willReturn('session-1');

        self::assertSame('prolonged', (new SessionGuardPlugin($sessions, $helper))->afterProlong($subject, 'prolonged'));
    }

    public function testTerminatedLoggedInSessionIsDestroyed(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $sessions = $this->createMock(SessionResource::class);
        $sessions->method('isTerminated')->with('session-1')->willReturn(true);
        $subject = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['getSessionId', 'destroy'])->getMock();
        $subject->method('getSessionId')->willReturn('session-1');
        $subject->expects(self::once())->method('destroy');

        self::assertFalse((new SessionGuardPlugin($sessions, $helper))->afterIsLoggedIn($subject, true));
    }

    public function testDisabledModuleDoesNotTouchOrTerminateSession(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(false);
        $sessions = $this->createMock(SessionResource::class);
        $sessions->expects(self::never())->method('touch');
        $sessions->expects(self::never())->method('isTerminated');
        $subject = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['getSessionId'])->getMock();

        $plugin = new SessionGuardPlugin($sessions, $helper);
        self::assertSame('ok', $plugin->afterProlong($subject, 'ok'));
        self::assertTrue($plugin->afterIsLoggedIn($subject, true));
    }
}
