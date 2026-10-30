<?php
namespace Tonyapps\ActionLog\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Controller\Adminhtml\Session\Terminate;
use Tonyapps\ActionLog\Ui\Component\Listing\Column\LogActions;
use Tonyapps\ActionLog\Ui\Component\Listing\Column\SessionActions;

class ActionsColumnTest extends TestCase
{
    public function testRestoreIsOfferedOnlyForRowsFlaggedWithSnapshot(): void
    {
        $result = $this->column(LogActions::class)->prepareDataSource(['data' => ['items' => [
            ['log_id' => 1, 'has_snapshot' => '1'],
            ['log_id' => 2, 'has_snapshot' => '0'],
        ]]]);

        self::assertTrue($result['data']['items'][0]['actions']['restore']['post']);
        self::assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }

    public function testTerminateIsPostOnly(): void
    {
        $result = $this->column(SessionActions::class)->prepareDataSource(['data' => ['items' => [
            ['id' => 5, 'status' => 'active'],
        ]]]);

        self::assertTrue($result['data']['items'][0]['actions']['terminate']['post']);
        self::assertTrue(is_subclass_of(Terminate::class, HttpPostActionInterface::class));
    }

    private function column(string $class): object
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createMock(Processor::class));
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->willReturn('https://example.test/action');
        return new $class($context, $this->createMock(UiComponentFactory::class), $url, [], ['name' => 'actions']);
    }
}
