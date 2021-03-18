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
 *   @copyright   Copyright (c) 2021 LeanSwift Inc. (http://www.leanswift.com)
 *   @license     https://www.leanswift.com/end-user-licensing-agreement
 *
 */

namespace LeanSwift\Login\Helper;

/**
 * Class Xpath
 * @package LeanSwift\Login\Helper
 */
class Xpath
{
    const UserRoles_VariationId = "LSUserRolesHeader/DocumentID/ID/@variationID";
    const BOD_ID_PATH = 'ApplicationArea/BODID';
    const CREATION_DATE_TIME = 'ApplicationArea/CreationDateTime';
    const ACTION_PATH = 'ActionCriteria/ActionExpression/_attribute/actionCode';
    const DATA_AREA_PATH = '_value/DataArea';
}
