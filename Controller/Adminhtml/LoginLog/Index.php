<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\LoginLog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::loginlog';

    public function __construct(Context $context, private readonly PageFactory $resultPageFactory)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->setActiveMenu('Tonyapps_ActionLog::loginlog');
        $result->getConfig()->getTitle()->prepend(__('Login Log'));
        return $result;
    }
}
