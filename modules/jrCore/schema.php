<?php
/**
 * Jamroom 5 jrCore module
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
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * jrCore_db_schema 
 */
function jrCore_db_schema()
{
    // Logs
    $_tmp = array(
        "log_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "log_created INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "log_priority VARCHAR(8) NOT NULL DEFAULT 'inf'",
        "log_ip VARCHAR(16) NOT NULL DEFAULT ''",
        "log_text VARCHAR(2048) NOT NULL DEFAULT ''",
        "INDEX log_priority (log_priority)"
    );
    jrCore_db_verify_table('jrCore','log',$_tmp,'MyISAM');  // Note: MyISAM for accurate row count!

    // Settings
    $_tmp = array(
        "`module` VARCHAR(64) NOT NULL DEFAULT ''",
        "`name` VARCHAR(64) NOT NULL DEFAULT ''",
        "`created` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`updated` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`value` VARCHAR(4096) NOT NULL DEFAULT ''",
        "`default` VARCHAR(4096) NOT NULL DEFAULT ''",
        "`type` VARCHAR(32) NOT NULL DEFAULT 'text'",
        "`validate` VARCHAR(32) NOT NULL DEFAULT ''",
        "`required` TINYINT(1) NOT NULL DEFAULT '0'",
        "`min` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`max` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`options` VARCHAR(8192) NOT NULL DEFAULT ''",
        "`user` VARCHAR(128) NOT NULL DEFAULT ''",
        "`label` VARCHAR(64) NOT NULL DEFAULT ''",
        "`sublabel` VARCHAR(128) NOT NULL DEFAULT ''",
        "`help` VARCHAR(4096) NOT NULL DEFAULT ''",
        "`section` VARCHAR(64) NOT NULL DEFAULT ''",
        "`order` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1'",
        "PRIMARY KEY (`module`,`name`)",
        "INDEX `order` (`order`)"
    );
    jrCore_db_verify_table('jrCore','setting',$_tmp);

    // Modules
    $_tmp = array(
        "module_id MEDIUMINT(7) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "module_created INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "module_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "module_priority TINYINT(3) UNSIGNED NOT NULL DEFAULT '100'",
        "module_directory VARCHAR(64) NOT NULL DEFAULT ''",
        "module_url VARCHAR(64) NOT NULL DEFAULT ''",
        "module_version VARCHAR(12) NOT NULL DEFAULT ''",
        "module_name VARCHAR(256) NOT NULL DEFAULT ''",
        "module_prefix VARCHAR(32) NOT NULL DEFAULT ''",
        "module_description VARCHAR(256) NOT NULL DEFAULT ''",
        "module_category VARCHAR(64) NOT NULL DEFAULT ''",
        "module_developer VARCHAR(128) NOT NULL DEFAULT ''",
        "module_active TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "module_locked TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "module_requires VARCHAR(512) NOT NULL DEFAULT ''",
        "INDEX module_priority (module_priority)",
        "INDEX module_directory (module_directory)",
        "INDEX module_url (module_url)"
    );
    jrCore_db_verify_table('jrCore','module',$_tmp);

    // Skins
    $_tmp = array(
        "skin_directory VARCHAR(64) NOT NULL DEFAULT ''",
        "skin_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "skin_custom_css MEDIUMTEXT NOT NULL",
        "skin_custom_image MEDIUMTEXT NOT NULL",
        "PRIMARY KEY (skin_directory)"
    );
    jrCore_db_verify_table('jrCore','skin',$_tmp);

    // Forms
    $_tmp = array(
        "`module` VARCHAR(64) NOT NULL DEFAULT ''",
        "`view` VARCHAR(64) NOT NULL DEFAULT ''",
        "`name` VARCHAR(64) NOT NULL DEFAULT ''",
        "`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "`locked` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "`created` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`updated` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`default` VARCHAR(4096) NOT NULL DEFAULT ''",
        "`type` VARCHAR(32) NOT NULL DEFAULT 'text'",
        "`validate` VARCHAR(32) NOT NULL DEFAULT ''",
        "`required` TINYINT(1) NOT NULL DEFAULT '0'",
        "`min` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`max` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`options` VARCHAR(8192) NOT NULL DEFAULT ''",
        "`user` VARCHAR(128) NOT NULL DEFAULT ''",
        "`group` VARCHAR(32) NOT NULL DEFAULT 'user'",
        "`label` VARCHAR(64) NOT NULL DEFAULT ''",
        "`sublabel` VARCHAR(128) NOT NULL DEFAULT ''",
        "`help` VARCHAR(4096) NOT NULL DEFAULT ''",
        "`section` VARCHAR(64) NOT NULL DEFAULT ''",
        "`order` TINYINT(3) UNSIGNED NOT NULL DEFAULT '1'",
        "PRIMARY KEY (`module`,`view`,`name`)",
        "INDEX `order` (`order`)"
    );
    jrCore_db_verify_table('jrCore','form',$_tmp);

    // Menu
    $_tmp = array(
        "menu_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "menu_module VARCHAR(64) NOT NULL DEFAULT 'jrCore'",
        "menu_unique VARCHAR(64) NOT NULL DEFAULT ''",
        "menu_active VARCHAR(3) NOT NULL DEFAULT 'off'",
        "menu_label VARCHAR(64) NOT NULL DEFAULT ''",
        "menu_category VARCHAR(64) NOT NULL DEFAULT 'user'",
        "menu_action VARCHAR(128) NOT NULL DEFAULT ''",
        "menu_groups VARCHAR(512) NOT NULL DEFAULT ''",
        "menu_order TINYINT(3) UNSIGNED NOT NULL DEFAULT '0'",
        "menu_function VARCHAR(128) NOT NULL DEFAULT ''",
        "menu_onclick VARCHAR(1024) NOT NULL DEFAULT ''",
        "UNIQUE menu_unique (menu_module,menu_action)",
        "INDEX menu_active (menu_active)",
        "INDEX menu_order (menu_order)"
    );
    jrCore_db_verify_table('jrCore','menu',$_tmp);

    // Cache
    $_tmp = array(
        "cache_key VARCHAR(32) NOT NULL PRIMARY KEY",
        "cache_expires INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "cache_module VARCHAR(64) NOT NULL DEFAULT ''",
        "cache_profile_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "cache_user_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "cache_item_id VARCHAR(8096) NOT NULL DEFAULT ''",
        "cache_encoded TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "cache_value MEDIUMTEXT NOT NULL DEFAULT ''",
        "INDEX cache_expires (cache_expires)",
        "INDEX cache_module (cache_module)",
        "INDEX cache_profile_id (cache_profile_id)"
    );
    jrCore_db_verify_table('jrCore','cache',$_tmp);

    // Temp
    $_tmp = array(
        "temp_module VARCHAR(50) NOT NULL DEFAULT 'jrCore'",
        "temp_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "temp_rand SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'",
        "temp_key VARCHAR(64) NOT NULL DEFAULT ''",
        "temp_tag VARCHAR(16) NOT NULL DEFAULT 'jrCore'",
        "temp_value TEXT NOT NULL DEFAULT ''",
        "INDEX temp_module (temp_module)",
        "INDEX temp_updated (temp_updated)",
        "INDEX temp_key (temp_key)",
        "INDEX temp_tag (temp_tag)"
    );
    jrCore_db_verify_table('jrCore','temp',$_tmp);

    // Template
    $_tmp = array(
        "template_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "template_module VARCHAR(50) NOT NULL DEFAULT 'jrCore'",
        "template_created INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "template_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "template_user VARCHAR(128) NOT NULL DEFAULT ''",
        "template_active TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "template_name VARCHAR(64) NOT NULL DEFAULT ''",
        "template_type VARCHAR(64) NOT NULL DEFAULT ''",
        "template_body TEXT NOT NULL",
        "UNIQUE template_unique (template_module, template_name)",
        "INDEX template_active (template_active)"
    );
    jrCore_db_verify_table('jrCore','template',$_tmp);

    // Form Sessions
    $_tmp = array(
        "form_token VARCHAR(32) NOT NULL PRIMARY KEY",
        "form_created INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "form_updated INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "form_rand INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "form_user_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "form_view VARCHAR(128) NOT NULL DEFAULT ''",
        "form_validated TINYINT(1) NOT NULL DEFAULT '0'",
        "form_params MEDIUMTEXT NOT NULL DEFAULT ''",
        "form_fields TEXT NOT NULL DEFAULT ''",
        "form_saved MEDIUMTEXT NOT NULL DEFAULT ''",
        "INDEX form_created (form_created)",
        "INDEX form_view (form_view)",
        "INDEX form_user_id (form_user_id)"
    );
    jrCore_db_verify_table('jrCore','form_session',$_tmp);

    // Counter
    $_tmp = array(
        "count_ip VARCHAR(16) NOT NULL DEFAULT ''",
        "count_uid INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "count_user_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "count_name VARCHAR(32) NOT NULL DEFAULT ''",
        "count_time INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "PRIMARY KEY (`count_ip`,`count_uid`,`count_name`)"
    );
    jrCore_db_verify_table('jrCore','count_ip',$_tmp);

    // Modal
    $_tmp = array(
        "modal_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "modal_updated INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "modal_key VARCHAR(32) NOT NULL DEFAULT ''",
        "modal_value VARCHAR(128) NOT NULL DEFAULT ''",
        "INDEX modal_key (modal_key)"
    );
    jrCore_db_verify_table('jrCore','modal',$_tmp);

    // Pending Item
    $_tmp = array(
        "pending_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "pending_created INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "pending_module VARCHAR(64) NOT NULL DEFAULT ''",
        "pending_item_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "pending_linked_item_module VARCHAR(64) NOT NULL DEFAULT ''",
        "pending_linked_item_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "pending_data VARCHAR(8192) NOT NULL DEFAULT ''",
        "UNIQUE pending_unique (pending_module,pending_item_id)"
    );
    jrCore_db_verify_table('jrCore','pending',$_tmp);

    // Pending Reason
    $_tmp = array(
        "reason_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "reason_key VARCHAR(32) NOT NULL DEFAULT ''",
        "reason_text VARCHAR(1024) NOT NULL DEFAULT ''",
        "UNIQUE reason_key (reason_key)"
    );
    jrCore_db_verify_table('jrCore','pending_reason',$_tmp);

    // Queue
    $_tmp = array(
        "queue_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY",
        "queue_name VARCHAR(64) NOT NULL DEFAULT ''",
        "queue_created INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "queue_module VARCHAR(64) NOT NULL DEFAULT ''",
        "queue_item_id INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "queue_data VARCHAR(16384) NOT NULL DEFAULT ''",
        "queue_worker VARCHAR(64) NOT NULL DEFAULT ''",
        "queue_started INT(10) UNSIGNED NOT NULL DEFAULT '0'",
        "queue_count TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'",
        "INDEX queue_name (queue_name)",
        "INDEX queue_worker (queue_worker)",
        "INDEX queue_started (queue_started)"
    );
    jrCore_db_verify_table('jrCore','queue',$_tmp,'MyISAM');

    return true;
}
?>
