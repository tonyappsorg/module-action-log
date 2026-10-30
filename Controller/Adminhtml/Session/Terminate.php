<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\Session;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Tonyapps\ActionLog\Model\ResourceModel\AdminSession as SessionResource;

class Terminate extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::session';

    public function __construct(Context $context, private readonly SessionResource $sessionResource)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $id = (int) $this->getRequest()->getParam('id');
        if ($id) {
            $this->sessionResource->terminateById($id);
            $this->messageManager->addSuccessMessage(__('The session was terminated.'));
        }
        return $resultRedirect->setPath('*/session/index');
    }
}
