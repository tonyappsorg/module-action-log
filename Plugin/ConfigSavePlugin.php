<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Config\Model\Config;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;

// ponytail: only the "after" state is captured (posted groups); a true
// before-diff would need reading every path via scopeConfig first. Add if reviewers need the old values too.
class ConfigSavePlugin
{
    private Logger $logger;
    private Helper $helper;

    public function __construct(Logger $logger, Helper $helper)
    {
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function aroundSave(Config $subject, callable $proceed)
    {
        if (!$this->helper->isEnabled()) {
            return $proceed();
        }
        $result = $proceed();
        $this->logger->recordConfigChange((string) $subject->getSection(), (array) $subject->getGroups());
        return $result;
    }
}
