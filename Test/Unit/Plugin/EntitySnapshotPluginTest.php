<?php
namespace Tonyapps\ActionLog\Test\Unit\Plugin;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Plugin\EntitySnapshotPlugin;

class EntitySnapshotPluginTest extends TestCase
{
    public function testUnwatchedModelSkipsRecordingButStillSaves(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isWatchedModel')->willReturn(false);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('recordChange');

        $plugin = new EntitySnapshotPlugin($helper, $logger);
        $subject = $this->createMock(AbstractDb::class);
        $object = $this->createMock(AbstractModel::class);

        $result = $plugin->aroundSave($subject, static fn ($obj) => 'saved', $object);

        $this->assertSame('saved', $result);
    }

    public function testDisabledModuleSkipsRecordingEvenIfWatched(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(false);
        $helper->method('isWatchedModel')->willReturn(true);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->never())->method('recordChange');

        $plugin = new EntitySnapshotPlugin($helper, $logger);
        $subject = $this->createMock(AbstractDb::class);
        $object = $this->createMock(AbstractModel::class);

        $plugin->aroundSave($subject, static fn ($obj) => $obj, $object);
    }

    public function testNewEntityRecordsCreateWithNullBefore(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isWatchedModel')->willReturn(true);

        $object = $this->createMock(AbstractModel::class);
        $object->method('getOrigData')->willReturn([]);
        $object->method('getData')->willReturn(['sku' => 'new-1']);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('recordChange')
            ->with($object, null, ['sku' => 'new-1'], 'create');

        $plugin = new EntitySnapshotPlugin($helper, $logger);
        $subject = $this->createMock(AbstractDb::class);

        $plugin->aroundSave($subject, static fn ($obj) => $obj, $object);
    }

    public function testExistingEntityRecordsUpdateWithPriorState(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isWatchedModel')->willReturn(true);

        $object = $this->createMock(AbstractModel::class);
        $object->method('getOrigData')->willReturn(['sku' => 'old-1']);
        $object->method('getData')->willReturn(['sku' => 'new-1']);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('recordChange')
            ->with($object, ['sku' => 'old-1'], ['sku' => 'new-1'], 'update');

        $plugin = new EntitySnapshotPlugin($helper, $logger);
        $subject = $this->createMock(AbstractDb::class);

        $plugin->aroundSave($subject, static fn ($obj) => $obj, $object);
    }

    public function testDeleteRecordsPriorStateWithNullAfter(): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isWatchedModel')->willReturn(true);

        $object = $this->createMock(AbstractModel::class);
        $object->method('getData')->willReturn(['sku' => 'gone-1']);

        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())
            ->method('recordChange')
            ->with($object, ['sku' => 'gone-1'], null, 'delete');

        $plugin = new EntitySnapshotPlugin($helper, $logger);
        $subject = $this->createMock(AbstractDb::class);

        $plugin->aroundDelete($subject, static fn ($obj) => $obj, $object);
    }
}
