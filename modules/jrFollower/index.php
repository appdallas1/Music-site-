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

//------------------------------
// follow
//------------------------------
function view_jrFollower_follow($_post, $_user, $_conf)
{
    // [_uri] => /fans/follow/5/__ajax=1
    // [module_url] => fans
    // [module] => jrFollower
    // [option] => follow
    // [_1] => 5 (profile_id to be followed)
    // [__ajax] => 1

    jrUser_session_require_login();
    $_lang = jrUser_load_lang_strings();
    $pid = (int)$_post['_1'];

    // We need to see if this profile is requiring approval before
    // any new follower can join up.
    $_pi = jrCore_db_get_item('jrProfile', $pid);
    $act = 1;
    if (isset($_pi['profile_jrFollower_approve']) && $_pi['profile_jrFollower_approve'] == 'on') {
        $act = 0;
    }
    // First - see if this user is already following
    $_rt = jrFollower_is_follower($_user['_user_id'], $pid);
    if ($_rt) {
        // User is already a follower
        return json_encode(array('OK' => 1, 'VALUE' => $_lang['jrFollower'][2]));
    }
    // Create our new following entry
    $_dt = array(
        'follow_profile_id' => $pid,
        'follow_active'     => $act
    );
    $_cr = array(
        '_profile_id' => jrUser_get_profile_home_key('_profile_id')
    );
    $fid = jrCore_db_create_item('jrFollower', $_dt, $_cr, $pid);
    if (isset($fid) && jrCore_checktype($fid, 'number_nz')) {
        $_owners = jrProfile_get_owner_info($pid);
        // If we are not active...
        if ($act === 0) {
            // Send out email to profile owners letting them know of the new follower
            if (isset($_owners) && is_array($_owners)) {
                $_rp = array(
                    'system_name'          => $_conf['jrCore_system_name'],
                    'follower_name'        => $_user['user_name'],
                    'follower_url'         => "{$_conf['jrCore_base_url']}/" . jrUser_get_profile_home_key('profile_url'),
                    'approve_follower_url' => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/approve/{$pid}/{$_user['_user_id']}",
                    'delete_follower_url'  => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/delete/{$pid}/{$_user['_user_id']}"
                );
                list($sub, $msg) = jrCore_parse_email_templates('jrFollower', 'approve', $_rp);
                foreach ($_owners as $_o) {
                    jrUser_notify($_o['_user_id'], 0, 'jrFollower', 'follower_pending', $sub, $msg);
                }
            }
            return json_encode(array('PENDING' => 1, 'VALUE' => $_lang['jrFollower'][5]));
        }
        else {
            if (isset($_owners) && is_array($_owners)) {
                $_rp = array(
                    'system_name'          => $_conf['jrCore_system_name'],
                    'follower_name'        => $_user['user_name'],
                    'follower_profile_url' => "{$_conf['jrCore_base_url']}/" . jrUser_get_profile_home_key('profile_url')
                );
                list($sub, $msg) = jrCore_parse_email_templates('jrFollower', 'new_follower', $_rp);
                foreach ($_owners as $_o) {
                    jrUser_notify($_o['_user_id'], 0, 'jrFollower', 'new_follower', $sub, $msg);
                }
            }
            // Add to Actions...
            jrCore_run_module_function('jrAction_save', 'create', 'jrFollower', $fid, $_pi, false);
            jrProfile_reset_cache($pid);
            jrProfile_reset_cache(jrUser_get_profile_home_key('_profile_id'));
            return json_encode(array('OK' => 1, 'VALUE' => $_lang['jrFollower'][2]));
        }
    }
    return json_encode(array('error' => 'unable to create follow request - please try again'));
}

//------------------------------
// unfollow
//------------------------------
function view_jrFollower_unfollow($_post, $_user, $_conf)
{
    // [_uri] => /fans/unfollow/5/__ajax=1
    // [module_url] => fans
    // [module] => jrFollower
    // [option] => follow
    // [_1] => 5 (profile_id to no longer followed)
    // [__ajax] => 1
    jrUser_session_require_login();
    $_lang = jrUser_load_lang_strings();
    $pid = (int)$_post['_1'];

    // Make sure user is a follower
    $_rt = jrFollower_is_follower($_user['_user_id'], $pid);
    if ($_rt) {
        jrCore_db_delete_item('jrFollower', $_rt['_item_id'], true, $pid);
    }
    return json_encode(array('OK' => 1, 'VALUE' => $_lang['jrFollower'][1]));
}

//------------------------------
// browse
//------------------------------
function view_jrFollower_browse($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    jrCore_page_banner(26);

    $pid = jrUser_get_profile_home_key('_profile_id');
    $_sc = array(
        'search'                       => array(
            "follow_profile_id = {$pid}"
        ),
        'pagebreak'                    => 12,
        'page'                         => 1,
        'order_by'                     => array(
            '_created' => 'numerical_desc'
        ),
        'return_keys'                  => array('_user_id', '_created', 'follow_active', 'user_name', 'profile_name', 'profile_url'),
        'exclude_jrProfile_quota_keys' => true,
        'privacy_check'                => false,
        'ignore_pending'               => true
    );
    if (isset($_post['p']) && jrCore_checktype($_post['p'], 'number_nz')) {
        $_pr['page'] = (int)$_post['p'];
    }
    $_us = jrCore_db_search_items('jrFollower', $_sc);
    $_ln = jrUser_load_lang_strings();

    $dat = array();
    $dat[1]['title'] = '&nbsp;';
    $dat[1]['width'] = '5%';
    $dat[2]['title'] = $_ln['jrFollower'][27]; // 'user name'
    $dat[2]['width'] = '35%';
    $dat[3]['title'] = $_ln['jrFollower'][28]; // 'profile name'
    $dat[3]['width'] = '30%';
    $dat[4]['title'] = $_ln['jrFollower'][29]; // 'follower since'
    $dat[4]['width'] = '20%';
    $dat[5]['title'] = $_ln['jrFollower'][30]; // 'approve'
    $dat[5]['width'] = '5%';
    $dat[6]['title'] = $_ln['jrFollower'][31]; // 'delete'
    $dat[6]['width'] = '5%';
    jrCore_page_table_header($dat);

    if (isset($_us['_items']) && is_array($_us['_items'])) {

        $murl = jrCore_get_module_url('jrUser');
        foreach ($_us['_items'] as $_usr) {
            $dat = array();
            $dat[1]['title'] = "<img src=\"{$_conf['jrCore_base_url']}/{$murl}/image/user_image/{$_usr['_user_id']}/xsmall/crop=auto\" alt=\"{$_usr['user_name']}\" title=\"{$_usr['user_name']}\">";
            $dat[2]['title'] = '<h3>' . $_usr['user_name'] . '</h3>';
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = "<a href=\"{$_conf['jrCore_base_url']}/{$_usr['profile_url']}\">{$_usr['profile_name']}</a>";
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = jrCore_format_time($_usr['_created']);
            $dat[4]['class'] = 'center';
            if (isset($_usr['follow_active']) && $_usr['follow_active'] == '0') {
                $dat[5]['title'] = jrCore_page_button("a{$_usr['_user_id']}", 'approve', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/approve/{$pid}/{$_usr['_user_id']}'");
            }
            else {
                $dat[5]['title'] = '-';
            }
            $dat[5]['class'] = 'center';
            $dat[6]['title'] = jrCore_page_button("d{$_usr['_user_id']}", 'delete', "if(confirm('". addslashes($_ln['jrFollower'][33]) ."')){ window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/delete/{$pid}/{$_usr['_user_id']}' }");
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_pager($_us);
    }
    else {
        $dat = array();
        $dat[1]['title'] = "<p>{$_ln['jrFollower'][32]}</p>";
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
    jrCore_page_display();
}

//------------------------------
// approve
//------------------------------
function view_jrFollower_approve($_post, $_user, $_conf)
{
    // [_uri] => /fans/approve/1/5/__ajax=1
    // [module_url] => fans
    // [module] => jrFollower
    // [option] => follow
    // [_1] => 1 (profile_id)
    // [_2] => 5 (user_id being approved)
    // [__ajax] => 1
    jrUser_session_require_login();
    jrUser_load_lang_strings();

    $pid = (int)$_post['_1'];
    $uid = (int)$_post['_2'];

    // Make sure this user has access to this profile
    if (!jrProfile_is_profile_owner($pid)) {
        jrUser_not_authorized();
    }

    // Make sure follow exists...
    $_rt = jrFollower_is_follower($uid, $pid);
    if (!$_rt) {
        jrCore_notice_page('error', 'User does not appear to have a following entry - please try again and ensure you are using the full URL you received in your email');
    }
    $_dt = array(
        'follow_active' => 1
    );
    jrCore_db_update_item('jrFollower', $_rt['_item_id'], $_dt);

    // Get profile info of user that we just approved
    $_pr = jrCore_db_get_item('jrProfile', $pid);

    // We only send the email on first activation
    $_rp = array(
        'profile_name' => $_pr['profile_name'],
        'profile_url'  => "{$_conf['jrCore_base_url']}/{$_pr['profile_url']}"
    );
    list($sub, $msg) = jrCore_parse_email_templates('jrFollower', 'follower_approved', $_rp);
    jrUser_notify($uid, 'jrFollower', 0, 'follow_approved', $sub, $msg);
    jrProfile_reset_cache($pid);
    if (strpos(jrCore_get_local_referrer(), "{$_post['module_url']}/browse")) {
        jrCore_location('referrer');
    }
    jrCore_notice_page('success', 'The new follower has been approved!');
}

//------------------------------
// delete
//------------------------------
function view_jrFollower_delete($_post, $_user, $_conf)
{
    // [_uri] => /fans/delete/1/5/__ajax=1
    // [module_url] => fans
    // [module] => jrFollower
    // [option] => follow
    // [_1] => 1 (profile_id)
    // [_2] => 5 (user_id being approved)
    // [__ajax] => 1
    jrUser_session_require_login();
    $pid = (int)$_post['_1'];
    $uid = (int)$_post['_2'];

    // Make sure this user has access to this profile
    if (!jrProfile_is_profile_owner($pid)) {
        jrUser_not_authorized();
    }

    // Make sure follow exists...
    $_rt = jrFollower_is_follower($uid, $pid);
    if ($_rt) {
        jrCore_db_delete_item('jrFollower', $_rt['_item_id']);
    }
    jrProfile_reset_cache($pid);
    if (strpos(jrCore_get_local_referrer(), "{$_post['module_url']}/browse")) {
        jrCore_location('referrer');
    }
    jrCore_notice_page('success', 'The follower has been successfully deleted!');
}
