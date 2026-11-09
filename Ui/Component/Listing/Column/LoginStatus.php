<?php
declare(strict_types=1);

namespace Tonyapps\ActionLog\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/** Login result as a grid badge: green for success, red for a failed attempt. */
class LoginStatus extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] ?? [] as $i => $item) {
            $success = ($item[$name] ?? '') === 'success';
            $dataSource['data']['items'][$i][$name] = sprintf(
                '<span class="%s"><span>%s</span></span>',
                $success ? 'grid-severity-notice' : 'grid-severity-critical',
                __($success ? 'Success' : 'Failed')
            );
        }
        return $dataSource;
    }
}
