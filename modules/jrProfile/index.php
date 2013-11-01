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

//------------------------------
// get_profile_users
//------------------------------
function view_jrProfile_get_profile_users($_post, $_user, $_conf)
{
    jrUser_admin_only();
    $tbl = jrCore_db_table_name('jrUser', 'item_key');
    $req = "SELECT `_item_id`, `value` FROM {$tbl} WHERE `key` = 'user_name' AND `value` LIKE '" . jrCore_db_escape($_post['q']) . "%' ORDER BY `value` ASC";
    $_sel = jrCore_db_query($req, '_item_id', false, 'value');
    return jrCore_live_search_results('profile_user_id', $_sel);
}

//------------------------------
// list_profiles (power,multi)
//------------------------------
function view_jrProfile_list_profiles($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    if (!jrUser_is_power_user() && !jrUser_is_multi_user()) {
        jrCore_location("{$_conf['jrCore_base_url']}/{$_user['profile_url']}");
    }

    // We're a power user or multi user and want to see the list of
    // profiles that we have access to - list them out here
    jrCore_page_banner('Your Profiles');

    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "SELECT profile_id FROM {$tbl} WHERE user_id = '" . intval($_user['_user_id']) . "'";
    $_rt = jrCore_db_query($req, 'profile_id');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice_page('error', 'Unable to retrieve any profiles from the database - please try again');
    }

    $_sp = array(
        'search'        => array(
            '_profile_id in ' . implode(',', array_keys($_rt))
        ),
        'order_by'      => array(
            'profile_name' => 'desc'
        ),
        'limit'         => 1000,
        'privacy_check' => false
    );
    $_rt = jrCore_db_search_items('jrProfile', $_sp);
    if (!isset($_rt) || !is_array($_rt['_items'])) {
        jrCore_notice_page('error', 'Unable to retrieve any profiles from the database - please try again');
    }

    $html = '<div class="profile_grid">';
    foreach ($_rt['_items'] as $_pr) {
        $html .= "<div class=\"item\" style=\"float:left;text-align:center\">
        <a href=\"{$_conf['jrCore_base_url']}/{$_pr['profile_url']}\"><img src=\"{$_conf['jrCore_base_url']}/{$_post['module_url']}/image/profile_image/{$_pr['_profile_id']}/icon/crop=auto\"></a>
        <br><a href=\"{$_conf['jrCore_base_url']}/{$_pr['profile_url']}\">{$_pr['profile_name']}</a>
        </div>";
    }
    $html .= '</div>';

    $_tmp = array(
        'label' => 'Your Profiles',
        'type'  => 'custom',
        'html'  => $html
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_cancel_button('referrer');
    jrCore_page_display();
}

//------------------------------
// create
//------------------------------
function view_jrProfile_create($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    // Make sure user is allowed to create profiles....
    if (!jrUser_is_power_user()) {
        jrUser_not_authorized();
    }

    // If this a master admin creating...
    if (jrUser_is_master()) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs('jrProfile');
    }

    // Show Quota picker - not that Power Users may/may not have access to
    // select a different Quota for the profiles created by them
    $_qut = jrProfile_get_quotas();
    if (!jrUser_is_admin()) {
        // We're a power user and may only have access to selected Quotas
        $key = jrUser_get_profile_home_key('quota_jrUser_power_user_quotas');
        if (strpos($key, ',')) {
            $_all = array();
            foreach (explode(',', $key) as $qid) {
                if (isset($_qut[$qid])) {
                    $_all[$qid] = $_qut[$qid];
                }
            }
            $_qut = $_all;
            unset($_all);
        }
        elseif (jrCore_checktype($key, 'number_nz') && isset($_qut[$key])) {
            $_qut = array($key => $_qut[$key]);
        }
        else {
            jrCore_notice_page('error', 'Unable to determine Power User Quota - please contact the system adminstrator');
        }
    }

    // Show create new Profile Form
    jrCore_page_banner(7, false, false);

    // Form init
    $_tmp = array(
        'submit_value' => 8,
        'cancel'       => 'referrer'
    );
    jrCore_form_create($_tmp);

    // Profile Name
    $_tmp = array(
        'name'     => 'profile_name',
        'label'    => 9,
        'help'     => 10,
        'type'     => 'text',
        'required' => true,
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    if (isset($_qut) && is_array($_qut) && count($_qut) > 1) {
        $_tmp = array(
            'name'          => 'profile_quota_id',
            'label'         => 'profile quota',
            'help'          => 'What Quota should this profile be part of?',
            'type'          => 'select',
            'options'       => $_qut,
            'required'      => true,
            'validate'      => 'number_nz',
            'form_designer' => false // We do not allow the form designer to override this field
        );
        jrCore_form_field_create($_tmp);
    }
    else {
        $qval = array_keys($_qut);
        $qval = reset($qval);
        $_tmp = array(
            'name'          => 'profile_quota_id',
            'type'          => 'hidden',
            'value'         => $qval,
            'validate'      => 'number_nz',
            'form_designer' => false // We do not allow the form designer to override this field
        );
        jrCore_form_field_create($_tmp);
    }

    // Show User Picker...  (ADMINS ONLY)
    $_tmp = array(
        'name'          => 'profile_user_id',
        'group'         => 'admin',
        'label'         => 'profile owner',
        'help'          => 'What User Account should this profile be created for?  The User Account selected here will have admin capabilities for the Profile.',
        'type'          => 'live_search',
        'target'        => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/get_profile_users",
        'value'         => $_user['_user_id'],
        'required'      => false,
        'validate'      => 'number_nz',
        'form_designer' => false // We do not allow the form designer to override this field
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrProfile_create_save($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    // Make sure user is allowed to create profiles....
    if (!jrUser_is_power_user()) {
        jrUser_not_authorized();
    }
    jrCore_form_validate($_post);

    // Make sure the given profile name does not already exist
    $_rt = jrCore_db_get_item_by_key('jrProfile', 'profile_name', $_post['profile_name']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 19);
        jrCore_form_field_hilight('profile_name');
        jrCore_form_result();
    }
    // Make sure user_name is not a banned word...
    if (jrCore_run_module_function('jrBanned_is_banned', 'name', $_post['profile_name'])) {
        jrCore_set_form_notice('error', 20);
        jrCore_form_field_hilight('profile_name');
        jrCore_form_result();
    }
    // Check for an active skin template with that name...
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['profile_name']}.tpl")) {
        jrCore_set_form_notice('error', 'There is an active skin page using that name - please try another');
        jrCore_form_field_hilight('profile_name');
        jrCore_form_result();
    }

    // Validate posted Quota
    $_qut = jrProfile_get_quotas();
    if (!jrUser_is_admin()) {
        // We're a power user and may only have access to selected Quotas
        $key = jrUser_get_profile_home_key('quota_jrUser_power_user_quotas');
        if (strpos($key, ',')) {
            $_all = array();
            foreach (explode(',', $key) as $qid) {
                if (isset($_qut[$qid])) {
                    $_all[$qid] = $_qut[$qid];
                }
            }
            $_qut = $_all;
            unset($_all);
        }
        elseif (jrCore_checktype($key, 'number_nz') && isset($_qut[$key])) {
            $_qut = array($key => $_qut[$key]);
        }
        else {
            jrCore_set_form_notice('error', 'Unable to determine Power User Quota - please contact the system adminstrator');
            jrCore_form_field_hilight('profile_quota_id');
            jrCore_form_result();
        }
    }

    $qid = (int) $_post['profile_quota_id'];
    if (!isset($_qut[$qid])) {
        jrCore_set_form_notice('error', 'Invalid Quota ID - please select from the list of allowed Quotas IDs');
        jrCore_form_field_hilight('profile_quota_id');
        jrCore_form_result();
    }

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_rt = jrCore_form_get_save_data('jrProfile', 'create', $_post);
    $_rt['profile_url'] = jrCore_url_string($_post['profile_name']);
    $_rt['profile_active'] = 1;
    $_rt['profile_private'] = 1;

    // Create new Profile
    $pid = jrCore_db_create_item('jrProfile', $_rt);
    if (!$pid) {
        jrCore_set_form_notice('error', 18);
        jrCore_form_result();
    }

    // If this is NOT an admin user, setup profile link
    if (jrUser_is_admin()) {
        if (isset($_post['profile_user_id']) && jrCore_checktype($_post['profile_user_id'], 'number_nz')) {
            $uid = $_post['profile_user_id'];
        }
    }
    else {
        $uid = $_user['_user_id'];
    }

    // Update with new profile id
    if (isset($uid)) {
        $_temp = array();
        $_core = array(
            '_user_id'    => $uid,
            '_profile_id' => $pid
        );
        jrCore_db_update_item('jrProfile', $pid, $_temp, $_core);
        jrProfile_create_user_link($uid, $pid);
    }

    // update the profile_count for the quota
    jrProfile_update_profile_count($qid);

    // Save any uploaded media files added in by our
    jrCore_save_all_media_files('jrProfile', 'create', $pid, $pid);

    jrCore_logger('INF', "created new profile: {$_post['profile_name']}");
    jrCore_form_delete_session();
    // Redirect to new Profile
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_rt['profile_url']}");
}

//------------------------------
// delete_save
//------------------------------
function view_jrProfile_delete_save($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    // Make sure user is allowed to create profiles....
    if (!jrUser_is_power_user() && !jrUser_is_admin()) {
        jrUser_not_authorized();
    }
    // Make sure we get a valid ID
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_notice_page('error', 'invalid profile id - please try again');
    }
    $_pr = jrCore_db_get_item('jrProfile', $_post['id'], true);
    if (!isset($_pr) || !is_array($_pr)) {
        jrCore_notice_page('error', 'invalid profile id - no data for profile found');
    }

    if (jrUser_is_admin()) {
        // We need to get info about this profile first, and make sure/ any user's are NOT admin users
        $_sp = array(
            'search'      => array(
                "_profile_id = {$_post['id']}"
            ),
            'return_keys' => array(
                'user_name','user_group'
            ),
            'limit'       => 100
        );
        $_rt = jrCore_db_search_items('jrUser', $_sp);
        if (isset($_rt) && is_array($_rt) && isset($_rt['_items']) && is_array($_rt['_items'])) {
            foreach ($_rt['_items'] as $_v) {
                if (isset($_v['user_group'])) {
                    switch ($_v['user_group']) {
                        case 'master':
                        case 'admin':
                            $murl = jrCore_get_module_url('jrUser');
                            jrCore_notice_page('error', "You cannot delete a profile that belongs to an Admin or Master User!<br>You must change the &quot;<a href=\"{$_conf['jrCore_base_url']}/{$murl}/account/user_id={$_v['_user_id']}\">{$_v['user_name']}</a>&quot; User Account to the &quot;user&quot; group before you can delete this profile.",'referrer','continue',false);
                            break;
                    }
                }
            }
        }
    }
    else {
        // We are a power user - we can only delete a profile that we created
        $_tmp = array_flip(explode(',', $_user['user_linked_profile_ids']));
        if (!isset($_tmp) || !is_array($_tmp) || !isset($_tmp["{$_post['id']}"])) {
            jrCore_notice_page('error', 'You cannot delete a profile that was not created by you!');
        }
    }

    // Fall through - delete profile
    // Delete key
    $tbl = jrCore_db_table_name('jrProfile', 'item');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$_post['id']}'";
    jrCore_db_query($req);

    // Delete values
    $tbl = jrCore_db_table_name('jrProfile', 'item_key');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$_post['id']}'";
    jrCore_db_query($req);

    // Get users associated with this profile - we're going to delete them
    // too if this profile is the only profile they are associated with
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "SELECT user_id FROM {$tbl} WHERE profile_id = '{$_post['id']}'";
    $_us = jrCore_db_query($req, 'NUMERIC');
    if (isset($_us) && is_array($_us)) {
        $_tm = array();
        foreach ($_us as $_usr) {
            $_tm["{$_usr['user_id']}"] = 1;
        }
        if (isset($_tm) && is_array($_tm) && count($_tm) > 0) {
            // See how many profiles each user is linked to...
            $req = "SELECT * FROM {$tbl} WHERE user_id IN(" . implode(',', array_keys($_tm)) . ")";
            $_fn = jrCore_db_query($req, 'NUMERIC');
            if (isset($_fn) && is_array($_fn)) {
                $_tm = array();
                foreach ($_fn as $_usr) {
                    if (!isset($_tm["{$_usr['user_id']}"])) {
                        $_tm["{$_usr['user_id']}"] = 0;
                    }
                    $_tm["{$_usr['user_id']}"]++;
                }
                if (isset($_tm) && is_array($_tm) && count($_tm) > 0) {
                    foreach ($_tm as $user_id => $num_profiles) {
                        if ($num_profiles <= 1) {

                            // Delete User Account
                            // Delete key
                            $tbl = jrCore_db_table_name('jrUser', 'item');
                            $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$user_id}'";
                            jrCore_db_query($req);

                            // Delete values
                            $tbl = jrCore_db_table_name('jrUser', 'item_key');
                            $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$user_id}'";
                            jrCore_db_query($req);
                        }
                    }
                }
            }
        }
    }

    // Delete Profile Links
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $req = "DELETE FROM {$tbl} WHERE profile_id = '{$_post['id']}'";
    jrCore_db_query($req);

    $_mds = jrCore_get_datastore_modules();
    if (isset($_mds) && is_array($_mds)) {
        foreach ($_mds as $module => $prefix) {
            if ($module == 'jrUser' || $module == 'jrProfile') {
                continue;
            }

            // Make sure table exists...
            if (!jrCore_db_table_exists($module, 'item_key')) {
                continue;
            }

            $tbl = jrCore_db_table_name($module, 'item_key');
            $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = '_profile_id' AND `value` = '{$_post['id']}'";
            $_rt = jrCore_db_query($req, 'NUMERIC');

            if (isset($_rt) && is_array($_rt)) {
                $_id = array();
                foreach ($_rt as $_v) {
                    $_id[] = $_v['_item_id'];
                }

                // Delete keys
                $tbl = jrCore_db_table_name($module, 'item');
                $req = "DELETE FROM {$tbl} WHERE `_item_id` IN(" . implode(',', $_id) . ')';
                $cnt = jrCore_db_query($req, 'COUNT');
                if (isset($cnt) && $cnt > 0) {
                    // Delete values
                    $tbl = jrCore_db_table_name($module, 'item_key');
                    $req = "DELETE FROM {$tbl} WHERE `_item_id` IN(" . implode(',', $_id) . ')';
                    jrCore_db_query($req);
                    jrCore_logger('INF', "deleted {$cnt} {$module} entries for profile {$_pr['profile_name']}");
                }
            }
        }
    }

    // Remove all files for this profile
    $dir = jrCore_get_media_directory($_post['id']);
    jrCore_delete_dir_contents($dir, false);
    rmdir($dir);

    // Lastly send out our profile delete trigger so other modules and do extra cleanup if needed
    jrCore_trigger_event('jrProfile', 'delete_profile', $_pr);

    jrCore_logger('INF', "profile {$_pr['profile_name']} has been deleted");
    jrCore_set_form_notice('success', "The &quot;{$_pr['profile_name']}&quot; profile has been successfully deleted");
    jrCore_location('referrer');
}

//------------------------------
// settings
//------------------------------
function view_jrProfile_settings($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_session_require_login();

    // make sure we get a good profile_id
    if (isset($_post['id'])) {
        $_post['profile_id'] = (int) $_post['id'];
    }
    if (!isset($_post['profile_id']) || !jrCore_checktype($_post['profile_id'], 'number_nz')) {
        $_post['profile_id'] = (int) $_SESSION['user_active_profile_id'];
    }

    // We need to make sure the viewing user has access to this profile
    if (!jrUser_is_admin()) {
        $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
        $req = "SELECT * FROM {$tbl} WHERE user_id = '" . intval($_user['_user_id']) . "' AND profile_id = '{$_post['profile_id']}' LIMIT 1";
        $_pr = jrCore_db_query($req, 'SINGLE');
        if (!isset($_pr) || !is_array($_pr)) {
            jrUser_not_authorized();
        }
    }

    // See if we are switching active profiles
    if ((jrUser_is_power_user() || jrUser_is_multi_user()) && isset($_post['profile_id']) && jrCore_checktype($_post['profile_id'], 'number_nz')) {
        $_profile = jrCore_db_get_item('jrProfile', $_post['profile_id']);
    }
    else {
        $_profile = jrCore_db_get_item('jrProfile', $_user['_profile_id']);

        // If we are an admin user and we click on the "Account Settings" link at the top of the site,
        // and the LAST profile we viewed was not our own, our active profile ID will not be set
        // correctly to modify our own profile - set it here.
        $_SESSION['user_active_profile_id'] = $_profile['_profile_id'];
        jrProfile_sync_active_profile_data();
    }
    $_lang = jrUser_load_lang_strings();

    // If this a master admin modifying...
    if (jrUser_is_master()) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs('jrProfile');
        jrUser_account_tabs('settings', $_profile);
    }
    else {
        jrUser_account_tabs('settings');
    }

    // Get additional settings
    foreach ($_mods as $module => $_inf) {
        if (is_file(APP_DIR . "/modules/{$module}/profile.php")) {
            require_once APP_DIR . "/modules/{$module}/profile.php";
            $func = "{$module}_profile_settings";
            if (function_exists($func)) {
                $func();
            }
        }
    }

    // If we have a Power User, we can create additional profiles
    $create = null;
    if (!jrUser_is_admin() && (jrUser_is_power_user() || jrUser_is_multi_user())) {
        // Get profiles we have access to
        $_sp = array(
            "_profile_id in {$_user['user_linked_profile_ids']}"
        );
        if (jrUser_is_multi_user()) {
            $create = jrCore_page_banner_item_jumper('jrProfile', 'profile_name', $_sp, null, 'settings');
        }
        else {
            // Power users can create additional profiles...
            $create = jrCore_page_banner_item_jumper('jrProfile', 'profile_name', $_sp, 'create', 'settings');
        }
    }
    // Power Users can also create new profiles
    if (jrUser_is_power_user()) {
        $max = jrUser_get_profile_home_key('quota_jrUser_power_user_max');
        if (jrUser_is_admin() || intval($max) > count(explode(',', $_user['user_linked_profile_ids']))) {
            $create .= '&nbsp;' . jrCore_page_button('profile_create', $_lang['jrProfile'][7], "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/create'");
        }
    }
    // our page banner
    jrCore_page_banner(2, $create, false);

    // Form init
    $_tmp = array(
        'submit_value'     => $_lang['jrCore'][72],
        'cancel'           => 'referrer',
        'form_ajax_submit' => false,
        'values'           => $_profile
    );
    jrCore_form_create($_tmp);

    if ((jrUser_is_power_user() || jrUser_is_multi_user()) && isset($_post['profile_id']) && jrCore_checktype($_post['profile_id'], 'number_nz')) {

        $_tmp = array(
            'name'  => 'user_id',
            'type'  => 'hidden',
            'value' => $_profile['_user_id']
        );
        jrCore_form_field_create($_tmp);

        $_tmp = array(
            'name'  => 'profile_id',
            'type'  => 'hidden',
            'value' => $_post['profile_id']
        );
        jrCore_form_field_create($_tmp);
    }

    // Profile Image
    $_img = $_profile;
    $_img['_item_id'] = $_profile['_profile_id'];
    $_tmp = array(
        'name'     => 'profile_image',
        'label'    => 6,
        'help'     => 23,
        'type'     => 'image',
        'size'     => 'medium',
        'required' => false,
        'value'    => $_img
    );
    jrCore_form_field_create($_tmp);

    // Profile Name
    $_tmp = array(
        'name'      => 'profile_name',
        'label'     => 9,
        'help'      => 10,
        'type'      => 'text',
        'required'  => true,
        'min'       => 3,
        'ban_check' => 'word',
        'validate'  => 'printable'
    );
    jrCore_form_field_create($_tmp);

    $_opt = array(
        0 => $_lang['jrProfile'][14], // Private
        1 => $_lang['jrProfile'][13], // Global
        2 => $_lang['jrProfile'][15] // Shared Only
    );
    // If we are not using the Followers Module we can't do shared...
    if (!isset($_mods['jrFollower'])) {
        unset($_opt[2]);
    }

    // Profile Privacy
    $_tmp = array(
        'name'     => 'profile_private',
        'label'    => 11,
        'help'     => 12,
        'type'     => 'select',
        'options'  => $_opt,
        'value'    => (isset($_user['profile_private'])) ? $_user['profile_private'] : '1',
        'required' => true,
        'max'      => 2,
        'validate' => 'number_nn'
    );
    jrCore_form_field_create($_tmp);

    // Bio
    $_tmp = array(
        'name'     => 'profile_bio',
        'label'    => 21,
        'help'     => 22,
        'type'     => 'editor',
        'theme'    => 'advanced',
        'validate' => 'allowed_html',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    $_tmp = jrCore_get_flag('jrprofile_register_setting');
    if ($_tmp) {
        foreach ($_tmp as $smod => $_entries) {
            foreach ($_entries as $_field) {
                // Language replacements...
                if (isset($_field['label']) && jrCore_checktype($_field['label'], 'number_nz') && isset($_lang[$smod]["{$_field['label']}"])) {
                    $_field['label'] = $_lang[$smod]["{$_field['label']}"];
                }
                if (isset($_field['help']) && jrCore_checktype($_field['help'], 'number_nz') && isset($_lang[$smod]["{$_field['help']}"])) {
                    $_field['help'] = $_lang[$smod]["{$_field['help']}"];
                }
                if (isset($_field['error_msg']) && jrCore_checktype($_field['error_msg'], 'number_nz') && isset($_lang[$smod]["{$_field['error_msg']}"])) {
                    $_field['error_msg'] = $_lang[$smod]["{$_field['error_msg']}"];
                }
                jrCore_form_field_create($_field);
            }
        }
    }

    if (jrUser_is_power_user()) {

        // Profile Quota (power users only)
        $_tmp = array(
            'name'          => 'profile_quota_id',
            'label'         => 'profile quota',
            'help'          => 'What Quota should this profile be part of?',
            'type'          => 'select',
            'options'       => 'jrProfile_get_settings_quotas',
            'value'         => $_profile['profile_quota_id'],
            'required'      => true,
            'group'         => 'power',
            'validate'      => 'number_nz',
            'form_designer' => false // We do not allow the form designer to override this field
        );
        jrCore_form_field_create($_tmp);
    }

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// settings_save
//------------------------------
function view_jrProfile_settings_save($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    jrCore_form_validate($_post);
    jrUser_load_lang_strings();

    if (isset($_post['profile_id']) && jrCore_checktype($_post['profile_id'], 'number_nz') && (jrUser_is_power_user() || jrUser_is_multi_user())) {
        $_profile = jrCore_db_get_item('jrProfile', $_post['profile_id']);
    }
    else {
        $_profile = jrCore_db_get_item('jrProfile', $_user['user_active_profile_id']);
    }

    // Check for updated profile URL
    $_rt = jrCore_db_get_item_by_key('jrProfile', 'profile_url', $_post['profile_name']);
    if (isset($_rt) && is_array($_rt) && $_profile['_profile_id'] != $_rt['_profile_id']) {
        jrCore_set_form_notice('error', 18);
        jrCore_form_field_hilight('profile_url');
        jrCore_form_result();
    }
    // Check for an active skin template with that name...
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_rt['profile_url']}.tpl")) {
        jrCore_set_form_notice('error', 18);
        jrCore_form_field_hilight('profile_name');
        jrCore_form_result();
    }

    if (!jrUser_is_power_user()) {
        // Only admins can change Quota...
        unset($_post['profile_quota_id']);
    }
    else {
        // Validate posted Quota
        $_qut = jrProfile_get_quotas();
        if (!jrUser_is_admin()) {
            // We're a power user and may only have access to selected Quotas
            $key = jrUser_get_profile_home_key('quota_jrUser_power_user_quotas');
            if (strpos($key, ',')) {
                $_all = array();
                foreach (explode(',', $key) as $qid) {
                    if (isset($_qut[$qid])) {
                        $_all[$qid] = $_qut[$qid];
                    }
                }
                $_qut = $_all;
                unset($_all);
            }
            elseif (jrCore_checktype($key, 'number_nz') && isset($_qut[$key])) {
                $_qut = array($key => $_qut[$key]);
            }
            else {
                jrCore_set_form_notice('error', 'Unable to determine Power User Quota - please contact the system adminstrator');
                jrCore_form_field_hilight('profile_quota_id');
                jrCore_form_result();
            }
        }
        $qid = (int) $_post['profile_quota_id'];
        if (!isset($_qut[$qid])) {
            jrCore_set_form_notice('error', 'Invalid Quota ID - please select from the list of allowed Quotas IDs');
            jrCore_form_field_hilight('profile_quota_id');
            jrCore_form_result();
        }
    }

    $_post['profile_private'] = (int) $_post['profile_private'];
    $_data = jrCore_form_get_save_data('jrProfile', 'settings', $_post);

    $_data['profile_url'] = jrCore_url_string($_data['profile_name']);
    jrCore_db_update_item('jrProfile', $_profile['_profile_id'], $_data);

    // Update Quota Counts for quotas if we are changing
    if (isset($_post['profile_quota_id']) && $_post['profile_quota_id'] != $_profile['profile_quota_id']) {
        // Update counts in both Quotas
        jrProfile_update_profile_count($_post['profile_quota_id']);
        jrProfile_update_profile_count($_profile['profile_quota_id']);
    }

    // Check for file upload
    $_image = jrCore_save_media_file('jrProfile', 'profile_image', $_profile['_profile_id'], $_profile['_profile_id']);

    // If the user does NOT have a user image, and we are uploading one to our home profile...
    if (!isset($_user['user_image_size']) && isset($_image) && is_array($_image) && $_profile['_profile_id'] == jrUser_get_profile_home_key('_profile_id')) {
        $_user = array_merge($_user, $_image);
        $_user['_item_id'] = $_profile['_profile_id'];
        $profile_image = jrCore_get_media_file_path('jrProfile', 'profile_image', $_user);
        if (is_file($profile_image)) {
            $ext = jrCore_file_extension($profile_image);
            $nam = "{$_user['_user_id']}_user_image";
            if (jrCore_copy_media_file($_profile['_profile_id'], $profile_image, $nam)) {
                $dir = dirname($profile_image);
                jrCore_write_to_file("{$dir}/{$nam}.tmp", "user_image.{$ext}");
                jrCore_save_media_file('jrUser', "{$dir}/{$nam}", $_profile['_profile_id'], $_user['_user_id']);
                unlink("{$dir}/{$nam}");
                unlink("{$dir}/{$nam}.tmp");
            }
        }
    }

    // If we have updated our OWN profile, then we need to update home URL
    if ($_profile['_profile_id'] == jrUser_get_profile_home_key('_profile_id')) {
        $_profile = jrCore_db_get_item('jrProfile', $_profile['_profile_id'], true);
        jrUser_save_profile_home_keys($_profile, true);
    }
    jrCore_form_delete_session();
    jrProfile_reset_cache($_profile['_profile_id']);
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_data['profile_url']}");
}

//------------------------------
// user_link
//------------------------------
function view_jrProfile_user_link($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrProfile');

    jrCore_set_form_notice('notice','User Accounts can be linked to more than one profile - each linked profile works the same as the User home profile.');
    jrCore_page_banner('User Profile Link');

    $_tmp = array(
        'submit_value'     => 'link user to profile',
        'cancel'           => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools",
        'form_ajax_submit' => false

    );
    jrCore_form_create($_tmp);

    // Select User
    $_tmp = array(
        'name'      => 'link_user_id',
        'label'     => 'user account',
        'type'      => 'live_search',
        'help'      => 'Select the User Account you want to link to an existing profile. The User Account can already be linked to an existing profile, and you can link a User Account to more than 1 profile.',
        'validate'  => 'number_nz',
        'required'  => true,
        'error_msg' => 'You have selected an invalid User Account - please try again',
        'target'    => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/user_link_get_user"
    );
    jrCore_form_field_create($_tmp);

    // Select Profile
    $_tmp = array(
        'name'      => 'link_profile_id',
        'label'     => 'profile name',
        'type'      => 'live_search',
        'help'      => 'Select the Profile you want to link the User Account to.  The linked User Account will have full access to the profile as if it was their own.',
        'validate'  => 'number_nz',
        'required'  => true,
        'error_msg' => 'You have selected an invalid Profile - please try again',
        'target'    => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/user_link_get_profile"
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// user_link_save
//------------------------------
function view_jrProfile_user_link_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // [link_user_id] => 2
    // [link_profile_id] => 26
    $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
    $uid = (int) $_post['link_user_id'];
    $pid = (int) $_post['link_profile_id'];
    $req = "SELECT * FROM {$tbl} WHERE user_id = '{$uid}' AND profile_id = '{$pid}' LIMIT 1";
    $_rt = jrCore_db_query($req,'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrProfile_create_user_link($uid,$pid);
    }
    jrCore_form_delete_session();
    jrCore_set_form_notice('success','The User Account has been linked with the Profile');
    jrCore_form_result();
}

//------------------------------
// user_link_get_user
//------------------------------
function view_jrProfile_user_link_get_user($_post, $_user, $_conf)
{
    jrUser_master_only();
    $ss = jrCore_db_escape($_post['q']) .'%';
    $tbl = jrCore_db_table_name('jrUser','item_key');
    $req  = "SELECT `_item_id`, `value` FROM {$tbl} WHERE `key` = 'user_name' AND `_item_id` IN(SELECT `_item_id` FROM {$tbl} WHERE `key` = 'user_group' AND `value` NOT IN('master','admin')) AND `value` LIKE '{$ss}' ORDER BY `value` ASC";
    $_fl = jrCore_db_query($req,'_item_id',false,'value');
    return jrCore_live_search_results('link_user_id',$_fl);
}

//------------------------------
// user_link_get_profile
//------------------------------
function view_jrProfile_user_link_get_profile($_post, $_user, $_conf)
{
    jrUser_master_only();
    $ss = jrCore_db_escape($_post['q']) .'%';
    $tbl = jrCore_db_table_name('jrProfile','item_key');
    $req  = "SELECT `_item_id`, `value` FROM {$tbl} WHERE `key` = 'profile_name' AND `value` LIKE '{$ss}' ORDER BY `value` ASC";
    $_fl = jrCore_db_query($req,'_item_id',false,'value');
    return jrCore_live_search_results('link_profile_id',$_fl);
}

//------------------------------
// quota_browser
//------------------------------
function view_jrProfile_quota_browser($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrProfile');
    jrCore_page_banner('Profile Quotas');

    // Start our output
    $dat = array();
    $dat[1]['title'] = 'ID';
    $dat[1]['width'] = '5%';
    $dat[2]['title'] = 'name';
    $dat[2]['width'] = '60%';
    $dat[3]['title'] = 'profiles';
    $dat[3]['width'] = '5%';
    $dat[4]['title'] = 'signup';
    $dat[4]['width'] = '5%';
    $dat[5]['title'] = 'note';
    $dat[5]['width'] = '5%';
    $dat[6]['title'] = 'rename';
    $dat[6]['width'] = '5%';
    $dat[7]['title'] = 'clone';
    $dat[7]['width'] = '5%';
    $dat[8]['title'] = 'transfer';
    $dat[8]['width'] = '5%';
    $dat[9]['title'] = 'delete';
    $dat[9]['width'] = '5%';
    jrCore_page_table_header($dat);

    // Get existing quotas
    $tbl = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "SELECT `quota_id`, `name`, `value` FROM {$tbl} WHERE `name` IN('name','profile_count','allow_signups','admin_note') ORDER BY `quota_id` ASC";
    $_rt = jrCore_db_query($req, 'NUMERIC');
    $_ft = array();
    if (isset($_rt) && is_array($_rt)) {
        foreach ($_rt as $_v) {
            $_ft["{$_v['quota_id']}"]["{$_v['name']}"] = $_v['value'];
        }
    }

    $pass = jrCore_get_option_image('pass');
    $fail = jrCore_get_option_image('fail');
    $murlu = jrCore_get_module_url('jrUser');

    foreach ($_ft as $qid => $_qt) {

        $num = (isset($_qt['profile_count'])) ? $_qt['profile_count'] : '0';
        $dat = array();
        $dat[1]['title'] = $qid;
        $dat[1]['class'] = 'center';
        $dat[2]['title'] = $_qt['name'];
        if (isset($num) && $num > 0) {
            $dat[3]['title'] = jrCore_page_button("c{$qid}", $num, "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser/search_string=profile_quota_id:{$qid}'");
        }
        else {
            $dat[3]['title'] = '0';
        }
        $dat[3]['class'] = 'center';
        $dat[4]['title'] = (isset($_qt['allow_signups']) && $_qt['allow_signups'] == 'on') ? '<a href="'.$_conf['jrCore_base_url'].'/'.$murlu.'/admin/quota/id='.$qid.'">'.$pass.'</a>' : '<a href="'.$_conf['jrCore_base_url'].'/'.$murlu.'/admin/quota/id='.$qid.'">'.$fail.'</a>';
        $dat[4]['class'] = 'center';
        $dat[5]['title'] = (isset($_qt['admin_note']{0})) ? '<img src="' . $_conf['jrCore_base_url'] . '/modules/jrProfile/img/note.png" width="24" height="24" alt="' . addslashes($_qt['admin_note']) . '" title="' . addslashes($_qt['admin_note']) . '">' : '&nbsp;';
        $dat[5]['class'] = 'center';
        $dat[6]['title'] = jrCore_page_button("r{$qid}", 'rename', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/quota/id={$qid}/hl=name#ff-name'");
        $dat[7]['title'] = jrCore_page_button("c{$qid}", 'clone', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_clone/id={$qid}'");
        if (isset($_qt['profile_count']) && $_qt['profile_count'] > 0) {
            $dat[8]['title'] = jrCore_page_button("t{$qid}", 'transfer', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_transfer/id={$qid}'");
            $dat[9]['title'] = jrCore_page_button("d{$qid}", 'delete" title="You cannot delete a Quota unless there are no profiles assigned to the quota', 'disabled');
        }
        else {
            $dat[8]['title'] = jrCore_page_button("t{$qid}", 'transfer" title="There are no profiles in this quota to transfer', 'disabled');
            $dat[9]['title'] = jrCore_page_button("d{$qid}", 'delete', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_delete/id={$qid}'");
        }
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    // Form init
    $_tmp = array(
        'submit_value'     => 'create new quota',
        'cancel'           => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools",
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // New Quota Name
    $_tmp = array(
        'name'      => 'new_quota_name',
        'label'     => 'new quota name',
        'help'      => 'To create a new Profile Quota, enter the name of the new quota you would like to create.',
        'type'      => 'text',
        'error_msg' => 'Please enter a valid quota name',
        'validate'  => 'printable'
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_display();
}

//------------------------------
// quota_browser_save
//------------------------------
function view_jrProfile_quota_browser_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (isset($_post['new_quota_name']) && strlen($_post['new_quota_name']) > 0) {
        $qid = jrProfile_create_quota($_post['new_quota_name']);
        if (isset($qid) && jrCore_checktype($qid, 'number_nz')) {
            jrCore_form_delete_session();
            jrCore_set_form_notice('success', 'The new Profile Quota was successfully created');
            jrCore_form_result('referrer');
        }
        jrCore_set_form_notice('error', 'An error was encountered creating the Profile Quota - please try again');
    }
    else {
        jrCore_set_form_notice('error', 'Please enter a valid Profile Quota name to create a new quota');
    }
    jrCore_form_result();
}

//------------------------------
// quota_clone
//------------------------------
function view_jrProfile_quota_clone($_post, $_user, $_conf)
{
    jrUser_master_only();

    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid quota_id - please try again');
        jrCore_form_result('referrer');
    }

    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrProfile');
    jrCore_page_banner('Clone to New Quota');

    // Form init
    $_tmp = array(
        'submit_value' => 'create new quota',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_browser"
    );
    jrCore_form_create($_tmp);

    // Clone Quota ID
    $_tmp = array(
        'name'  => 'clone_id',
        'type'  => 'hidden',
        'value' => $_post['id']
    );
    jrCore_form_field_create($_tmp);

    // New Quota Name
    $_tmp = array(
        'name'      => 'new_quota_name',
        'label'     => 'new quota name',
        'help'      => 'Enter the name of the new Profile Quota you want to create by cloning an existing quota.',
        'type'      => 'text',
        'error_msg' => 'Please enter a valid quota name',
        'validate'  => 'printable'
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// quota_clone_save
//------------------------------
function view_jrProfile_quota_clone_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (isset($_post['new_quota_name']) && strlen($_post['new_quota_name']) > 0) {
        $qid = jrProfile_create_quota($_post['new_quota_name']);
        if (isset($qid) && jrCore_checktype($qid, 'number_nz')) {

            // Next - we need to get all settings for
            // the quota we are cloning FROM, and add them to our new quota
            $_qt = jrProfile_get_quota($_post['clone_id']);
            foreach ($_qt as $k => $v) {
                switch ($k) {
                    // There are some keys we do not copy over
                    case 'quota_jrProfile_name':
                    case 'quota_jrProfile_profile_count':
                        continue 2;
                        break;
                    default:
                        // [quota_jrAudio_allowed_audio_types]
                        list(, $module, $name) = explode('_', $k, 3);
                        jrProfile_set_quota_value($module, $qid, $name, $v);
                        break;
                }
            }
            jrCore_form_delete_session();
            jrCore_set_form_notice('success', "The new Profile Quota was successfully cloned from the {$_qt['quota_jrProfile_name']} quota");
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_browser");
        }
        jrCore_set_form_notice('error', 'An error was encountered creating the Profile Quota - please try again');
    }
    else {
        jrCore_set_form_notice('error', 'Please enter a valid Profile Quota name to create a new quota');
    }
    jrCore_form_result();
}

//------------------------------
// quota_transfer
//------------------------------
function view_jrProfile_quota_transfer($_post, $_user, $_conf)
{
    jrUser_master_only();

    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid quota_id - please try again');
        jrCore_form_result('referrer');
    }

    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrProfile');
    jrCore_page_banner('Select Quota to Transfer to');

    // Form init
    $_tmp = array(
        'submit_value' => 'transfer profiles',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_browser"
    );
    jrCore_form_create($_tmp);

    $_qt = jrProfile_get_quotas();
    unset($_qt["{$_post['id']}"]);

    // Clone Quota ID
    $_tmp = array(
        'name'  => 'transfer_id',
        'type'  => 'hidden',
        'value' => $_post['id']
    );
    jrCore_form_field_create($_tmp);

    // New Quota Name
    $_tmp = array(
        'name'      => 'new_quota_id',
        'label'     => 'transfer to quota',
        'help'      => 'Select the Quota you want to transfer profiles to.',
        'type'      => 'select',
        'options'   => $_qt,
        'error_msg' => 'Please enter a valid quota name',
        'validate'  => 'printable'
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// quota_transfer_save
//------------------------------
function view_jrProfile_quota_transfer_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Get affected profile id's
    $tid = intval($_post['transfer_id']);
    $tbl = jrCore_db_table_name('jrProfile', 'item_key');
    $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = 'profile_quota_id' AND `value` = '{$tid}'";
    $_rt = jrCore_db_query($req, '_item_id', false, '_item_id');

    // Transfer to new quota
    $req = "UPDATE {$tbl} SET `value` = '" . intval($_post['new_quota_id']) . "' WHERE `key` = 'profile_quota_id' AND `value` = '{$tid}'";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt > 0) {

        // Make sure caches are reset for affected profiles
        if (isset($_rt) && is_array($_rt)) {
            $tbl = jrCore_db_table_name('jrCore', 'cache');
            $req = "DELETE FROM {$tbl} WHERE cache_profile_id IN('" . implode("','", $_rt) . "')";
            jrCore_db_query($req);
        }

        // Update profile counts
        jrProfile_set_quota_value('jrProfile', $_post['transfer_id'], 'profile_count', 0);
        jrProfile_set_quota_value('jrProfile', $_post['new_quota_id'], 'profile_count', count($_rt));

        jrCore_form_delete_session();
        jrCore_set_form_notice('success', "Successfully transferred {$cnt} profiles to the new quota");
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/quota_browser");
    }
    jrCore_set_form_notice('error', 'An error was encountered transferring the profiles - please try again');
    jrCore_form_result();
}

//------------------------------
// quota_delete
//------------------------------
function view_jrProfile_quota_delete($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid quota_id - please try again');
        jrCore_form_result('referrer');
    }
    if (!jrProfile_delete_quota($_post['id'])) {
        jrCore_set_form_notice('error', 'An error was encountered deleting the quota - please try again');
    }
    jrCore_form_result('referrer');
}
