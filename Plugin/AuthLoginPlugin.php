<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Backend\Model\Auth;
use Magento\Framework\Exception\AuthenticationException;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class AuthLoginPlugin
{
    private Logger $logger;
    private Helper $helper;
    private SessionResource $sessionResource;

    public function __construct(Logger $logger, Helper $helper, SessionResource $sessionResource)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->sessionResource = $sessionResource;
    }

    public function aroundLogin(Auth $subject, callable $proceed, $username, $password)
    {
        try {
            $result = $proceed($username, $password);
        } catch (AuthenticationException $e) {
            if ($this->helper->isEnabled()) {
                $this->logger->recordLogin($username, false);
            }
            throw $e;
        }
        if ($this->helper->isEnabled()) {
            $user = $subject->getAuthStorage()->getUser();
            $this->logger->recordLogin($username, true, $user ? (int) $user->getId() : null);
            if ($user) {
                $this->logger->openSession((int) $user->getId(), $username, $subject->getAuthStorage()->getSessionId());
            }
        }
        return $result;
    }

    public function aroundLogout(Auth $subject, callable $proceed)
    {
        $sessionId = $subject->getAuthStorage()->getSessionId();
        $result = $proceed();
        if ($sessionId) {
            $this->sessionResource->terminateBySessionId($sessionId);
        }
        return $result;
    }
}
