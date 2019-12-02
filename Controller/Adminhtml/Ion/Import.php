<?php
/**
 * LeanSwift eConnect Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the LeanSwift eConnect Extension License
 * that is bundled with this package in the file LICENSE.txt located in the
 * Connector Server.
 *
 * DISCLAIMER
 *
 * This extension is licensed and distributed by LeanSwift. Do not edit or add
 * to this file if you wish to upgrade Extension and Connector to newer
 * versions in the future. If you wish to customize Extension for your needs
 * please contact LeanSwift for more information. You may not reverse engineer,
 * decompile, or disassemble LeanSwift Connector Extension (All Versions),
 * except and only to the extent that such activity is expressly permitted by
 * applicable law not withstanding this limitation.
 *
 * @copyright   Copyright (c) 2019 LeanSwift Inc. (http://www.leanswift.com)
 * @license     https://www.leanswift.com/end-user-licensing-agreement
 */

namespace LeanSwift\Login\Controller\Adminhtml\Ion;

use LeanSwift\Login\Helper\Data;
use LeanSwift\Login\Model\ResourceModel\Userrole;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;

/**
 * Class Import
 *
 * @package LeanSwift\Login\Controller\Adminhtml\Ion
 */
class Import extends Action
{

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Userrole
     */
    protected $roleResource;

    /**
     * Import constructor.
     *
     * @param Context  $context
     * @param Registry $coreRegistry
     * @param Data     $erpapi
     * @param Userrole $userrole
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Data $erpapi,
        Userrole $userrole
    ) {
        $this->helper = $erpapi;
        $this->roleResource = $userrole;
        parent::__construct($context);
    }

    /**
     * Controller to test the ION connection
     *
     * \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $roles = $this->helper->erpapi()->getRolesList();
        $rolesInfo = $this->helper->erpapi()->getRolesInfo();
        $message = false;
        if ($roles) {
            $message = $this->roleResource->updateRoles($roles);
        }
        if ($rolesInfo && $message) {
            $message = $this->roleResource->updateRoleInfo($rolesInfo);
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($message == true) {
            $this->messageManager->addSuccess(__('User and Role import was successful!'));
        } else {
            $this->messageManager->addErrorMessage(__('User and Role import was failed!'));
        }
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
