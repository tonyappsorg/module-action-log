<?php
namespace Tonyapps\ActionLog\Cron;

use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminLoginLog as LoginLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class Cleanup
{
    private Helper $helper;
    private ActionLogResource $actionLogResource;
    private LoginLogResource $loginLogResource;
    private SessionResource $sessionResource;

    public function __construct(
        Helper $helper,
        ActionLogResource $actionLogResource,
        LoginLogResource $loginLogResource,
        SessionResource $sessionResource
    ) {
        $this->helper = $helper;
        $this->actionLogResource = $actionLogResource;
        $this->loginLogResource = $loginLogResource;
        $this->sessionResource = $sessionResource;
    }

    public function execute(): void
    {
        $days = $this->helper->getRetentionDays();
        if ($days <= 0) {
            return;
        }
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $connection = $this->actionLogResource->getConnection();
        $connection->delete($this->actionLogResource->getMainTable(), ['created_at < ?' => $date]);
        $connection->delete($this->loginLogResource->getMainTable(), ['created_at < ?' => $date]);
        // A session idle that long is long expired, whatever its status says.
        $connection->delete($this->sessionResource->getMainTable(), ['last_activity_at < ?' => $date]);
    }
}
