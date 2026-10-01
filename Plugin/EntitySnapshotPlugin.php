<?php
namespace Tonyapps\ActionLog\Plugin;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Tonyapps\ActionLog\Helper\Data as Helper;
use Tonyapps\ActionLog\Model\Logger;

// Applies to every resource model's save/delete; short-circuits immediately
// unless the entity's model class is on the configured watch list, so the
// overhead on unwatched saves (the vast majority) is a single in_array check.
class EntitySnapshotPlugin
{
    private Helper $helper;
    private Logger $logger;

    public function __construct(Helper $helper, Logger $logger)
    {
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function aroundSave(AbstractDb $subject, callable $proceed, AbstractModel $object)
    {
        if (!$this->helper->isEnabled() || !$this->helper->isWatchedModel($object)) {
            return $proceed($object);
        }
        $before = $object->getOrigData();
        $isNew = empty($before);
        $result = $proceed($object);
        $this->logger->recordChange($object, $isNew ? null : $before, $object->getData(), $isNew ? 'create' : 'update');
        return $result;
    }

    public function aroundDelete(AbstractDb $subject, callable $proceed, AbstractModel $object)
    {
        if (!$this->helper->isEnabled() || !$this->helper->isWatchedModel($object)) {
            return $proceed($object);
        }
        $before = $object->getData();
        $result = $proceed($object);
        $this->logger->recordChange($object, $before, null, 'delete');
        return $result;
    }
}
