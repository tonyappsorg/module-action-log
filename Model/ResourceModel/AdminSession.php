<?php
namespace Tonyapps\ActionLog\Model\ResourceModel;

use Magento\Framework\DB\Expr;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AdminSession extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tonyapps_admin_session', 'id');
    }

    public function touch(string $sessionId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['last_activity_at' => new Expr('NOW()')],
            ['session_id = ?' => $sessionId, 'status = ?' => 'active']
        );
    }

    public function terminateBySessionId(string $sessionId): void
    {
        $this->getConnection()->update($this->getMainTable(), ['status' => 'terminated'], ['session_id = ?' => $sessionId]);
    }

    public function terminateById(int $id): void
    {
        $this->getConnection()->update($this->getMainTable(), ['status' => 'terminated'], ['id = ?' => $id]);
    }

    public function isTerminated(string $sessionId): bool
    {
        if (!$sessionId) {
            return false;
        }
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'status')
            ->where('session_id = ?', $sessionId)
            ->order('id DESC')
            ->limit(1);
        return $connection->fetchOne($select) === 'terminated';
    }
}
