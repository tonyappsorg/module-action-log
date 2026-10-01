<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\LoginLog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::loginlog';

    private PageFactory $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->setActiveMenu('Tonyapps_ActionLog::loginlog');
        $result->getConfig()->getTitle()->prepend(__('Login Log'));
        return $result;
    }
}
