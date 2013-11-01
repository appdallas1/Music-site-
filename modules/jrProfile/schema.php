<?php
/**
 * Jamroom 5 jrProfile module
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
 * jrProfile_db_schema 
 */
function jrProfile_db_schema()
{
    // This module uses a Data Store - create it.  The Data store holds
    // all information (key value pairs) about profiles
    jrCore_db_create_datastore('jrProfile','profile');

    // Profile Quota
    $_tmp = array(
      "quota_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
      "quota_created INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "quota_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'"
    );
    jrCore_db_verify_table('jrProfile','quota',$_tmp);

    // Profile Quota Setting
    $_tmp = array(
      "`module` VARCHAR(64) NOT NULL DEFAULT ''",
      "`name` VARCHAR(64) NOT NULL DEFAULT ''",
      "`created` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "`default` VARCHAR(4096) NOT NULL DEFAULT ''",
      "`type` VARCHAR(32) NOT NULL DEFAULT 'text'",
      "`validate` VARCHAR(32) NOT NULL DEFAULT ''",
      "`required` TINYINT(1) NOT NULL DEFAULT '0'",
      "`min` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "`max` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "`options` VARCHAR(8192) NOT NULL DEFAULT ''",
      "`label` VARCHAR(64) NOT NULL DEFAULT ''",
      "`sublabel` VARCHAR(128) NOT NULL DEFAULT ''",
      "`help` VARCHAR(4096) NOT NULL DEFAULT ''",
      "`section` VARCHAR(64) NOT NULL DEFAULT ''",
      "`order` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1'",
      "PRIMARY KEY (`module`,`name`)",
      "INDEX `order` (`order`)"
    );
    jrCore_db_verify_table('jrProfile','quota_setting',$_tmp);

    // Profile Quota Value
    $_tmp = array(
      "`quota_id` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "`module` VARCHAR(64) NOT NULL DEFAULT ''",
      "`name` VARCHAR(64) NOT NULL DEFAULT ''",
      "`updated` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "`value` VARCHAR(8196) NOT NULL DEFAULT ''",
      "`user` VARCHAR(128) NOT NULL DEFAULT ''",
      "PRIMARY KEY (`quota_id`,`module`,`name`)"
    );
    jrCore_db_verify_table('jrProfile','quota_value',$_tmp);

    // Profile -> User ID Link
    $_tmp = array(
      "user_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "profile_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
      "PRIMARY KEY (user_id,profile_id)"
    );
    jrCore_db_verify_table('jrProfile','profile_link',$_tmp);

    return true;
}
?>
