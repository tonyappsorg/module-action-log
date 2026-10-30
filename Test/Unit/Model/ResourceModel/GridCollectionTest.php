<?php
namespace Tonyapps\ActionLog\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog\Grid\Collection as LogGrid;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession\Grid\Collection as SessionGrid;

class GridCollectionTest extends TestCase
{
    public function testLogGridSelectsSnapshotFlagInsteadOfSnapshots(): void
    {
        $columns = $this->initSelect(LogGrid::class, 'tonyapps_action_log');

        self::assertArrayHasKey('has_snapshot', $columns);
        self::assertStringContainsString("event_type <> 'delete'", (string) $columns['has_snapshot']);
        self::assertNotContains('data_before', $columns);
        self::assertNotContains('data_after', $columns);
        self::assertContains('log_id', $columns);
    }

    public function testSessionGridDoesNotSelectSessionId(): void
    {
        $columns = $this->initSelect(SessionGrid::class, 'tonyapps_admin_session');

        self::assertNotContains('session_id', $columns);
        self::assertContains('status', $columns);
    }

    private function initSelect(string $class, string $table): array
    {
        $captured = null;
        $select = $this->createMock(Select::class);
        $select->expects(self::once())->method('from')
            ->with(['main_table' => $table], self::anything())
            ->willReturnCallback(function ($name, $cols) use (&$captured, $select) {
                $captured = $cols;
                return $select;
            });
        $collection = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSelect', 'getMainTable'])
            ->getMock();
        $collection->method('getSelect')->willReturn($select);
        $collection->method('getMainTable')->willReturn($table);

        (new \ReflectionMethod($class, '_initSelect'))->invoke($collection);
        return $captured;
    }
}
