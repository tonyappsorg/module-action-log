<?php
namespace Tonyapps\ActionLog\Model\ResourceModel;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AdminSession extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tonyapps_admin_session', 'id');
    }

    /**
     * Only a SHA-256 of the session id is stored, so a leaked row can't be replayed as a session cookie.
     */
    public static function hashSessionId(string $sessionId): string
    {
        return hash('sha256', $sessionId);
    }

    public function touch(string $sessionId): void
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            ['last_activity_at' => new Expression('NOW()')],
            ['session_id = ?' => self::hashSessionId($sessionId), 'status = ?' => 'active']
        );
    }

    public function terminateBySessionId(string $sessionId): void
    {
        $this->getConnection()->update($this->getMainTable(), ['status' => 'terminated'], ['session_id = ?' => self::hashSessionId($sessionId)]);
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
            ->where('session_id = ?', self::hashSessionId($sessionId))
            ->order('id DESC')
            ->limit(1);
        return $connection->fetchOne($select) === 'terminated';
    }
}
