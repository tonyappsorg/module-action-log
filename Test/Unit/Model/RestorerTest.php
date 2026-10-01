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

        $log = $this->createMock(ActionLog::class);
        $log->method('getModelClass')->willReturn(null);
        $log->method('getDataBefore')->willReturn(null);

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

        $log = $this->createMock(ActionLog::class);
        $log->method('getModelClass')->willReturn('Some\Model\Class');
        $log->method('getDataBefore')->willReturn('{"sku":"old-1"}');
        $log->method('getEntityId')->willReturn('1');

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

        $log = $this->createMock(ActionLog::class);
        $log->method('getModelClass')->willReturn('Some\Model\Class');
        $log->method('getDataBefore')->willReturn('{"sku":"old-1"}');
        $log->method('getEntityId')->willReturn('1');

        $restorer->restore($log);
    }
}
