<?php
namespace Tonyapps\ActionLog\Model\ResourceModel\ActionLog\Grid;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * The grid never shows the JSON snapshots (personal data, often several KB per row); Restore only needs to know one
 * exists. Deleted entities can't be restored, so they get no flag.
 */
class Collection extends SearchResult
{
    public const COLUMNS = [
        'log_id', 'admin_user_id', 'username', 'full_action_name', 'event_type', 'entity_type', 'entity_id',
        'model_class', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()], self::COLUMNS + [
            'has_snapshot' => new Expression(
                "main_table.data_before IS NOT NULL AND main_table.model_class IS NOT NULL"
                . " AND main_table.event_type <> 'delete'"
            ),
        ]);
        return $this;
    }
}
