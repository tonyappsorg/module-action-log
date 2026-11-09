<?php
declare(strict_types=1);

namespace Tonyapps\ActionLog\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/** "Chrome 156 on macOS" instead of the raw User-Agent, which stays in the cell's tooltip. */
class UserAgent extends Column
{
    private const BROWSERS = [
        'Edge' => '#Edg(?:e|A|iOS)?/(\d+)#',
        'Opera' => '#OPR/(\d+)#',
        'Firefox' => '#(?:Firefox|FxiOS)/(\d+)#',
        'Chrome' => '#(?:Chrome|CriOS)/(\d+)#',
        'Safari' => '#Version/(\d+)[.\d]* (?:Mobile/\S+ )?Safari/#',
    ];

    private const PLATFORMS = [
        'iOS' => '#iPhone|iPad|iPod#',
        'Android' => '#Android#',
        'Windows' => '#Windows#',
        'macOS' => '#Macintosh|Mac OS X#',
        'Linux' => '#Linux|X11#',
    ];

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $agent = (string)($item[$name] ?? '');
            $dataSource['data']['items'][$i][$name] = $agent === ''
                ? '—'
                : sprintf(
                    '<span title="%s">%s</span>',
                    $this->escaper->escapeHtmlAttr($agent),
                    $this->escaper->escapeHtml(self::describe($agent))
                );
        }
        return $dataSource;
    }

    /** "Chrome 156 on macOS"; an unknown agent (an API client, curl) is shown shortened. */
    public static function describe(string $agent): string
    {
        $browser = null;
        foreach (self::BROWSERS as $label => $pattern) {
            if (preg_match($pattern, $agent, $match)) {
                $browser = $label . ' ' . $match[1];
                break;
            }
        }
        $platform = null;
        foreach (self::PLATFORMS as $label => $pattern) {
            if (preg_match($pattern, $agent)) {
                $platform = $label;
                break;
            }
        }
        if ($browser === null) {
            return mb_strimwidth($agent, 0, 40, '…');
        }
        return $platform === null ? $browser : (string)__('%1 on %2', $browser, $platform);
    }
}
