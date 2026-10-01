<?php
namespace Tonyapps\ActionLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Tonyapps_ActionLog::log';

    private PageFactory $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $result = $this->resultPageFactory->create();
        $result->setActiveMenu('Tonyapps_ActionLog::log');
        $result->getConfig()->getTitle()->prepend(__('Action Log'));
        return $result;
    }
}
