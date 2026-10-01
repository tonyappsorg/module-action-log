<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Tonyapps\ActionLog\Model\ActionLogFactory;
use Tonyapps\ActionLog\Model\ResourceModel\ActionLog as ActionLogResource;
use Tonyapps\ActionLog\Model\Restorer;

class Restore extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::log';

    private ActionLogFactory $actionLogFactory;
    private ActionLogResource $actionLogResource;
    private Restorer $restorer;

    public function __construct(
        Context $context,
        ActionLogFactory $actionLogFactory,
        ActionLogResource $actionLogResource,
        Restorer $restorer
    ) {
        parent::__construct($context);
        $this->actionLogFactory = $actionLogFactory;
        $this->actionLogResource = $actionLogResource;
        $this->restorer = $restorer;
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
        try {
            $this->restorer->restore($log);
            $this->messageManager->addSuccessMessage(__('The entity was restored to its previous state.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('*/log/index');
    }
}
