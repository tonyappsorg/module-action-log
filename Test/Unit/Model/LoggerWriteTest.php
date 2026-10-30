<?php
namespace Tonyapps\ActionLog\Test\Unit\Model;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Config\Model\Config\Structure;
use Magento\Config\Model\Config\TypePool;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\ActionLogFactory;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminLoginLog as LoginLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class LoggerWriteTest extends TestCase
{
    public function testRecordLoginInsertsTheLogRowDirectly(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects(self::once())->method('insert')->with(
            'tonyapps_admin_login_log',
            [
                'admin_user_id' => 12,
                'username' => 'admin',
                'status' => 'success',
                'ip_address' => '192.0.2.10',
                'user_agent' => 'test-agent',
            ]
        );
        $resource = $this->createMock(LoginLogResource::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getMainTable')->willReturn('tonyapps_admin_login_log');
        $request = $this->createMock(Http::class);
        $request->method('getServer')->willReturn('test-agent');
        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn('192.0.2.10');

        $logger = new Logger(
            $this->createMock(ActionLogFactory::class),
            $this->createMock(ActionLogResource::class),
            $resource,
            $this->createMock(SessionResource::class),
            $remoteAddress,
            $request,
            $this->createMock(AuthSession::class),
            $this->createMock(Json::class),
            $this->createMock(Structure::class),
            $this->createMock(TypePool::class)
        );

        $logger->recordLogin('admin', true, 12);
    }
}
