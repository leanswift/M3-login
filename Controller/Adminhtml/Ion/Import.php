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

use LeanSwift\Login\Helper\Erpapi;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use LeanSwift\Login\Model\ResourceModel\Userrole;

/**
 * Class Import
 *
 * @package LeanSwift\Login\Controller\Adminhtml\Ion
 */
class Import extends Action
{

    /**
     * @var Erpapi
     */
    protected $_apiHelper;

    /**
     * @var Userrole
     */
    protected $roleResource;

    /**
     * TestConnection constructor.
     *
     * @param Context  $context
     * @param Registry $coreRegistry
     * @param Erpapi   $erpapi
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Erpapi $erpapi,
        Userrole $userrole
    ) {
        $this->_apiHelper = $erpapi;
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
        $roles = $this->_apiHelper->getRolesList();
        $rolesInfo = $this->_apiHelper->getRolesInfo();
        if ($roles) {
            $this->roleResource->updateRoles($roles);
        }
        if ($rolesInfo) {
            $this->roleResource->updateRoleInfo($rolesInfo);
        }
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
