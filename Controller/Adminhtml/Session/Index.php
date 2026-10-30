<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\Session;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::session';

    public function __construct(Context $context, private readonly PageFactory $resultPageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->setActiveMenu('Tonyapps_ActionLog::session');
        $result->getConfig()->getTitle()->prepend(__('Active Sessions'));
        return $result;
    }
}
