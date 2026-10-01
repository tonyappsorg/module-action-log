<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Framework\Model\AbstractModel;

class AdminSession extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\AdminSession::class);
    }
}
