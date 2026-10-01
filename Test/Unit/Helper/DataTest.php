<?php
namespace Tonyapps\ActionLog\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data;

class DummyWatchedEntity
{
}

class DataTest extends TestCase
{
    private function createHelper(array $configByPath): Data
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn ($path) => $configByPath[$path] ?? null
        );

        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        return new Data($context);
    }

    public function testWatchedModelClassesAreParsedFromCsvConfig(): void
    {
        $helper = $this->createHelper([
            'tonyapps_actionlog/general/watched_models' => 'Foo\Bar,Baz\Qux',
        ]);

        $this->assertSame(['Foo\Bar', 'Baz\Qux'], $helper->getWatchedModelClasses());
    }

    public function testWatchedModelClassesEmptyWhenUnconfigured(): void
    {
        $helper = $this->createHelper([]);

        $this->assertSame([], $helper->getWatchedModelClasses());
    }

    public function testIsWatchedModelMatchesConfiguredClass(): void
    {
        $helper = $this->createHelper([
            'tonyapps_actionlog/general/watched_models' => DummyWatchedEntity::class,
        ]);

        $this->assertTrue($helper->isWatchedModel(new DummyWatchedEntity()));
    }

    public function testIsWatchedModelFalseForUnlistedClass(): void
    {
        $helper = $this->createHelper([
            'tonyapps_actionlog/general/watched_models' => 'Some\Other\Class',
        ]);

        $this->assertFalse($helper->isWatchedModel(new DummyWatchedEntity()));
    }

    public function testRetentionDaysDefaultsTo90WhenUnset(): void
    {
        $helper = $this->createHelper([]);

        $this->assertSame(90, $helper->getRetentionDays());
    }

    public function testRetentionDaysUsesConfiguredValue(): void
    {
        $helper = $this->createHelper([
            'tonyapps_actionlog/general/retention_days' => '30',
        ]);

        $this->assertSame(30, $helper->getRetentionDays());
    }

    public function testIsEnabledReflectsConfigFlag(): void
    {
        $enabled = $this->createHelper(['tonyapps_actionlog/general/enabled' => '1']);
        $disabled = $this->createHelper(['tonyapps_actionlog/general/enabled' => '0']);

        $this->assertTrue($enabled->isEnabled());
        $this->assertFalse($disabled->isEnabled());
    }
}
