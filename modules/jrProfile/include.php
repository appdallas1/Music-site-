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
 * jrProfile_meta
 */
function jrProfile_meta()
{
    $_tmp = array(
        'name'        => 'User Profiles',
        'url'         => 'profile',
        'version'     => '1.0.2',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Core support for User Profiles',
        'category'    => 'profiles',
        'priority'    => 1, // HIGHEST load priority
        'locked'      => true,
        'activate'    => true
    );
    return $_tmp;
}

/**
 * jrProfile_init
 */
function jrProfile_init()
{
    // Let modules get into the profile data
    jrCore_register_event_trigger('jrProfile', 'get_profile_info', 'Fired when information about a specific profile is requested');
    jrCore_register_event_trigger('jrProfile', 'profile_view', 'Fired when a profile page is viewed');
    jrCore_register_event_trigger('jrProfile', 'profile_index', 'Fired when the profile index is viewed');

    // Listen for user signups so we can create associated profile
    jrCore_register_event_listener('jrUser', 'signup_created', 'jrProfile_signup_created_listener');
    jrCore_register_event_listener('jrUser', 'signup_activated', 'jrProfile_signup_activated_listener');

    // We provide a "profile_id" parameter to the jrCore_db_search_item function
    jrCore_register_event_listener('jrCore', 'db_search_params', 'jrProfile_db_search_params_listener');
    jrCore_register_event_listener('jrCore', 'db_search_items', 'jrProfile_db_search_items_listener');
    jrCore_register_event_listener('jrCore', 'verify_module', 'jrProfile_verify_module_listener');

    // Register our tools
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrProfile', 'browser', array('Browse User Profiles', 'Browse User Profiles in your system'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrProfile', 'quota_browser', array('Quota Browser', 'Browse the existing Profile Quotas in your system'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrProfile', 'create', array('Create New Profile', 'Create a new Profile'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrProfile', 'user_link', array('Link User Accounts', 'Link User Accounts with existing Profiles'));

    // Register our account tab..
    jrCore_register_module_feature('jrUser', 'account_tab', 'jrProfile', 'settings', 2);

    // Allow admin to customize our forms
    jrCore_register_module_feature('jrCore', 'designer_form', 'jrProfile', 'create');
    jrCore_register_module_feature('jrCore', 'designer_form', 'jrProfile', 'settings');

    // We provide our own data browser
    jrCore_register_module_feature('jrCore', 'data_browser', 'jrProfile', 'jrProfile_data_browser');

    // We have fields that can be search
    jrCore_register_module_feature('jrSearch', 'search_fields', 'jrProfile', 'profile_name', 26);

    // Link to manage profiles (Power Users and Multi Profile users)
    $_tmp = array(
        'group'    => 'power,multi',
        'label'    => 25,
        'url'      => 'list_profiles',
        'function' => 'jrProfile_get_number_profiles'
    );
    jrCore_register_module_feature('jrCore', 'skin_menu_item', 'jrProfile', 'profile_manage', $_tmp);

    return true;
}

//---------------------------------------------------------
// PROFILE EVENT LISTENERS
//---------------------------------------------------------

/**
 * Adds support for "profile_id" parameter to jrCore_list
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrProfile_verify_module_listener($_data, $_user, $_conf, $_args, $event)
{
    // Make sure some fields are removed from the designer form table
    jrCore_delete_designer_form_field('jrProfile', 'create', 'profile_quota_id');
    jrCore_delete_designer_form_field('jrProfile', 'create', 'profile_user_id');
    return true;
}

/**
 * Adds support for "profile_id" parameter to jrCore_list
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrProfile_db_search_params_listener($_data, $_user, $_conf, $_args, $event)
{
    // profile_id=(id)[,id][,id][,..]
    if (isset($_data['profile_id'])) {
        if (jrCore_checktype($_data['profile_id'], 'number_nz')) {
            if (!isset($_data['search'])) {
                $_data['search'] = array();
            }
            $_data['search'][] = "_profile_id = " . intval($_data['profile_id']);
        }
        elseif (strpos($_data['profile_id'], ',')) {
            $_tmp = explode(',', $_data['profile_id']);
            if (isset($_tmp) && is_array($_tmp)) {
                $_pi = array();
                foreach ($_tmp as $pid) {
                    if (is_numeric($pid)) {
                        $_pi[] = (int) $pid;
                    }
                }
                if (isset($_pi) && is_array($_pi) && count($_pi) > 0) {
                    if (!isset($_data['search'])) {
                        $_data['search'] = array();
                    }
                    $_data['search'][] = "_profile_id in " . implode(',', $_pi);
                }
            }
        }
    }

    // quota_id=(id)[,id][,id][,..]
    if (isset($_data['quota_id'])) {
        if (jrCore_checktype($_data['quota_id'], 'number_nz')) {
            if (!isset($_data['search'])) {
                $_data['search'] = array();
            }
            $_data['search'][] = "profile_quota_id = " . intval($_data['quota_id']);
        }
        elseif (strpos($_data['quota_id'], ',')) {
            $_tmp = explode(',', $_data['quota_id']);
            if (isset($_tmp) && is_array($_tmp)) {
                $_pi = array();
                foreach ($_tmp as $pid) {
                    if (is_numeric($pid)) {
                        $_pi[] = (int) $pid;
                    }
                }
                if (isset($_pi) && is_array($_pi) && count($_pi) > 0) {
                    if (!isset($_data['search'])) {
                        $_data['search'] = array();
                    }
                    $_data['search'][] = "profile_quota_id in " . implode(',', $_pi);
                }
            }
        }
    }

    // Check for quota feature search - by default we always look for the quota
    // "access" flag - i.e. "quota_jrAudio_allowed" - it must be set to "on"
    // in order for profiles to be returned
    $_sup = jrCore_get_registered_module_features('jrCore', 'quota_support');
    if (isset($_sup["{$_args['module']}"])) {
        $_tm = jrCore_get_flag('jrprofile_check_quota_support');
        if (!$_tm) {
            // Any info returned must be from a profile that is allowed to use this module
            $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
            $req = "SELECT `module`, `quota_id`, `value` FROM {$tbl} WHERE `name` = 'allowed'";
            $_qt = jrCore_db_query($req, 'NUMERIC');
            if (isset($_qt) && is_array($_qt)) {
                $_tm = array(
                    'subquery_required' => array()
                );
                foreach ($_qt as $_entry) {
                    if (!isset($_tm["{$_entry['module']}"])) {
                        $_tm["{$_entry['module']}"] = array();
                    }
                    $_tm["{$_entry['module']}"]["{$_entry['quota_id']}"] = 1;
                    // If this item is turned off for any quota, we must run our
                    // sub query to get just those quota_id's that are allowed
                    if (isset($_entry['value']) && $_entry['value'] == 'off') {
                        $_tm['subquery_required']["{$_entry['module']}"] = 1;
                    }
                }
                jrCore_set_flag('jrprofile_check_quota_support', $_tm);
            }
        }
        if (isset($_tm["{$_args['module']}"]) && is_array($_tm["{$_args['module']}"]) && isset($_tm['subquery_required']["{$_args['module']}"])) {
            $tbl = jrCore_db_table_name('jrProfile', 'item_key');
            $_data['search'][] = "_profile_id IN (SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = 'profile_quota_id' AND `value` IN(" . implode(',', array_keys($_tm["{$_args['module']}"])) . "))";
        }
    }

    // Check for search on profile keys in another module
    if ($_args['module'] != 'jrProfile' && isset($_data['search']) && is_array($_data['search'])) {

        $_sc = false;
        foreach ($_data['search'] as $k => $cond) {
            $cond = trim($cond);
            if (strpos($cond, 'profile_') === 0 && strpos($cond, '_profile_id IN') !== 0) {
                // We have a profile search... convert to sub query
                list($key, $opt, $val) = explode(' ', $cond);
                if (!$_sc) {
                    $_sc = array();
                }
                switch (jrCore_str_to_lower($opt)) {
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                        $_sc[] = array($key, strtoupper($opt), intval($val), 'no_quotes');
                        break;
                    case '=':
                    case '!=':
                    case 'like';
                        $_sc[] = array($key, strtoupper($opt), jrCore_db_escape($val));
                        break;
                    case 'not_like':
                        $opt = 'NOT LIKE';
                        $_sc[] = array($key, $opt, jrCore_db_escape($val));
                        break;
                    case 'in':
                        $esc = false;
                        $_vl = array();
                        foreach (explode(',', $val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = jrCore_db_escape($iv);
                                $esc = true;
                            }
                        }
                        if ($esc) {
                            $val = "('" . implode("','", $_vl) . "') ";
                        }
                        else {
                            $val = "(" . implode(',', $_vl) . ") ";
                        }
                        $_sc[] = array($key, strtoupper($opt), $val, 'no_quotes');
                        break;
                    case 'not_in':
                        $opt = 'NOT IN';
                        $esc = false;
                        $_vl = array();
                        foreach (explode(',', $val) as $iv) {
                            if (ctype_digit($iv)) {
                                $_vl[] = (int) $iv;
                            }
                            else {
                                $_vl[] = jrCore_db_escape($iv);
                                $esc = true;
                            }
                        }
                        if ($esc) {
                            $val = "('" . implode("','", $_vl) . "') ";
                        }
                        else {
                            $val = "(" . implode(',', $_vl) . ") ";
                        }
                        $_sc[] = array($key, $opt, $val, 'no_quotes');
                        unset($_vl);
                        break;
                }
                unset($_data['search'][$k]);
            }
        }
        if ($_sc) {
            $tbl = jrCore_db_table_name('jrProfile', 'item_key');
            foreach ($_sc as $_sub) {
                // Construct sub query
                if (isset($_sub[3]) && $_sub[3] == 'no_quotes') {
                    $_data['search'][] = "_profile_id IN (SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = '" . jrCore_db_escape($_sub[0]) . "' AND `value` {$_sub[1]} {$_sub[2]})";
                }
                else {
                    $_data['search'][] = "_profile_id IN (SELECT SQL_SMALL_RESULT `_item_id` FROM {$tbl} WHERE `key` = '" . jrCore_db_escape($_sub[0]) . "' AND `value` {$_sub[1]} '{$_sub[2]}')";
                }
            }
        }
    }
    return $_data;
}

/**
 * jrProfile_db_search_items_listener
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrProfile_db_search_items_listener($_data, $_user, $_conf, $_args, $event)
{
    if ($_args['module'] != 'jrUser' && $_args['module'] != 'jrProfile' && isset($_data['_items'][0]) && isset($_data['_items'][0]['_profile_id'])) {

        // See if we do NOT include Profile keys with the results
        if (isset($_args['exclude_jrProfile_keys']) && $_args['exclude_jrProfile_keys'] === true) {
            return $_data;
        }

        // See if only specific keys are being requested - if none of them are profile keys
        // then we do not need to go back to the DB to get any profile/quota info
        if (isset($_params['return_keys']) && is_array($_params['return_keys']) && count($_params['return_keys']) > 0) {
            $found = false;
            foreach ($_params['return_keys'] as $key) {
                if (strpos($key, 'profile_') === 0) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $_data;
            }
            unset($found);
        }

        // Add profile keys to data
        $_us = array();
        foreach ($_data['_items'] as $v) {
            if (isset($v['_profile_id']) && jrCore_checktype($v['_profile_id'], 'number_nz') && !isset($v['profile_url'])) {
                $_us[] = (int) $v['_profile_id'];
            }
        }
        if (isset($_us) && is_array($_us) && count($_us) > 0) {
            $_rt = jrCore_db_get_multiple_items('jrProfile', $_us);
            if (isset($_rt) && is_array($_rt)) {
                // We've found profile info - go though and setup by _profile_id
                $_pr = array();
                $_up = array();
                foreach ($_rt as $v) {
                    $_pr["{$v['_profile_id']}"] = $v;
                    unset($_pr["{$v['_profile_id']}"]['_created']);
                    unset($_pr["{$v['_profile_id']}"]['_updated']);
                    unset($_pr["{$v['_profile_id']}"]['_item_id']);
                    $_up["{$v['_profile_id']}"] = array($v['_updated'], $v['_created']);
                }
                // Add to results
                foreach ($_data['_items'] as $k => $v) {
                    if (isset($_pr["{$v['_profile_id']}"]) && is_array($_pr["{$v['_profile_id']}"])) {
                        $_data['_items'][$k] = $v + $_pr["{$v['_profile_id']}"];
                        $_data['_items'][$k]['profile_created'] = $_up["{$v['_profile_id']}"][0];
                        $_data['_items'][$k]['profile_updated'] = $_up["{$v['_profile_id']}"][1];
                        // Bring in Quota info
                        if (!isset($_args['exclude_jrProfile_quota_keys']) || $_args['exclude_jrProfile_quota_keys'] !== true) {
                            $_temp = jrProfile_get_quota($_pr["{$v['_profile_id']}"]['profile_quota_id']);
                            if (isset($_temp) && is_array($_temp)) {
                                $_data['_items'][$k] = $_data['_items'][$k] + $_temp;
                            }
                        }
                    }
                }
            }
        }
    }
    return $_data;
}

/**
 * Listens for when new users sign up so it can create their profile
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrProfile_signup_created_listener($_data, $_user, $_conf, $_args, $event)
{
    // User account is created - create profile

    // We need to check and see if a quota_id came in via $_post ($_data).  If
    // it did, then we are going to check to be sure that quota_id allows sign ups -
    // if it does, then we use that quota_id - else we use the default.
    $qid = (isset($_conf['jrProfile_default_quota_id']) && jrCore_checktype($_conf['jrProfile_default_quota_id'], 'number_nz')) ? intval($_conf['jrProfile_default_quota_id']) : '1';
    if (isset($_data['quota_id']) && jrCore_checktype($_data['quota_id'], 'number_nz')) {
        // Looks like a different quota_id is being requested - validate
        $_qt = jrProfile_get_quota($_data['quota_id']);
        if (!isset($_qt) || !is_array($_qt)) {
            jrCore_db_delete_item('jrUser', $_args['_user_id']);
            jrCore_set_form_notice('error', 'An error was encountered creating your profile - please try again');
            jrCore_form_result();
        }
        // Make sure sign ups are allowed
        if (!isset($_qt['quota_jrUser_allow_signups']) || $_qt['quota_jrUser_allow_signups'] != 'on') {
            jrCore_db_delete_item('jrUser', $_args['_user_id']);
            jrCore_logger('MAJ', "attempted signup to a Quota that does not allow signups (quota_id: {$_data['quota_id']}");
            jrCore_set_form_notice('error', 'An error was encountered creating your profile - please try again');
            jrCore_form_result();
        }
        $qid = intval($_data['quota_id']);
    }

    // data to save to datastore
    $_prof = array(
        'profile_name'     => $_args['user_name'],
        'profile_url'      => jrCore_url_string($_args['user_name']),
        'profile_quota_id' => $qid,
        'profile_active'   => (isset($_data['user_active']) && intval($_data['user_active']) === 1) ? 1 : 0,
        'profile_private'  => 1
    );
    $pid = jrCore_db_create_item('jrProfile', $_prof);
    if (!isset($pid) || !jrCore_checktype($pid, 'number_nz')) {
        jrCore_db_delete_item('jrUser', $_args['_user_id']);
        jrCore_set_form_notice('error', 'An error was encountered creating your profile - please try again');
        jrCore_form_result();
    }

    // Update with new profile id
    $_temp = array();
    $_core = array(
        '_user_id'    => $_args['_user_id'],
        '_profile_id' => $pid
    );
    jrCore_db_update_item('jrProfile', $pid, $_temp, $_core);

    // Update User info with the proper _profile_id
    unset($_core['_user_id']);
    jrCore_db_update_item('jrUser', $_args['_user_id'], $_temp, $_core);

    // Profile is created - add user_id -> profile_id into link table
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "INSERT INTO {$tbl} (user_id,profile_id) VALUES ('{$_args['_user_id']}','{$pid}')";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (!isset($cnt) || $cnt !== 1) {
        jrCore_db_delete_item('jrUser', $_args['_user_id']);
        jrCore_db_delete_item('jrProfile', $pid);
        jrCore_set_form_notice('error', 'An error was encountered creating your profile - please try again');
        jrCore_form_result();
    }

    // Make sure profile media directory is created
    jrCore_create_media_directory($pid);

    // Update the profile_count for the quota this profile just signed up
    jrProfile_update_profile_count($qid);

    // Save off Quota Info - this is used in the User Module to determine the Signup Method.
    if (!isset($_qt)) {
        $_qt = jrProfile_get_quota($qid);
    }
    $_data['signup_method'] = (isset($_qt['quota_jrUser_signup_method'])) ? $_qt['quota_jrUser_signup_method'] : 'email';

    return $_data;
}

/**
 * Listens for when new users activate their account so it can
 * activate the associated user profile
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrProfile_signup_activated_listener($_data, $_user, $_conf, $_args, $event)
{
    // Update Profile so it is active
    if (isset($_data['_profile_id']) && jrCore_checktype($_data['_profile_id'], 'number_nz')) {

        // Make sure profile is active
        $_prof = array('profile_active' => '1');
        jrCore_db_update_item('jrProfile', $_data['_profile_id'], $_prof);

        // Now get our data and merge it with the user data...
        $_rt = jrCore_db_get_item('jrProfile', $_data['_profile_id']);
        if (isset($_rt) && is_array($_rt)) {
            // Get all the profiles this user has access to
            if (isset($_data['_user_id']) && jrCore_checktype($_data['_user_id'], 'number_nz')) {
                $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
                $req = "SELECT profile_id FROM {$tbl} WHERE user_id = '{$_data['_user_id']}'";
                $_pn = jrCore_db_query($req, 'profile_id');
                if (isset($_pn) && is_array($_pn)) {
                    $_rt['user_linked_profile_ids'] = implode(',', array_keys($_pn));
                }
            }
            // Setup profile updated/created so we don't overwrite user info
            $_rt['profile_created'] = $_rt['_created'];
            $_rt['profile_updated'] = $_rt['_updated'];
            unset($_rt['_created'], $_rt['_updated'], $_rt['_item_id']);
            // Next - grab info about this profile's quota
            $_temp = jrProfile_get_quota($_rt['profile_quota_id']);
            if (isset($_temp) && is_array($_temp)) {
                $_rt = $_rt + $_temp;
            }
            return $_data + $_rt;
        }
    }
    return $_data;
}

//---------------------------------------------------------
// PROFILE FUNCTIONS
//---------------------------------------------------------

/**
 * Check profile media disk space and show error if over limit
 * @param $profile_id
 * @return bool
 */
function jrProfile_check_disk_usage($profile_id = null)
{
    global $_user;
    if (jrUser_is_admin()) {
        // Admins can always add new items
        return true;
    }
    if (is_null($profile_id)) {
        $profile_id = (int) $_user['user_active_profile_id'];
    }
    if (isset($_user['quota_jrCore_disk']) && $_user['quota_jrCore_disk'] > 0) {
        $tmp = jrCore_get_flag('jrprofile_check_disk_usage');
        if (!$tmp) {
            $tmp = jrProfile_get_disk_usage($profile_id);
            if ($tmp) {
                jrCore_set_flag('jrprofile_check_disk_usage', $tmp);
            }
        }
        if ($tmp && $tmp > ($_user['quota_jrCore_disk'] * 1048576)) {
            $_ln = jrUser_load_lang_strings();
            jrCore_notice_page('error', $_ln['jrProfile'][27], 'referrer');
        }
    }
    return true;
}

/**
 * Get profile media directory disk usage in bytes
 * @param $profile_id
 * @return bool|int
 */
function jrProfile_get_disk_usage($profile_id)
{
    $dir = jrCore_get_media_directory($profile_id);
    if (is_dir($dir)) {
        $io = popen('/usr/bin/du -sm ' . $dir, 'r');
        $sz = intval(fgets($io, 80));
        pclose($io);
        return ($sz * 1048576);
    }
    return false;
}

/**
 * Custom Data Store browser tool
 * @param $_post array Global $_post
 * @param $_user array Viewing user array
 * @param $_conf array Global config
 * @return bool
 */
function jrProfile_data_browser($_post, $_user, $_conf)
{
    $order_dir = 'asc';
    $order_opp = 'desc';
    if (isset($_post['order_dir']) && ($_post['order_dir'] == 'desc' || $_post['order_dir'] == 'numerical_desc')) {
        $order_dir = 'desc';
        $order_opp = 'asc';
    }

    $order_by = '_item_id';
    if (isset($_post['order_by'])) {
        switch ($_post['order_by']) {
            case '_item_id';
            case '_created';
                $order_dir = 'numerical_' . $order_dir;
                $order_opp = 'numerical_' . $order_opp;
            case 'profile_name':
                $order_by = $_post['order_by'];
                break;
        }
    }

    // get our items
    $_pr = array(
        'search'         => array(
            '_item_id > 0'
        ),
        'pagebreak'      => 12,
        'page'           => 1,
        'order_by'       => array(
            $order_by => $order_dir
        ),
        'skip_triggers'  => true,
        'ignore_pending' => true,
        'privacy_check'  => false
    );
    if (isset($_post['p']) && jrCore_checktype($_post['p'], 'number_nz')) {
        $_pr['page'] = (int) $_post['p'];
    }
    // See we have a search condition
    $_ex = false;
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $_ex = array('search_string' => $_post['search_string']);
        // Check for passing in a specific key name for search
        if (strpos($_post['search_string'], ':')) {
            list($sf, $ss) = explode(':', $_post['search_string'], 2);
            $_post['search_string'] = $ss;
            $_pr['search'][] = "{$sf} like {$ss}%";
        }
        else {
            $_pr['search'][] = "profile_name like %{$_post['search_string']}%";
        }
    }
    $_us = jrCore_db_search_items('jrProfile', $_pr);

    // Start our output
    $url = $_conf['jrCore_base_url'] . jrCore_strip_url_params($_post['_uri'], array('order_by', 'order_dir'));
    $dat = array();
    $dat[1]['title'] = 'img';
    $dat[1]['width'] = '5%';
    $dat[2]['title'] = 'ID';
    $dat[2]['width'] = '5%';
    $dat[3]['title'] = '<a href="' . $url . '/order_by=profile_name/order_dir=' . $order_opp . '">profile name</a>';
    $dat[3]['width'] = '30%';
    $dat[4]['title'] = 'user accounts';
    $dat[4]['width'] = '30%';
    $dat[5]['title'] = '<a href="' . $url . '/order_by=_created/order_dir=' . $order_opp . '">created</a>';
    $dat[5]['width'] = '20%';
    $dat[6]['title'] = 'modify';
    $dat[6]['width'] = '5%';
    $dat[7]['title'] = 'delete';
    $dat[7]['width'] = '5%';
    jrCore_page_table_header($dat);

    if (isset($_us['_items']) && is_array($_us['_items'])) {

        // Get user info for these profiles
        $_pi = array();
        foreach ($_us['_items'] as $_usr) {
            $_pi[] = (int) $_usr['_profile_id'];
        }

        // get users
        $_pr = array(
            'search'         => array(
                '_profile_id in ' . implode(',', $_pi)
            ),
            'return_keys'    => array('_profile_id', '_user_id', 'user_name', 'user_group'),
            'skip_triggers'  => true,
            'ignore_pending' => true,
            'privacy_check'  => false,
            'limit'          => 100
        );
        $_pi = jrCore_db_search_items('jrUser', $_pr);
        if (isset($_pi['_items']) && is_array($_pi['_items'])) {
            $_pr = array();
            $url = jrCore_get_module_url('jrUser');
            foreach ($_pi['_items'] as $_usr) {
                if (!isset($_pr["{$_usr['_profile_id']}"])) {
                    $_pr["{$_usr['_profile_id']}"] = array();
                }
                $_pr["{$_usr['_profile_id']}"][] = "<a href=\"{$_conf['jrCore_base_url']}/{$url}/account/user_id={$_usr['_user_id']}\">{$_usr['user_name']}</a>";
            }
        }
        unset($_pi);

        foreach ($_us['_items'] as $_prf) {
            $dat = array();
            $dat[1]['title'] = "<img src=\"{$_conf['jrCore_base_url']}/{$_post['module_url']}/image/profile_image/{$_prf['_profile_id']}/xsmall/crop=auto\" alt=\"{$_prf['profile_name']}\" title=\"{$_prf['profile_name']}\">";
            $dat[2]['title'] = $_prf['_profile_id'];
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = "<a href=\"{$_conf['jrCore_base_url']}/{$_prf['profile_url']}\"><h3>{$_prf['profile_name']}</h3></a>";
            $dat[4]['title'] = (isset($_pr["{$_prf['_profile_id']}"])) ? implode('<br>', $_pr["{$_prf['_profile_id']}"]) : '-';
            $dat[4]['class'] = 'center';
            $dat[5]['title'] = jrCore_format_time($_prf['_created']);
            $dat[5]['class'] = 'center';
            $dat[6]['title'] = jrCore_page_button("m{$_prf['_profile_id']}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/settings/profile_id={$_prf['_profile_id']}'");
            // If this profile belongs to a Master User, it can only be deleted by a Master Admin
            $master = false;
            if (isset($_pr["{$_prf['_profile_id']}"]) && is_array($_pr["{$_prf['_profile_id']}"])) {
                foreach ($_pr["{$_prf['_profile_id']}"] as $_uinf) {
                    if (isset($_uinf['user_group']) && $_uinf['user_group'] == 'master') {
                        $master = true;
                        break;
                    }
                }
            }
            if ($master && !jrUser_is_master()) {
                $dat[7]['title'] = jrCore_page_button("d{$_prf['_profile_id']}", 'delete', 'disabled');
            }
            else {
                $dat[7]['title'] = jrCore_page_button("d{$_prf['_profile_id']}", 'delete', "if(confirm('Are you sure you want to delete this profile? Any User Accounts associated with ONLY this profile will also be removed.')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/delete_save/id={$_prf['_profile_id']}'}");
            }
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_pager($_us, $_ex);
    }
    else {
        $dat = array();
        if (isset($_post['search_string'])) {
            $dat[1]['title'] = '<p>No Results found for your Search Criteria.</p>';
        }
        else {
            $dat[1]['title'] = '<p>No User Profiles found!</p>';
        }
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
    return true;
}

/**
 * Check if a viewing user has access to a profile based on the profile_privacy setting
 * @param $profile_id integer Profile_ID
 * @param $privacy_setting integer Profile_Privacy (one of 0|1|2)
 * @return bool
 */
function jrProfile_privacy_check($profile_id, $privacy_setting)
{
    global $_user;
    // Admins see everything
    if (jrUser_is_admin()) {
        return true;
    }
    switch (intval($privacy_setting)) {

        case 0:
            // Private Access
            if (jrUser_is_logged_in() && jrProfile_is_profile_owner($profile_id)) {
                return true;
            }
            break;

        case 1:
            // Global Access
            return true;
            break;

        case 2:
            // Shared Access
            if (jrUser_is_logged_in()) {
                // We can always view our own profile
                if ($profile_id == $_user['user_active_profile_id'] || $profile_id == jrUser_get_profile_home_key('_profile')) {
                    return true;
                }
                // If we are a Power User or Multi Profile user, we always
                // can view the profiles we have access to.
                if (jrUser_is_power_user() || jrUser_is_multi_user()) {
                    if (isset($_user['user_linked_profile_ids']) && strlen($_user['user_linked_profile_ids']) > 0 && strpos(" ,{$_user['user_linked_profile_ids']},", ",{$profile_id},")) {
                        return true;
                    }
                }
                // We're shared - viewer must be a follower of the profile
                if (jrCore_module_is_active('jrFollower')) {
                    if (jrFollower_is_follower($_user['_user_id'], $profile_id)) {
                        return true;
                    }
                }
            }
            break;
    }
    return false;
}

/**
 * Return number of profiles a user manages if more than 1
 */
function jrProfile_get_number_profiles()
{
    global $_user;
    // [user_linked_profile_ids] => 4,18
    if (isset($_user['user_linked_profile_ids']) && strpos($_user['user_linked_profile_ids'], ',')) {
        return true;
    }
    return false;
}

/**
 * Return an array of profile owner emails
 * @param $pid integer Profile ID
 * @return array
 */
function jrProfile_get_owner_email($pid)
{
    $pid = (int) $pid;
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "SELECT user_id FROM {$tbl} WHERE profile_id = '{$pid}'";
    $_rt = jrCore_db_query($req, 'user_id');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    $tbl = jrCore_db_table_name('jrUser', 'item_key');
    $req = "SELECT `value` FROM {$tbl} WHERE `key` = 'user_email' AND `_item_id` IN('" . implode("','", array_keys($_rt)) . "')";
    $_rt = jrCore_db_query($req, '_item_id', false, 'value');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    return $_rt;
}

/**
 * Return an array of profile owner info
 * @param $pid integer Profile ID
 * @return array
 */
function jrProfile_get_owner_info($pid)
{
    $pid = (int) $pid;
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "SELECT user_id FROM {$tbl} WHERE profile_id = '{$pid}'";
    $_rt = jrCore_db_query($req, 'user_id');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    $_us = jrCore_db_get_multiple_items('jrUser', array_keys($_rt));
    if (isset($_us) && is_array($_us)) {
        foreach ($_us as $k => $v) {
            unset($_us[$k]['user_password']);
            unset($_us[$k]['user_old_password']);
        }
        return $_us;
    }
    return false;
}

/**
 * Register a setting to be shown in Profile Settings
 * @param $module string Module registering setting for
 * @param $_field array Array of setting information
 * @return bool
 */
function jrProfile_register_setting($module, $_field)
{
    if (!isset($_field['name'])) {
        jrCore_set_form_notice('error', "You must provide a valid field name");
        return false;
    }
    if ($_field['name'] == "item_count") {
        jrCore_set_form_notice('error', "Invalid profile_setting name - item_count is reserved for internal use");
        return false;
    }
    $_tmp = jrCore_get_flag('jrprofile_register_setting');
    if (!$_tmp) {
        $_tmp = array();
    }
    if (!isset($_tmp[$module])) {
        $_tmp[$module] = array();
    }
    $_field['name'] = "profile_{$module}_{$_field['name']}";
    $_tmp[$module][] = $_field;
    jrCore_set_flag('jrprofile_register_setting', $_tmp);
    return true;
}

/**
 * Returns true if viewing user can modify the given profile id
 * @param $id integer Profile ID to check
 * @return bool
 */
function jrProfile_is_profile_owner($id)
{
    // validate id
    if (!isset($id) || !jrCore_checktype($id, 'number_nz')) {
        return false;
    }
    if (jrUser_is_admin() || (isset($_SESSION['user_linked_profile_ids']) && in_array($id, explode(',', $_SESSION['user_linked_profile_ids'])))) {
        // The viewing user can manage this profile
        return true;
    }
    return false;
}

/**
 * Create a new link between a User and a Profile
 * @param $profile_id integer Profile ID to link to
 * @param $user_id integer User ID to link to
 * @return bool
 */
function jrProfile_create_user_link($user_id, $profile_id)
{
    global $_user;
    // We don't link to admin users
    if ($user_id == $_user['_user_id'] && jrUser_is_admin()) {
        return true;
    }
    $uid = (int) $user_id;
    $pid = (int) $profile_id;
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "INSERT INTO {$tbl} (user_id,profile_id) VALUES ('{$uid}','{$pid}') ON DUPLICATE KEY UPDATE profile_id = '{$pid}'";
    jrCore_db_query($req);
    return true;
}

/**
 * Get array of profile_id's that user_id is linked to
 * @param $user_id integer User_ID to get profile id's for
 * @return bool|mixed  Returns an array of profile_id => user_id links
 */
function jrProfile_get_user_linked_profiles($user_id)
{
    if (isset($user_id) && jrCore_checktype($user_id, 'number_nz')) {
        $uid = (int) $user_id;
        $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
        $req = "SELECT * FROM {$tbl} WHERE user_id = '{$uid}'";
        return jrCore_db_query($req, 'profile_id', false, 'user_id');
    }
    return false;
}

/**
 * Changes the active profile information for a user
 * @return bool
 */
function jrProfile_sync_active_profile_data()
{
    global $_user;
    if (!isset($_SESSION['user_active_profile_id']) || !jrCore_checktype($_SESSION['user_active_profile_id'], 'number_nz')) {
        return false;
    }
    // Get latest data and sync
    $_tmp = jrCore_db_get_item('jrProfile', $_SESSION['user_active_profile_id']);
    if (isset($_tmp) && is_array($_tmp)) {
        // Merge profile data in
        $_SESSION = array_merge($_SESSION, $_tmp);
        $_user = array_merge($_user, $_tmp);
    }
    return true;
}

/**
 * Get quota settings by quota_id
 * @param $quota_id integer Quota ID
 * @return array
 */
function jrProfile_get_quota($quota_id)
{
    $_rt = jrCore_get_flag("jrprofile_get_quota_{$quota_id}");
    if ($_rt) {
        return $_rt;
    }
    $tb1 = jrCore_db_table_name('jrProfile', 'quota_setting');
    $tb2 = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "SELECT s.`module` AS m, s.`name` AS k, s.`default` AS d, v.`value` AS v FROM {$tb1} s LEFT JOIN {$tb2} v ON (v.`quota_id` = '{$quota_id}' AND v.`module` = s.`module` AND v.`name` = s.`name`)";
    $_rt = jrCore_db_query($req, 'NUMERIC');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    $_qt = array();
    foreach ($_rt as $_v) {
        $_qt["quota_{$_v['m']}_{$_v['k']}"] = (isset($_v['v']) && strlen($_v['v']) > 0) ? $_v['v'] : $_v['d'];
    }
    unset($_rt);
    jrCore_set_flag("jrprofile_get_quota_{$quota_id}", $_qt);
    return $_qt;
}

/**
 * Create a new profile quota
 * @param $name string Name for new Quota
 * @return mixed integer on success, bool false on failure
 */
function jrProfile_create_quota($name)
{
    // Make sure this quota does not already exist
    $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "SELECT quota_id FROM {$tbl} WHERE `name` = 'name' AND `value` = '" . jrCore_db_escape($name) . "'";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt)) {
        return $_rt['quota_id'];
    }
    $tbl = jrCore_db_table_name('jrProfile', 'quota');
    $req = "INSERT INTO {$tbl} (quota_created,quota_updated) VALUES (UNIX_TIMESTAMP(),UNIX_TIMESTAMP())";
    $qid = jrCore_db_query($req, 'INSERT_ID');
    if (isset($qid) && jrCore_checktype($qid, 'number_nz')) {
        jrProfile_set_quota_value('jrProfile', $qid, 'name', $name);
        return $qid;
    }
    return false;
}

/**
 * Delete an Existing Profile Quota
 * @param $id integer Quota ID to delete
 * @return bool
 */
function jrProfile_delete_quota($id)
{
    // Make sure there are no profile's using this quota
    $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "DELETE FROM {$tbl} WHERE quota_id = '{$id}'";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt > 0) {
        return true;
    }
    return false;
}

/**
 * Updates the profile count for a given quota_id
 * @param $quota_id integer Quota ID to update profile count for
 * @return bool
 */
function jrProfile_update_profile_count($quota_id)
{
    $qid = intval($quota_id);
    $tbl = jrCore_db_table_name('jrProfile', 'item_key');
    $req = "SELECT COUNT(_item_id) AS pcount FROM {$tbl} WHERE `key` = 'profile_quota_id' AND `value` = '{$qid}'";
    $_rt = jrCore_db_query($req, 'SINGLE');
    $num = 0;
    if (isset($_rt['pcount']) && jrCore_checktype($_rt['pcount'], 'number_nz')) {
        $num = intval($_rt['pcount']);
    }
    // Update quota value
    jrProfile_set_quota_value('jrProfile', $qid, 'profile_count', $num);
}

/**
 * Create a new entry in the Quota Settings
 * @param $module string Module to create Quota setting for
 * @param $_field array Array of field information for new setting
 * @return bool
 */
function jrProfile_register_quota_setting($module, $_field)
{
    if (!isset($_field['type'])) {
        jrCore_notice('CRI', "jrProfile_register_quota_setting() required field: type missing for setting");
    }

    // example $_field:
    // $_tmp = array(
    //     'name'     => 'from_email',
    //     'label'    => 'from email address',
    //     'default'  => '',
    //     'type'     => 'text',
    //     'validate' => 'email',
    //     'help'     => 'When the system sends an automated / system message, what email address should the email be sent from?',
    //     'section'  => 'general email settings'
    // );
    // Optional:
    //     'min'      => (int) (minimum allow numerical value - validated)
    //     'max'      => (int) (maximum allow numerical value - validated)
    //     'options'  => array() (array of key => value pairs for fields with "select" type

    // See if we have already been called for this module/key in this process
    $key = jrCore_db_escape($_field['name']);
    $_tm = jrCore_get_flag('jrprofile_register_quota_setting');
    if (isset($_tm) && is_array($_tm) && isset($_tm["{$module}_{$key}"])) {
        return true;
    }
    if (!isset($_tm) || !is_array($_tm)) {
        $_tm = array();
    }
    $_tm["{$module}_{$key}"] = 1;
    jrCore_set_flag('jrprofile_register_quota_setting', $_tm);

    // Some items are required for form fields
    $_ri = array_flip(array('name', 'default', 'validate', 'label', 'help'));
    switch ($_field['type']) {
        // we already internally validate hidden and select elements
        case 'hidden':
            unset($_ri['validate'], $_ri['label'], $_ri['help']);
            break;
        case 'checkbox':
            $_field['validate'] = 'onoff';
            break;
        case 'select':
        case 'select_multiple':
        case 'optionlist':
        case 'radio':
            unset($_ri['validate']);
            // Handle field options for select statements if set
            if (isset($_field['options']) && is_array($_field['options'])) {
                $_field['options'] = json_encode($_field['options']);
            }
            elseif (isset($_field['options']) && !function_exists($_field['options'])) {
                // These select options are generated at display time by a function
                jrCore_notice('CRI', "jrProfile_register_quota_setting() option function defined for field: {$_field['name']} does not exist");
            }
            break;
    }
    foreach ($_ri as $k => $v) {
        if (!isset($_field[$k])) {
            jrCore_notice('CRI', "jrProfile_register_quota_setting() required field: {$k} missing for setting: {$_field['name']}");
        }
    }
    // Make sure setting is properly updated
    return jrProfile_update_quota_setting($module, $_field);
}

/**
 * Create a new entry in the Quota Settings for other modules
 * @param $module string Module to create Quota setting for
 * @param $_field array Array of field information for new setting
 * @return bool
 */
function jrProfile_register_global_quota_setting($module, $_field)
{
    $_tmp = jrCore_get_flag('jrprofile_register_global_quota_setting');
    if (!$_tmp) {
        $_tmp = array();
    }
    if (!isset($_tmp[$module])) {
        $_tmp[$module] = array();
    }
    $_tmp[$module][] = $_field;
    jrCore_set_flag('jrprofile_register_global_quota_setting', $_tmp);
    return true;
}

/**
 * Verifies a Quota Setting is configured correctly in the settings
 * table - creates or updates as necessary.
 * @param $module string Module to create quota setting for
 * @param $_field array Array of setting information
 * @return bool
 */
function jrProfile_update_quota_setting($module, $_field)
{
    $tbl = jrCore_db_table_name('jrProfile', 'quota_setting');
    $req = "SELECT `name` FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($module) . "' AND `name` = '" . jrCore_db_escape($_field['name']) . "'";
    $_ex = jrCore_db_query($req, 'SINGLE');
    $_rt = jrCore_db_table_columns('jrProfile', 'quota_setting');

    // Create
    if (!isset($_ex) || !is_array($_ex)) {
        $_cl = array();
        $_vl = array();

        // When creating a NEW entry in settings, our value is set to the default
        $_field['value'] = $_field['default'];

        foreach ($_rt as $k => $v) {
            if (isset($_field[$k])) {
                $_cl[] = "`{$k}`";
                $_vl[] = jrCore_db_escape($_field[$k]);
            }
        }
        if (!isset($_cl) || !is_array($_cl) || count($_cl) < 1) {
            return false;
        }
        $req = "INSERT INTO {$tbl} (`module`,`created`," . implode(',', $_cl) . ") VALUES ('" . jrCore_db_escape($module) . "',UNIX_TIMESTAMP(),'" . implode("','", $_vl) . "')";
    }
    // Update
    else {
        $req = "UPDATE {$tbl} SET ";
        foreach ($_rt as $k => $v) {
            if (isset($_field[$k])) {
                $req .= "`{$k}` = '" . jrCore_db_escape($_field[$k]) . "',";
            }
        }
        if (!isset($req) || !strpos($req, '=')) {
            return false;
        }
        $req = substr($req, 0, strlen($req) - 1) . " WHERE module = '" . jrCore_db_escape($module) . "' AND `name` = '" . jrCore_db_escape($_field['name']) . "' LIMIT 1";
    }
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($_cl) && isset($cnt) && $cnt === 1) {
        jrCore_logger('INF', "validated quota setting for {$module} module: {$_field['name']}");
        return true;
    }
    return false;
}

/**
 * Deletes an existing quota setting from the quota settings table
 * @param $module string Module Name
 * @param $name string Quota Setting Name
 * @return bool
 */
function jrProfile_delete_quota_setting($module, $name)
{
    $tbl = jrCore_db_table_name('jrProfile', 'quota_setting');
    $req = "DELETE FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($module) . "' AND `name` = '" . jrCore_db_escape($name) . "' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
        $req = "DELETE FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($module) . "' AND `name` = '" . jrCore_db_escape($name) . "' LIMIT 1";
        $cnt = jrCore_db_query($req, 'COUNT');
        if (isset($cnt) && $cnt === 1) {
            jrCore_logger('INF', "quota setting {$module}_{$name} was successfully deleted");
            return true;
        }
    }
    return false;
}

/**
 * Update a Quota setting to a new value
 * @param $module string Module to set Quota value for
 * @param $quota_id integer Quota ID to set value for
 * @param $name string Quota setting to set value for
 * @param $value mixed Value to set for $name
 * @return bool
 */
function jrProfile_set_quota_value($module, $quota_id, $name, $value)
{
    global $_user;
    if (!isset($quota_id) || !jrCore_checktype($quota_id, 'number_nz')) {
        return false;
    }
    $uid = (isset($_user['user_name']) && strlen($_user['user_name']) > 0 && isset($_user['user_group']) && $_user['user_group'] == 'master') ? $_user['user_name'] : 'system';
    $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
    // See if we exist
    $qid = intval($quota_id);
    $mod = jrCore_db_escape($module);
    $nam = jrCore_db_escape($name);
    $val = jrCore_db_escape($value);
    $req = "INSERT INTO {$tbl} (`quota_id`,`module`,`name`,`updated`,`value`,`user`) VALUES ('{$qid}','{$mod}','{$nam}',UNIX_TIMESTAMP(),'{$val}','{$uid}') ON DUPLICATE KEY UPDATE `updated` = UNIX_TIMESTAMP(), `value` = '{$val}', `user` = '{$uid}'";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        return true;
    }
    return false;
}

/**
 * Change a user's active profile keys to a new profile
 * @param $_profile array New Profile Info
 * @return array
 */
function jrProfile_change_to_profile($_profile)
{
    global $_user;
    // set all profile info to this profile
    // We need to go through and REMOVE all existing profile entries
    foreach ($_SESSION as $k => $v) {
        if ((strpos($k, '_') === 0 || strpos($k, 'profile_') === 0) && strpos($k, 'profile_home_') !== 0 && !isset($_profile[$k])) {
            unset($_SESSION[$k]);
        }
    }
    // Merge in new profile data in
    $_SESSION = array_merge($_SESSION, $_profile);
    $_SESSION['_user_id'] = (int) $_user['_user_id'];
    $_SESSION['user_active_profile_id'] = (int) $_profile['_profile_id'];
    return $_SESSION;
}

/**
 * Display registered profile tabs
 * @param $active string Active tab
 * @param $module string Module Name
 * @param $profile_url string Profile URL
 * @param $_tabs array Array of tabs as profile_view => profile_title
 * @return bool
 */
function jrProfile_show_profile_tabs($active, $module, $profile_url, $_tabs)
{
    global $_conf;
    // Core Module URL
    $url = jrCore_get_module_url($module);
    $_lang = jrUser_load_lang_strings();
    $_temp = array(
        'tabs' => array()
    );

    foreach ($_tabs as $view => $title) {
        if (is_numeric($title) && isset($_lang[$module][$title])) {
            $title = $_lang[$module][$title];
        }
        if ($view == 'default') {
            $_temp['tabs'][$view] = array(
                'label' => $title,
                'url'   => "{$_conf['jrCore_base_url']}/{$profile_url}/{$url}"
            );
        }
        else {
            $_temp['tabs'][$view] = array(
                'label' => $title,
                'url'   => "{$_conf['jrCore_base_url']}/{$profile_url}/{$url}/{$view}"
            );
        }
        $_temp['tabs'][$view]['id'] = "t{$view}";
        $_temp['tabs'][$view]['class'] = 'page_tab';
    }
    $_temp['tabs'][$active]['active'] = true;
    return jrCore_parse_template('profile_tabs.tpl', $_temp, 'jrProfile');
}

/**
 * Displays a profile for a given profile name
 * @param $_post array global $_post (from jrCore_parse_url())
 * @param $_user array Viewing user info
 * @param $_conf array Global Config
 * @return string returns rendered profile page
 */
function jrProfile_show_profile($_post, $_user, $_conf)
{
    global $_urls;
    unset($_SESSION['designer_referral_url'], $_SESSION['jruser_save_location']);

    // Make sure this is a good module...
    if (isset($_post['option']) && strlen($_post['option']) > 0 && !isset($_urls["{$_post['option']}"])) {
        jrCore_page_not_found();
    }

    // Save our current URL for memory purposes
    $_SESSION['memory_profile_cancel_url'] = jrCore_get_current_url();
    $_lang = jrUser_load_lang_strings();

    // Inside our profile call we can handle the following routing:
    // profile_name                     (Skin/profile_index.tpl)
    // profile_name/module_url          (Module/item_index.tpl  => Skin/profile_item_index.tpl)
    // profile_name/module_url/###/...  (Module/item_detail.tpl => Skin/profile_item_detail.tpl)
    // profile_name/module_url/???/...  (Module/item_list.tpl   => Skin/profile_item_list.tpl)
    //
    // <module_url>/<option>/<_1>/...

    // Get profile info for this profile
    if (!isset($_post['module_url']) || mb_strlen($_post['module_url']) === 0) {
        jrCore_page_not_found();
    }
    $_post['module_url'] = str_replace('_', '-', $_post['module_url']); // JR4 -> JR5 URL change for profiles
    $_rt = jrCore_db_get_item_by_key('jrProfile', 'profile_url', $_post['module_url']);
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_page_not_found();
    }

    // NOTE: We global here so it changes at the global level
    global $_post;
    $_post['_profile_id'] = $_rt['_profile_id'];

    // Set our viewing profile for the user
    if (jrProfile_is_profile_owner($_rt['_profile_id'])) {

        // Switch active profile info
        $_user = jrProfile_change_to_profile($_rt);
    }

    // Check for Profile Privacy - note that privacy options are bypassed for admin users
    elseif (!jrUser_is_admin()) {
        switch ($_rt['profile_private']) {

            // Fully Private Profile...
            case '0':
                if (!jrUser_is_logged_in() || $_user['user_active_profile_id'] != $_rt['_profile_id']) {
                    // We are not a profile owner...
                    jrCore_notice_page('error', $_lang['jrProfile'][16]);
                }
                break;

            // Followers only...
            case '2':
                // Make sure we are installed...
                if (jrCore_module_is_active('jrFollower')) {
                    if (jrUser_is_logged_in()) {
                        if (!jrProfile_is_profile_owner($_rt['_profile_id'])) {
                            $_if = jrFollower_is_follower($_user['_user_id'], $_rt['_profile_id']);
                            if (!$_if || $_if['follow_active'] != '1') {
                                // We are not an owner or follower of this profile, or our follow has not been approved
                                $params = array('profile_id' => $_rt['_profile_id']);
                                $follow = smarty_function_jrFollower_button($params, new stdClass());
                                jrCore_notice_page('error', "{$_lang['jrProfile'][24]}<br><br>{$follow}", false, false, false);
                            }
                        }
                    }
                    else {
                        // Not logged in - tell them to log in
                        jrUser_session_require_login();
                    }
                }
                break;

            // Note that "0" (zero) is Global
        }
    }

    $tmp = '';
    $cch = true;
    $key = false;

    // Module controller - gets everything
    if (isset($_post['option']) && isset($_urls["{$_post['option']}"])) {
        $mod = $_urls["{$_post['option']}"];
        if (is_file(APP_DIR . "/modules/{$mod}/profile.php")) {
            require_once APP_DIR . "/modules/{$mod}/profile.php";
            $active_tab = 'default';
            if (!empty($_post['_1'])) {
                $fnc = "profile_view_{$mod}_{$_post['_1']}";
                if (!function_exists($fnc)) {
                    $fnc = "profile_view_{$mod}_default";
                }
                else {
                    $active_tab = $_post['_1'];
                }
            }
            else {
                $fnc = "profile_view_{$mod}_default";
            }
            if (function_exists($fnc)) {

                jrCore_page_set_no_header_or_footer();
                $old_module = $_post['module'];
                $old_option = $_post['option'];
                $_post['module'] = $mod;
                $_post['option'] = (isset($_post['_1'])) ? $_post['_1'] : '';
                $tmp = $fnc($_rt, $_post, $_user, $_conf);
                if ($tmp) {

                    if ($active_tab != 'default') {
                        $_rt['profile_option_content'] = $tmp;
                        $tmp = jrCore_parse_template('profile_option.tpl', $_rt, 'jrProfile');
                    }

                    // Add in Profile Tabs if any have been registered for this module
                    if (jrProfile_is_profile_owner($_rt['_profile_id'])) {
                        $_tab = jrCore_get_registered_module_features('jrProfile', 'profile_tab');
                        if ($_tab && isset($_tab[$mod])) {
                            $tmp = jrProfile_show_profile_tabs($active_tab, $mod, $_rt['profile_url'], $_tab[$mod]) . $tmp;
                        }
                    }
                    $_rt['profile_item_list_content'] = $tmp;
                    $tmp = jrCore_parse_template('profile_item_list.tpl', $_rt);

                    // In our custom profile handlers, we can add in things like
                    // custom Javascript and CSS...
                    $_pe = jrCore_get_flag('jrcore_page_elements');
                    if ($_pe) {
                        $_rt = $_rt + $_pe;
                    }
                    $cch = false;
                }
                $_post['module'] = $old_module;
                $_post['option'] = $old_option;
            }
        }
    }

    // See if we came out of that with anything...
    if (strlen($tmp) === 0) {

        // Item Details (specific item)
        if (isset($_post['_1']) && jrCore_checktype($_post['_1'], 'number_nz')) {

            // NOTE: It does not matter what comes after _1 - for SEO
            // purposes we should use the <item>_url

            // Save the referring URL here - if the user deletes or modifies this
            // item, we need to be able to send them back where they came from.
            jrCore_create_memory_url('item_delete');

            $mod = $_urls["{$_post['option']}"];
            $_it = jrCore_db_get_item($mod, $_post['_1']);
            if (!isset($_it) || !is_array($_it)) {
                jrCore_page_not_found();
            }
            // Make sure the item belongs to the profile
            if ($_rt['_profile_id'] != $_it['_profile_id']) {
                jrCore_page_not_found();
            }
            // Lastly - check to see if this item is pending approval.  If it is, only
            // admins and the profile owner(s) can view it
            $pfx = jrCore_db_get_prefix($mod);
            if (isset($_it["{$pfx}_pending"]) && $_it["{$pfx}_pending"] == '1') {
                if (!jrProfile_is_profile_owner($_it['_profile_id'])) {
                    jrCore_page_not_found();
                }
            }
            $dir = $mod;

            // See if we have a TITLE field for this item
            $title = $_rt['profile_name'];
            if (isset($_lang[$dir]['menu'])) {
                $title = "{$_lang[$dir]['menu']}/{$_rt['profile_name']}";
            }
            if (isset($_it["{$pfx}_title"])) {
                $title = "{$title}/" . $_it["{$pfx}_title"];
            }
            jrCore_page_title(jrCore_str_to_lower($title));

            $tmp = jrCore_parse_template('item_detail.tpl', array('item' => $_it), $dir);
            unset($_it);
            $_rt['profile_item_detail_content'] = $tmp;
            $tmp = jrCore_parse_template('profile_item_detail.tpl', $_rt);
        }

        // Module Item Index
        elseif (isset($_post['option']) && strlen($_post['option']) > 0 && isset($_urls["{$_post['option']}"]) && (!isset($_post['_1']) || strlen($_post['_1']) === 0)) {

            // Add in Profile Tabs if any have been registered for this module
            $tmp = '';
            if (jrProfile_is_profile_owner($_rt['_profile_id'])) {
                $mod = $_urls["{$_post['option']}"];
                $_tab = jrCore_get_registered_module_features('jrProfile', 'profile_tab');
                if ($_tab && isset($_tab[$mod])) {
                    $tmp = jrProfile_show_profile_tabs('default', $mod, $_rt['profile_url'], $_tab[$mod]);
                }
            }

            // It's a call to a module index - run our index template
            $dir = $_urls["{$_post['option']}"];
            $tmp .= jrCore_parse_template('item_index.tpl', $_rt, $dir);
            $_rt['profile_item_index_content'] = $tmp;
            // Set title to module menu entry
            if (isset($_lang[$dir]['menu'])) {
                jrCore_page_title(jrCore_str_to_lower("{$_rt['profile_name']}/{$_lang[$dir]['menu']}"));
            }
            $tmp = jrCore_parse_template('profile_item_index.tpl', $_rt);
        }

        // Module Profile View - check templates
        elseif (isset($_post['_1']) && strlen($_post['_1']) > 0) {

            // Make sure it is a good module...
            if (!isset($_urls["{$_post['option']}"])) {
                jrCore_page_not_found();
            }
            $mod = $_urls["{$_post['option']}"];
            $dir = null;

            // Check for Skin template
            if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$mod}_item_{$_post['_1']}.tpl")) {
                $tpl = "{$mod}_item_{$_post['_1']}.tpl";
            }
            // Check for Module template
            elseif (is_file(APP_DIR . "/modules/{$mod}/templates/item_{$_post['_1']}.tpl")) {
                $tpl = "item_{$_post['_1']}.tpl";
                $dir = $mod;
            }
            if (isset($tpl)) {
                $tmp = jrCore_parse_template($tpl, $_rt, $dir);
                $_rt['profile_item_list_content'] = $tmp;
                $tmp = jrCore_parse_template('profile_item_list.tpl', $_rt);
            }
        }
    }

    // Profile Index (fall through)
    if (strlen($tmp) === 0) {

        // Send out profile index trigger
        $_rt = jrCore_trigger_event('jrProfile', 'profile_index', $_rt);

        // Set title, parse and return
        jrCore_page_title($_rt['profile_name']);

        // Parse profile_index Template
        $tmp = jrCore_parse_template('profile_index.tpl', $_rt);
    }

    // See if we are cached
    if ($cch) {
        $key = "profile-view-cache-{$_post['_uri']}";
        if ($out = jrCore_is_cached('jrProfile', $key)) {
            $out .= "\n<!--c-->";
            return $out;
        }
    }

    // Send out profile view trigger
    $_rt = jrCore_trigger_event('jrProfile', 'profile_view', $_rt);

    // Pick up header elements set by plugins
    $_tmp = jrCore_get_flag('jrcore_page_elements');
    if ($_tmp) {
        unset($_tmp['page']);
        $_rt = array_merge($_tmp, $_rt);
    }

    // See if this skin is providing a profile header, or if we are using the standard header
    $out = '';
    $hdr = "header.tpl";
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/profile_header.tpl")) {
        $hdr = "profile_header.tpl";
    }
    $out .= jrCore_parse_template($hdr, $_rt);

    $out .= $tmp;

    $ftr = "footer.tpl";
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/profile_footer.tpl")) {
        $ftr = "profile_footer.tpl";
    }
    $out .= jrCore_parse_template($ftr, $_rt);

    // Add to Cache
    if ($key) {
        jrCore_add_to_cache('jrProfile', $key, $out, false, $_rt['_profile_id']);
    }
    return $out;
}

/**
 * Resets cached info for a given profile ID
 * @param $profile_id integer Profile ID to reset cache for
 * @return bool
 */
function jrProfile_reset_cache($profile_id = null)
{
    global $_user;
    if ((!isset($profile_id) || !jrCore_checktype($profile_id, 'number_nz')) && isset($_user['user_active_profile_id'])) {
        $profile_id = (int) $_user['user_active_profile_id'];
    }
    $pid = (int) $profile_id;
    if (isset($pid) && $pid > 0) {
        $tbl = jrCore_db_table_name('jrCore', 'cache');
        $req = "DELETE FROM {$tbl} WHERE (cache_profile_id = '{$pid}' OR cache_item_id LIKE '%,{$pid},%')";
        jrCore_db_query($req);
    }
    return true;
}

/**
 * Returns profile information for the given profile name
 * @return array Returns profile information
 */
function jrProfile_get_quotas()
{
    $_rt = jrCore_get_flag('jrprofile_get_quotas');
    if ($_rt) {
        return $_rt;
    }
    $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "SELECT `quota_id`, `value` FROM {$tbl} WHERE `module` = 'jrProfile' AND `name` = 'name' GROUP BY `quota_id` ORDER BY `value` ASC";
    $_rt = jrCore_db_query($req, 'quota_id');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }
    foreach ($_rt as $k => $_v) {
        $_rt[$k] = $_v['value'];
    }
    jrCore_set_flag('jrprofile_get_quotas', $_rt);
    return $_rt;
}

/**
 * A special function used in the profile "settings" screen
 * @return array
 */
function jrProfile_get_settings_quotas()
{
    $_qt = array();
    if (jrUser_is_admin()) {
        $_qt = jrProfile_get_quotas();
    }
    else {
        // We're a power user and may only have access to selected Quotas
        $key = jrUser_get_profile_home_key('quota_jrUser_power_user_quotas');
        if (strpos($key, ',')) {
            $_qt = array();
            $_aq = jrProfile_get_quotas();
            foreach (explode(',', $key) as $qid) {
                if (isset($_aq[$qid])) {
                    $_qt[$qid] = $_aq[$qid];
                }
            }
        }
        elseif (jrCore_checktype($key, 'number_nz') && isset($_qut[$key])) {
            $_qt = array($key => $_qut[$key]);
        }
    }
    return $_qt;
}

/**
 * Display Quota settings for the master admin
 * @param $module string Module to show Quota settings for
 * @param $_post array global $_post
 * @param $_user array global $_user
 * @param $_conf array global $_conf
 * @return string
 */
function jrProfile_show_module_quota_settings($module, $_post, $_user, $_conf)
{
    global $_mods;
    $_quota = jrCore_get_registered_module_features('jrCore', 'quota_support');
    // Make sure we have a valid quota.php file for this module
    if (!isset($_quota[$module]) && !is_file(APP_DIR . "/modules/{$module}/quota.php")) {
        jrCore_notice('CRI', "unable to open required quota config file: {$module}/quota.php");
    }
    // If we do not get a quota_id, we use the oldest created quota
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        $tbl = jrCore_db_table_name('jrProfile', 'quota');
        $req = "SELECT quota_id FROM {$tbl} ORDER BY quota_created ASC LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (isset($_rt) && is_array($_rt)) {
            $_post['id'] = (int) $_rt['quota_id'];
        }
        else {
            jrCore_notice('CRI', 'unable to retrieve any quotas from database!');
        }
    }

    if (isset($_post['hl']) && strlen($_post['hl']) > 0) {
        jrCore_form_field_hilight($_post['hl']);
    }

    // Bring in quota config
    if (is_file(APP_DIR . "/modules/{$module}/quota.php")) {
        require_once APP_DIR . "/modules/{$module}/quota.php";
        $func = "{$module}_quota_config";
        if (function_exists($func)) {
            $func();
        }
    }

    // Get this module's quota config fields
    $tb1 = jrCore_db_table_name('jrProfile', 'quota_setting');
    $tb2 = jrCore_db_table_name('jrProfile', 'quota_value');
    $mod = jrCore_db_escape($module);
    $req = "SELECT s.*, v.`updated`, v.`value`, v.`user`,
            IF(LENGTH(s.`section`) = 0,'general',s.`section`) AS `section`
            FROM {$tb1} s LEFT JOIN {$tb2} v ON (v.`quota_id` = '{$_post['id']}' AND v.`module` = '{$mod}' AND v.`name` = s.`name`) WHERE s.`module` = '{$mod}' AND s.`type` != 'hidden' ORDER BY FIELD(s.`section`,'access') DESC, s.`order` ASC, s.`section` ASC, s.`name` ASC";
    $_rt = jrCore_db_query($req, 'NUMERIC');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice('CRI', "unable to retrieve quota settings for module: {$module}");
    }

    // Generate our output
    jrCore_page_admin_tabs($module, 'quota');

    // Setup our module jumper
    $url = jrCore_get_module_url('jrCore');

    $subtitle = '<select name="module_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/quota/id={$_post['id']}'\">";
    $_tmpm = array();
    foreach ($_mods as $mod_dir => $_info) {
        $_tmpm[$mod_dir] = $_info['module_name'];
    }
    asort($_tmpm);
    foreach ($_tmpm as $mod_dir => $title) {
        if (!jrCore_module_is_active($mod_dir)) {
            continue;
        }
        if (is_file(APP_DIR . "/modules/{$mod_dir}/quota.php")) {
            if ($mod_dir == $_post['module']) {
                $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
            }
            else {
                $murl = jrCore_get_module_url($mod_dir);
                $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
            }
        }
    }
    $subtitle .= '</select>';
    $subtitle .= '<input type="text" value="search" name="ss" class="form_text form_admin_search" onfocus="if(this.value==\'search\'){this.value=\'\';}" onblur="if(this.value==\'\'){this.value=\'search\';}" onkeypress="if(event && event.keyCode == 13 && this.value.length > 0){ window.location=\'' . $_conf['jrCore_base_url'] . '/' . $url . '/search/ss=\'+ jrE(this.value);return false; }">';
    jrCore_page_banner('Quota Settings', $subtitle);
    // See if we are disabled
    if (!jrCore_module_is_active($module)) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }
    jrCore_get_form_notice();

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => 'admin_save/quota/id=' . intval($_post['id'])
    );
    jrCore_form_create($_tmp);

    // Show our Quota Jumper
    $_tmp = array(
        'name'      => 'id',
        'label'     => 'selected quota',
        'help'      => 'Select the Quota you would like to adjust the settings for',
        'type'      => 'select',
        'options'   => 'jrProfile_get_quotas',
        'value'     => $_post['id'],
        'error_msg' => 'you have selected an invalid quota',
        'validate'  => 'number_nz',
        'onchange'  => "var m=this.options[this.selectedIndex].value;self.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/quota/id='+ m;"
    );
    jrCore_form_field_create($_tmp);

    // Apply to all quotas
    if ($_post['module'] != 'jrProfile') {
        $_tmp = array(
            'name'     => 'apply_to_all_quotas',
            'label'    => 'apply to all quotas',
            'help'     => 'If this option is checked, the quota settings saved here will be applied to all quotas.',
            'type'     => 'checkbox',
            'validate' => 'onoff'
        );
        jrCore_form_field_create($_tmp);
    }

    foreach ($_rt as $_field) {
        if (!isset($_field['value']) || strlen($_field['value']) === 0) {
            $_field['value'] = $_field['default'];
        }
        // See if this is the "allowed" field - if so, our module can change
        // the label and help that is shown to the user
        if (isset($_field['name']) && $_field['name'] == 'allowed' && isset($_quota[$module]['on']) && $_quota[$module]['on'] != '1') {
            if (is_array($_quota[$module]['on'])) {
                // Both label and help
                $_field = array_merge($_field,$_quota[$module]['on']);
            }
            else {
                // Just label
                $_field['label'] = $_quota[$module]['on'];
            }
        }
        jrCore_form_field_create($_field);
    }

    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * Return true if a module has been enabled for a Quota
 * @param $module string Module to check for
 * @param $smarty object Current Smarty object
 * @param $value string on|off If provided will be used instead of smarty template value
 * @return bool
 */
function jrProfile_is_allowed_by_quota($module, &$smarty, $value = null)
{
    if (!is_null($value) && $value == 'on') {
        return true;
    }
    // Not provided - find it
    elseif (isset($smarty->tpl_vars['item']->value["quota_{$module}_allowed"]) && $smarty->tpl_vars['item']->value["quota_{$module}_allowed"] == 'on') {
        return true;
    }
    elseif (isset($smarty->tpl_vars["quota_{$module}_allowed"]->value) && $smarty->tpl_vars["quota_{$module}_allowed"]->value == 'on') {
        return true;
    }
    return false;
}

//-------------------------------------------------------
// SMARTY FUNCTIONS
//-------------------------------------------------------

/**
 * Profile Statistics for modules that have registered
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrProfile_stats($params, $smarty)
{
    if (!isset($params['profile_id']) || !jrCore_checktype($params['profile_id'], 'number_nz')) {
        return 'jrProfile_stats: invalid profile_id parameter';
    }
    if (!isset($params['template']{1})) {
        return 'jrProfile_stats: template parameter required';
    }
    $_tmp = jrCore_get_registered_module_features('jrProfile', 'profile_stats');
    if (!$_tmp) {
        // No registered modules
        return '';
    }
    $_prf = jrCore_db_get_item('jrProfile', $params['profile_id'], true);
    if (!$_prf || !is_array($_prf)) {
        // Invalid profile id
        return '';
    }
    $_prf['_stats'] = array();

    $_lang = jrUser_load_lang_strings();
    foreach ($_tmp as $mod => $_stats) {
        foreach ($_stats as $key => $title) {
            if (is_numeric($title) && isset($_lang[$mod][$title])) {
                $title = $_lang[$mod][$title];
            }
            // See if we have been given a function
            $count = false;
            if (function_exists($key)) {
                $count = $key($_prf);
            }
            elseif (isset($_prf[$key]) && $_prf[$key] > 0) {
                $count = $_prf[$key];
            }
            if ($count) {
                $_prf['_stats'][$title] = array(
                    'count'  => $count,
                    'module' => $mod
                );
            }
        }
    }
    $out = '';
    if (isset($_prf['_stats']) && is_array($_prf['_stats'])) {
        $out = jrCore_parse_template($params['template'], $_prf, 'jrProfile');
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}

/**
 * Creates a URL to a specific Item page in a Profile
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrProfile_item_url($params, $smarty)
{
    global $_conf;
    if (!isset($params['module'])) {
        return 'jrProfile_item_url: module parameter required';
    }
    if (!isset($params['profile_url'])) {
        return 'jrProfile_item_url: profile_url parameter required';
    }
    if (!isset($params['item_id']) || !jrCore_checktype($params['item_id'], 'number_nz')) {
        return 'jrProfile_item_url: item_id required';
    }
    if (!isset($params['title']) || strlen($params['title']) === 0) {
        return 'jrProfile_item_url: title required';
    }
    $url = jrCore_get_module_url($params['module']);
    $nam = jrCore_url_string($params['title']);
    $out = "{$_conf['jrCore_base_url']}/{$params['profile_url']}/{$url}/{$params['item_id']}/{$nam}";
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}

/**
 * Creates a dynamic Profile Menu based on the modules and options
 * That are available to a profile within their Profile Quota
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrProfile_menu($params, $smarty)
{
    global $_conf, $_mods;
    if (!isset($params['template']{0})) {
        return 'jrProfile_menu: template parameter required';
    }
    if (!isset($params['profile_quota_id']) || !jrCore_checktype($params['profile_quota_id'], 'number_nz')) {
        return 'jrProfile_menu: valid profile_quota_id parameter required';
    }
    $_qt = jrProfile_get_quota($params['profile_quota_id']);
    if (!isset($_qt) || !is_array($_qt)) {
        return 'jrProfile_menu: invalid profile_quota_id parameter - quota does not exist';
    }
    if (!isset($params['profile_url'])) {
        return 'jrProfile_menu: valid profile_url parameter required';
    }
    // We need to go through EACH module that has been passed in
    // and see if the module is ACTIVE and ALLOWED
    if (!isset($params['modules']) || strlen($params['modules']) === 0) {
        $params['modules'] = array_keys($_mods);
        sort($params['modules']);
    }
    else {
        $params['modules'] = explode(',', $params['modules']);
    }
    // Check for excluded modules
    if (isset($params['exclude_modules']) && strlen($params['exclude_modules']) > 0) {
        $_exc = explode(',', $params['exclude_modules']);
        $_exc = array_flip($_exc);
    }
    if (!isset($params['modules']) || !is_array($params['modules']) || count($params['modules']) === 0) {
        return '';
    }
    // Check for alternate module targets
    if (isset($params['targets']{0}) && strpos($params['targets'], ':')) {
        $_tgt = explode(',', $params['targets']);
        if (isset($_tgt) && is_array($_tgt)) {
            foreach ($_tgt as $k => $target) {
                list($mod, $tgt) = explode(':', $target, 2);
                $mod = trim($mod);
                $tgt = trim($tgt);
                if (jrCore_module_is_active($mod) && isset($tgt) && strlen($tgt) > 0) {
                    $_tgt[$mod] = $tgt;
                }
                unset($_tgt[$k]);
            }
        }
    }

    // See if we are ordering our output
    if (isset($params['order']) && strlen($params['order']) > 0) {
        $_ord = explode(',', $params['order']);
        if (isset($_ord) && is_array($_ord)) {
            $_new = array();
            foreach ($_ord as $mod) {
                $_new[$mod] = $mod;
            }
            foreach ($params['modules'] as $mod) {
                if (!isset($_new[$mod])) {
                    $_new[$mod] = $mod;
                }
            }
            $params['modules'] = $_new;
            unset($_new);
        }
    }

    // BY default a menu button will NOT show if there are no entries in the DS
    // for that specific module/profile_id. We can override that behavior by
    // setting which modules should always show.
    $_show = false;
    if (isset($params['always_show']) && strlen($params['always_show']) > 0) {
        $_show = array_flip(explode(',', $params['always_show']));
    }

    // See if we are requiring a login
    $_login = array();
    if (isset($params['require_login']) && strlen($params['require_login']) > 0) {
        $_login = array_flip(explode(',', $params['require_login']));
    }

    $url = jrCore_get_current_url();
    // Bring in language strings
    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Get loaded template vars...
    $_prf = $smarty->getTemplateVars();

    // Modules can register "exclude", "always_show" and "require_login"
    $_rm = jrCore_get_registered_module_features('jrProfile', 'profile_menu');

    $_rt = array('_items' => array());
    foreach ($params['modules'] as $module) {
        switch ($module) {

            case 'jrCore':
            case 'jrProfile':
            case 'jrUser':
            case 'jrMailer':
                continue 2;
                break;

            default:

                // Module is NOT active
                if (!jrCore_module_is_active($module)) {
                    continue 2;
                }

                // Module has been purposely excluded (parameter override)
                if (isset($_exc[$module])) {
                    continue 2;
                }
                elseif (isset($_rm[$module]['exclude']) && $_rm[$module]['exclude'] !== false) {
                    continue 2;
                }

                // Check that we are not requiring a login (parameter override)
                if (isset($_login[$module]) && !jrUser_is_logged_in()) {
                    continue 2;
                }
                elseif (isset($_rm[$module]['require_login']) && $_rm[$module]['require_login'] !== false && !jrUser_is_logged_in()) {
                    continue 2;
                }

                // See if this Profile's Quota allows access to the module
                if (isset($_qt["quota_{$module}_allowed"]) && $_qt["quota_{$module}_allowed"] != 'on') {
                    continue 2;
                }

                // If this module REQUIRES another module, and the other module is excluded, we don't show
                if (isset($_mods[$module]['module_requires']{1})) {
                    $_req = explode(',', $_mods[$module]['module_requires']);
                    if (isset($_req) && is_array($_req)) {
                        foreach ($_req as $rmod) {
                            if (isset($_exc[$rmod]) || !isset($_mods[$rmod]) || isset($_qt["quota_{$rmod}_allowed"]) && $_qt["quota_{$rmod}_allowed"] != 'on' || isset($_login[$rmod]) && !jrUser_is_logged_in()) {
                                continue 3;
                            }
                        }
                    }
                }

                // Our module must have an item_index.tpl file...
                if (!is_file(APP_DIR . "/modules/{$module}/templates/item_index.tpl")) {
                    continue 2;
                }

                if (isset($_rm[$module]['always_show']) && $_rm[$module]['always_show'] !== false) {
                    $_show[$module] = true;
                }

                // See if we have been given an alternate target
                $mod_url = jrCore_get_module_url($module);
                $target = $mod_url;
                if (isset($_tgt[$module])) {
                    $target .= '/' . $_tgt[$module];
                }
                // If there are NO ITEMS of this type, we only show the menu option to profile owners so they can create a new one.
                // [profile_jrAction_item_count] => 49
                // [profile_jrAudio_item_count] => 18
                if (!isset($_show[$module]) && (!isset($_prf["profile_{$module}_item_count"]) || $_prf["profile_{$module}_item_count"] == '0')) {
                    if (!jrProfile_is_profile_owner($_prf['_profile_id'])) {
                        continue;
                    }
                }
                $_rt['_items'][$module] = array(
                    'active'     => 0,
                    'module_url' => $mod_url,
                    'label'      => (isset($_lang[$module]['menu'])) ? $_lang[$module]['menu'] : $mod_url,
                    'target'     => "{$_conf['jrCore_base_url']}/{$params['profile_url']}/{$target}"
                );
                if (strpos($url, $_rt['_items'][$module]['target']) === 0) {
                    $_rt['_items'][$module]['active'] = 1;
                }
                break;
        }
    }
    $out = '';
    if (isset($_rt['_items']) && is_array($_rt['_items']) && count($_rt['_items']) > 0) {
        // Process template
        $out = jrCore_parse_template($params['template'], $_rt);
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}
