<?php
namespace Tonyapps\ActionLog\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class LogActions extends Column
{
    private UrlInterface $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (!empty($item['data_before']) && !empty($item['model_class'])) {
                $item[$this->getData('name')] = [
                    'restore' => [
                        'href' => $this->urlBuilder->getUrl('tonyapps_actionlog/log/restore', ['log_id' => $item['log_id']]),
                        'label' => __('Restore'),
                        'post' => true,
                        'confirm' => [
                            'title' => __('Restore entity'),
                            'message' => __('Are you sure you want to restore this entity to its previous state?'),
                        ],
                    ],
                ];
            }
        }
        return $dataSource;
    }
}
