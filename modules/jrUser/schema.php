<?php
/**
 * Jamroom 5 jrUser module
 *
 * copyright 2003 - 2013 by The Jamroom Network - All Rights Reserved
 * http://www.jamroom.net
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0.  Please see the included "license.html" file.
 *
 * This module may include works that are not developed by The Jamroom
 * Network and are used under license - any licenses are included and
 * can be found in the "contrib" directory within this module.
 *
 * Jamroom may use modules and skins that are licensed by third party
 * developers, and licensed under a different license than the Jamroom
 * Core - please reference the individual module or skin license that
 * is included with your download.
 *
 * This software is provided "as is" and any express or implied
 * warranties, including, but not limited to, the implied warranties
 * of merchantability and fitness for a particular purpose are
 * disclaimed.  In no event shall the Jamroom Network be liable for
 * any direct, indirect, incidental, special, exemplary or
 * consequential damages (including but not limited to, procurement
 * of substitute goods or services; loss of use, data or profits;
 * or business interruption) however caused and on any theory of
 * liability, whether in contract, strict liability, or tort
 * (including negligence or otherwise) arising from the use of this
 * software, even if advised of the possibility of such damage.
 * Some jurisdictions may not allow disclaimers of implied warranties
 * and certain statements in the above disclaimer may not apply to
 * you as regards implied warranties; the other terms and conditions
 * remain enforceable notwithstanding. In some jurisdictions it is
 * not permitted to limit liability and therefore such limitations
 * may not apply to you.
 *
 * @copyright 2012 Talldude Networks, LLC.
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * jrUser_db_schema 
 */
function jrUser_db_schema()
{
    // This module uses a Data Store - create it.  The Data store holds
    // all information (key value pairs) about user accounts.
    jrCore_db_create_datastore('jrUser','user');

    // User Session
    $_tmp = array(
        "session_id VARCHAR(128) NOT NULL PRIMARY KEY",
        "session_updated INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "session_user_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "session_user_name VARCHAR(64) NOT NULL DEFAULT ''",
        "session_user_group VARCHAR(32) NOT NULL DEFAULT ''",
        "session_profile_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "session_quota_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "session_user_ip VARCHAR(16) NOT NULL DEFAULT ''",
        "session_user_action VARCHAR(256) NOT NULL DEFAULT ''",
        "session_sync INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "session_data TEXT NOT NULL DEFAULT ''",
        "INDEX session_updated (session_updated)",
        "INDEX session_user_id (session_user_id)",
        "INDEX session_user_ip (session_user_ip)"
    );
    jrCore_db_verify_table('jrUser','session',$_tmp);

    // User Cookies
    $_tmp = array(
        "cookie_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "cookie_user_id INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "cookie_time INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "cookie_value VARCHAR(40) NOT NULL DEFAULT ''",
        "INDEX cookie_user_id (cookie_user_id)",
        "INDEX cookie_time (cookie_time)"
    );
    jrCore_db_verify_table('jrUser','cookie',$_tmp);

    // User Language
    $_tmp = array(
        "lang_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "lang_module VARCHAR(50) NOT NULL DEFAULT ''",
        "lang_code VARCHAR(12) NOT NULL DEFAULT 'en-US'",
        "lang_charset VARCHAR(12) NOT NULL DEFAULT 'utf-8'",
        "lang_ltr VARCHAR(12) NOT NULL DEFAULT 'ltr'",
        "lang_key VARCHAR(50) NOT NULL DEFAULT ''",
        "lang_text VARCHAR(1024) NOT NULL DEFAULT ''",
        "lang_default VARCHAR(1024) NOT NULL DEFAULT ''",
        "INDEX lang_module (lang_module)",
        "INDEX lang_code (lang_code)",
        "INDEX lang_key (lang_key)"
    );
    jrCore_db_verify_table('jrUser','language',$_tmp,'MyISAM');

    // User Forgot
    $_tmp = array(
        "forgot_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "forgot_user_id INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "forgot_time INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "forgot_key VARCHAR(32) NOT NULL DEFAULT ''",
        "INDEX forgot_user_id (forgot_user_id)",
        "INDEX forgot_time (forgot_time)"
    );
    jrCore_db_verify_table('jrUser','forgot',$_tmp);

    return true;
}
?>
