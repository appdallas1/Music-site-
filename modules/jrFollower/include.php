<?php
/**
 * Jamroom 5 jrFollower module
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
 * meta
 */
function jrFollower_meta()
{
    $_tmp = array(
        'name'        => 'Profile Followers',
        'url'         => 'follow',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Users can &quot;follow&quot; other User profiles',
        'category'    => 'profiles'
    );
    return $_tmp;
}

/**
 * init
 */
function jrFollower_init()
{
    // Register our custom JS
    jrCore_register_module_feature('jrCore', 'javascript', 'jrFollower', 'jrFollower.js');

    // Register our CSS
    jrCore_register_module_feature('jrCore', 'css', 'jrFollower', 'jrFollower.css');

    // Let the core Action System know we are adding actions to followers Support
    jrCore_register_module_feature('jrCore', 'action_support', 'jrFollower', 'create', 'item_action.tpl');

    // follower notifications
    $_tmp = array(
        'label' => 9, // 'new pending follower'
        'help'  => 23 // 'If you are approving new followers, do you want to be notified when a new follower is waiting to be approved?'
    );
    jrCore_register_module_feature('jrUser', 'notification', 'jrFollower', 'follower_pending', $_tmp);

    $_tmp = array(
        'label' => 10, // 'new follower'
        'help'  => 24 // 'Do you want to be notified when you get a new follower?'
    );
    jrCore_register_module_feature('jrUser', 'notification', 'jrFollower', 'new_follower', $_tmp);

    $_tmp = array(
        'label' => 11, // 'follow approved'
        'help'  => 25 // 'Do you want to be notified if your pending follow request for another profile is approved?'
    );
    jrCore_register_module_feature('jrUser', 'notification', 'jrFollower', 'follow_approved', $_tmp);

    // Skin menu link to Pending Followers
    $_tmp = array(
        'group'    => 'user',
        'label'    => 26, // 'followers'
        'url'      => 'browse',
        'function' => 'jrFollower_pending_count'
    );
    jrCore_register_module_feature('jrCore', 'skin_menu_item', 'jrFollower', 'pending_followers_link', $_tmp);

    // We provide an "include_followed" search param to allow our Action Lists to show out followers
    jrCore_register_event_listener('jrCore', 'db_search_params', 'jrFollower_db_search_params_listener');
    return true;
}

/**
 * Add support for "include_followed" jrCore_list param for jrAction lists
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrFollower_db_search_params_listener($_data, $_user, $_conf, $_args, $event)
{
    if (!isset($_args['module']) || $_args['module'] !== 'jrAction') {
        return $_data;
    }
    // include_followed="true"
    if (isset($_data['include_followed']) && $_data['include_followed'] == true && isset($_data['profile_id'])) {
        // We need to get the profile's users of this profile follow
        $_us = jrProfile_get_owner_info($_data['profile_id']);
        if (isset($_us) && is_array($_us)) {
            $_pr = array($_data['profile_id']);
            foreach ($_us as $_user) {
                $_tm = jrFollower_get_profiles_followed($_user['_user_id']);
                if (isset($_tm) && is_array($_tm)) {
                    $_pr = array_merge($_pr, $_tm);
                }
            }
            if (isset($_pr) && is_array($_pr)) {
                // Remove any existing _profile_id entries
                if (isset($_data['search']) && is_array($_data['search'])) {
                    foreach ($_data['search'] as $k => $v) {
                        if (strpos(trim($v), '_profile_id') === 0) {
                            unset($_data['search'][$k]);
                        }
                    }
                }
                $_data['search'][] = "_profile_id in " . implode(',', $_pr);
            }
        }
    }
    return $_data;
}

/**
 * Get number of Followers
 * @param array $_conf Global Config
 * @param array $_user User Information
 * @return int Number of unread Private Notes
 */
function jrFollower_pending_count($_conf, $_user)
{
    $pid = jrUser_get_profile_home_key('_profile_id');
    if ($tmp = jrCore_is_cached('jrFollower', "follower_count_{$pid}")) {
        if ($tmp > 0) {
            return $tmp;
        }
        return true;
    }
    $_sc = array(
        'search'         => array(
            "follow_profile_id = {$pid}",
            "follow_active = 0"
        ),
        'limit'          => 250,
        'skip_triggers'  => true,
        'privacy_check'  => false,
        'ignore_pending' => true,
        'return_count'   => true
    );
    $tot = (int) jrCore_db_search_items('jrFollower', $_sc);
    jrCore_add_to_cache('jrFollower', "follower_count_{$pid}", $tot, 0, 0, false);
    if ($tot > 0) {
        return $tot;
    }
    return true;
}

/**
 * Returns an array of profiles a given user_id follows
 * @param $user_id string User ID
 * @return mixed Array of profile IDs or false if none
 */
function jrFollower_get_profiles_followed($user_id)
{
    $_rt = jrCore_get_flag("jrfollower_get_profiles_followed_{$user_id}");
    if (!$_rt) {
        // DO NOT USE jrCore_db_search_items here!
        $tbl = jrCore_db_table_name('jrFollower', 'item_key');
        $req = "SELECT a.`value` AS i FROM {$tbl} a
                  LEFT JOIN {$tbl} b ON (b.`_item_id` = a.`_item_id` AND b.`key` = '_user_id')
                  LEFT JOIN {$tbl} c ON (c.`_item_id` = a.`_item_id` AND c.`key` = 'follow_active')
                 WHERE a.`key` = 'follow_profile_id'
                   AND b.`value` = '" . intval($user_id) . "'
                   AND c.`value` = '1'";
        $_rt = jrCore_db_query($req, 'i', false, 'i');
        if (isset($_rt) && is_array($_rt)) {
            jrCore_set_flag("jrfollower_get_profiles_followed_{$user_id}", array_keys($_rt));
        }
        else {
            jrCore_set_flag("jrfollower_get_profiles_followed_{$user_id}", 'no_profiles');
        }
    }
    if ($_rt == 'no_profiles') {
        return false;
    }
    return $_rt;
}

/**
 * Returns an array (user_id => user_name) of users following the given profile_id
 * @param $profile_id
 * @return bool|mixed
 */
function jrFollower_get_users_following($profile_id)
{
    $tbl = jrCore_db_table_name('jrFollower', 'item_key');
    $req = "SELECT a.`value` AS i FROM {$tbl} a LEFT JOIN {$tbl} b ON (b.`_item_id` = a.`_item_id` AND b.`key` = 'follow_profile_id')
             WHERE a.`key` = '_user_id' AND b.`value` = '" . intval($profile_id) . "'";
    $_rt = jrCore_db_query($req, 'i', false, 'i');
    if (isset($_rt) && is_array($_rt)) {
        $_sp = array(
            'search'                 => array(
                "_user_id IN " . implode(',', array_keys($_rt))
            ),
            'order_by'               => array(
                'user_name' => 'desc'
            ),
            'limit'                  => 2500,
            'return_keys'            => array('_user_id', 'user_name'),
            'exclude_jrProfile_keys' => true
        );
        $_rt = jrCore_db_search_items('jrUser', $_sp);
        if (isset($_rt) && is_array($_rt['_items'])) {
            $_us = array();
            foreach ($_rt['_items'] as $v) {
                $_us["{$v['_user_id']}"] = $v['user_name'];
            }
            return $_us;
        }
        return false;
    }
    return false;
}

/**
 * Return follower info if user is a follower
 * @param $user_id string User ID
 * @param $profile_id string Profile ID
 * @return bool
 */
function jrFollower_is_follower($user_id, $profile_id)
{
    // Make sure user is a follower
    $_sc = array(
        'search'                 => array(
            "_user_id = {$user_id}",
            "follow_profile_id = {$profile_id}"
        ),
        'exclude_jrUser_keys'    => true,
        'exclude_jrProfile_keys' => true,
        'privacy_check'          => false,
        'limit'                  => 1
    );
    $_rt = jrCore_db_search_items('jrFollower', $_sc);
    if (isset($_rt) && is_array($_rt) && isset($_rt['_items']) && isset($_rt['_items'][0])) {
        return $_rt['_items'][0];
    }
    return false;
}

/**
 * Return the number of profiles a user is following
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrFollower_following_count($params, $smarty)
{
    if (!isset($params['user_id']) || !jrCore_checktype($params['user_id'], 'number_nz')) {
        return 'jrFollower_following_count: user_id required';
    }
    $_sc = array(
        'search'                 => array(
            "_user_id = {$params['user_id']}",
            "follow_active = 1"
        ),
        'return_count'           => true,
        'exclude_jrUser_keys'    => true,
        'exclude_jrProfile_keys' => true,
        'privacy_check'          => false
    );
    $cnt = jrCore_db_search_items('jrFollower', $_sc);
    $num = 0;
    if (isset($cnt) && jrCore_checktype($cnt, 'number_nz')) {
        $num = $cnt;
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $num);
        return '';
    }
    return $num;
}

/**
 * Creates a Follow/Unfollow button for logged in users on a profile
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrFollower_button($params, $smarty)
{
    global $_conf, $_user;
    if (!jrUser_is_logged_in()) {
        return '';
    }
    // we must get a profile id
    if (!isset($params['profile_id']) || !jrCore_checktype($params['profile_id'], 'number_nz')) {
        return 'jrFollower_button: profile_id required';
    }
    // Enabled?
    if (!jrCore_module_is_active('jrFollower')) {
        return '';
    }
    // If we are viewing our own profile....
    if (jrUser_get_profile_home_key('_profile_id') == $params['profile_id']) {
        return '';
    }
    $params['profile_id'] = (int) $params['profile_id'];
    $_lang = jrUser_load_lang_strings();

    // Figure template
    $tpl = 'button_follow.tpl';
    $val = $_lang['jrFollower'][1];
    if ($_rt = jrFollower_is_follower($_user['_user_id'], $params['profile_id'])) {
        // See if we are pending or active...
        if (isset($_rt['follow_active']) && $_rt['follow_active'] != '1') {
            $tpl = 'button_pending.tpl';
            $val = $_lang['jrFollower'][5];
        }
        else {
            $tpl = 'button_following.tpl';
            $val = $_lang['jrFollower'][2];
        }
    }
    $params['value'] = $val;
    if (!isset($params['title'])) {
        $params['title'] = $val;
    }
    if (isset($params['title']) && jrCore_checktype($params['title'], 'number_nz') && isset($_lang["{$_conf['jrCore_active_skin']}"]["{$params['title']}"])) {
        $params['title'] = $_lang["{$_conf['jrCore_active_skin']}"]["{$params['title']}"];
    }
    $params['title'] = htmlentities($params['title']);

    // process and return
    $out = jrCore_parse_template($tpl, $params, 'jrFollower');
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}
