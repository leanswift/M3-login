<?php
/**
 *  LeanSwift Login Extension
 *
 *  DISCLAIMER
 *
 *   This extension is licensed and distributed by LeanSwift. Do not edit or add
 *   to this file if you wish to upgrade Extension and Connector to newer
 *   versions in the future. If you wish to customize Extension for your needs
 *   please contact LeanSwift for more information. You may not reverse engineer,
 *   decompile, or disassemble LeanSwift Login Extension (All Versions),
 *   except and only to the extent that such activity is expressly permitted by
 *    applicable law not withstanding this limitation.
 *
 * @copyright   Copyright (c) 2021 LeanSwift Inc. (http://www.leanswift.com)
 * @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Controller\Adminhtml\Ion;

use LeanSwift\EconnectBase\Helper\Erpapi;
use LeanSwift\Login\Helper\Constant;
use LeanSwift\Login\Helper\Data;
use LeanSwift\Login\Model\ResourceModel\Userrole;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

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
     * @var Erpapi|Data
     */
    protected $baseErpAPI;

    /**
     * Import constructor.
     * @param Context $context
     * @param Data $data
     * @param Userrole $userRole
     * @param Erpapi $baseErpAPI
     */
    public function __construct(
        Context $context,
        Data $data,
        Userrole $userRole,
        Erpapi $baseErpAPI
    ) {
        $this->helper = $data;
        $this->roleResource = $userRole;
        $this->baseErpAPI = $baseErpAPI;
        parent::__construct($context);
    }

    /**
     * Controller to test the ION connection
     *
     * \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $erpApiObject = $this->helper->erpapi();
        $roles = $erpApiObject->getRolesList();
        $rolesInfo = $erpApiObject->getRolesInfo();
        $flag = false;
        if ($roles) {
            $flag = $this->roleResource->updateRoles($roles);
        }
        if ($rolesInfo && $flag) {
            $flag = $this->roleResource->updateRoleInfo($rolesInfo);
        }
        $message = $this->baseErpAPI->getInitialLoadMessage(Constant::TYPE);
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($flag) {
            $this->roleResource->updateImportHistory();
            $this->messageManager->addSuccess($message);
        } else {
            $this->messageManager->addErrorMessage('User and Role import was failed!');
        }
        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
