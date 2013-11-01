<?php
/**
 * Jamroom 5 jrBanned module
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
 * jrBanned_meta
 */
function jrBanned_meta()
{
    $_tmp = array(
        'name'        => 'Banned Items',
        'url'         => 'banned',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;'. strftime('%Y'),
        'description' => 'Create, Update and Delete Banned names, words and IP addresses',
        'category'    => 'tools'
    );
    return $_tmp;
}

/**
 * jrBanned_init
 */
function jrBanned_init()
{
    // After the core has parsed the URL, we can check for a sitemap call
    jrCore_register_event_listener('jrCore','process_init','jrBanned_process_init_listener');

    // Tool to create, update and delete banned Items
    jrCore_register_module_feature('jrCore','tool_view','jrBanned','browse',array('Banned Items','Create, Update and Delete Banned names, words and other items'));

    return true;
}

//---------------------------------------------------------
// EVENT LISTENERS
//---------------------------------------------------------

/**
 * Check for banned IPs
 * @param array $_data incoming data array from jrCore_save_media_file()
 * @param array $_user current user info
 * @param array $_conf Global config
 * @param array $_args additional info about the module
 * @param string $event Event Trigger name
 * @return array
 */
function jrBanned_process_init_listener($_data,$_user,$_conf,$_args,$event)
{
    // Make sure this is NOT a banned IP
    if (jrBanned_is_banned('ip')) {
        header('HTTP/1.0 403 Forbidden');
        echo "You do not have permission to access this server";
        exit;
    }
    return $_data;
}

//---------------------------------------------------------
// FUNCTIONS
//---------------------------------------------------------

/**
 * Test if a given value for a type is a banned item
 *
 * @param string $type Type of Banned Item
 * @param string $value Value to check
 * @return bool
 */
function jrBanned_is_banned($type,$value = null)
{
    $_rt = jrCore_get_flag("jrbanned_is_banned_{$type}");
    if (!$_rt) {
        $tbl = jrCore_db_table_name('jrBanned','banned');
        $req = "SELECT ban_value FROM {$tbl} WHERE ban_type = '". jrCore_db_escape($type) ."'";
        $_rt = jrCore_db_query($req,'ban_value',false,'ban_value');
        jrCore_set_flag("jrbanned_is_banned_{$type}",$_rt);
    }
    if (!isset($_rt) || !is_array($_rt) || count($_rt) === 0) {
        // No items of this type
        return false;
    }
    switch ($type) {

        case 'ip':
            $ip = jrCore_get_ip();
            foreach ($_rt as $v) {
                if (strpos($ip,$v) === 0) {
                    return $v;
                }
            }
            break;

        case 'name':
        case 'word':
            foreach ($_rt as $v) {
                if (stripos(' '. $value,$v)) {
                    return $v;
                }
            }
            break;
    }
    return false;
}
