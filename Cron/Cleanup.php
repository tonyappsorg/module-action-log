<?php
namespace Tonyapps\ActionLog\Cron;

use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminLoginLog as LoginLogResource;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class Cleanup
{
    public function __construct(
        private readonly Helper $helper,
        private readonly ActionLogResource $actionLogResource,
        private readonly LoginLogResource $loginLogResource,
        private readonly SessionResource $sessionResource
    ) {
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
