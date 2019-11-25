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

namespace LeanSwift\Login\Block\Backend\Roles\Ion;

use LeanSwift\Econnect\Block\Backend\Mapping\AbstractInitial;

/**
 * Class Initial
 * @package LeanSwift\Econnect\Block\Backend\Mapping\Invoice\Ion
 */
class Initial extends AbstractInitial
{
    protected $ionConfigURL = "login";

    protected $type = "roles";

    /**
     * @return string
     */
    public function getMessage()
    {
        $message = 'This is a system resource intensive process.';
        $message .= ' Are you sure you want to initiate importing invoice data from M3?';
        return $message;
    }
}
