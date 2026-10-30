<?php
namespace Tonyapps\ActionLog\Test\Unit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession;

class AdminSessionTest extends TestCase
{
    public function testTouchAndTerminateMatchTheHashedSessionId(): void
    {
        $hash = hash('sha256', 'raw-session');
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects(self::exactly(2))->method('update')->with(
            'tonyapps_admin_session',
            self::anything(),
            self::callback(static fn (array $where) => $where['session_id = ?'] === $hash)
        );

        $resource = $this->resource($connection);
        $resource->touch('raw-session');
        $resource->terminateBySessionId('raw-session');
    }

    public function testIsTerminatedLooksUpTheHashedSessionId(): void
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->expects(self::once())->method('where')->with('session_id = ?', hash('sha256', 'raw-session'))->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn('terminated');

        self::assertTrue($this->resource($connection)->isTerminated('raw-session'));
    }

    private function resource(AdapterInterface $connection): AdminSession
    {
        $resource = $this->getMockBuilder(AdminSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getMainTable'])
            ->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('tonyapps_admin_session');
        return $resource;
    }
}
