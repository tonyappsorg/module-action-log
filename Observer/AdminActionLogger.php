<?php
namespace Tonyapps\ActionLog\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;

// ponytail: matches write-ish actions by name pattern instead of logging every GET; add real ACL-based classification if this misses a controller.
class AdminActionLogger implements ObserverInterface
{
    private const ACTION_PATTERNS = [
        '/(save|delete|massStatus|massEnable|massDisable|upload|remove)$/i',
    ];

    public function __construct(
        private readonly Helper $helper,
        private readonly Logger $logger,
        private readonly RequestInterface $request
    ) {
    }

    public function execute(EventObserver $observer)
    {
        if (!$this->helper->isEnabled()) {
            return;
        }
        $fullAction = $this->request->getFullActionName();
        if (strpos($fullAction, 'tonyapps_actionlog') === 0) {
            return;
        }
        foreach (self::ACTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $fullAction)) {
                $this->logger->recordAction($fullAction);
                return;
            }
        }
    }
}
