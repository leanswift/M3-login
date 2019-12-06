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

use LeanSwift\Econnect\Helper\Erpapi;
use LeanSwift\Login\Helper\Constant;
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
     * @var Erpapi|Data
     */
    protected $econnectErpAPI;

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
        Data $data,
        Userrole $userrole,
        Erpapi $erpapi
    ) {
        $this->helper = $data;
        $this->roleResource = $userrole;
        $this->econnectErpAPI = $erpapi;
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
        $message = $this->econnectErpAPI->getInitialLoadMessage(Constant::TYPE);
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
