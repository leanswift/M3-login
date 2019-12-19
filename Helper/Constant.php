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

namespace LeanSwift\Login\Helper;

use LeanSwift\Econnect\Helper\Constant as EconnectConstant;

/**
 * Class Constant
 *
 * @package LeanSwift\Login\Helper
 */
class Constant extends EconnectConstant
{
    const XML_PATH_WEB_MINGLE_URL = 'leanswift_login/authentication/mingle_url';

    const XML_PATH_WEB_SERVICE_URL = 'leanswift_login/authentication/service_url';

    const XML_PATH_ION_URL = 'ion/general_config/service_url';

    const XML_PATH_WEB_SERVICE_CLIENTID = 'leanswift_login/authentication/web_service_clientid';

    const XML_PATH_WEB_SERVICE_CLIENTSECRET = 'leanswift_login/authentication/web_service_clientsecret';

    const XML_PATH_DOMAIN = 'leanswift_login/general/domain_name';

    const XML_PATH_ENABLE = 'leanswift_login/general/enable_login';

    const XML_PATH_VALIDATE_EMAIL = 'leanswift_login/general/validate_email';

    const CLOUD_MINGLE_HOST = 'mingle-sso.inforcloudsuite.com';

    const TYPE = 'M3 User Roles';
    const SyncLSUserRoles = 'SyncLSUserRoles';
    const LSUserRoles = 'LSUserRoles';
    const LOGGER_NAME = '/var/log/m3Login.log';
    const LOGGER_ENABLE_PATH = 'leanswift_login/general/log';
    const MINGLE_USER_DETAIL = '/api/v1/mingle/go/User/Detail';
    const GET_USER_BY_EUID = '/MNS150MI/GetUserByEuid?EUID=';
    const USID = 'USID';
}
