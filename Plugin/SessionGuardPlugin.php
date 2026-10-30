<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Backend\Model\Auth\Session;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

// Heartbeats the tracked session on every admin request and enforces a
// manual "Terminate" from the admin grid by killing the real session on
// its next request (prolong()/isLoggedIn() both fire per-request already).
class SessionGuardPlugin
{
    public function __construct(
        private readonly SessionResource $sessionResource,
        private readonly Helper $helper
    )
    {
    }

    public function afterProlong(Session $subject, $result)
    {
        if (!$this->helper->isEnabled()) {
            return $result;
        }
        $this->sessionResource->touch($subject->getSessionId());
        return $result;
    }

    public function afterIsLoggedIn(Session $subject, $result)
    {
        if (!$result || !$this->helper->isEnabled()) {
            return $result;
        }
        if ($this->sessionResource->isTerminated($subject->getSessionId())) {
            $subject->destroy();
            return false;
        }
        return $result;
    }
}
