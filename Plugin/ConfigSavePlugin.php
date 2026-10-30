<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Config\Model\Config;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;

// ponytail: only the "after" state is captured (posted groups); a true
// before-diff would need reading every path via scopeConfig first. Add if reviewers need the old values too.
class ConfigSavePlugin
{
    public function __construct(private readonly Logger $logger, private readonly Helper $helper)
    {
    }

    public function aroundSave(Config $subject, callable $proceed)
    {
        if (!$this->helper->isEnabled()) {
            return $proceed();
        }
        $result = $proceed();
        $website = $subject->getWebsite();
        $store = $subject->getStore();
        $this->logger->recordConfigChange(
            (string) $subject->getSection(),
            (array) $subject->getGroups(),
            $website !== null && $website !== '' ? (string) $website : null,
            $store !== null && $store !== '' ? (string) $store : null
        );
        return $result;
    }
}
