<?php
namespace Tonyapps\ActionLog\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Observer\AdminActionLogger;

class AdminActionLoggerTest extends TestCase
{
    /**
     * @dataProvider actions
     */
    public function testWriteActionsAreLogged(string $fullActionName, bool $logged): void
    {
        $helper = $this->createMock(Helper::class);
        $helper->method('isEnabled')->willReturn(true);
        $request = $this->getMockBuilder(RequestInterface::class)->addMethods(['getFullActionName'])->getMockForAbstractClass();
        $request->method('getFullActionName')->willReturn($fullActionName);
        $logger = $this->createMock(Logger::class);
        $logger->expects($logged ? self::once() : self::never())->method('recordAction')->with($fullActionName);

        (new AdminActionLogger($helper, $logger, $request))->execute(new Observer());
    }

    public static function actions(): array
    {
        return [
            ['catalog_product_save', true],
            ['adminhtml_user_role_saverole', true],
            ['loginascustomer_login_login', true],
            ['sales_order_cancel', true],
            ['sales_order_hold', true],
            ['sales_order_unhold', true],
            ['sales_order_massCancel', true],
            ['sales_order_view', false],
            ['catalog_product_index', false],
        ];
    }
}
