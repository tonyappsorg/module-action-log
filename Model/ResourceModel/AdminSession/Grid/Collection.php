<?php
namespace Tonyapps\ActionLog\Model\ResourceModel\AdminSession\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Leaves out session_id: even hashed, the grid has no use for it.
 */
class Collection extends SearchResult
{
    public const COLUMNS = [
        'id', 'admin_user_id', 'username', 'ip_address', 'user_agent', 'login_at', 'last_activity_at', 'status',
    ];

    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()], self::COLUMNS);
        return $this;
    }
}
