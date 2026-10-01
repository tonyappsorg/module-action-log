<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;

class Restorer
{
    private ObjectManagerInterface $objectManager;
    private Json $json;

    public function __construct(ObjectManagerInterface $objectManager, Json $json)
    {
        $this->objectManager = $objectManager;
        $this->json = $json;
    }

    // ponytail: generic load+addData+save restore; entities with multi-table/EAV side effects (e.g. product images, tier prices) may not fully roll back. Good enough for simple fields, revisit per-entity if needed.
    public function restore(ActionLog $log): void
    {
        if (!$log->getModelClass() || !$log->getDataBefore()) {
            throw new LocalizedException(__('This entry has no snapshot to restore.'));
        }
        $data = $this->json->unserialize($log->getDataBefore());
        $model = $this->objectManager->create($log->getModelClass());
        $model->load($log->getEntityId());
        if (!$model->getId()) {
            throw new LocalizedException(__('The original entity no longer exists and cannot be restored.'));
        }
        $model->addData($data);
        $model->save();
    }
}
