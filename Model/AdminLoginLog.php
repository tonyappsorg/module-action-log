<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Framework\Model\AbstractModel;

class AdminLoginLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\AdminLoginLog::class);
    }
}
