<?php
/**
 * LeanSwift eConnect Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the LeanSwift eConnect Extension License
 * that is bundled with this package in the file LICENSE.txt located in the Connector Server.
 *
 * DISCLAIMER
 *
 * This extension is licensed and distributed by LeanSwift. Do not edit or add to this file
 * if you wish to upgrade Extension and Connector to newer versions in the future.
 * If you wish to customize Extension for your needs please contact LeanSwift for more
 * information. You may not reverse engineer, decompile,
 * or disassemble LeanSwift Connector Extension (All Versions), except and only to the extent that
 * such activity is expressly permitted by applicable law not withstanding this limitation.
 *
 * @copyright   Copyright (c) 2018 LeanSwift Inc. (http://www.leanswift.com)
 * @license     http://www.leanswift.com/license/connector-extension
 */

namespace LeanSwift\Login\Plugin;

use LeanSwift\Econnect\Helper\Erpapi;
use LeanSwift\Login\Helper\Constant;

/**
 * Class ErpapiPlugin
 * @package LeanSwift\Login\Plugin
 */
class ErpapiPlugin
{
    /**
     * Label Conversion Name
     *
     * @param Erpapi $subject
     * @param callable $proceed
     * @param $type
     * @return string
     */
    public function aroundTransactionTypeLabelConversion(Erpapi $subject, callable $proceed, $type)
    {
        if ($type == Constant::TYPE) {
            return Constant::TYPE;
        }
        return $proceed($type);
    }
}