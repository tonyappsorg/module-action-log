<?php
namespace Tonyapps\ActionLog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AdminLoginLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tonyapps_admin_login_log', 'id');
    }
}
