<?php
namespace Tonyapps\ActionLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ActionLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tonyapps_action_log', 'log_id');
    }
}
