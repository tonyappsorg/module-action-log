<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Backend\Model\Auth\Session;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

// Heartbeats the tracked session on every admin request and enforces a
// manual "Terminate" from the admin grid by killing the real session on
// its next request (prolong()/isLoggedIn() both fire per-request already).
class SessionGuardPlugin
{
    private SessionResource $sessionResource;

    public function __construct(SessionResource $sessionResource)
    {
        $this->sessionResource = $sessionResource;
    }

    public function afterProlong(Session $subject, $result)
    {
        $this->sessionResource->touch($subject->getSessionId());
        return $result;
    }

    public function afterIsLoggedIn(Session $subject, $result)
    {
        if (!$result) {
            return $result;
        }
        if ($this->sessionResource->isTerminated($subject->getSessionId())) {
            $subject->destroy();
            return false;
        }
        return $result;
    }
}
