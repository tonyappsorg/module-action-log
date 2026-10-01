<?php
namespace Tonyapps\ActionLog\Model\ResourceModel\ActionLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'log_id';

    protected function _construct()
    {
        $this->_init(\Tonyapps\ActionLog\Model\ActionLog::class, \Tonyapps\ActionLog\Model\ResourceModel\ActionLog::class);
    }
}
