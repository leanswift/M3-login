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
 *   @copyright   Copyright (c) 2019 LeanSwift Inc. (http://www.leanswift.com)
 *   @license     https://www.leanswift.com/end-user-licensing-agreement
 *  
 */

namespace LeanSwift\Login\Model\ResourceModel\Userrole;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * Initialize the object
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('LeanSwift\Login\Model\Userrole', 'LeanSwift\Login\Model\ResourceModel\Userrole');
    }
}
