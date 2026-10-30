<?php
namespace Tonyapps\ActionLog\Test\Unit\Plugin;

use Magento\Config\Model\Config;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Plugin\ConfigSavePlugin;

class ConfigSavePluginTest extends TestCase
{
    public function testEnabledModuleRecordsAfterSuccessfulSaveWithScope(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $logger = $this->createMock(Logger::class);
        $logger->expects(self::once())->method('recordConfigChange')->with('payment', ['group' => ['field' => '1']], '1', null);
        $subject = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->addMethods(['getSection', 'getGroups', 'getWebsite', 'getStore'])
            ->getMock();
        $subject->method('getSection')->willReturn('payment');
        $subject->method('getWebsite')->willReturn('1');
        $subject->method('getStore')->willReturn('');
        $subject->method('getGroups')->willReturn(['group' => ['field' => '1']]);

        $result = (new ConfigSavePlugin($logger, $helper))->aroundSave($subject, static fn() => 'saved');

        self::assertSame('saved', $result);
    }

    public function testDisabledModuleOnlySavesWithoutLogging(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(false);
        $logger = $this->createMock(Logger::class);
        $logger->expects(self::never())->method('recordConfigChange');
        $proceeded = false;

        $result = (new ConfigSavePlugin($logger, $helper))->aroundSave(
            $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock(),
            static function () use (&$proceeded) { $proceeded = true; return 'saved'; }
        );

        self::assertTrue($proceeded);
        self::assertSame('saved', $result);
    }
}
