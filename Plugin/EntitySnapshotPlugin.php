<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;

// Applies to every resource model's save/delete (flat Db\AbstractDb and EAV
// AbstractEntity, see di.xml); short-circuits immediately
// unless the entity's model class is on the configured watch list, so the
// overhead on unwatched saves (the vast majority) is a single in_array check.
class EntitySnapshotPlugin
{
    public function __construct(
        private readonly Helper $helper,
        private readonly Logger $logger,
        private readonly State $appState
    ) {
    }

    public function aroundSave(object $subject, callable $proceed, $object)
    {
        if (!$this->shouldRecord($object)) {
            return $proceed($object);
        }
        $before = $object->getOrigData();
        $isNew = empty($before) || $object->isObjectNew();
        // EAV values are per store view; keep the scope the change was made in so Restore writes the same one.
        if (!$isNew && !array_key_exists('store_id', $before) && $object->hasData('store_id')) {
            $before['store_id'] = $object->getData('store_id');
        }
        $result = $proceed($object);
        $this->logger->recordChange($object, $isNew ? null : $before, $object->getData(), $isNew ? 'create' : 'update');
        return $result;
    }

    public function aroundDelete(object $subject, callable $proceed, $object)
    {
        if (!$this->shouldRecord($object)) {
            return $proceed($object);
        }
        $before = $object->getData();
        $result = $proceed($object);
        $this->logger->recordChange($object, $before, null, 'delete');
        return $result;
    }

    /**
     * Only admin edits are snapshotted: storefront, cron and API saves (e.g. customers updating their account) would
     * fill the table with personal data nobody restores.
     */
    private function shouldRecord($object): bool
    {
        if (!$object instanceof AbstractModel || !$this->helper->isEnabled() || !$this->helper->isWatchedModel($object)) {
            return false;
        }
        try {
            return $this->appState->getAreaCode() === Area::AREA_ADMINHTML;
        } catch (LocalizedException) {
            return false;
        }
    }
}
