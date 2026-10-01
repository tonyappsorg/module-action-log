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
        '/save$/i', '/delete$/i', '/massDelete$/i', '/massStatus$/i',
        '/massEnable$/i', '/massDisable$/i', '/upload$/i', '/remove$/i',
    ];

    private Helper $helper;
    private Logger $logger;
    private RequestInterface $request;

    public function __construct(Helper $helper, Logger $logger, RequestInterface $request)
    {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->request = $request;
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
