<?php
declare(strict_types=1);

namespace Tonyapps\ActionLog\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;
use Tonyapps\ActionLog\Model\Source\LoginStatus as LoginStatusSource;
use Tonyapps\ActionLog\Ui\Component\Listing\Column\Actor;
use Tonyapps\ActionLog\Ui\Component\Listing\Column\LoginStatus;
use Tonyapps\ActionLog\Ui\Component\Listing\Column\UserAgent;

class GridCellsTest extends TestCase
{
    /** @dataProvider agents */
    public function testUserAgentIsDescribed(string $agent, string $expected): void
    {
        self::assertSame($expected, UserAgent::describe($agent));
    }

    public static function agents(): array
    {
        return [
            'Chrome on macOS' => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36', 'Chrome 156 on macOS'],
            'Edge on Windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'Edge 140 on Windows'],
            'Firefox on Linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0', 'Firefox 139 on Linux'],
            'Safari on iPhone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'Safari 18 on iOS'],
            'API client' => ['curl/8.7.1', 'curl/8.7.1'],
        ];
    }

    public function testUserAgentCellKeepsTheRawAgentInTheTooltip(): void
    {
        $column = new UserAgent($this->context(), $this->createMock(UiComponentFactory::class), $this->escaper(), [], ['name' => 'user_agent']);

        $result = $column->prepareDataSource(['data' => ['items' => [
            ['user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0'],
            ['user_agent' => ''],
        ]]]);

        self::assertSame(
            '<span title="Mozilla/5.0 (X11; Linux x86_64; rv:139.0) Gecko/20100101 Firefox/139.0">Firefox 139 on Linux</span>',
            $result['data']['items'][0]['user_agent']
        );
        self::assertSame('—', $result['data']['items'][1]['user_agent']);
    }

    public function testLoginStatusIsABadge(): void
    {
        $column = new LoginStatus($this->context(), $this->createMock(UiComponentFactory::class), [], ['name' => 'status']);

        $items = $column->prepareDataSource(['data' => ['items' => [['status' => 'success'], ['status' => 'failure']]]])['data']['items'];

        self::assertSame('<span class="grid-severity-notice"><span>Success</span></span>', $items[0]['status']);
        self::assertSame('<span class="grid-severity-critical"><span>Failed</span></span>', $items[1]['status']);
        self::assertSame(['success', 'failure'], array_column((new LoginStatusSource())->toOptionArray(), 'value'));
    }

    public function testChangesWithoutAnAdminSayTheyCameFromTheSystem(): void
    {
        $column = new Actor($this->context(), $this->createMock(UiComponentFactory::class), $this->escaper(), [], ['name' => 'username']);

        $items = $column->prepareDataSource(['data' => ['items' => [['username' => 'wk<b>'], ['username' => '']]]])['data']['items'];

        self::assertSame('wk&lt;b&gt;', $items[0]['username']);
        self::assertSame('<em>System (CLI / cron)</em>', $items[1]['username']);
    }

    private function escaper(): Escaper
    {
        $escaper = $this->createMock(Escaper::class);
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES);
        $escaper->method('escapeHtml')->willReturnCallback($escape);
        $escaper->method('escapeHtmlAttr')->willReturnCallback($escape);
        return $escaper;
    }

    private function context(): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createMock(Processor::class));
        return $context;
    }
}
