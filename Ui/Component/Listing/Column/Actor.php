<?php
declare(strict_types=1);

namespace Tonyapps\ActionLog\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/** Changes made without a signed-in admin (CLI, cron, setup scripts) say so instead of an empty cell. */
class Actor extends Column
{
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
            $username = (string)($item[$name] ?? '');
            $dataSource['data']['items'][$i][$name] = $username !== ''
                ? $this->escaper->escapeHtml($username)
                : '<em>' . $this->escaper->escapeHtml(__('System (CLI / cron)')) . '</em>';
        }
        return $dataSource;
    }
}
