<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Framework\Model\AbstractModel;

class ActionLog extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\ActionLog::class);
    }
}
