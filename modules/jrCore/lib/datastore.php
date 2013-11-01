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
 * @package DataStore
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * An array of modules that have a datastore enabled
 */
function jrCore_get_datastore_modules()
{
    global $_mods;
    $_out = array();
    foreach ($_mods as $module => $_inf) {
        if (isset($_inf['module_prefix']) && strlen($_inf['module_prefix']) > 0) {
            $_out[$module] = $_inf['module_prefix'];
        }
    }
    return $_out;
}

/**
 * Returns DataStore Prefix for a module
 * @param string $module Module to return prefix for
 * @return mixed
 */
function jrCore_db_get_prefix($module)
{
    global $_mods;
    return (isset($_mods[$module]['module_prefix']) && strlen($_mods[$module]['module_prefix']) > 0) ? $_mods[$module]['module_prefix'] : false;
}

/**
 * Creates a new module DataStore
 * @param string $module Module to create DataStore for
 * @param string $prefix Key Prefix in DataStore
 * @return bool
 */
function jrCore_db_create_datastore($module,$prefix)
{
    // Items
    $_tmp = array(
        "`_item_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY"
    );
    jrCore_db_verify_table($module,'item',$_tmp,'MyISAM');

    // Item
    $_tmp = array(
        "`_item_id` INT(11) UNSIGNED NOT NULL DEFAULT '0'",
        "`key` VARCHAR(128) NOT NULL DEFAULT ''",
        "`index` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0'",
        "`value` VARCHAR(512) NOT NULL DEFAULT ''",
        "PRIMARY KEY (`key`,`_item_id`,`index`)",
        "INDEX `_item_id` (`_item_id`)",
        "INDEX `index` (`index`)",
        "INDEX `value` (`value`(64))",
    );
    jrCore_db_verify_table($module,'item_key',$_tmp,'InnoDB');

    // Make sure our DataStore prefix is stored with the module info
    $efx = jrCore_db_get_prefix($module);
    if (!$efx || $efx != $prefix) {
        $tbl = jrCore_db_table_name('jrCore','module');
        $req = "UPDATE {$tbl} SET
                  module_prefix = '". jrCore_db_escape($prefix) ."'
                 WHERE module_directory = '". jrCore_db_escape($module) ."'
                 LIMIT 1";
        jrCore_db_query($req,'COUNT');
        $mods[$module]['module_prefix'] = $prefix;
    }
    // Let modules know we are creating/validating a DataStore
    $_args = array(
        'module' => $module,
        'prefix' => $prefix
    );
    $_data = array();
    jrCore_trigger_event('jrCore','db_create_datastore',$_data,$_args);
    return true;
}

/**
 * Increment a DataStore key for a an Item ID by a value
 * @param $module string Module Name
 * @param $id integer Unique Item ID
 * @param $key string Key to increment
 * @param $value number Integer/Float to increment by
 * @return bool
 */
function jrCore_db_increment_key($module,$id,$key,$value)
{
    if (!is_numeric($value)) {
        return false;
    }
    $uid = intval($id);
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "INSERT INTO {$tbl} (`_item_id`,`key`,`index`,`value`) VALUES ('{$uid}','". jrCore_db_escape($key) ."',0,'{$value}') ON DUPLICATE KEY UPDATE `value` = (`value` + {$value})";
    $cnt = jrCore_db_query($req,'COUNT');
    if (!isset($cnt) || $cnt < 1) {
        return false;
    }
    return true;
}

/**
 * Decrement a DataStore key for a an Item ID by a value
 * @param $module string Module Name
 * @param $id integer Unique Item ID
 * @param $key string Key to decrement
 * @param $value number Integer/Float to decrement by
 * @param $min_value number Lowest Value allowed for Key (default 0)
 * @return bool
 */
function jrCore_db_decrement_key($module,$id,$key,$value,$min_value = null)
{
    if (!is_numeric($value)) {
        return false;
    }
    if (is_null($min_value) || !isset($min_value) || !is_numeric($min_value)) {
        $min_value = 0;
    }
    $uid = intval($id);
    $tbl = jrCore_db_table_name($module,'item_key');
    $val = ($min_value + $value);
    $req = "UPDATE {$tbl} SET `value` = (`value` - {$value}) WHERE `_item_id` = '{$uid}' AND `key` = '". jrCore_db_escape($key) ."' AND `value` >= {$val}";
    $cnt = jrCore_db_query($req,'COUNT');
    if (!isset($cnt) || $cnt < 1) {
        return false;
    }
    return true;
}

/**
 * Return an array of _item_id's that do NOT have a specified key set
 * @param $module string Module DataStore to search through
 * @param $key string Key Name that should not be set
 * @return array|bool
 */
function jrCore_db_get_items_missing_key($module,$key)
{
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `_item_id` NOT IN(SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = '". jrCore_db_escape($key) ."') GROUP BY `_item_id`";
    $_rt = jrCore_db_query($req,'_item_id');
    if (isset($_rt) && is_array($_rt)) {
        return array_keys($_rt);
    }
    return false;
}

/**
 * Deletes a single key from an item
 * @param string $module Module the DataStore belongs to
 * @param int $id Item ID
 * @param string $key Key to delete
 * @return mixed INSERT_ID on success, false on error
 */
function jrCore_db_delete_item_key($module,$id,$key)
{
    // Some things we cannot remove
    if (strpos($key,'_') === 0) {
        // internally used - cannot remove
        return false;
    }
    // Delete key
    $uid = intval($id);
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$uid}' AND `key` = '". jrCore_db_escape($key) ."' LIMIT 1";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt === 1) {
        // We need to reset the cache for this item
        jrCore_delete_cache($module,"{$module}-{$id}",false);
        return true;
    }
    return false;
}

/**
 * Validates DataStore key names are allowed and correct
 * @param string $module Module the DataStore belongs to
 * @param array $_data Array of Key => Value pairs to check
 * @return mixed true on success, exits on error
 */
function jrCore_db_get_allowed_item_keys($module,$_data)
{
    if (!isset($_data) || !is_array($_data)) {
        return false;
    }
    $pfx = jrCore_db_get_prefix($module);
    $_rt = array();
    foreach ($_data as $k => $v) {
        if (strpos($k,'_') === 0) {
            jrCore_notice_page('CRI',"invalid key name: {$k} - key names cannot start with an underscore");
        }
        elseif (strpos($k,$pfx) !== 0) {
            jrCore_notice_page('CRI',"invalid key name: {$k} - key name must begin with module prefix: {$pfx}_");
        }
        $_rt[$k] = $v;
    }
    return $_rt;
}

/**
 * Creates a new item in a module datastore
 * @param string $module Module the DataStore belongs to
 * @param array $_data Array of Key => Value pairs for insertion
 * @param array $_core Array of Key => Value pairs for insertion - skips jrCore_db_get_allowed_item_keys()
 * @param bool $profile_count If set to true, profile_count will be incremented for given _profile_id
 * @return mixed INSERT_ID on success, false on error
 */
function jrCore_db_create_item($module,$_data,$_core = null,$profile_count = true)
{
    global $_user;

    // Validate incoming data
    $_data = jrCore_db_get_allowed_item_keys($module,$_data);

    // Check for additional core fields being added in
    if (isset($_core) && is_array($_core)) {
        foreach ($_core as $k => $v) {
            if (strpos($k,'_') === 0) {
                $_data[$k] = $_core[$k];
            }
        }
        unset($_core);
    }

    // Internal defaults
    $now = time();
    $_check = array(
        '_created'    => $now,
        '_updated'    => $now,
        '_profile_id' => 0,
        '_user_id'    => 0
    );
    // If user is logged in, defaults to their account
    if (jrUser_is_logged_in()) {
        $_check['_profile_id'] = (int) $_user['user_active_profile_id'];
        $_check['_user_id']    = (int) $_user['_user_id'];
    }
    foreach ($_check as $k => $v) {
        if (!isset($_data[$k])) {
            $_data[$k] = $_check[$k];
        }
    }

    // See if we are limiting the number of items that can be created by a profile in this quota
    if (isset($_user["quota_{$module}_max_items"]) && $_user["quota_{$module}_max_items"] > 0 && isset($_user["profile_{$module}_item_count"]) && $_user["profile_{$module}_item_count"] >= $_user["quota_{$module}_max_items"]) {
        // We've hit the limit for this quota
        return false;
    }

    // Get our unique item id
    $tbl = jrCore_db_table_name($module,'item');
    $req = "INSERT INTO {$tbl} (`_item_id`) VALUES ('null')";
    $iid = jrCore_db_query($req,'INSERT_ID');
    if (isset($iid) && $iid > 0) {

        // Our module prefix
        $pfx = jrCore_db_get_prefix($module);

        // Check for Pending Support for this module
        // NOTE: Items created by master/admin users bypass pending
        $pnd = false;
        $_pn = jrCore_get_registered_module_features('jrCore','pending_support');
        if ($_pn && isset($_pn[$module])) {
            $_data["{$pfx}_pending"] = '0';
            if (!jrUser_is_admin()) {
                // Pending support is on for this module - check quota
                // 0 = immediately active
                // 1 = review needed on CREATE
                // 2 = review needed on CREATE and UPDATE
                if (isset($_user["quota_{$module}_pending"]) && intval($_user["quota_{$module}_pending"]) > 0) {
                    $_data["{$pfx}_pending"] = '1';
                    $pnd = true;
                    jrCore_set_flag("jrcore_created_pending_item_{$iid}",1);
                }
            }
        }

        // Check for visible support
        $_pn = jrCore_get_registered_module_features('jrCore','visible_support');
        if ($_pn && isset($_pn[$module])) {
            $_data["{$pfx}_visible"] = 'on';
        }

        // Check for item_order_support
        $_pn = jrCore_get_registered_module_features('jrCore','item_order_support');
        if ($_pn && isset($_pn[$module])) {
            // New entries at top
            $_data["{$pfx}_display_order"] = 0;
        }

        // Trigger create event
        $_args = array(
            '_item_id' => $iid,
            'module'   => $module
        );
        $_data = jrCore_trigger_event('jrCore','db_create_item',$_data,$_args);

        // Check for actions that are linking to pending items
        $lid = 0;
        $lmd = '';
        if (isset($_data['action_pending_linked_item_id']) && jrCore_checktype($_data['action_pending_linked_item_id'],'number_nz')) {
            $lid = (int) $_data['action_pending_linked_item_id'];
            $lmd = jrCore_db_escape($_data['action_pending_linked_item_module']);
            unset($_data['action_pending_linked_item_id']);
            unset($_data['action_pending_linked_item_module']);
        }

        $tbl = jrCore_db_table_name($module,'item_key');
        $req = "INSERT INTO {$tbl} (`_item_id`,`key`,`index`,`value`) VALUES ";
        foreach ($_data as $k => $v) {
            // If our value is longer than 512 bytes we split it up
            if (strlen($v) > 512) {
                $_tm = str_split($v,512);
                foreach ($_tm as $idx => $part) {
                    $req .= "('{$iid}','". jrCore_db_escape($k) ."','". ($idx + 1) ."','". jrCore_db_escape($part) ."'),";
                }
            }
            else {
                $req .= "('{$iid}','". jrCore_db_escape($k) ."','0','". jrCore_db_escape($v) ."'),";
            }
        }
        $req = substr($req,0,strlen($req) - 1);
        $cnt = jrCore_db_query($req,'COUNT');
        if (isset($cnt) && $cnt > 0) {
            // Increment profile counts for this item
            if ($profile_count) {
                switch ($module) {
                    // Some modules we do not store counts for
                    case 'jrProfile':
                    case 'jrUser':
                        break;
                    default:
                        $pid = $_data['_profile_id'];
                        if (isset($profile_count) && jrCore_checktype($profile_count,'number_nz')) {
                            $pid = (int) $profile_count;
                        }
                        // Update counts for module items
                        $ptb = jrCore_db_table_name('jrProfile','item_key');
                        $req = "UPDATE {$ptb} SET `value` = (SELECT SQL_SMALL_RESULT COUNT(`_item_id`) FROM {$tbl} WHERE `key` = '_profile_id' AND `value` = '{$pid}') WHERE `key` = 'profile_{$module}_item_count' AND `_item_id` = '{$pid}' LIMIT 1";
                        $cnt = jrCore_db_query($req,'COUNT');
                        if (!isset($cnt) || $cnt === 0) {
                            // The first entry for a new module item
                            $req = "INSERT INTO {$ptb} (`_item_id`,`key`,`index`,`value`) VALUES ({$pid},'profile_{$module}_item_count',0,1) ON DUPLICATE KEY UPDATE `value` = (`value` + 1)";
                            jrCore_db_query($req);
                        }
                        break;
                }
            }
            if ($pnd) {
                // Add pending entry to Pending table...
                $_pd = array(
                    'module' => $module,
                    'item'   => $_data,
                    'user'   => $_user
                );
                $dat = jrCore_db_escape(json_encode($_pd));
                $pnd = jrCore_db_table_name('jrCore','pending');
                $req = "INSERT INTO {$pnd} (pending_created,pending_module,pending_item_id,pending_linked_item_module,pending_linked_item_id,pending_data)
                        VALUES (UNIX_TIMESTAMP(),'". jrCore_db_escape($module) ."','{$iid}','{$lmd}','{$lid}','{$dat}')
                        ON DUPLICATE KEY UPDATE pending_created = UNIX_TIMESTAMP()";
                jrCore_db_query($req,'INSERT_ID');
                unset($_pd);
            }
            return $iid;
        }
    }
    return false;
}

/**
 * Gets all items from a module datastore matching a key and value
 * @param string $module Module the item belongs to
 * @param string $key Key name to find
 * @param mixed $value Value to find
 * @param bool $keys_only if set to TRUE returns array of id's
 * @return mixed array on success, bool false on failure
 */
function jrCore_db_get_multiple_items_by_key($module,$key,$value,$keys_only = false)
{
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = '". jrCore_db_escape($key) ."' AND `value` = '". jrCore_db_escape($value) ."'";
    $_rt = jrCore_db_query($req,'_item_id');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    if ($keys_only) {
        return array_keys($_rt);
    }
    return jrCore_db_get_multiple_items($module,array_keys($_rt));
}

/**
 * Gets a single item from a module datastore by key name and value
 * @param string $module Module the item belongs to
 * @param string $key Key name to find
 * @param mixed $value Value to find
 * @return mixed array on success, bool false on failure
 */
function jrCore_db_get_item_by_key($module,$key,$value)
{
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = '". jrCore_db_escape($key) ."' AND `value` = '". jrCore_db_escape($value) ."' LIMIT 1";
    $_rt = jrCore_db_query($req,'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    return jrCore_db_get_item($module,$_rt['_item_id']);
}

/**
 * Gets an item from a module datastore
 * @param string $module Module the item belongs to
 * @param int $id Item ID to retrieve
 * @param bool $skip_trigger By default the db_get_item event trigger is sent out to allow additional modules to add data to the item.  Set to TRUE to just return the item from the item datastore.
 * @return mixed array on success, bool false on failure
 */
function jrCore_db_get_item($module,$id,$skip_trigger = false)
{
    if (!is_numeric($id)) {
        return false;
    }

    // See if we are cached - note that this is a GLOBAL cache
    // since it will be the same for any viewing user
    $key = "{$module}-{$id}-{$skip_trigger}";
    if ($module != 'jrUser' && $_rt = jrCore_is_cached($module,$key,false)) {
        return $_rt;
    }

    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "SELECT SQL_SMALL_RESULT `key`,`value` FROM {$tbl} WHERE `_item_id` = '". intval($id) ."' ORDER BY `index` ASC";
    $_rt = jrCore_db_query($req,'NUMERIC');
    if (isset($_rt) && is_array($_rt)) {
        // Construct item
        $_ot = array();
        foreach ($_rt as $_v) {
            if (!isset($_ot["{$_v['key']}"])) {
                $_ot["{$_v['key']}"] = $_v['value'];
            }
            else {
                $_ot["{$_v['key']}"] .= $_v['value'];
            }
        }
        unset($_rt);
        $_ot['_item_id'] = intval($id);
        if ($skip_trigger !== true) {

            $_md = array('module' => $module);
            // Every item always gets User, Profile and Quota information added in.
            switch ($module) {

                // The one exception is "jrProfile" - since a profile can have
                // more than one User Account associated with it, we let the
                // developer handle that themselves.
                case 'jrProfile':
                    // We only add in Quota info (below)
                    break;

                // For Users we always add in their ACTIVE profile info
                case 'jrUser':
                    if (!strpos(' '. $skip_trigger,'exclude_jrProfile_keys')) {
                        $_tm = jrCore_db_get_item('jrProfile',$_ot['_profile_id'],true);
                        if (isset($_tm) && is_array($_tm)) {
                            unset($_tm['_item_id']);
                            $_ot = $_ot + $_tm;
                        }
                    }
                    break;

                // Everything else gets both as long as we have not disabled it
                default:
                    $pid = $_ot['_profile_id'];
                    // Add in User Info
                    if (!strpos(' '. $skip_trigger,'exclude_jrUser_keys')) {
                        $_tm = jrCore_db_get_item('jrUser',$_ot['_user_id'],true);
                        if (isset($_tm) && is_array($_tm)) {
                            unset($_tm['_item_id']);
                            $_ot = $_ot + $_tm;
                        }
                    }
                    // Add in Profile Info
                    if (!strpos(' '. $skip_trigger,'exclude_jrProfile_keys')) {
                        $_tm = jrCore_db_get_item('jrProfile',$pid,true);
                        if (isset($_tm) && is_array($_tm)) {
                            unset($_tm['_item_id']);
                            $_ot = $_ot + $_tm;
                        }
                    }
                    break;
            }
            // Add in Quota info to item
            if (isset($_ot['profile_quota_id']) && !strpos(' '. $skip_trigger,'exclude_jrProfile_quota_keys')) {
                $_tm = jrProfile_get_quota($_ot['profile_quota_id']);
                if ($_tm) {
                    unset($_tm['_item_id']);
                    $_ot = $_ot + $_tm;
                }
            }
            unset($_tm);
            // Trigger db_get_item event
            $_ot = jrCore_trigger_event('jrCore','db_get_item',$_ot,$_md);
            // Make sure listeners do not change our _item_id
            $_ot['_item_id'] = intval($id);
        }
        // Save to cache
        jrCore_add_to_cache($module,$key,$_ot,86400,$_ot['_profile_id'],false);
        return $_ot;
    }
    return false;
}

/**
 * Get multiple items by _item_id from a module datastore
 *
 * NOTE: This function does NOT send out a trigger to add User/Profile information.  If you need
 * User and Profile information in the returned array of items, make sure and use jrCore_db_search_items
 * With an "in" search for your items ids - i.e. _item_id IN 1,5,7,9,12
 *
 * @param string $module Module the item belongs to
 * @param array $_ids array array of _item_id's to get
 * @param array $_keys Array of key names to get, default is all keys for each item
 * @return mixed array on success, bool false on failure
 */
function jrCore_db_get_multiple_items($module,$_ids,$_keys = null)
{
    if (!isset($_ids) || !is_array($_ids) || count($_ids) === 0) {
        return false;
    }
    // validate id's
    foreach ($_ids as $id) {
        if (!jrCore_checktype($id,'number_nz')) {
            return false;
        }
    }
    $ink = false;
    $tbl = jrCore_db_table_name($module,'item_key');
    if (isset($_keys) && is_array($_keys) && count($_keys) > 0) {
        $_ky = array();
        foreach ($_keys as $k) {
            if ($k == '_item_id') {
                // We handle _item_id down below...
                if (!in_array('_created',$_keys)) {
                    $_ky[] = '_created';
                    $ink = true;
                }
            }
            else {
                $_ky[] = jrCore_db_escape($k);
            }
        }
    }

    // See if we have leftover keys..
    if (isset($_ky) && is_array($_ky) && count($_ky) > 0) {
        $req = "SELECT SQL_SMALL_RESULT `_item_id` AS i,`key` AS k,`value` AS v FROM {$tbl} WHERE `_item_id` IN(". implode(',',$_ids) .") AND `key` IN('". implode("','",$_ky) ."') ORDER BY FIELD(`_item_id`,". implode(',',$_ids) ."), `index` ASC";
    }
    else {
        $req = "SELECT SQL_SMALL_RESULT `_item_id` AS i,`key` AS k,`value` AS v FROM {$tbl} WHERE `_item_id` IN(". implode(',',$_ids) .") ORDER BY FIELD(`_item_id`,". implode(',',$_ids) ."), `index` ASC";
    }
    $_rt = jrCore_db_query($req,'NUMERIC');
    if (isset($_rt) && is_array($_rt)) {
        $_rs = array();
        $i = 0;
        $l = false;
        foreach ($_rt as $v) {
            if (!$l) {
                // First time through, set l
                $l = $v['i'];
            }
            // If our _item_id is changing...
            if ($l != $v['i']) {
                // It means we are closing out the existing item so add _item_id in
                // NOTE: we do not add this in for jrUser/jrProfile
                if ($module != 'jrUser' && $module != 'jrProfile') {
                    $_rs[$i]['_item_id'] = $l;
                }
                // increment index offset
                $i++;
                // this belongs to our next index
                $l = $v['i'];
            }
            if (!isset($_rs[$i]["{$v['k']}"])) {
                $_rs[$i]["{$v['k']}"] = $v['v'];
            }
            else {
                $_rs[$i]["{$v['k']}"] .= $v['v'];
            }
            if ($ink && $v['k'] == '_created') {
                unset($_rs[$i]["{$v['k']}"]);
            }
        }
        // Add in last item_id...
        if ($module != 'jrUser' && $module != 'jrProfile') {
            $_rs[$i]['_item_id'] = $l;
        }
        return $_rs;
    }
    return false;
}

/**
 * Gets a single item attribute from a module datastore
 * @param string $module Module the item belongs to
 * @param int $id Item ID to retrieve
 * @param string $key Key value to return
 * @return mixed array on success, bool false on failure
 */
function jrCore_db_get_item_key($module,$id,$key)
{
    if (!jrCore_checktype($id,'number_nz')) {
        return false;
    }
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "SELECT SQL_SMALL_RESULT `value` FROM {$tbl} WHERE `_item_id` = '". intval($id) ."' AND `key` = '". jrCore_db_escape($key) ."' ORDER BY `index` ASC";
    $_rt = jrCore_db_query($req,'NUMERIC');
    if (isset($_rt) && is_array($_rt)) {
        if (!isset($_rt[1])) {
            return $_rt[0]['value'];
        }
        $out = '';
        foreach ($_rt as $_v) {
            $out .= $_v['value'];
        }
        return $out;
    }
    return false;
}

/**
 * Updates an Item in a module datastore
 * @param string $module Module the DataStore belongs to
 * @param int $id Unique ID to update
 * @param array $_data Array of Key => Value pairs for insertion
 * @param array $_core Array of Key => Value pairs for insertion - skips jrCore_db_get_allowed_item_keys()
 * @return mixed INSERT_ID on success, false on error
 */
function jrCore_db_update_item($module,$id,$_data = null,$_core = null)
{
    global $_user;
    if (!jrCore_checktype($id,'number_nz')) {
        return false;
    }
    // Validate incoming array
    if (isset($_data) && is_array($_data)) {
        $_data = jrCore_db_get_allowed_item_keys($module,$_data);
    }
    else {
        $_data = array();
    }
    // We're being updated
    $_data['_updated'] = time();

    // Check for additional core fields being overridden
    if (isset($_core) && is_array($_core)) {
        $_check = array(
            '_created',
            '_updated',
            '_profile_id',
            '_user_id',
        );
        foreach ($_check as $k) {
            if (isset($_core[$k])) {
                $_data[$k] = $_core[$k];
            }
        }
    }

    // Check for Pending Support for this module
    // NOTE: We must check for this function being called as part of another (usually save)
    // routine - we don't want to change the value if this is an update that is part of a create process
    $tmp = jrCore_get_flag("jrcore_created_pending_item_{$id}");
    if (!$tmp) {
        $pnd = false;
        $pfx = jrCore_db_get_prefix($module);
        $_data["{$pfx}_pending"] = '0';
        $_pnd = jrCore_get_registered_module_features('jrCore','pending_support');
        if ($_pnd && isset($_pnd[$module])) {
            // Pending support is on for this module - check quota
            // 0 = immediately active
            // 1 = review needed on CREATE
            // 2 = review needed on CREATE and UPDATE
            if (isset($_user["quota_{$module}_pending"]) && $_user["quota_{$module}_pending"] == '2') {
                $_data["{$pfx}_pending"] = '1';
                $pnd = true;
            }
        }
    }

    // Trigger update event
    $_args = array(
        '_item_id' => $id,
        'module'   => $module
    );
    $_data = jrCore_trigger_event('jrCore','db_update_item',$_data,$_args);

    // Check for actions that are linking to pending items
    $lid = 0;
    $lmd = '';
    if (isset($_data['action_pending_linked_item_id']) && jrCore_checktype($_data['action_pending_linked_item_id'],'number_nz')) {
        $lid = (int) $_data['action_pending_linked_item_id'];
        $lmd = jrCore_db_escape($_data['action_pending_linked_item_module']);
        unset($_data['action_pending_linked_item_id']);
        unset($_data['action_pending_linked_item_module']);
    }

    // Update
    $_mx = array();
    $_zo = array();
    $tbl = jrCore_db_table_name($module,'item_key');
    $iid = (int) $id;
    $req = "INSERT INTO {$tbl} (`_item_id`,`key`,`index`,`value`) VALUES ";
    foreach ($_data as $k => $v) {
        // If our value is longer than 512 bytes we split it up
        if (strlen($v) > 512) {
            $_tm = str_split($v,512);
            $idx = 0;
            foreach ($_tm as $i => $part) {
                $idx = ($i + 1);
                $req .= "('{$iid}','". jrCore_db_escape($k) ."','{$idx}','". jrCore_db_escape($part) ."'),";
            }
            $_mx[$k] = $idx;
            // We have to also delete any previous 0 index
            $_zo[] = $k;
        }
        else {
            $req .= "('{$iid}','". jrCore_db_escape($k) ."',0,'". jrCore_db_escape($v) ."'),";
            $_mx[$k] = '0';
        }
    }
    $req = substr($req,0,strlen($req) - 1) ." ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
    jrCore_db_query($req);

    // Cleanup
    $_tm = array();
    foreach ($_mx as $fld => $max) {
        $_tm[] = "(`key` = '". jrCore_db_escape($fld) ."' AND `index` > {$max})";
    }
    if (isset($_zo) && is_array($_zo) && count($_zo) > 0) {
        foreach ($_zo as $fld) {
            $_tm[] = "(`key` = '". jrCore_db_escape($fld) ."' AND `index` = '0')";
        }
    }
    $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$iid}' AND (". implode(' OR ',$_tm) .')';
    jrCore_db_query($req);
    unset($_mx,$idx);

    // Check for pending
    if (isset($pnd) && $pnd) {
        // Add pending entry to Pending table...
        $_pd = array(
            'module' => $module,
            'item'   => $_data,
            'user'   => $_user
        );
        $dat = jrCore_db_escape(json_encode($_pd));
        $pnd = jrCore_db_table_name('jrCore','pending');
        $req = "INSERT INTO {$pnd} (pending_created,pending_module,pending_item_id,pending_linked_item_module,pending_linked_item_id,pending_data) VALUES (UNIX_TIMESTAMP(),'". jrCore_db_escape($module) ."','{$iid}','{$lmd}','{$lid}','{$dat}') ON DUPLICATE KEY UPDATE pending_created = UNIX_TIMESTAMP()";
        jrCore_db_query($req,'INSERT_ID');
        unset($_pd);
    }

    // We need to reset the cache for this item
    jrCore_delete_cache($module,"{$module}-{$id}",false);

    return true;
}

/**
 * Delete multiple items from a module DataStore
 * @param $module string Module DataStore belongs to
 * @param $_ids array Array of _item_id's to delete
 * @param bool $delete_media Set to false to NOT delete associated media files
 * @param mixed $profile_count If set to true, profile_count will be decremented by 1 for given _profile_id.  If set to an integer, it will be used as the profile_id for the counts
 * @return bool
 */
function jrCore_db_delete_multiple_items($module,$_ids,$delete_media = true,$profile_count = true)
{
    if (!isset($_ids) || !is_array($_ids) || count($_ids) === 0) {
        return false;
    }
    // validate id's
    foreach ($_ids as $id) {
        if (!jrCore_checktype($id,'number_nz')) {
            return false;
        }
    }

    // First, get all items so we can check for attached media
    $_it = jrCore_db_get_multiple_items($module,$_ids);
    if (!isset($_it) || !is_array($_it)) {
        // no items matching
        return true;
    }

    // Delete item
    $tbl = jrCore_db_table_name($module,'item');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` IN(". implode(',',$_ids) .")";
    jrCore_db_query($req);

    // Delete keys
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` IN(". implode(',',$_ids) .")";
    jrCore_db_query($req);

    // Take care of media
    if ($delete_media) {
        foreach ($_it as $_item) {
            foreach ($_item as $k => $v) {
                if (strpos($k,'_extension')) {
                    $field = str_replace('_extension','',$k);
                    jrCore_delete_item_media_file($module,$field,$_item['_profile_id'],$_item['_item_id']);
                }
            }
        }
    }

    // Take care of profile counts
    if ($profile_count) {
        switch ($module) {
            case 'jrProfile':
            case 'jrUser':
                break;
            default:
                // Update counts for module items
                $ptb = jrCore_db_table_name('jrProfile','item_key');
                $req = "UPDATE {$ptb} SET `value` = (SELECT SQL_SMALL_RESULT COUNT(`_item_id`) FROM {$tbl} WHERE `key` = '_profile_id' AND `value` = '{$_it[0]['_profile_id']}') WHERE `key` = 'profile_{$module}_item_count' AND `_item_id` = '{$_it[0]['_profile_id']}' LIMIT 1";
                jrCore_db_query($req);
                break;
        }
    }

    // Lastly, trigger
    foreach ($_it as $_item) {
        $_args = array(
            '_item_id' => $_item['_item_id'],
            'module'   => $module
        );
        jrCore_trigger_event('jrCore','db_delete_item',$_item,$_args);
    }
    return true;
}


/**
 * Deletes an Item in the module DataStore
 *
 * <b>NOTE:</b> By default this function will also delete any
 * media files that are associated with the item id!
 *
 * @param string $module Module the DataStore belongs to
 * @param int $id Item ID to delete
 * @param bool $delete_media Set to false to NOT delete associated media files
 * @param mixed $profile_count If set to true, profile_count will be decremented by 1 for given _profile_id.  If set to an integer, it will be used as the profile_id for the counts
 * @return bool
 */
function jrCore_db_delete_item($module,$id,$delete_media = true,$profile_count = true)
{
    $id = array($id);
    return jrCore_db_delete_multiple_items($module,$id,$delete_media,$profile_count);
}

/**
 * Search a module DataStore and return matching items
 *
 * $_params is an array that contains all the function parameters - i.e.:
 *
 * <code>
 * $_params = array(
 *     'search' => array(
 *         'user_name = brian',
 *         'user_height > 72'
 *     ),
 *     'order_by' => array(
 *         'user_name' => 'asc',
 *         'user_height' => 'desc'
 *     ),
 *     'group_by' => '_user_id',
 *     'return_keys' => array(
 *         'user_email',
 *         'username'
 *      ),
 *     'return_count' => true|false,
 *     'limit' => 50
 * );
 *
 * wildcard searches use a % in the key name:
 * 'search' => array(
 *     'user_% = brian',
 *     '% like brian%'
 * );
 * </code>
 *
 * "return_keys" - only return the matching keys
 *
 * "return_count" - If the "return_count" parameter is set to TRUE, then only the COUNT of matching
 * entries will be returned.
 *
 * "privacy_check" - by default only items that are viewable to the calling user will be returned -
 * set "privacy_check" to FALSE to disable privacy settings checking.
 *
 * "ignore_pending" - by default only items that are NOT pending are shown - set ignore_pending to
 * TRUE to skip the pending item check
 *
 * "exclude_(module)_keys" - some modules (such as jrUser and jrProfile) add extra keys into the returned
 * results - you can skip adding these extra keys in by disable the module(s) you do not want keys for.
 *
 * Valid Search conditions are:
 * <code>
 *  =        - "equals"
 *  !=       - "not equals"
 *  >        - greater than
 *  >=       - greater than or equal to
 *  <        - less than
 *  <=       - less than or equal to
 *  like     - wildcard text search - i.e. "user_name like %ob%" would find "robert" and "bob". % is wildcard character.
 *  not_like - wildcard text negated search - same format as "like"
 *  in       - "in list" of values - i.e. "user_name in brian,douglas,paul,michael" would find all 4 matches
 *  not_in   - negated "in least" search - same format as "in"
 * </code>
 * @param string $module Module the DataStore belongs to
 * @param array $_params Search Parameters
 * @return mixed Array on success, Bool on error
 */
function jrCore_db_search_items($module,$_params)
{
    global $_user, $_conf;
    if (!isset($_params) || !is_array($_params)) {
        return false;
    }
    $_params['module'] = $module;
    // Backup copy of original params
    $_backup = $_params;

    // Other modules can provide supported parameters for searching - send
    // our trigger so those events can be added in.
    $_module = array('module' => $module);
    if (!isset($_params['skip_triggers'])) {
        $_params = jrCore_trigger_event('jrCore','db_search_params',$_params,$_module);
    }

    // See if we are cached
    $cky = json_encode($_params);
    if ((!isset($_params['no_cache']) || $_params['no_cache'] === false) && $tmp = jrCore_is_cached($module,$cky)) {
        return $tmp;
    }

    // The DataStore works in 2 ways - a "faster for smaller sites way" and a
    // "bit slower but will work for even really big sites" way - depends on number
    // of items that are currently in the DataStore
    if (jrCore_db_number_rows($module,'item') > 25000) {

        $_sc = array();  // Holds search params
        $_ky = array();  // Holds search keys

        // Privacy Check - non admin users
        // 0 = Private
        // 1 = Global
        // 2 = Shared
        if (!jrUser_is_admin() && (!isset($_params['privacy_check']) || $_params['privacy_check'] !== false)) {

            $tbp = jrCore_db_table_name('jrProfile','item_key');
            $req = "SELECT `_item_id` FROM {$tbp} WHERE `key` = 'profile_private' AND `value` = '1'";
            $_pr = jrCore_db_query($req,'_item_id',false,'_item_id');
            // Logged in users can (possibly) see more profiles
            if (jrUser_is_logged_in()) {
                if (jrCore_module_is_active('jrFollower')) {
                    // We can see profiles we are followers of (if set)
                    $_ad = jrFollower_get_profiles_followed($_user['_user_id']);
                    if (isset($_ad) && is_array($_ad)) {
                        foreach ($_ad as $pid) {
                            $_pr[$pid] = $pid;
                        }
                    }
                    // We can always see our active profile id
                    $_ad["{$_user['user_active_profile_id']}"] = $_user['user_active_profile_id'];
                    // We can always see our own profile
                    $hid = jrUser_get_profile_home_key('_profile_id');
                    $_ad[$hid] = $hid;
                    unset($hid);
                }

                // Power and Multi Users can always see profiles they admin
                if (jrUser_is_power_user() || jrUser_is_multi_user()) {
                    $_ad = array();
                    // Power/Multi users can always see the profiles they manage
                    if (isset($_user['user_linked_profile_ids']) && strlen($_user['user_linked_profile_ids']) > 0) {
                        $_tm = explode(',',$_user['user_linked_profile_ids']);
                        if (isset($_tm) && is_array($_tm)) {
                            if (isset($_ad) && is_array($_ad)) {
                                $_ad = array_merge($_ad,$_tm);
                            }
                            else {
                                $_ad = $_tm;
                            }
                            unset($_tm);
                        }
                    }
                    foreach ($_ad as $pid) {
                        $_pr[$pid] = $pid;
                    }
                    unset($_ad);
                }
            }
            // Make sure _profile_id is added in as a search key if we are not searching on it
            $_sc[] = array('_profile_id','IN','(' . implode(',',$_pr) . ')','no_quotes');
        }

        // Search params
        $obv = false;
        $tcn = 0;
        if (isset($_params['search']) && count($_params['search']) > 0) {
            foreach ($_params['search'] as $v) {
                list($key,$opt,$val) = @explode(' ',$v,3);
                if (!isset($val) || strlen($val) === 0 || !isset($opt) || strlen($opt) === 0) {
                    // Bad Search
                    jrCore_logger('MAJ','invalid search criteria in jrCore_db_search_items parameters - check usage');
                    fdebug('invalid search criteria',$module,$_params); // OK
                    return false;
                }
                $key = jrCore_str_to_lower($key);
                if (!strpos(' '. $key,'%')) {
                    $_ky[$key] = 1;
                }
                if (strpos($val,'(SELECT ') === 0) {
                    // We have a sub query as our match condition
                    $_sc[] = array($key,$opt,$val,'no_quotes');
                    continue;
                }
                switch (jrCore_str_to_lower($opt)) {
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                        $_sc[] = array($key,strtoupper($opt),intval($val),'no_quotes');
                        break;
                    case '!=':
                        // With a NOT EQUAL operator, we also need to include items where the key may be NULL
                        $_sc[] = array($key,strtoupper($opt),"'" . jrCore_db_escape($val) ."'",'add_null');
                        break;
                    case '=':
                    case 'like';
                    case 'regexp':
                        $_sc[] = array($key,strtoupper($opt),jrCore_db_escape($val));
                        break;
                    case 'not_like':
                        $opt = 'NOT LIKE';
                        $_sc[] = array($key,$opt,jrCore_db_escape($val));
                        break;
                    case 'in':
                        $_vl = array();
                        foreach (explode(',',$val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = "'" . jrCore_db_escape($iv) . "'";
                            }
                        }
                        // By default if we do NOT get an ORDER BY clause on an IN, order by FIELD
                        if (!isset($_params['order_by']) && $key != '_profile_id' && $key != '_user_id' && !isset($_params['return_item_id_only'])) {
                            $_params['order_by'] = array($key => 'field');
                            $tcn = count($_vl);
                            // Check limits/pagebreak
                            if (isset($_params['limit']) && jrCore_checktype($_params['limit'],'number_nz')) {
                                $_vl = array_slice($_vl,0,$_params['limit']);
                            }
                            elseif (isset($_params['pagebreak']) && jrCore_checktype($_params['pagebreak'],'number_nz')) {
                                // Check for good page num
                                if (!isset($_params['page']) || !jrCore_checktype($_params['page'],'number_nz')) {
                                    $_params['page'] = 1;
                                }
                                $_vl = array_slice($_vl,0,($_params['page'] * $_params['pagebreak']));
                            }
                            else {
                                $_vl = array_slice($_vl,0,10);
                            }
                            $obv = implode(',',$_vl);
                            $val = "(". $obv .") ";
                            $_sc[] = array($key,strtoupper($opt),$val,'no_quotes');
                        }
                        else {
                            $val = "(". implode(',',$_vl) .") ";
                            $_sc[] = array($key,strtoupper($opt),$val,'no_quotes');
                        }
                        break;
                    case 'not_in':
                        $opt = 'NOT IN';
                        $_vl = array();
                        foreach (explode(',',$val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = "'" . jrCore_db_escape($iv) . "'";
                            }
                        }
                        $val = "(". implode(',',$_vl) .") ";
                        $_sc[] = array($key,$opt,$val,'no_quotes');
                        unset($_vl);
                        break;
                    default:
                        return 'error: invalid search operator: '. htmlentities($opt);
                        break;
                }

            }
        }

        // Module prefix
        $pfx = jrCore_db_get_prefix($module);

        // Check for Pending Support
        $_pn = jrCore_get_registered_module_features('jrCore','pending_support');
        if (isset($_pn) && isset($_pn[$module]) && !isset($_params['ignore_pending'])) {
            // Pending support is on for this module - check status
            // 0 = immediately active
            // 1 = review needed
            // Let's see if anything is pending
            $_pq = jrCore_get_flag('jrcore_db_search_items_pending_modules');
            if (!$_pq) {
                $ptb = jrCore_db_table_name('jrCore', 'pending');
                $prq = "SELECT pending_module FROM {$ptb} GROUP BY pending_module";
                $_pq = jrCore_db_query($prq,'pending_module',false,'pending_module');
                jrCore_set_flag('jrcore_db_search_items_pending_modules', $_pq);
            }
            if (isset($_pq) && is_array($_pq) && isset($_pq[$module])) {
                $_sc[] = array("{$pfx}_pending",'!=','1');
                $_ky["{$pfx}_pending"] = 1;
            }
        }

        // in order to properly ORDER BY, we must be including the key we are
        // ordering by in our JOIN - thus, if the user specifies an ORDER BY on
        // a key that they did not search on, then we must add in an IS NOT NULL
        // condition for the order by key
        if (isset($_params['order_by']) && is_array($_params['order_by'])) {
            foreach ($_params['order_by'] as $k => $v) {
                // We order by FIELD below
                if ($v == 'field') {
                    continue;
                }
                // Catch some common errors when ordering on a numerical key
                $k = trim($k);
                switch ($k) {
                    case '_created':
                    case '_updated':
                    case '_item_id':
                        if (stripos($v,'numerical') !== 0) {
                            switch (strtolower($v)) {
                                case 'desc':
                                    $_params['order_by'][$k] = 'numerical_desc';
                                    break;
                                default:
                                    $_params['order_by'][$k] = 'numerical_asc';
                                    break;
                            }
                        }
                        else {
                            $_params['order_by'][$k] = strtolower($v);
                        }
                        break;
                    default:
                        $_params['order_by'][$k] = strtolower($v);
                        break;
                }
                // our "order" by parameter must be part of our SEARCH
                // criteria, or there is no way for us to order on it
                if (!isset($_ky[$k])) {
                    $_sc[] = array($k,'IS OR IS NOT','NULL');
                    $_ky[$k] = 1;
                }
            }
        }
        // Lastly - if we get a group_by parameter, we have to make sure the field
        // that is being grouped on is joined so it can be grouped
        // group_by => audio_album
        if (isset($_params['group_by']) && strlen($_params['group_by']) > 0 && $_params['group_by'] != '_item_id') {
            if (!isset($_ky["{$_params['group_by']}"])) {
                $_sc[] = array($_params['group_by'],'IS NOT','NULL');
            }
        }
        // Make sure we got something
        if (!isset($_sc) || !is_array($_sc) || count($_sc) === 0) {
            // Default - _item_id greater than 0
            $_sc[] = array('_item_id','>',0);
        }


        // Start our result set.  When doing a search an array with 2 keys is returned:
        // "_items" - contains the actual search results numerically indexed
        // "info" - contains meta information about the result set
        $_rs = array(
            '_items' => false,
            'info'   => array()
        );

        $req = 'SET SESSION group_concat_max_len=1048576';
        jrCore_db_query($req);

        // Build query and get data
        $_rt = array();  // matching Item ID's
        $tba = jrCore_db_table_name($module,'item_key');
        $add = '';
        foreach ($_sc as $k => $v) {

            // Check if we have an ORDER BY
            if (isset($_params['order_by']) && is_array($_params['order_by']) && isset($_params['order_by']["{$v[0]}"])) {
                $ov = 'value';
                if ($v[0] == '_item_id') {
                    $ov = '_item_id';
                }
                switch ($_params['order_by']["{$v[0]}"]) {
                    case 'field':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY FIELD(`{$v[0]}`,{$obv})) AS ids FROM {$tba} ";
                        break;
                    case 'asc':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY `{$ov}` ASC) AS ids FROM {$tba} ";
                        break;
                    case 'desc':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY `{$ov}` DESC) AS ids FROM {$tba} ";
                        break;
                    case 'numerical_asc':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY (`{$ov}` + 0) ASC) AS ids FROM {$tba} ";
                        break;
                    case 'numerical_desc':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY (`{$ov}` + 0) DESC) AS ids FROM {$tba} ";
                        break;
                    case 'random':
                        $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`) ORDER BY RAND()) AS ids FROM {$tba} ";
                        break;
                }
            }
            else {
                $req = "SELECT GROUP_CONCAT(DISTINCT(`_item_id`){$add}) AS ids FROM {$tba} ";
            }

            if ($v[0] == '_item_id') {
                if ($v[1] == 'IS OR IS NOT') {
                    $req .= "WHERE `_item_id` > 0";
                }
                elseif ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                    $req .= "WHERE `_item_id` {$v[1]} {$v[2]}";
                }
                else {
                    $req .= "WHERE `_item_id` {$v[1]} '{$v[2]}'";
                }
            }
            else {
                if ($v[1] == 'IS OR IS NOT') {
                    $req .= "WHERE `key` = '{$v[0]}'";
                }
                elseif (isset($v[3]) && $v[3] == 'add_null') {
                    $req .= "WHERE `key` = '{$v[0]}' AND (`value` {$v[1]} {$v[2]} OR `value` IS NULL)";
                }
                // Wildcard
                elseif ($v[0] == '%') {
                    $req .= "WHERE `value` {$v[1]} '{$v[2]}'";
                }
                // wildcard match on key
                elseif (strpos(' '. $v[0],'%')) {
                    $req .= "WHERE `key` LIKE '{$v[0]}' AND `value` {$v[1]} {$v[2]}";
                }
                elseif ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                    $req .= "WHERE `key` = '{$v[0]}' AND `value` {$v[1]} {$v[2]}";
                }
                else {
                    $req .= "WHERE `key` = '{$v[0]}' AND `value` {$v[1]} '{$v[2]}'";
                }
            }

            // Check if we have a GROUP BY
            if (isset($_params['group_by']) && strlen($_params['group_by']) > 0 && $v[0] == $_params['group_by']) {
                $req .= " GROUP BY `value`";
                $_id = jrCore_db_query($req,'NUMERIC');
                if (isset($_id) && is_array($_id)) {
                    foreach ($_id as $uk => $uv) {
                        $_id[$uk] = explode(',',$uv['ids']);
                        $_id[$uk] = reset($_id[$uk]);
                    }
                    $_id = array('ids' => implode(',',$_id));
                }
            }
            else {
                $_id = jrCore_db_query($req,'SINGLE');
            }
            if (isset($_id['ids']) && strlen($_id['ids']) > 0) {
                $ids = trim(trim($_id['ids']),',');
                $_id = explode(',',$ids);
            }
            else {
                // No matches from this condition - can't match any
                return false;
            }

            // all queries AFTER this one must be ordered by our order by value to maintain order
            if (isset($_params['order_by']) && is_array($_params['order_by']) && isset($_params['order_by']["{$v[0]}"]) && strlen($ids) > 0) {
                $add = " ORDER BY FIELD(`_item_id`,{$ids})";
            }

            // Run query
            if ($k === 0) {
                $_rt = $_id;
            }
            else {
                // Note - order matters here - must be _id, _rt
                $_rt = array_intersect($_id,$_rt);
            }
        }

        // If all we need is the count of results...
        if (isset($_params['return_count']) && $_params['return_count'] !== false) {
            $cnt = count($_rt);
            unset($_rt);
            return $cnt;
        }

        // Check for Limit
        if (isset($_params['limit']) && jrCore_checktype($_params['limit'],'number_nz')) {
            $_rt = array_slice($_rt, 0, $_params['limit']);
        }
        // See if pagination has been requested
        elseif (isset($_params['pagebreak']) && jrCore_checktype($_params['pagebreak'],'number_nz')) {

            // Check for good page num
            if (!isset($_params['page']) || !jrCore_checktype($_params['page'],'number_nz')) {
                $_params['page'] = 1;
            }

            // Check if we also have a limit - this is going to limit the total
            // result set to a specific size, but still allow pagination
            if (isset($_params['limit'])) {
                // We need to see WHERE we are in the requested set
                $_rs['info']['total_items'] = (isset($_ct['tc']) && jrCore_checktype($_ct['tc'],'number_nz')) ? intval($_ct['tc']) : 0;
                if ($_rs['info']['total_items'] > $_params['limit']) {
                    $_rs['info']['total_items'] = $_params['limit'];
                }
                // Find out how many we are returning on this query...
                $pnum = $_params['pagebreak'];
                if (($_params['page'] * $_params['pagebreak']) > $_params['limit']) {
                    $pnum = (int) ($_params['limit'] % $_params['pagebreak']);
                }
                // See if the request range is completely outside the last page
                if ($_params['pagebreak'] < $_params['limit'] && $_params['page'] > ceil($_params['limit'] / $_params['pagebreak'])) {
                    // invalid set
                    return false;
                }
                // We only need the Item ID's from the slice selected
                $_rt = array_slice($_rt, (intval($_params['page'] - 1) * $_params['pagebreak']), $pnum);
            }
            else {
                // We only need the Item ID's from the slice selected
                $_rs['info']['total_items'] = ($tcn > 0) ? $tcn : (int) count($_rt);
                $_rt = array_slice($_rt, (intval($_params['page'] - 1) * $_params['pagebreak']), $_params['pagebreak']);
            }
            $_rs['info']['total_pages']   = (int) ceil($_rs['info']['total_items'] / $_params['pagebreak']);
            $_rs['info']['pagebreak']     = (int) $_params['pagebreak'];
            $_rs['info']['page']          = (int) $_params['page'];
            $_rs['info']['next_page']     = ($_rs['info']['total_pages'] > $_params['page']) ? intval($_params['page'] + 1) : 0;
            $_rs['info']['this_page']     = $_params['page'];
            $_rs['info']['prev_page']     = ($_params['page'] > 1) ? intval($_params['page'] - 1) : 0;
            $_rs['info']['page_base_url'] = jrCore_strip_url_params(jrCore_get_current_url(),array('p'));
        }
        // Default (10)
        else {
            $_rt = array_slice($_rt, 0, 10);
        }

        if (isset($_rt) && is_array($_rt)) {

            // We can ask to just get the item_id's for our own use.
            // NOTE: No need for triggers here
            if (isset($_params['return_item_id_only']) && $_params['return_item_id_only'] === true) {
                return array_keys($_rt);
            }

            $_ky = null;
            if (isset($_params['return_keys']) && is_array($_params['return_keys']) && count($_params['return_keys']) > 0) {
                $_params['return_keys'][] = '_user_id';     // We must include _user_id or jrUser search items trigger does not know the user to include
                $_params['return_keys'][] = '_profile_id';  // We must include _profile_id or jrProfile search items trigger does not know the profile to include
                $_ky = $_params['return_keys'];
            }
            $_rs['_items'] = jrCore_db_get_multiple_items($module,$_rt,$_ky);
            unset($_rt);
            if (isset($_rs['_items']) && is_array($_rs['_items'])) {
                // Add in some meta data
                if (!isset($_rs['info']['total_items'])) {
                    $_rs['info']['total_items'] = count($_rs['_items']);
                }
                // Trigger search event
                if (!isset($_params['skip_triggers'])) {
                    $_rs = jrCore_trigger_event('jrCore','db_search_items',$_rs,$_params);
                }
                if (!isset($GLOBALS['JRCORE_CACHE_PROFILE_IDS'])) {
                    $GLOBALS['JRCORE_CACHE_PROFILE_IDS'] = array();
                }
                foreach ($_rs['_items'] as $v) {
                    if (isset($v['_profile_id'])) {
                        $GLOBALS['JRCORE_CACHE_PROFILE_IDS']["{$v['_profile_id']}"] = $v['_profile_id'];
                    }
                }
                // Check for return keys
                if ($_ky) {
                    $_ky = array_flip($_ky);
                    foreach ($_rs['_items'] as $k => $v) {
                        foreach ($v as $ky => $kv) {
                            if (!isset($_ky[$ky])) {
                                unset($_rs['_items'][$k][$ky]);
                            }
                        }
                    }
                }
                $_rs['_params'] = $_backup;
                $_rs['_params']['module'] = $module;
                $_rs['_params']['module_url'] = jrCore_get_module_url($module);
                unset($_params);
                jrCore_add_to_cache($module,$cky,$_rs);
                return $_rs;
            }
        }
    }

    //-----------------------
    // Standard way
    //-----------------------
    else {

        $_ob = array();
        $_sc = array();
        $_ky = array();
        $ino = false;
        if (isset($_params['search']) && count($_params['search']) > 0) {
            foreach ($_params['search'] as $v) {
                list($key,$opt,$val) = @explode(' ',$v,3);
                if (!isset($val) || strlen($val) === 0 || !isset($opt) || strlen($opt) === 0) {
                    // Bad Search
                    jrCore_logger('MAJ','invalid search criteria in jrCore_db_search_items parameters - check usage');
                    fdebug('invalid search criteria',$module,$_params); // OK
                    return false;
                }
                $key = jrCore_str_to_lower($key);
                if (!strpos(' '. $key,'%')) {
                    $_ky[$key] = 1;
                }
                if (strpos($val,'(SELECT ') === 0) {
                    // We have a sub query as our match condition
                    $_sc[] = array($key,$opt,$val,'no_quotes');
                    continue;
                }
                switch (jrCore_str_to_lower($opt)) {
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                        $_sc[] = array($key,strtoupper($opt),intval($val),'no_quotes');
                        break;
                    case '!=':
                        // With a NOT EQUAL operator, we also need to include items where the key may be NULL
                        $_sc[] = array($key,strtoupper($opt),"'" . jrCore_db_escape($val) ."'",'add_null');
                        break;
                    case '=':
                    case 'like';
                    case 'regexp':
                        $_sc[] = array($key,strtoupper($opt),jrCore_db_escape($val));
                        break;
                    case 'not_like':
                        $opt = 'NOT LIKE';
                        $_sc[] = array($key,$opt,jrCore_db_escape($val));
                        break;
                    case 'in':
                        $_vl = array();
                        foreach (explode(',',$val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = "'" . jrCore_db_escape($iv) . "'";
                            }
                        }
                        $val = "(". implode(',',$_vl) .") ";
                        $_sc[] = array($key,strtoupper($opt),$val,'no_quotes');
                        // By default if we do NOT get an ORDER BY clause on an IN, order by FIELD
                        if (!isset($_params['order_by']) && $key != '_profile_id' && $key != '_user_id' && !isset($_params['return_item_id_only'])) {
                            $ino = $key;
                            $_do = $_vl;
                        }
                        break;
                    case 'not_in':
                        $opt = 'NOT IN';
                        $_vl = array();
                        foreach (explode(',',$val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = "'" . jrCore_db_escape($iv) . "'";
                            }
                        }
                        $val = "(". implode(',',$_vl) .") ";
                        $_sc[] = array($key,$opt,$val,'no_quotes');
                        unset($_vl);
                        break;
                    default:
                        return 'error: invalid search operator: '. htmlentities($opt);
                        break;
                }

            }
        }

        // Module prefix
        $pfx = jrCore_db_get_prefix($module);

        // Check for Pending Support
        $_pn = jrCore_get_registered_module_features('jrCore','pending_support');
        if (isset($_pn) && isset($_pn[$module]) && !isset($_params['ignore_pending'])) {
            // Pending support is on for this module - check status
            // 0 = immediately active
            // 1 = review needed
            // Let's see if anything is pending
            $_pq = jrCore_get_flag('jrcore_db_search_items_pending_modules');
            if (!$_pq) {
                $ptb = jrCore_db_table_name('jrCore', 'pending');
                $prq = "SELECT pending_module FROM {$ptb} GROUP BY pending_module";
                $_pq = jrCore_db_query($prq,'pending_module',false,'pending_module');
                jrCore_set_flag('jrcore_db_search_items_pending_modules', $_pq);
            }
            if (isset($_pq) && is_array($_pq) && isset($_pq[$module])) {
                $_sc[] = array("{$pfx}_pending",'!=','1');
                $_ky["{$pfx}_pending"] = 1;
            }
        }

        // in order to properly ORDER BY, we must be including the key we are
        // ordering by in our JOIN - thus, if the user specifies an ORDER BY on
        // a key that they did not search on, then we must add in an IS NOT NULL
        // condition for the order by key
        if (isset($_params['order_by']) && is_array($_params['order_by'])) {
            if (isset($_params['order_by']['_created'])) {
                // We substitute _created with _item_id as it is faster
                $_params['order_by']['_item_id'] = $_params['order_by']['_created'];
                unset($_params['order_by']['_created']);
            }
            // Check for special "display" order_by
            if (isset($_params['order_by']["{$pfx}_display_order"]) && count($_params['order_by']) === 1) {
                // Sort by display order, _created desc default
                $_params['order_by']['_item_id'] = 'numerical_desc';
            }
            foreach ($_params['order_by'] as $k => $v) {
                // Catch some common errors when ordering on a numerical key
                switch ($k) {
                    case '_created':
                    case '_updated':
                    case '_item_id':
                        if (stripos($v,'numerical') !== 0) {
                            switch (strtolower($v)) {
                                case 'desc':
                                    $_params['order_by'][$k] = 'numerical_desc';
                                    break;
                                default:
                                    $_params['order_by'][$k] = 'numerical_asc';
                                    break;
                            }
                        }
                        break;
                }
                // Check for random order - no need to join
                if (!isset($_ky[$k]) && $k != '_item_id' && $v != 'random') {
                    // (e.`value` IS NOT NULL OR e.`value` IS NULL)
                    $_sc[] = array($k,'IS OR IS NOT','NULL');
                    $_ky[$k] = 1;
                }
            }
        }
        // Lastly - if we get a group_by parameter, we have to make sure the field
        // that is being grouped on is joined so it can be grouped
        if (isset($_params['group_by']) && strlen($_params['group_by']) > 0 && $_params['group_by'] != '_item_id') {
            if (!isset($_ky["{$_params['group_by']}"])) {
                $_sc[] = array($_params['group_by'],'IS NOT','NULL');
            }
        }
        // Make sure we got something
        if (!isset($_sc) || !is_array($_sc) || count($_sc) === 0) {
            // Default - _item_id greater than 0
            $_sc[] = array('_item_id','>',0);
        }

        // To try and avoiding creating temp tables, we need to make sure if we have
        // an ORDER BY clause, the table that is being ordered on needs to be the
        // first table in the query
        // https://dev.mysql.com/doc/refman/5.0/en/order-by-optimization.html
        if (isset($_params['order_by']) && is_array($_params['order_by'])) {
            $o_key = array_keys($_params['order_by']);
            $o_key = reset($o_key);
            $_stmp = array();
            $found = false;
            foreach($_sc as $k => $v) {
                if (!$found && $v[0] == $o_key) {
                    $_stmp[0] = $v;
                    $found = true;
                }
                else {
                    $t_key = ($k + 1);
                    $_stmp[$t_key] = $v;
                }
            }
            ksort($_stmp,SORT_NUMERIC);
            $_sc = array_values($_stmp);
            unset($_stmp,$o_key,$found,$t_key);
        }

        // Build query and get data
        $tba = jrCore_db_table_name($module,'item_key');
        $_al = range('a','z');
        $req = '';      // Main data Query
        foreach ($_sc as $k => $v) {
            $als = $_al[$k];
            if ($k == 0) {
                if (isset($_params['return_count']) && $_params['return_count'] !== false) {
                    $req .= "SELECT SQL_SMALL_RESULT DISTINCT(a.`_item_id`) AS tc FROM {$tba} a\n";
                    // NOTE: If return_count is set we will not be doing pagination, so we can skip $re2 here
                }
                else {
                    $req .= "SELECT SQL_SMALL_RESULT DISTINCT(a.`_item_id`) AS _item_id FROM {$tba} a\n";
                }
            }
            // wildcard
            elseif (strpos(' '. $v[0],'%')) {
                $req .= "LEFT JOIN {$tba} {$als} ON {$als}.`_item_id` = a.`_item_id`\n";
            }
            elseif ($v[0] !== '_item_id') {
                $req .= "LEFT JOIN {$tba} {$als} ON ({$als}.`_item_id` = a.`_item_id` AND {$als}.`key` = '{$v[0]}')\n";
            }
            // Save for our "order by" below - we must be searching on a column to order by it
            $_ob["{$v[0]}"] = $als;
            // See if this is our group_by column
            if (isset($_params['group_by']) && strlen($_params['group_by']) > 0 && $v[0] == $_params['group_by'] && (!isset($_params['return_count']) || $_params['return_count'] === false)) {
                $group_by = "GROUP BY {$als}.`value` ";
            }
        }

        // For privacy Check we will need to bring in _profile_id for our sub select
        if (!jrUser_is_admin() && (!isset($_params['privacy_check']) || $_params['privacy_check'] !== false)) {
            $req .= "LEFT JOIN {$tba} pr ON (pr.`_item_id` = a.`_item_id` AND pr.`key` = '_profile_id')\n";
        }

        $req .= 'WHERE ';
        foreach ($_sc as $k => $v) {
            if ($k == 0) {
                if ($v[1] == 'IS OR IS NOT') {
                    $req .= "a.`key` = '{$v[0]}'\n";
                }
                elseif (isset($v[3]) && $v[3] == 'add_null') {
                    $req .= "a.`key` = '{$v[0]}' AND (a.`value` {$v[1]} {$v[2]} OR a.`value` IS NULL)\n";
                }
                elseif ($v[0] == '_item_id') {
                    if ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                        $req .= "a.`_item_id` {$v[1]} {$v[2]}\n";
                    }
                    else {
                        $req .= "a.`_item_id` {$v[1]} '{$v[2]}'\n";
                    }
                }
                elseif ($v[0] == "{$pfx}_visible") {
                    $req .= "a.`key` = '{$v[0]}' AND (a.`value` IS NULL OR a.`value` != 'off')\n";
                }
                // wildcard (all keys)
                elseif ($v[0] == '%') {
                    if (isset($v[3]) && $v[3] == 'no_quotes') {
                        $req .= "a.`value` {$v[1]} {$v[2]}\n";
                    }
                    else {
                        $req .= "a.`value` {$v[1]} '{$v[2]}'\n";
                    }
                }
                // wildcard match on key
                elseif (strpos(' '. $v[0],'%')) {
                    if (isset($v[3]) && $v[3] == 'no_quotes') {
                        $req .= "a.`key` LIKE '{$v[0]}' AND a.`value` {$v[1]} {$v[2]}\n";
                    }
                    else {
                        $req .= "a.`key` LIKE '{$v[0]}' AND a.`value` {$v[1]} '{$v[2]}'\n";
                    }
                }
                // IN / NOT IN (no quotes) or NULL
                elseif ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                    $req .= "a.`key` = '{$v[0]}' AND a.`value` {$v[1]} {$v[2]}\n";
                }
                else {
                    $req .= "a.`key` = '{$v[0]}' AND a.`value` {$v[1]} '{$v[2]}'\n";
                }
            }
            else {
                // If we are searching by _item_id we always use "a" for our prefix
                if ($v[0] == '_item_id') {
                    if ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                        $req .= "AND a.`_item_id` {$v[1]} {$v[2]}\n";
                    }
                    else {
                        $req .= "AND a.`_item_id` {$v[1]} '{$v[2]}'\n";
                    }
                }
                else {
                    $als = $_al[$k];
                    // Special is or is not condition
                    // (e.`value` IS NOT NULL OR e.`value` IS NULL)
                    // This allows an ORDER_BY on a column that may not be set in all DS entries
                    if ($v[1] == 'IS OR IS NOT') {
                        $req .= "AND ({$als}.`value` > '' OR {$als}.`value` IS NULL)\n";
                    }
                    elseif (isset($v[3]) && $v[3] == 'add_null') {
                        $req .= "AND ({$als}.`value` {$v[1]} {$v[2]} OR {$als}.`value` IS NULL)\n";
                    }
                    // wildcard (all keys)
                    elseif ($v[0] == '%') {
                        $req .= "AND {$als}.`value` {$v[1]} '{$v[2]}'\n";
                    }
                    // wildcard match on key
                    elseif (strpos(' '. $v[0],'%')) {
                        $req .= "AND {$als}.`key` LIKE '{$v[0]}' AND {$als}.`value` {$v[1]} '{$v[2]}'\n";
                    }
                    elseif ($v[2] == 'NULL' || (isset($v[3]) && $v[3] == 'no_quotes')) {
                        $req .= "AND {$als}.`value` {$v[1]} {$v[2]}\n";
                    }
                    else {
                        $req .= "AND {$als}.`value` {$v[1]} '{$v[2]}'\n";
                    }
                }
            }
        }

        // Privacy Check (Sub Select) - non admin users
        // 0 = Private
        // 1 = Global
        // 2 = Shared
        if (!jrUser_is_admin() && (!isset($_params['privacy_check']) || $_params['privacy_check'] !== false)) {

            // Users that are not logged in only see global profiles
            $tbp = jrCore_db_table_name('jrProfile','item_key');
            if (!jrUser_is_logged_in()) {
                $req .= "AND pr.`value` IN(SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbp} WHERE `key` = 'profile_private' AND `value` = '1')\n";
            }
            else {
                $chk = true;
                if (jrCore_module_is_active('jrFollower')) {
                    if (jrUser_is_logged_in()) {
                        // If we are logged in, we can see GLOBAL profiles as well as profiles we are followers of (if set)
                        $_pr = jrFollower_get_profiles_followed($_user['_user_id']);
                        $_pr[] = $_user['user_active_profile_id'];
                        if (jrUser_is_power_user() || jrUser_is_multi_user()) {
                            // Power/Multi users can always see the profiles they manage
                            if (isset($_user['user_linked_profile_ids']) && strlen($_user['user_linked_profile_ids']) > 0) {
                                $_tm = explode(',',$_user['user_linked_profile_ids']);
                                if (isset($_tm) && is_array($_tm)) {
                                    if (isset($_pr) && is_array($_pr)) {
                                        $_pr = array_merge($_pr,$_tm);
                                    }
                                    else {
                                        $_pr = $_tm;
                                    }
                                    unset($_tm);
                                }
                            }
                            // We can always see our own profile
                            $_pr[] = jrUser_get_profile_home_key('_profile_id');
                        }
                        if (isset($_pr) && is_array($_pr)) {
                            $chk = false;
                            $req .= "AND (pr.`value` = '0' OR pr.`value` IN(". implode(',',$_pr) .") OR pr.`value` IN(SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbp} WHERE `key` = 'profile_private' AND `value` = '1'))\n";
                        }
                        unset($_pr);
                    }
                }
                if ($chk) {
                    // For Not logged in Users OR followers is not enabled, we can only return items for profiles that are GLOBALLY accessible
                    // NOTE: pr.`value` = _profile_id
                    $req .= "AND (pr.`value` = '0' OR pr.`value` IN(SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbp} WHERE `key` = 'profile_private' AND `value` = '1'))\n";
                }
            }
        }

        // For our counting query
        $re2 = $req;

        // Special check for RANDOM ordering
        if ((!isset($_params['return_count']) || $_params['return_count'] === false) && isset($_params['order_by']) && is_array($_params['order_by'])) {
            foreach ($_params['order_by'] as $k => $v) {
                $v = strtoupper($v);
                switch ($v) {
                    case 'RAND':
                    case 'RANDOM':
                        // With random ordering we ignore all other orders...
                        if (isset($_params['limit']) && intval($_params['limit']) === 1) {
                            $req .= "AND a.`_item_id` >= FLOOR(1 + RAND() * (SELECT MAX(_item_id) FROM ". jrCore_db_table_name($module,'item') .")) ";
                        }
                        else {
                            // When getting more than 1 "random" entry from the DB, it gets tricky, since
                            // there is no real (easy) optimization for doing this effectively at scale.
                            // this should remain relatively fast as the "item" table only has a primary key
                            $rtb = jrCore_db_table_name($module,'item');
                            $rqr = "SELECT `_item_id` FROM {$rtb} ORDER BY RAND() LIMIT ". intval($_params['limit']);
                            $_qr = jrCore_db_query($rqr,'_item_id');
                            $req .= "ORDER BY FIELD(a.`_item_id`,". implode(',',array_keys($_qr)) .") ";
                        }
                        unset($_params['order_by'][$k]);
                        continue 2;
                        break;
                }
            }
        }

        // Some items are not needed in our counting query
        if (!isset($_params['return_count']) || $_params['return_count'] === false) {

            // Group by
            if (isset($group_by{0})) {
                $req .= $group_by;
                $re2 .= $group_by;
            }
            elseif (!strpos($req,'RAND()')) {
                // Default - group by item_id
                if (isset($ino) && $ino == '_item_id') {
                    $req .= "GROUP BY a._item_id ";
                    $re2 .= "GROUP BY a._item_id ";
                }
            }

            // Order by
            if (isset($_params['order_by']) && is_array($_params['order_by']) && count($_params['order_by']) > 0) {
                $_ov = array();
                $oby = 'ORDER BY ';
                foreach ($_params['order_by'] as $k => $v) {
                    if (!isset($_ob[$k]) && $k != '_item_id') {
                        return "error: you must include the {$k} field in your search criteria in order to order_by it";
                    }
                    $v = strtoupper($v);
                    switch ($v) {

                        case 'ASC':
                        case 'DESC':
                            // If we are ordering by _item_id, we do not order by value...
                            if ($k == '_item_id') {
                                $_ov[] = "a.`_item_id` {$v}";
                            }
                            else {
                                $_ov[] = $_ob[$k] .".`value` {$v}";
                            }
                            break;

                        case 'NUMERICAL_ASC':
                            if ($k == '_item_id') {
                                $_ov[] = "(a.`_item_id` + 0) ASC";
                            }
                            else {
                                $_ov[] = '('. $_ob[$k] .".`value` + 0) ASC";
                            }
                            break;

                        case 'NUMERICAL_DESC':
                            if ($k == '_item_id') {
                                $_ov[] = "(a.`_item_id` + 0) DESC";
                            }
                            else {
                                $_ov[] = '('. $_ob[$k] .".`value` + 0) DESC";
                            }
                            break;

                        default:
                            return "error: invalid order direction received for {$k} - must be one of: ASC, DESC, NUMERICAL_ASC, NUMERICAL_DESC, RANDOM";
                            break;
                    }
                }
                if (isset($oby) && strlen($oby) > 0) {
                    $req .= $oby . implode(', ',$_ov) .' ';
                }
            }

            // If we get a LIST of items, we (by default) order by that list unless we get a different order by
            elseif ($ino && isset($_do)) {
                if ($ino == '_item_id') {
                    $field = "a.`_item_id`";
                }
                else {
                    $field = $_ob[$ino] .".`_item_id`";
                }
                if (isset($_params['limit'])) {
                    $req .= "ORDER BY FIELD({$field},". implode(',',array_reverse(array_slice($_do,0,$_params['limit'],true))) .") DESC ";
                }
                elseif (isset($_params['pagebreak']) && jrCore_checktype($_params['pagebreak'],'number_nz')) {
                    // Check for good page num
                    if (!isset($_params['page']) || !jrCore_checktype($_params['page'],'number_nz')) {
                        $_params['page'] = 1;
                    }
                    $req .= "ORDER BY FIELD({$field},". implode(',',array_reverse(array_slice($_do,0,($_params['page'] * $_params['pagebreak'])))) .") DESC ";
                }
                else {
                    $req .= "ORDER BY FIELD({$field},". implode(',',$_do) .") ";
                }
                unset($_do);
            }
        }

        // Start our result set.  When doing a search an array with 2 keys is returned:
        // "_items" - contains the actual search results numerically indexed
        // "info" - contains meta information about the result set
        $_rs = array(
            '_items' => false,
            'info'   => array()
        );

        // Limit
        if (isset($_params['limit']) && !isset($_params['pagebreak'])) {
            if (!jrCore_checktype($_params['limit'],'number_nz')) {
                return "error: invalid limit value - must be a number greater than 0";
            }
            $req .= 'LIMIT '. intval($_params['limit']) .' ';
            $_rs['info']['limit'] = intval($_params['limit']);
        }

        // Pagebreak
        elseif ((!isset($_params['return_count']) || $_params['return_count'] === false) && isset($_params['pagebreak']) && jrCore_checktype($_params['pagebreak'],'number_nz')) {

            // Check for good page num
            if (!isset($_params['page']) || !jrCore_checktype($_params['page'],'number_nz')) {
                $_params['page'] = 1;
            }
            $re2 = str_replace('SELECT SQL_SMALL_RESULT DISTINCT(a.`_item_id`) AS _item_id','SELECT COUNT(DISTINCT(a.`_item_id`)) AS tc',$re2);

            $beg = explode(' ',microtime());
            $beg = $beg[1] + $beg[0];

            if (strpos($req,'GROUP BY')) {
                $_ct = array(
                    'tc' => jrCore_db_query($re2,'NUM_ROWS')
                );
            }
            else {
                $_ct = jrCore_db_query($re2,'SINGLE');
            }

            $end = explode(' ',microtime());
            $end = $end[1] + $end[0];
            $end = round(($end - $beg),2);
            if ($end > 2 && isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
                fdebug("SLOW COUNT QUERY: {$end} seconds",$re2); // OK
            }

            if (isset($_ct) && is_array($_ct) && isset($_ct['tc'])) {

                // Check if we also have a limit - this is going to limit the total
                // result set to a specific size, but still allow pagination
                if (isset($_params['limit'])) {
                    // We need to see WHERE we are in the requested set
                    $_rs['info']['total_items'] = (isset($_ct['tc']) && jrCore_checktype($_ct['tc'],'number_nz')) ? intval($_ct['tc']) : 0;
                    if ($_rs['info']['total_items'] > $_params['limit']) {
                        $_rs['info']['total_items'] = $_params['limit'];
                    }
                    // Find out how many we are returning on this query...
                    $pnum = $_params['pagebreak'];
                    if (($_params['page'] * $_params['pagebreak']) > $_params['limit']) {
                        $pnum = (int) ($_params['limit'] % $_params['pagebreak']);
                        // See if the request range is completely outside the last page
                        if ($_params['pagebreak'] < $_params['limit'] && $_params['page'] > ceil($_params['limit'] / $_params['pagebreak'])) {
                            // invalid set
                            return false;
                        }
                    }
                    $req .= "LIMIT ". intval(($_params['page'] - 1) * $_params['pagebreak']) .",{$pnum}";
                }
                else {
                    $_rs['info']['total_items']   = (isset($_ct['tc']) && jrCore_checktype($_ct['tc'],'number_nz')) ? intval($_ct['tc']) : 0;
                    $req .= "LIMIT ". intval(($_params['page'] - 1) * $_params['pagebreak']) .",{$_params['pagebreak']}";
                }
                $_rs['info']['total_pages']   = (int) ceil($_rs['info']['total_items'] / $_params['pagebreak']);
                $_rs['info']['next_page']     = ($_rs['info']['total_pages'] > $_params['page']) ? intval($_params['page'] + 1) : 0;
                $_rs['info']['pagebreak']     = (int) $_params['pagebreak'];
                $_rs['info']['page']          = (int) $_params['page'];
                $_rs['info']['this_page']     = $_params['page'];
                $_rs['info']['prev_page']     = ($_params['page'] > 1) ? intval($_params['page'] - 1) : 0;
                $_rs['info']['page_base_url'] = jrCore_strip_url_params(jrCore_get_current_url(),array('p'));
            }
            else {
                // No items
                return false;
            }
        }
        else {
            // Default limit of 10
            $req .= 'LIMIT 10';
        }

        $beg = explode(' ',microtime());
        $beg = $beg[1] + $beg[0];

        $_rt = jrCore_db_query($req,'NUMERIC');

        $end = explode(' ',microtime());
        $end = $end[1] + $end[0];
        $end = round(($end - $beg),2);
        if ($end > 2 && isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
            fdebug("SLOW SELECT QUERY: {$end} seconds",$req); // OK
        }

        if (isset($_rt) && is_array($_rt)) {

            // See if we are only providing a count...
            // NOTE: No need for triggers here
            if (isset($_params['return_count']) && $_params['return_count'] !== false) {
                if (isset($_rt[0]['tc'])) {
                    return (int) $_rt[0]['tc'];
                }
                return 0;
            }

            $_id = array();
            foreach ($_rt as $v) {
                $_id[] = $v['_item_id'];
            }

            // We can ask to just get the item_id's for our own use.
            // NOTE: No need for triggers here
            if (isset($_params['return_item_id_only']) && $_params['return_item_id_only'] === true) {
                return $_id;
            }

            $_ky = null;
            if (isset($_params['return_keys']) && is_array($_params['return_keys']) && count($_params['return_keys']) > 0) {
                $_params['return_keys'][] = '_user_id';     // We must include _user_id or jrUser search items trigger does not know the user to include
                $_params['return_keys'][] = '_profile_id';  // We must include _profile_id or jrProfile search items trigger does not know the profile to include
                $_ky = $_params['return_keys'];
            }
            $_rs['_items'] = jrCore_db_get_multiple_items($module,$_id,$_ky);
            if (isset($_rs['_items']) && is_array($_rs['_items'])) {
                // Add in some meta data
                if (!isset($_rs['info']['total_items'])) {
                    $_rs['info']['total_items'] = count($_rs['_items']);
                }
                // Trigger search event
                if (!isset($_params['skip_triggers'])) {
                    $_rs = jrCore_trigger_event('jrCore','db_search_items',$_rs,$_params);
                }
                if (!isset($GLOBALS['JRCORE_CACHE_PROFILE_IDS'])) {
                    $GLOBALS['JRCORE_CACHE_PROFILE_IDS'] = array();
                }
                foreach ($_rs['_items'] as $v) {
                    if (isset($v['_profile_id'])) {
                        $GLOBALS['JRCORE_CACHE_PROFILE_IDS']["{$v['_profile_id']}"] = $v['_profile_id'];
                    }
                }
                // Check for return keys
                if ($_ky) {
                    $_ky = array_flip($_ky);
                    foreach ($_rs['_items'] as $k => $v) {
                        foreach ($v as $ky => $kv) {
                            if (!isset($_ky[$ky])) {
                                unset($_rs['_items'][$k][$ky]);
                            }
                        }
                    }
                }
                $_rs['_params'] = $_backup;
                $_rs['_params']['module'] = $module;
                $_rs['_params']['module_url'] = jrCore_get_module_url($module);
                unset($_params);
                jrCore_add_to_cache($module,$cky,$_rs);
                return $_rs;
            }
        }
    }
    return false;
}
