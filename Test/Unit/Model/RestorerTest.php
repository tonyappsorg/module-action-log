<?php
namespace Tonyapps\ActionLog\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\ActionLog;
use Tonyapps\ActionLog\Model\Restorer;

class RestorerTest extends TestCase
{
    public function testThrowsWhenSnapshotMissing(): void
    {
        $restorer = new Restorer(
            $this->createMock(ObjectManagerInterface::class),
            $this->createMock(Json::class)
        );

        $log = $this->log([]);

        $this->expectException(LocalizedException::class);
        $restorer->restore($log);
    }

    public function testThrowsWhenOriginalEntityNoLongerExists(): void
    {
        $json = $this->createMock(Json::class);
        $json->method('unserialize')->willReturn(['sku' => 'old-1']);

        $model = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'addData', 'save'])
            ->getMockForAbstractClass();
        $model->method('load')->willReturnSelf();
        $model->method('getId')->willReturn(null);
        $model->expects($this->never())->method('save');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturn($model);

        $restorer = new Restorer($objectManager, $json);

        $log = $this->log(['model_class' => 'Some\Model\Class', 'data_before' => '{"sku":"old-1"}', 'entity_id' => '1']);

        $this->expectException(LocalizedException::class);
        $restorer->restore($log);
    }

    public function testRestoresPriorDataAndSaves(): void
    {
        $json = $this->createMock(Json::class);
        $json->method('unserialize')->willReturn(['sku' => 'old-1']);

        $model = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'addData', 'save'])
            ->getMockForAbstractClass();
        $model->method('load')->willReturnSelf();
        $model->method('getId')->willReturn('1');
        $model->expects($this->once())->method('addData')->with(['sku' => 'old-1'])->willReturnSelf();
        $model->expects($this->once())->method('save');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturn($model);

        $restorer = new Restorer($objectManager, $json);

        $log = $this->log(['model_class' => 'Some\Model\Class', 'data_before' => '{"sku":"old-1"}', 'entity_id' => '1']);

        $restorer->restore($log);
    }

    public function testLoadsTheStoreViewTheSnapshotWasTakenIn(): void
    {
        $json = $this->createMock(Json::class);
        $json->method('unserialize')->willReturn(['name' => 'Old', 'store_id' => '2']);

        $model = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'getId', 'addData', 'save'])
            ->addMethods(['setStoreId'])
            ->getMockForAbstractClass();
        $calls = [];
        $model->expects($this->once())->method('setStoreId')->with(2)
            ->willReturnCallback(function () use (&$calls, $model) { $calls[] = 'setStoreId'; return $model; });
        $model->method('load')->willReturnCallback(function () use (&$calls, $model) { $calls[] = 'load'; return $model; });
        $model->method('getId')->willReturn('1');
        $model->expects($this->once())->method('save');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('create')->willReturn($model);

        (new Restorer($objectManager, $json))
            ->restore($this->log(['model_class' => 'Some\Model\Class', 'data_before' => '{}', 'entity_id' => '1']));

        $this->assertSame(['setStoreId', 'load'], $calls);
    }

    private function log(array $data): ActionLog
    {
        // getModelClass() etc. are magic DataObject getters, so set real data instead of mocking them
        $log = $this->getMockBuilder(ActionLog::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $log->setData($data);
        return $log;
    }
}
