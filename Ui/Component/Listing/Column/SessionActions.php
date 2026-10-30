<?php
namespace Tonyapps\ActionLog\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SessionActions extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (($item['status'] ?? '') === 'active') {
                $item[$this->getData('name')] = [
                    'terminate' => [
                        'href' => $this->urlBuilder->getUrl('tonyapps_actionlog/session/terminate', ['id' => $item['id']]),
                        'label' => __('Terminate'),
                        'confirm' => [
                            'title' => __('Terminate session'),
                            'message' => __('Are you sure you want to terminate this admin session?'),
                        ],
                    ],
                ];
            }
        }
        return $dataSource;
    }
}
