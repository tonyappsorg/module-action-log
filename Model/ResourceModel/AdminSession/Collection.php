<?php
namespace Tonyapps\ActionLog\Model\ResourceModel\AdminSession;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(\Tonyapps\ActionLog\Model\AdminSession::class, \Tonyapps\ActionLog\Model\ResourceModel\AdminSession::class);
    }
}
