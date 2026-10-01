<?php
namespace Tonyapps\ActionLog\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    private const XML_PATH_ENABLED = 'tonyapps_actionlog/general/enabled';
    private const XML_PATH_RETENTION_DAYS = 'tonyapps_actionlog/general/retention_days';
    private const XML_PATH_WATCHED_MODELS = 'tonyapps_actionlog/general/watched_models';

    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED);
    }

    public function getRetentionDays(): int
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_RETENTION_DAYS);
        return $value !== null && $value !== '' ? (int) $value : 90;
    }

    public function getWatchedModelClasses(): array
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH_WATCHED_MODELS);
        return $value ? explode(',', $value) : [];
    }

    public function isWatchedModel(object $object): bool
    {
        return in_array(get_class($object), $this->getWatchedModelClasses(), true);
    }
}
