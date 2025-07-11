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

namespace LeanSwift\Login\Helper;

use LeanSwift\EconnectBase\Helper\Constant as BaseConstant;

/**
 * Class Constant
 *
 * @package LeanSwift\Login\Helper
 */
class Constant extends BaseConstant
{
    const XML_PATH_WEB_IFS_URL = 'leanswift_login/authentication/ifs_url';

    //const XML_PATH_WEB_SERVICE_URL = 'leanswift_login/authentication/service_url';

    const XML_PATH_WEB_SERVICE_URL = 'lsbase/authentication/token_url';

    const XML_PATH_ION_API_SERVICE_URL = 'lsbase/service_config/service_url';

    const XML_PATH_AUTHORIZE_URL = 'leanswift_login/authentication/authorize_url';

    const XML_PATH_WEB_SERVICE_CLIENTID = 'leanswift_login/authentication/web_service_clientid';

    const XML_PATH_WEB_SERVICE_CLIENTSECRET = 'leanswift_login/authentication/web_service_clientsecret';

    const XML_PATH_DOMAIN = 'leanswift_login/general/domain_name';

    const XML_PATH_ENABLE = 'leanswift_login/general/enable_login';

    const CLOUD_MINGLE_HOST = 'mingle-sso.inforcloudsuite.com';

    const TYPE = 'M3 User Roles';

    const Sync = 'Sync';
    const SyncLSUserRoles = 'SyncLSUserRoles';
    const LSUserRoles = 'LSUserRoles';
    const VariationID = 'variation_id';
    const BOD_ID = 'bodid';
    const BOD_Timestamp = 'bod_timestamp';

    const LOGGER_NAME = '/var/log/m3Login.log';

    const LOGGER_ENABLE_PATH = 'leanswift_login/general/log';
    const IFS_USER_DETAIL = '/usermgt/v2/users/me';
    const GET_USER_BY_EUID = '/MNS150MI/GetUserByEuid?EUID=';

    const USID = 'USID';
}
