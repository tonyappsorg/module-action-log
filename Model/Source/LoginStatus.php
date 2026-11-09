<?php
declare(strict_types=1);

namespace Tonyapps\ActionLog\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/** Filter options of the Login Log status column (stored values: success, failure). */
class LoginStatus implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'success', 'label' => __('Success')],
            ['value' => 'failure', 'label' => __('Failed')],
        ];
    }
}
