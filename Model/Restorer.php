<?php
namespace Tonyapps\ActionLog\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;

class Restorer
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly Json $json
    ) {
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
        // Redacted values (password hash, reset token) stay as they are now.
        $model->addData(array_filter($data, static fn ($value) => $value !== Logger::REDACTED));
        $model->save();
    }
}
