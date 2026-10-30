<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Tonyapps\ActionLog\Model\ActionLogFactory;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\Restorer;

class Restore extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::restore';

    // Restoring writes the entity, so the admin also needs the permission to edit it directly.
    private const ENTITY_RESOURCES = [
        \Magento\Catalog\Model\Product::class => 'Magento_Catalog::products',
        \Magento\Catalog\Model\Category::class => 'Magento_Catalog::categories',
        \Magento\Cms\Model\Page::class => 'Magento_Cms::save',
        \Magento\Cms\Model\Block::class => 'Magento_Cms::block',
        \Magento\Customer\Model\Customer::class => 'Magento_Customer::manage',
    ];

    public function __construct(
        Context $context,
        private readonly ActionLogFactory $actionLogFactory,
        private readonly ActionLogResource $actionLogResource,
        private readonly Restorer $restorer
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $id = (int) $this->getRequest()->getParam('log_id');
        $log = $this->actionLogFactory->create();
        $this->actionLogResource->load($log, $id);
        if (!$log->getId()) {
            $this->messageManager->addErrorMessage(__('Log entry not found.'));
            return $resultRedirect->setPath('*/log/index');
        }
        // Entries logged before the \Interceptor suffix was stripped still carry it.
        $entityResource = self::ENTITY_RESOURCES[preg_replace('/\\\\Interceptor$/', '', (string) $log->getModelClass())] ?? null;
        if (!$entityResource || !$this->_authorization->isAllowed($entityResource)) {
            $this->messageManager->addErrorMessage(__('You are not allowed to change this entity.'));
            return $resultRedirect->setPath('*/log/index');
        }
        try {
            $this->restorer->restore($log);
            $this->messageManager->addSuccessMessage(__('The entity was restored to its previous state.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/log/index');
    }
}
