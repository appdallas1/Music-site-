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

//------------------------------
// create_language
//------------------------------
function view_jrUser_create_language($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrUser');
    jrCore_page_banner('create user language');

    // Form init
    $_tmp = array(
        'submit_value' => 'create new language',
        'cancel'       => 'referrer'
    );
    jrCore_form_create($_tmp);

    // Clone Language
    $_tmp = array(
        'name'     => 'new_lang_clone',
        'label'    => 'clone language',
        'help'     => 'Select the existing User Language you would like to clone to create the new User Language',
        'type'     => 'select',
        'options'  => jrUser_get_languages(),
        'value'    => 'en-US',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // New Lang Code
    $_tmp = array(
        'name'     => 'new_lang_code',
        'label'    => 'language code',
        'help'     => 'This should be the 2 digit ISO-639-1 Code for the language family, followed by a dash (-) and a 2 digit, uppercase local code - i.e. "en-US", "en-GB", etc. A list of 2 digit ISO-639-1 codes can be found in Wikipedia:<br><br><a href="http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes" target="_blank"><u>List of ISO-639-1 Codes on Wikipedia</u></a>',
        'type'     => 'text',
        'required' => true,
        'min'      => 5,
        'max'      => 5,
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // New Lang Direction
    $_tmp = array(
        'name'     => 'new_lang_direction',
        'label'    => 'language text direction',
        'help'     => 'Does this new language flow from left to right (ltr) or from right to left (rtl)?',
        'type'     => 'select',
        'options'  => array('ltr' => 'Left to Right', 'rtl' => 'Right to Left'),
        'value'    => 'ltr',
        'required' => true
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// create_language_save
//------------------------------
function view_jrUser_create_language_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Validate new_lang_code
    list($one, $two) = explode('-', $_post['new_lang_code']);
    if ((!isset($one) || strlen($one) !== 2) || (!isset($two) || strlen($two) !== 2)) {
        jrCore_set_form_notice('error', 'invalid language code - should be in xx-XX format');
        jrCore_form_field_hilight('new_lang_code');
        jrCore_form_result();
    }
    // Make sure that code does not already exist in the DB
    $cod = jrCore_db_escape($_post['new_lang_code']);
    $tbl = jrCore_db_table_name('jrUser', 'language');
    $req = "SELECT * FROM {$tbl} WHERE lang_code = '{$cod}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 'that language code is already being used in the database - please select another');
        jrCore_form_field_hilight('new_lang_code');
        jrCore_form_result();
    }

    // Next we need to copy every entry from the CLONE language into the TARGET language
    $ltr = jrCore_db_escape($_post['new_lang_direction']);
    $cln = jrCore_db_escape($_post['new_lang_clone']);
    $req = "INSERT INTO {$tbl} (lang_module,lang_code,lang_charset,lang_ltr,lang_key,lang_text,lang_default)
            (SELECT lang_module,'{$cod}','utf-8','{$ltr}',lang_key,lang_text,lang_default FROM {$tbl} WHERE lang_code = '{$cln}')";
    $cnt = jrCore_db_query($req, 'COUNT');

    // Redirect to edit..
    jrCore_logger('INF', "created new User Language: {$_post['new_lang_code']}");
    jrCore_form_delete_session();
    jrCore_set_form_notice('success', 'The new language has been successfully created');
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/language/lang_code={$cod}");
}

//------------------------------
// create
//------------------------------
function view_jrUser_create($_post, $_user, $_conf)
{
    jrUser_admin_only();
    jrUser_load_lang_strings();

    // If this a master admin creating...
    if (jrUser_is_master()) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs('jrUser');
    }
    else {
        jrCore_page_dashboard_tabs('online');
    }

    // our page banner
    jrCore_page_banner('create user account');

    // Form init
    $_tmp = array(
        'submit_value'     => 'create user',
        'cancel'           => 'referrer',
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // User Name
    $_tmp = array(
        'name'      => 'user_name',
        'label'     => 4,
        'help'      => 5,
        'type'      => 'text',
        'error_msg' => 6,
        'ban_check' => 'word',
        'required'  => true,
        'validate'  => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // User Email
    $_tmp = array(
        'name'     => 'user_email',
        'label'    => 18,
        'help'     => 57,
        'type'     => 'text',
        'required' => true,
        'validate' => 'email'
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_section_header('password options');

    // Generate Password
    $_tmp = array(
        'name'     => 'user_password_create',
        'label'    => 'create password',
        'sublabel' => 'and send user an email',
        'help'     => 'If this option is checked, a random password will be generated for this new User Account if NO PASSWORD is entered into the password form field.  The new user will be sent an email with their password - the user can change their password when they login.',
        'type'     => 'checkbox',
        'value'    => 'on'
    );
    jrCore_form_field_create($_tmp);

    // Password #1
    $_tmp = array(
        'name'      => 'user_passwd1',
        'label'     => 7,
        'help'      => 8,
        'type'      => 'password',
        'error_msg' => 9,
        'required'  => false,
        'validate'  => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    // Password #2
    $_tmp = array(
        'name'      => 'user_passwd2',
        'label'     => 32,
        'help'      => 23,
        'type'      => 'password',
        'error_msg' => 9,
        'required'  => false,
        'validate'  => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    // Master Admin options
    if (jrUser_is_master()) {
        jrCore_page_section_header('master admin options');
        $_tmp = array(
            'name'     => 'user_group',
            'label'    => 'user group',
            'help'     => 'Select the user group this user should be part of:<br><br><b>Standard User:</b> a normal user account in your system - can modify items they have created only.<br><b>Profile Admin:</b> can modify users and profiles and items created by any user on the system. Has access to the Dashboard.<br><b>Master Admin:</b> full access to all system areas including the Admin Control Panel and Dashboard.',
            'type'     => 'select',
            'options'  => array('user' => 'Standard User', 'admin' => 'Profile Admin', 'master' => 'Master Admin'),
            'value'    => 'user',
            'validate' => 'core_string'
        );
        jrCore_form_field_create($_tmp);
    }
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrUser_create_save($_post, $_user, $_conf)
{
    jrUser_admin_only();
    jrCore_form_validate($_post);

    // Make sure they don't already exist
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_name', $_post['user_name']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Make sure they don't already exist
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_email', $_post['user_email']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 34);
        jrCore_form_field_hilight('user_email');
        jrCore_form_result();
    }

    // Make sure the user_name is not being used by a profile
    $_rt = jrCore_db_get_item_by_key('jrProfile', 'profile_url', $_post['user_name']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Make sure user_name is not a banned word...
    if (jrCore_run_module_function('jrBanned_is_banned', 'name', $_post['user_name'])) {
        jrCore_set_form_notice('error', 55);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Check for an active skin template with that name...
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['user_name']}.tpl")) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // See if we are generating a password
    if (isset($_post['user_passwd1']) && strlen($_post['user_passwd1']) > 0 && isset($_post['user_passwd2']) && strlen($_post['user_passwd2']) > 0) {
        // Verify our passwords match
        if (!isset($_post['user_passwd1']) || strlen($_post['user_passwd1']) === 0 || !isset($_post['user_passwd2']) || strlen($_post['user_passwd2']) === 0) {
            jrCore_set_form_notice('error', 35);
            jrCore_form_field_hilight('user_passwd1');
            jrCore_form_field_hilight('user_passwd2');
            jrCore_form_result();
        }
        if (isset($_post['user_passwd1']) && isset($_post['user_passwd2']) && $_post['user_passwd1'] != $_post['user_passwd2']) {
            jrCore_set_form_notice('error', 35);
            jrCore_form_field_hilight('user_passwd1');
            jrCore_form_field_hilight('user_passwd2');
            jrCore_form_result();
        }
    }
    else {
        // Create and generate a password
        $_post['user_passwd1'] = substr(md5(microtime()), 8, 8);
    }
    $password = $_post['user_passwd1'];

    // Setup our default user values
    require APP_DIR . '/modules/jrUser/contrib/phpass/PasswordHash.php';
    $hash = new PasswordHash(12, false);
    $pass = $hash->HashPassword($_post['user_passwd1']);
    $code = md5(microtime());

    // Create our user account
    $_data = array(
        'user_name'      => $_post['user_name'],
        'user_email'     => $_post['user_email'],
        'user_group'     => 'user',
        'user_password'  => $pass,
        'user_language'  => (isset($_post['user_language']{0})) ? $_post['user_language'] : $_conf['jrUser_default_language'],
        'user_active'    => 1,
        'user_validated' => 1,
        'user_validate'  => $code
    );
    // Check for master setting group
    if (jrUser_is_master()) {
        if (isset($_post['user_group']{0})) {
            $_data['user_group'] = $_post['user_group'];
        }
    }

    $uid = jrCore_db_create_item('jrUser', $_data);
    if (!isset($uid) || !jrCore_checktype($uid, 'number_nz')) {
        jrCore_set_form_notice('error', 36);
        jrCore_form_result();
    }
    $_data['_user_id'] = $uid;
    $_post = jrCore_trigger_event('jrUser', 'signup_created', $_data, $_data);

    // User account is created - send out trigger so any listening
    // modules can do their work for this new user
    $_temp = array();
    $_core = array(
        '_user_id' => $uid
    );
    // Update account just created with proper user_id...
    jrCore_db_update_item('jrUser', $uid, $_temp, $_core);

    // Send User Account email
    if (isset($_post['user_password_create']) && $_post['user_password_create'] == 'on') {
        $_rp = array(
            'system_name' => $_conf['jrCore_system_name'],
            'jamroom_url' => $_conf['jrCore_base_url'],
            'user_name'   => $_post['user_name'],
            'user_pass'   => $password,
            'user_email'  => $_post['user_email']
        );
        list($sub, $msg) = jrCore_parse_email_templates('jrUser', 'created', $_rp);
        jrCore_send_email($_post['user_email'], $sub, $msg);
        jrCore_set_form_notice('success', 'The account has been created and a welcome email sent.<br>You can now modify information about the Profile for this new User.', false);
    }
    else {
        jrCore_set_form_notice('success', 'The User account has been successfully created.<br>You can now modify information about the Profile for this new User.', false);
    }

    // Our User Account is created...
    jrCore_logger('INF', "account created for {$_post['user_email']}");
    jrCore_form_delete_session();

    // Redirect to the Update Profile page so the admin can change anything needed
    $purl = jrCore_get_module_url('jrProfile');
    $_usr = jrCore_db_get_item('jrUser', $uid, true); // OK
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$purl}/settings/profile_id={$_usr['_profile_id']}");
}

//------------------------------
// signup
//------------------------------
function view_jrUser_signup($_post, $_user, $_conf)
{
    if (isset($_post['_1']) && $_post['_1'] == 'modal') {
        jrCore_page_set_meta_header_only();
    }

    jrUser_load_lang_strings();
    // Make sure sign ups are turned on...
    if (!isset($_conf['jrUser_signup_on']) || $_conf['jrUser_signup_on'] != 'on') {
        jrCore_notice_page('error', 58);
    }

    // Make sure at least ONE of our Quotas allows signing up
    $tbq = jrCore_db_table_name('jrProfile', 'quota');
    $req = "SELECT quota_id FROM {$tbq}";
    $_qt = jrCore_db_query($req, 'quota_id');

    // Get Sign Up setting and Quota Names
    $tbv = jrCore_db_table_name('jrProfile', 'quota_value');
    $req = "SELECT `quota_id`,`name`,`value` FROM {$tbv} WHERE `name` IN('allow_signups','name')";
    $_rt = jrCore_db_query($req, 'NUMERIC');

    if (isset($_rt) && is_array($_rt)) {
        foreach ($_rt as $_quota) {
            $_qt["{$_quota['quota_id']}"]["{$_quota['name']}"] = $_quota['value'];
        }
    }

    $def_id = 0;
    if (isset($_qt) && is_array($_qt)) {
        $_opt = array();
        foreach ($_qt as $qid => $_quota) {
            if (isset($_quota['allow_signups']) && $_quota['allow_signups'] == 'on') {
                $def_id = $qid;
                $_opt[$qid] = $_quota['name'];
            }
        }
    }

    if (!isset($_opt) || !is_array($_opt) || count($_opt) === 0) {
        if (jrUser_is_admin()) {
            jrCore_notice_page('error', 'There are currently NO QUOTAS that allow signups - please check the User Account Quota Config for quotas and allow signups!');
        }
        else {
            jrCore_notice_page('error', 58);
        }
    }
    // our page banner
    jrCore_page_banner(31, null, false);

    // Form init
    $_tmp = array(
        'submit_value' => 45,
        'cancel'       => 'referrer'
    );
    $tok = jrCore_form_create($_tmp);

    // User Name
    $_tmp = array(
        'name'      => 'user_name',
        'label'     => 4,
        'help'      => 5,
        'type'      => 'text',
        'error_msg' => 6,
        'ban_check' => 'word',
        'validate'  => 'printable',
        'required'  => true,
        'min'       => 1
    );
    jrCore_form_field_create($_tmp);

    // User Email
    $_tmp = array(
        'name'     => 'user_email',
        'label'    => 18,
        'help'     => 19,
        'type'     => 'text',
        'validate' => 'email',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Password #1
    $_tmp = array(
        'name'      => 'user_passwd1',
        'label'     => 7,
        'help'      => 8,
        'type'      => 'password',
        'error_msg' => 9,
        'validate'  => 'not_empty',
        'required'  => true
    );
    jrCore_form_field_create($_tmp);

    // Password #2
    $_tmp = array(
        'name'       => 'user_passwd2',
        'label'      => 32,
        'help'       => 23,
        'type'       => 'password',
        'error_msg'  => 9,
        'validate'   => 'not_empty',
        'required'   => true,
        'onkeypress' => "if (event && event.keyCode == 13 && this.value.length > 0) { jrFormSubmit('#jrUser_signup','{$tok}','ajax'); }"
    );
    jrCore_form_field_create($_tmp);

    // Show Signup Options
    if (isset($_opt) && is_array($_opt) && count($_opt) > 1) {
        $_tmp = array(
            'name'     => 'quota_id',
            'label'    => 59,
            'help'     => 60,
            'type'     => 'select',
            'options'  => $_opt,
            'validate' => 'number_nz'
        );
        jrCore_form_field_create($_tmp);
    }
    else {
        $_tmp = array(
            'name'  => 'quota_id',
            'type'  => 'hidden',
            'value' => $def_id
        );
        jrCore_form_field_create($_tmp);
    }

    // Spam Bot Check
    $_tmp = array(
        'name'      => 'user_is_human',
        'label'     => 90,
        'help'      => 91,
        'type'      => 'checkbox_spambot',
        'error_msg' => 92,
        'validate'  => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// signup_save
//------------------------------
function view_jrUser_signup_save($_post, $_user, $_conf)
{
    jrCore_form_validate($_post);

    // Make sure they don't already exist
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_name', $_post['user_name']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Make sure they don't already exist
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_email', $_post['user_email']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 34);
        jrCore_form_field_hilight('user_email');
        jrCore_form_result();
    }

    // Make sure the user_name is not being used by a profile
    $_rt = jrCore_db_get_item_by_key('jrProfile', 'profile_url', $_post['user_name']);
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Make sure user_name is not a banned word...
    if (jrCore_run_module_function('jrBanned_is_banned', 'name', $_post['user_name'])) {
        jrCore_set_form_notice('error', 55);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Check for an active skin template with that name...
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['user_name']}.tpl")) {
        jrCore_set_form_notice('error', 33);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // Verify our passwords match
    if (!isset($_post['user_passwd1']) || strlen($_post['user_passwd1']) === 0 || !isset($_post['user_passwd2']) || strlen($_post['user_passwd2']) === 0) {
        jrCore_set_form_notice('error', 35);
        jrCore_form_field_hilight('user_passwd1');
        jrCore_form_field_hilight('user_passwd2');
        jrCore_form_result();
    }
    if (isset($_post['user_passwd1']) && isset($_post['user_passwd2']) && $_post['user_passwd1'] != $_post['user_passwd2']) {
        jrCore_set_form_notice('error', 35);
        jrCore_form_field_hilight('user_passwd1');
        jrCore_form_field_hilight('user_passwd2');
        jrCore_form_result();
    }

    // Make sure the quota they are signing up for is allowed
    if (!isset($_post['quota_id']) || !jrCore_checktype($_post['quota_id'], 'number_nz')) {
        jrCore_set_form_notice('error', 61);
        jrCore_form_result();
    }
    $_qt = jrProfile_get_quota($_post['quota_id']);
    if (!isset($_qt['quota_jrUser_allow_signups']) || $_qt['quota_jrUser_allow_signups'] != 'on') {
        jrCore_set_form_notice('error', 61);
        jrCore_form_result();
    }

    // Setup our default user values
    require APP_DIR . '/modules/jrUser/contrib/phpass/PasswordHash.php';
    $hash = new PasswordHash(12, false);
    $pass = $hash->HashPassword($_post['user_passwd1']);
    $code = md5(microtime());

    // Create our user account
    $_data = array(
        'user_name'      => $_post['user_name'],
        'user_email'     => $_post['user_email'],
        'user_password'  => $pass,
        'user_language'  => (isset($_post['user_language']{0})) ? $_post['user_language'] : $_conf['jrUser_default_language'],
        'user_active'    => 0,
        'user_validated' => 0,
        'user_validate'  => $code
    );
    $uid = jrCore_db_create_item('jrUser', $_data);
    if (!isset($uid) || !jrCore_checktype($uid, 'number_nz')) {
        jrCore_set_form_notice('error', 36);
        jrCore_form_result();
    }
    // Update our _user_id value
    // If this is the FIRST USER on the system, they are master
    $_temp = array('user_group' => 'user');
    $_core = array('_user_id' => $uid);
    if (isset($uid) && $uid == '1') {
        // For our first master user, we automatically activate their account
        $_temp = array(
            'user_group' => 'master'
        );
        // Let's also update the CORE with their email address
        jrCore_set_setting_value('jrMailer', 'from_email', $_post['user_email']);
        jrCore_delete_all_cache_entries('jrCore', 0);
    }
    jrCore_db_update_item('jrUser', $uid, $_temp, $_core);

    // User account is created - send out trigger so any listening
    // modules can do their work for this new user
    $_data['_user_id'] = $uid;
    $_post = jrCore_trigger_event('jrUser', 'signup_created', $_post, $_data);

    if (isset($uid) && $uid == '1') {
        // For our first account, we automatically activate
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/activate/{$code}");
    }
    else {

        // See what type of signup method are are doing
        switch ($_post['signup_method']) {

            case 'instant':
                // Instant Account validation
                jrCore_form_delete_session();
                jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/activate/{$code}");
                break;

            default:
                // Send User Account validation email
                $_rp = array(
                    'system_name'    => $_conf['jrCore_system_name'],
                    'activation_url' => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/activate/{$code}"
                );
                list($sub, $msg) = jrCore_parse_email_templates('jrUser', 'signup', $_rp);
                jrCore_send_email($_post['user_email'], $sub, $msg);

                // Our User Account is created...
                jrCore_logger('INF', "{$_post['user_email']} has signed up and is pending validation");
                jrCore_set_form_notice('success', 37, false);
                jrCore_form_delete_session();
                jrCore_form_result("{$_conf["jrCore_base_url"]}/{$_post['module_url']}/signup");
                break;
        }

    }
    return true;
}

//------------------------------
// activation_resend
//------------------------------
function view_jrUser_activation_resend($_post, $_user, $_conf)
{
    // Prevent abuse
    if (!isset($_SESSION['allow_activation_resend'])) {
        jrCore_notice_page('error', 89);
    }
    // Our user_id will come in as _1
    if (!isset($_post['_1']) || !jrCore_checktype($_post['_1'], 'number_nz')) {
        jrCore_notice_page('error', 89);
    }
    // Get our user info
    $_rt = jrCore_db_get_item('jrUser', $_post['_1'], true); // OK
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice_page('error', 89);
    }
    // See if this user is already activated
    if (isset($_rt['user_validated']) && $_rt['user_validated'] != '0') {
        jrCore_notice_page('error', 56);
    }
    // Resend User Account validation email
    $_rp = array(
        'system_name'    => $_conf['jrCore_system_name'],
        'activation_url' => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/activate/{$_rt['user_validate']}"
    );
    list($sub, $msg) = jrCore_parse_email_templates('jrUser', 'signup', $_rp);
    jrCore_send_email($_rt['user_email'], $sub, $msg);

    // Our User Account is created...
    jrCore_logger('INF', "{$_rt['user_email']} resent account activation email");
    jrCore_set_form_notice('success', 37, false);
    jrCore_form_delete_session();
    jrCore_form_result("{$_conf["jrCore_base_url"]}/{$_post['module_url']}/login");
}

//------------------------------
// activate
//------------------------------
function view_jrUser_activate($_post, $_user, $_conf)
{
    global $_user;
    // Bring in user and language
    ignore_user_abort();
    jrUser_load_lang_strings();

    if (!isset($_post['_1']) || !jrCore_checktype($_post['_1'], 'md5')) {
        jrCore_notice_page('error', 38, false, false, false);
    }

    // Make sure account has been created
    $_params = array(
        'search' => array(
            "user_validate = {$_post['_1']}"
        )
    );
    $_rt = jrCore_db_search_items('jrUser', $_params);
    if (!isset($_rt['_items'][0]) || !is_array($_rt['_items'][0])) {
        jrCore_notice_page('error', 38, false, false, false);
    }
    // Make sure this account has not already been validated
    if (isset($_rt['_items'][0]['user_validated']) && $_rt['_items'][0]['user_validated'] != '0') {
        jrCore_notice_page('error', 56);
    }

    $now = time();
    // Update user account so it is active
    $_data = array(
        'user_last_login' => $now,
        'user_active'     => '1',
        'user_validated'  => '1'
    );
    jrCore_db_update_item('jrUser', $_rt['_items'][0]['_user_id'], $_data);

    // Send out trigger on successful account activation - only first time
    if (!isset($_rt['_items'][0]['user_validated']) || $_rt['_items'][0]['user_validated'] != '1') {
        $_rt['_items'][0]['user_last_login'] = $_data['user_last_login'];
        $_rt['_items'][0]['user_active'] = '1';
        $_rt['_items'][0]['user_validated'] = '1';
        $_rt['_items'][0] = jrCore_trigger_event('jrUser', 'signup_activated', $_rt['_items'][0]);
        jrCore_logger('INF', "{$_rt['_items'][0]['user_email']} has validated their account and logged in");
    }

    // Startup session with user info
    $_SESSION = $_rt['_items'][0];
    $_user = $_SESSION;
    unset($_rt);

    // Save home profile keys
    jrUser_save_profile_home_keys($_SESSION);

    // Login Success Trigger - other modules can add
    // to our User Info
    $_user = jrCore_trigger_event('jrUser', 'login_success', $_user);

    // Show them success
    if (jrUser_is_admin()) {
        jrCore_notice_page('success', 39, "{$_conf['jrCore_base_url']}/core/system_check", 'Continue to server check', false);
    }
    else {
        jrCore_notice_page('success', 39, "{$_conf['jrCore_base_url']}/{$_user['profile_url']}", 54, false);
    }
    return true;
}

//------------------------------
// login
//------------------------------
function view_jrUser_login($_post, $_user, $_conf)
{
    if (isset($_post['_1']) && $_post['_1'] == 'modal') {
        jrCore_page_set_meta_header_only();
    }

    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Check for maintenance mode
    if (isset($_conf['jrCore_maintenance_mode']) && $_conf['jrCore_maintenance_mode'] == 'on') {
        jrCore_set_form_notice('notice', $_lang['jrCore'][35]);
    }

    // our page banner
    $html = jrCore_page_button('forgot', $_lang['jrUser'][41], "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/forgot'");
    jrCore_page_banner(40, $html, false);

    // Form init
    $_tmp = array(
        'submit_value' => 3,
        'cancel'       => jrCore_is_local_referrer()
    );
    $tok = jrCore_form_create($_tmp);

    // User Email OR User Name
    $_tmp = array(
        'name'     => 'user_email_or_name',
        'label'    => 1,
        'help'     => 19,
        'type'     => 'text',
        'validate' => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    // Password
    $_tmp = array(
        'name'       => 'user_password',
        'label'      => 7,
        'help'       => 8,
        'type'       => 'password',
        'error_msg'  => 9,
        'validate'   => 'not_empty',
        'onkeypress' => "if (event && event.keyCode == 13 && this.value.length > 0) { jrFormSubmit('#jrUser_login','{$tok}','ajax'); }"
    );
    jrCore_form_field_create($_tmp);

    // Remember Me
    $_tmp = array(
        'name'     => 'user_remember',
        'label'    => 13,
        'help'     => 14,
        'type'     => 'checkbox',
        'value'    => 'on',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// login_save
//------------------------------
function view_jrUser_login_save($_post, &$_user, $_conf)
{
    global $_user;
    jrCore_form_validate($_post);

    // Make sure user is valid
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_name', $_post['user_email_or_name']);
    if (!$_rt) {
        $_rt = jrCore_db_get_item_by_key('jrUser', 'user_email', $_post['user_email_or_name']);
        if (!$_rt) {
            jrCore_set_form_notice('error', 26);
            jrCore_form_result();
        }
    }

    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Validate password
    if (!class_exists('PasswordHash')) {
        require APP_DIR . '/modules/jrUser/contrib/phpass/PasswordHash.php';
    }
    $hash = new PasswordHash(12, false);
    if (!$hash->CheckPassword($_post['user_password'], $_rt['user_password'])) {
        jrUser_brute_force_check($_rt['_user_id']);
        jrCore_set_form_notice('error', 26);
        jrCore_form_result();
    }

    // Make sure account is validated
    if (!isset($_rt['user_validated']) || $_rt['user_validated'] != '1') {
        if (isset($_rt['quota_jrUser_signup_method']) && $_rt['quota_jrUser_signup_method'] == 'email') {
            // Give the user the ability to resend the activation email
            $_SESSION['allow_activation_resend'] = 1;
            $tmp = jrCore_page_button('resend', $_lang['jrUser'][28], "window.location='" . $_conf['jrCore_base_url'] . '/user/activation_resend/' . $_rt['_user_id'] . "'");
            jrCore_set_form_notice('error', $_lang['jrUser'][27] . '<br><br>' . $tmp, false);
        }
        else {
            jrCore_set_form_notice('error', $_lang['jrUser'][27]);
        }
        jrCore_form_result();
    }

    // Make sure account is active
    if (!isset($_rt['user_active']) || $_rt['user_active'] != '1') {
        jrCore_set_form_notice('error', 29);
        jrCore_form_result();
    }

    // Get any saved location from login
    $url = jrUser_get_saved_location();

    // Startup Session and login
    $_SESSION = $_rt;
    $_user = $_rt;
    $_SESSION['_user_id'] = $_rt['_user_id'];

    // Save home profile keys
    jrUser_save_profile_home_keys($_SESSION);

    // Maintenance login check
    if (jrCore_is_maintenance_mode($_conf, $_post)) {
        jrCore_set_form_notice('error', 30);
        jrCore_form_result();
    }

    // User has logged in - Start session
    jrUser_brute_force_cleanup($_SESSION['_user_id']);

    // Update last login time
    $now = time();
    $_data = array(
        'user_last_login' => $now
    );
    jrCore_db_update_item('jrUser', $_SESSION['_user_id'], $_data);
    $_SESSION['user_last_login'] = $now;

    // Bring in all profile and Quota info
    $_SESSION = jrUser_session_start();

    // Setup our "remember me" cookie if requested
    if (isset($_post['user_remember']) && $_post['user_remember'] === 'on') {
        jrUser_session_set_login_cookie($_SESSION['_user_id']);
    }
    else {
        jrUser_session_delete_login_cookie();
    }

    // Send out trigger on successful account activation
    $_user = $_SESSION;
    $_user = jrCore_trigger_event('jrUser', 'login_success', $_user);

    jrCore_logger('INF', "successful login by {$_post['user_email_or_name']}");
    jrCore_form_delete_session();

    // Redirect to Profile or Saved Location
    if (isset($url) && jrCore_checktype($url, 'url') && strpos($url, $_conf['jrCore_base_url']) === 0 && $url != $_conf['jrCore_base_url'] && $url != $_conf['jrCore_base_url'] . '/' && !strpos($url, '/signup')) {
        jrCore_form_result($url);
    }
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}");
}

//------------------------------
// logout
//------------------------------
function view_jrUser_logout($_post, $_user, $_conf)
{
    jrUser_session_require_login();

    // Delete all form sessions...
    $tbl = jrCore_db_table_name('jrCore', 'form_session');
    $req = "DELETE FROM {$tbl} WHERE form_user_id = '" . jrCore_db_escape($_user['_user_id']) . "'";
    jrCore_db_query($req);

    // Send logout trigger
    jrCore_trigger_event('jrUser', 'logout', $_user);

    // Destroy session and remove any login cookies
    jrUser_session_destroy();
    jrUser_session_delete_login_cookie();

    // Redirect to front page
    jrCore_form_result($_conf['jrCore_base_url']);
}

//------------------------------
// forgot
//------------------------------
function view_jrUser_forgot($_post, $_user, $_conf)
{
    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Check for maintenance mode
    if (isset($_conf['jrCore_maintenance_mode']) && $_conf['jrCore_maintenance_mode'] == 'on') {
        jrCore_set_form_notice('notice', $_lang['jrCore'][35]);
    }

    // our page banner
    jrCore_page_banner(44, null, false);

    // Form init
    $_tmp = array(
        'submit_value'     => 46,
        'cancel'           => 'referrer',
        'form_ajax_submit' => false
    );
    $tok = jrCore_form_create($_tmp);

    // User Email OR User Name
    $_tmp = array(
        'name'       => 'user_email',
        'label'      => 18,
        'help'       => 47,
        'type'       => 'text',
        'validate'   => 'email',
        'onkeypress' => "if (event && event.keyCode == 13 && this.value.length > 0) { jrFormSubmit('#jrUser_forgot','{$tok}','ajax'); }"
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// forgot_save
//------------------------------
function view_jrUser_forgot_save($_post, $_user, $_conf)
{
    jrCore_form_validate($_post);

    // Make sure user is valid
    $_rt = jrCore_db_get_item_by_key('jrUser', 'user_email', $_post['user_email']);
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 48);
        jrCore_form_result();
    }

    // Okay - this user is requesting a password reset:
    // - set a temp "reset" key
    // - send email to the address with the proper key URL
    // - user comes to form and resets password
    // - reset ALL user sessions/cookies for this user
    // - Send user an email letting them know their password was changed

    // First - cleanup
    $tbl = jrCore_db_table_name('jrUser', 'forgot');
    $dif = (time() - 86400);
    $req = "DELETE FROM {$tbl} WHERE forgot_time < {$dif}";
    jrCore_db_query($req);

    // New Entry
    $key = md5(microtime());
    $req = "INSERT INTO {$tbl} (forgot_user_id,forgot_time,forgot_key) VALUES ('{$_rt['_user_id']}',UNIX_TIMESTAMP(),'" . jrCore_db_escape($key) . "')";
    $uid = jrCore_db_query($req, 'INSERT_ID');
    if (!isset($uid) || !jrCore_checktype($uid, 'number_nz')) {
        jrCore_set_form_notice('error', 36);
        jrCore_form_result();
    }

    // Send out password reset email
    $_rp = array(
        'system_name' => $_conf['jrCore_system_name'],
        'reset_url'   => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/new_password/{$key}"
    );
    list($sub, $msg) = jrCore_parse_email_templates('jrUser', 'forgot', $_rp);
    jrCore_send_email($_rt['user_email'], $sub, $msg);

    // Our User Account is created...
    jrCore_logger('INF', "{$_rt['user_email']} has requested a password reset");

    jrCore_set_form_notice('success', 49, false);
    jrCore_form_delete_session();
    jrCore_form_result();
}

//------------------------------
// new_password
//------------------------------
function view_jrUser_new_password($_post, $_user, $_conf)
{
    $_lang = jrUser_load_lang_strings();

    // Make sure our token is valid
    if (!isset($_post['_1']) || !jrCore_checktype($_post['_1'], 'md5')) {
        jrCore_notice_page('error', 52);
    }

    // Validate Token
    $tbl = jrCore_db_table_name('jrUser', 'forgot');
    $req = "SELECT * FROM {$tbl} WHERE forgot_key = '" . jrCore_db_escape($_post['_1']) . "' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice_page('error', 52);
    }

    // Check for maintenance mode
    if (isset($_conf['jrCore_maintenance_mode']) && $_conf['jrCore_maintenance_mode'] == 'on') {
        jrCore_set_form_notice('notice', $_lang['jrCore'][35]);
    }

    // our page banner
    jrCore_page_banner(50);

    // Form init
    $_tmp = array(
        'submit_value' => 51,
        'cancel'       => false
    );
    $tok = jrCore_form_create($_tmp);

    // Token
    $_tmp = array(
        'name'  => 'password_token',
        'type'  => 'hidden',
        'value' => $_post['_1']
    );
    jrCore_form_field_create($_tmp);

    // Password #1
    $_tmp = array(
        'name'      => 'user_passwd1',
        'label'     => 20,
        'help'      => 21,
        'type'      => 'password',
        'error_msg' => 9,
        'validate'  => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    // Password #2
    $_tmp = array(
        'name'       => 'user_passwd2',
        'label'      => 22,
        'help'       => 23,
        'type'       => 'password',
        'error_msg'  => 9,
        'validate'   => 'not_empty',
        'onkeypress' => "if (event && event.keyCode == 13 && this.value.length > 0) { jrFormSubmit('#jrUser_new_password','{$tok}','ajax'); }"
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// new_password_save
//------------------------------
function view_jrUser_new_password_save($_post, $_user, $_conf)
{
    jrCore_form_validate($_post);

    // Make sure our token is valid
    if (!isset($_post['password_token']) || !jrCore_checktype($_post['password_token'], 'md5')) {
        jrCore_set_form_notice('error', 52);
        jrCore_form_result();
    }

    // Validate Token
    $tbl = jrCore_db_table_name('jrUser', 'forgot');
    $req = "SELECT * FROM {$tbl} WHERE forgot_key = '" . jrCore_db_escape($_post['password_token']) . "' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 52);
        jrCore_form_result();
    }

    // Get user info
    $_us = jrCore_db_get_item('jrUser', $_rt['forgot_user_id'], true); // OK
    if (!isset($_us) || !is_array($_us)) {
        jrCore_set_form_notice('error', 52);
        jrCore_form_result();
    }

    // Make sure passwords match
    if (!isset($_post['user_passwd1']) || strlen($_post['user_passwd1']) === 0 || !isset($_post['user_passwd2']) || strlen($_post['user_passwd2']) === 0) {
        jrCore_set_form_notice('error', 35);
        jrCore_form_field_hilight('user_passwd1');
        jrCore_form_field_hilight('user_passwd2');
        jrCore_form_result();
    }
    if (isset($_post['user_passwd1']) && isset($_post['user_passwd2']) && $_post['user_passwd1'] != $_post['user_passwd2']) {
        jrCore_set_form_notice('error', 35);
        jrCore_form_field_hilight('user_passwd1');
        jrCore_form_field_hilight('user_passwd2');
        jrCore_form_result();
    }
    // Setup new password
    require APP_DIR . '/modules/jrUser/contrib/phpass/PasswordHash.php';
    $hash = new PasswordHash(12, false);
    $pass = $hash->HashPassword($_post['user_passwd1']);

    // Update user with new password
    $_dt = array(
        'user_password'   => $pass,
        'user_last_login' => time()
    );
    if (!jrCore_db_update_item('jrUser', $_rt['forgot_user_id'], $_dt)) {
        jrCore_set_form_notice('error', 36);
        jrCore_form_result();
    }

    // Cleanup forgot
    $dif = (time() - 86400);
    $req = "DELETE FROM {$tbl} WHERE (forgot_key = '" . jrCore_db_escape($_post['_1']) . "' OR forgot_time < {$dif})";
    jrCore_db_query($req);

    // Cleanup Session, Cookie and Cache
    $tbl = jrCore_db_table_name('jrUser', 'session');
    $req = "DELETE FROM {$tbl} WHERE session_user_id = '{$_rt['forgot_user_id']}'";
    jrCore_db_query($req);

    $tbl = jrCore_db_table_name('jrUser', 'cookie');
    $req = "DELETE FROM {$tbl} WHERE cookie_user_id = '{$_rt['forgot_user_id']}'";
    jrCore_db_query($req);

    $tbl = jrCore_db_table_name('jrCore', 'cache');
    $req = "DELETE FROM {$tbl} WHERE cache_user_id = '{$_rt['forgot_user_id']}'";
    jrCore_db_query($req);

    if (!isset($_us['user_validated']) || $_us['user_validated'] != '1') {
        // User has not been validated yet
        jrCore_notice_page('error', 27);
    }

    // Log user in if we are NOT in maintenance mode
    if (isset($_conf['jrCore_maintenance_mode']) && $_conf['jrCore_maintenance_mode'] == 'on') {
        // If we are NOT an admin...
        if ($_us['user_group'] != 'master' && $_us['user_group'] != 'admin') {
            jrCore_notice_page('error', 30);
        }
    }

    // Startup session with user info
    $_SESSION = $_us;
    $_SESSION = jrCore_trigger_event('jrUser', 'login_success', $_SESSION);
    unset($_rt);

    // Show them success
    jrCore_logger('INF', "{$_SESSION['user_email']} has reset their password and logged in");

    // Redirect to Profile
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_us['profile_url']}");
}

//------------------------------
// account
//------------------------------
function view_jrUser_account($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_session_require_login();
    $_lang = jrUser_load_lang_strings();

    // If this a master admin modifying...
    if (jrUser_is_master()) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs('jrUser');
    }

    // See if we are modifying a different account
    if (jrUser_is_admin() && isset($_post['user_id']) && jrCore_checktype($_post['user_id'], 'number_nz')) {
        $_data = jrCore_db_get_item('jrUser', $_post['user_id'], true); // OK
        if (!isset($_data) || !is_array($_data)) {
            jrCore_notice_page('error', 'invalid id - please pass in a valid user_id');
        }
        jrUser_account_tabs('account', $_data);
    }
    else {
        jrUser_account_tabs('account');
    }

    // Get additional settings
    foreach ($_mods as $module => $_inf) {
        if (is_file(APP_DIR . "/modules/{$module}/user.php")) {
            require_once APP_DIR . "/modules/{$module}/user.php";
            $func = "{$module}_user_settings";
            if (function_exists($func)) {
                $func();
            }
        }
    }

    // See if this is a profile admin
    if (isset($_data)) {

        // User ID we are modifying
        $_tmp = array(
            'name'     => 'user_id',
            'type'     => 'hidden',
            'value'    => $_data['_user_id'],
            'validate' => 'number_nz'
        );
        jrCore_form_field_create($_tmp);

        // Profile ID we are modifying
        $_tmp = array(
            'name'     => 'profile_id',
            'type'     => 'hidden',
            'value'    => $_data['_profile_id'],
            'validate' => 'number_nz'
        );
        jrCore_form_field_create($_tmp);
    }
    else {
        $_data = jrCore_db_get_item('jrUser', $_user['_user_id'], true); // OK
        $_post['user_id'] = $_user['_user_id'];
        $_post['profile_id'] = $_user['_profile_id'];
    }

    // Make sure we set error if no email address
    // NOTE: this can happen using the social login
    if (!jrCore_checktype($_user['user_email'], 'email')) {
        jrCore_set_form_notice('error', 68);
        jrCore_form_field_hilight('user_email');
    }

    // our page banner
    jrCore_page_banner(42, false, false);

    // Form init
    $_tmp = array(
        'submit_value'     => $_lang['jrCore'][72],
        'cancel'           => 'referrer',
        'values'           => $_data,
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // User Avatar
    $_tmp = array(
        'name'     => 'user_image',
        'label'    => 53,
        'help'     => 93,
        'type'     => 'image',
        'size'     => 'medium',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // User Name
    $_tmp = array(
        'name'      => 'user_name',
        'label'     => 4,
        'help'      => 5,
        'type'      => 'text',
        'validate'  => 'printable',
        'ban_check' => 'word',
        'required'  => true
    );
    jrCore_form_field_create($_tmp);

    // User Email
    $_tmp = array(
        'name'     => 'user_email',
        'label'    => 18,
        'help'     => 57,
        'type'     => 'text',
        'validate' => 'email',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Preferred Language
    $_lng = jrUser_get_languages();
    if (isset($_lng) && is_array($_lng) && count($_lng) > 1) {
        $_tmp = array(
            'name'     => 'user_language',
            'label'    => 62,
            'help'     => 63,
            'type'     => 'select',
            'options'  => $_lng,
            'required' => true
        );
        jrCore_form_field_create($_tmp);
    }

    // Password #1
    $_tmp = array(
        'name'      => 'user_passwd1',
        'label'     => 7,
        'help'      => 8,
        'type'      => 'password',
        'error_msg' => 9,
        'required'  => false,
        'validate'  => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    // Password #2
    $_tmp = array(
        'name'      => 'user_passwd2',
        'label'     => 32,
        'help'      => 23,
        'type'      => 'password',
        'error_msg' => 9,
        'required'  => false,
        'validate'  => 'not_empty'
    );
    jrCore_form_field_create($_tmp);

    $_tmp = jrCore_get_flag('jruser_register_setting');
    if ($_tmp) {
        foreach ($_tmp as $smod => $_entries) {
            // Make sure the viewing user has Quota access to this module
            if (isset($_user["quota_{$smod}_allowed"]) && $_user["quota_{$smod}_allowed"] != 'on') {
                continue;
            }
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

    // Master Admin options
    if (jrUser_is_master()) {
        jrCore_page_section_header('master admin options');
        $_tmp = array(
            'name'          => 'user_group',
            'label'         => 'user group',
            'help'          => 'Select the user group this user should be part of:<br><br><b>Standard User:</b> a normal user account in your system - can modify items they have created only.<br><b>Profile Admin:</b> can modify users and profiles and items created by any user on the system. Has access to the Dashboard.<br><b>Master Admin:</b> full access to all system areas including the Admin Control Panel and Dashboard.',
            'type'          => 'select',
            'options'       => array('user' => 'Standard User', 'admin' => 'Profile Admin', 'master' => 'Master Admin'),
            'value'         => $_data['user_group'],
            'group'         => 'master',
            'validate'      => 'core_string',
            'form_designer' => false
        );
        jrCore_form_field_create($_tmp);

        // See if this user is linked to more than 1 profile
        if ($_data['user_group'] != 'master' && $_data['user_group'] != 'admin') {
            $_lp = jrProfile_get_user_linked_profiles($_data['_user_id']);
            if (isset($_lp) && is_array($_lp) && count($_lp) > 0) {
                // looks like this user is linked to more than 1 profile
                $tbl = jrCore_db_table_name('jrProfile', 'item_key');
                $req = "SELECT `_item_id`, `value` FROM {$tbl} WHERE `key` = 'profile_name' AND `_item_id` IN('" . implode("','", array_keys($_lp)) . "') AND `_item_id` != '{$_data['_profile_id']}' ORDER BY `value` DESC";
                $_pr = jrCore_db_query($req, '_item_id', false, 'value');
                if (isset($_pr) && is_array($_pr)) {
                    $_tmp = array(
                        'name'          => 'user_linked_profiles',
                        'label'         => 'additional profiles',
                        'help'          => "This User Account is linked to additional User Profiles. Uncheck a profile to prevent this user from accessing it.",
                        'type'          => 'optionlist',
                        'options'       => $_pr,
                        'value'         => array_keys($_lp),
                        'group'         => 'master',
                        'form_designer' => false
                    );
                    jrCore_form_field_create($_tmp);
                }
            }
        }
    }

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// account_save
//------------------------------
function view_jrUser_account_save($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    jrCore_form_validate($_post);

    // Get posted data
    $_data = jrCore_form_get_save_data('jrUser', 'account', $_post);

    // Check for changing passwords
    if ((isset($_post['user_passwd1']) && strlen($_post['user_passwd1']) > 0) || (isset($_post['user_passwd2']) && strlen($_post['user_passwd2']) > 0)) {
        if (isset($_post['user_passwd1']) && isset($_post['user_passwd2']) && $_post['user_passwd1'] != $_post['user_passwd2']) {
            jrCore_set_form_notice('error', 35);
            jrCore_form_field_hilight('user_passwd1');
            jrCore_form_field_hilight('user_passwd2');
            jrCore_form_result();
        }
        // Setup new password
        require APP_DIR . '/modules/jrUser/contrib/phpass/PasswordHash.php';
        $hash = new PasswordHash(12, false);
        $pass = $hash->HashPassword($_post['user_passwd1']);
        // Add in new password hash
        $_data['user_password'] = $pass;
    }

    // See if this ias an admin modifying this user account
    $uid = $_user['_user_id'];
    if (jrUser_is_admin() && isset($_post['user_id']) && jrCore_checktype($_post['user_id'], 'number_nz')) {
        $uid = (int) $_post['user_id'];
        $_us = jrCore_db_get_item('jrUser', $uid); // OK
        if (isset($_post['user_group']{0})) {
            $_data['user_group'] = $_post['user_group'];
        }
        // Check for changes in linked profiles
        if (isset($_post['user_linked_profiles']) && strlen($_post['user_linked_profiles']) > 0) {
            $tbl = jrCore_db_table_name('jrProfile', 'profile_link');
            $req = "DELETE FROM {$tbl} WHERE user_id = '{$uid}' AND profile_id NOT IN({$_post['profile_id']},{$_post['user_linked_profiles']})";
            jrCore_db_query($req);
        }
    }
    else {
        $_us = $_user;
    }

    // Check for changing user_email
    $tbl = jrCore_db_table_name('jrUser', 'item_key');
    $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = 'user_email' AND `value` = '" . jrCore_db_escape($_post['user_email']) . "' AND `_item_id` != '{$uid}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 96);
        jrCore_form_field_hilight('user_email');
        jrCore_form_result();
    }

    // Check for changing user_name
    $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = 'user_name' AND `value` = '" . jrCore_db_escape($_post['user_name']) . "' AND `_item_id` != '{$uid}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 100);
        jrCore_form_field_hilight('user_name');
        jrCore_form_result();
    }

    // See if we got a language
    if (isset($_post['user_language'])) {
        $_data['user_language'] = $_post['user_language'];
    }

    // Save info
    jrCore_db_update_item('jrUser', $uid, $_data);

    // If we are changing email, send an email to the OLD email address
    // outlining that the email address has been changed on the account
    if (!jrUser_is_admin() && $_data['user_email'] != $_user['user_email']) {
        // They are changing email...
        $_rp = array(
            'system_name' => $_conf['jrCore_system_name'],
            'new_email'   => $_data['user_email']
        );
        list($sub, $msg) = jrCore_parse_email_templates('jrUser', 'change', $_rp);
        jrCore_send_email($_user['user_email'], $sub, $msg);
    }

    // Check for Photo upload
    $tempid = $_us['_profile_id'];
    $_us['_profile_id'] = jrUser_get_profile_home_key('_profile_id');
    $_image = jrCore_save_media_file('jrUser', 'user_image', $_us['_profile_id'], $uid);
    // If the user does NOT have a profile image yet, set the user image to be the profile image...
    if (!isset($_us['profile_image_size']) && isset($_image) && is_array($_image)) {
        $_us = array_merge($_us, $_image);
        $user_image = jrCore_get_media_file_path('jrUser', 'user_image', $_us);
        if (is_file($user_image)) {
            $ext = jrCore_file_extension($user_image);
            $nam = "{$_us['_profile_id']}_profile_image";
            if (jrCore_copy_media_file($_us['_profile_id'], $user_image, $nam)) {
                $dir = dirname($user_image);
                jrCore_write_to_file("{$dir}/{$nam}.tmp", "profile_image.{$ext}");
                jrCore_save_media_file('jrProfile', "{$dir}/{$nam}", $_us['_profile_id'], $_us['_profile_id']);
                unlink("{$dir}/{$nam}");
                unlink("{$dir}/{$nam}.tmp");
            }
        }
        $_us['_profile_id'] = $tempid;
    }
    unset($tempid);
    jrCore_form_delete_session();

    // Re-sync session
    if (isset($uid) && $uid == $_user['_user_id']) {
        jrUser_session_sync($uid);
    }

    // Reset caches
    $_ln = jrProfile_get_user_linked_profiles($_us['_user_id']);
    if (isset($_ln) && is_array($_ln)) {
        foreach ($_ln as $pid => $uid) {
            jrProfile_reset_cache($pid);
        }
    }

    // If we are an ADMIN user modifying someone else...
    if (jrUser_is_admin() && isset($_post['user_id']) && jrCore_checktype($_post['user_id'], 'number_nz')) {
        jrCore_set_form_notice('success', 'The user account has been successfully updated');
        jrCore_form_result();
    }
    // jrProfile_sync_active_profile_data();
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}");
}

//------------------------------
// notifications
//------------------------------
function view_jrUser_notifications($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    $_lang = jrUser_load_lang_strings();

    // If this a master admin modifying...
    if (jrUser_is_master()) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs('jrUser');
    }

    // See if we are modifying a different account
    $disabled = false;
    if (jrUser_is_admin() && isset($_post['user_id']) && jrCore_checktype($_post['user_id'], 'number_nz')) {
        $_data = jrCore_db_get_item('jrUser', $_post['user_id'], true); // OK
        if (!isset($_data) || !is_array($_data)) {
            jrCore_notice_page('error', 'invalid id - please pass in a valid user_id');
        }
        jrUser_account_tabs('notifications', $_data);
    }
    else {
        jrUser_account_tabs('notifications');
        // See if this user has all notifications disabled
        if (isset($_user['user_notifications_disabled']) && $_user['user_notifications_disabled'] == 'on') {
            jrCore_set_form_notice('notice', 95);
            $disabled = true;
        }
    }

    // our page banner
    jrCore_page_banner(64, false, false);

    // Form init
    $_tmp = array(
        'submit_value'     => $_lang['jrCore'][72],
        'cancel'           => 'referrer',
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // See if this is a profile admin
    if (isset($_data)) {

        // User ID we are modifying
        $_tmp = array(
            'name'     => 'user_id',
            'type'     => 'hidden',
            'value'    => $_data['_user_id'],
            'validate' => 'number_nz'
        );
        jrCore_form_field_create($_tmp);

        // Profile ID we are modifying
        $_tmp = array(
            'name'     => 'profile_id',
            'type'     => 'hidden',
            'value'    => $_data['_profile_id'],
            'validate' => 'number_nz'
        );
        jrCore_form_field_create($_tmp);
    }
    else {
        $_post['user_id'] = $_user['_user_id'];
        $_post['profile_id'] = $_user['_profile_id'];
    }

    // Get our registered notification events
    $_tmp = jrCore_get_registered_module_features('jrUser', 'notification');
    if (isset($_tmp) && is_array($_tmp)) {
        foreach ($_tmp as $module => $_events) {

            // Make sure this user has Quota access
            if (isset($_user["quota_{$module}_allowed"]) && $_user["quota_{$module}_allowed"] != 'on') {
                continue;
            }

            foreach ($_events as $name => $label) {
                $_opts = array(
                    'off'   => $_lang['jrUser'][65],
                    'email' => $_lang['jrUser'][66],
                    'note'  => $_lang['jrUser'][67]
                );
                if ($name == 'note_received') {
                    unset($_opts['note']);
                }

                // If we have disabled all notifications, show that
                if ($disabled) {
                    $_user["user_{$module}_{$name}_notifications"] = 'off';
                }

                if (isset($label) && is_array($label)) {

                    // With our $label being an array we have some control over
                    // how this notification option will appear in the User Notifications
                    if (isset($label['function']) && function_exists($label['function'])) {
                        $func = $label['function'];
                        $_args = array(
                            'module' => $module,
                            'event'  => $name,
                        );
                        if (!$func($_post, $_user, $_conf, $_args)) {
                            continue;
                        }
                    }
                    $_tmp = array(
                        'name'    => "event_{$module}_{$name}",
                        'label'   => ((isset($_lang[$module]["{$label['label']}"])) ? $_lang[$module]["{$label['label']}"] : $label['label']) . ':',
                        'type'    => 'radio',
                        'options' => $_opts,
                        'value'   => (isset($_user["user_{$module}_{$name}_notifications"])) ? $_user["user_{$module}_{$name}_notifications"] : 'email'
                    );
                    if (!empty($label['help'])) {
                        $_tmp['help'] = ((isset($_lang[$module]["{$label['help']}"])) ? $_lang[$module]["{$label['help']}"] : $label['help']);
                    }
                }
                else {
                    $_tmp = array(
                        'name'    => "event_{$module}_{$name}",
                        'label'   => ((isset($_lang[$module][$label])) ? $_lang[$module][$label] : $label) . ':',
                        'type'    => 'radio',
                        'options' => $_opts,
                        'value'   => (isset($_user["user_{$module}_{$name}_notifications"])) ? $_user["user_{$module}_{$name}_notifications"] : 'email'
                    );
                }
                jrCore_form_field_create($_tmp);
            }
        }
    }
    jrCore_page_display();
}

//------------------------------
// notifications_save
//------------------------------
function view_jrUser_notifications_save($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    jrCore_form_validate($_post);

    // See if this ias an admin modifying this user account
    $uid = $_user['_user_id'];
    if (jrUser_is_admin() && isset($_post['user_id']) && jrCore_checktype($_post['user_id'], 'number_nz')) {
        $uid = (int) $_post['user_id'];
    }
    $_up = array();
    foreach ($_post as $k => $v) {
        if (strpos($k, 'event_') === 0) {
            $nam = 'user_' . substr($k, 6) . '_notifications';
            $_up[$nam] = $v;
            if (isset($uid) && $uid == $_user['_user_id']) {
                $_SESSION[$nam] = $v;
            }
        }
    }
    if (isset($_up) && is_array($_up) && count($_up) > 0) {
        $_up['user_notifications_disabled'] = 'off';
        jrCore_db_update_item('jrUser', $uid, $_up);
    }
    jrCore_set_form_notice('success', 43);
    jrCore_form_delete_session();

    // Re-sync session
    if (isset($uid) && $uid == $_user['_user_id']) {
        jrUser_session_sync($uid);
    }
    jrCore_form_result('referrer');
}

//------------------------------
// online
//------------------------------
function view_jrUser_online($_post, $_user, $_conf)
{
    if (!jrUser_is_admin()) {
        jrUser_not_authorized();
    }
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrUser');

    // our page banner
    jrCore_page_banner('Users Online');
    jrCore_get_form_notice();

    jrUser_online_users($_post, $_user, $_conf);

    jrCore_page_display();
}

//------------------------------
// online
//------------------------------
function view_jrUser_session_remove_save($_post, $_user, $_conf)
{
    if (!jrUser_is_admin()) {
        jrUser_not_authorized();
    }

    if (!isset($_post['_1']) || strlen($_post['_1']) === 0) {
        jrCore_set_form_notice('error', 'Invalid Session ID');
        jrCore_form_result('referrer');
    }
    $tbl = jrCore_db_table_name('jrUser', 'session');
    $req = "DELETE FROM {$tbl} WHERE session_id = '" . jrCore_db_escape($_post['_1']) . "' AND session_user_id != '{$_user['_user_id']}'";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (!isset($cnt) || $cnt === 0) {
        jrCore_set_form_notice('error', 'Unable to remove session from session table - please try again.');
    }
    jrCore_form_result('referrer');
}

//------------------------------
// Un-subscribe
//------------------------------
function view_jrUser_unsubscribe($_post, $_user, $_conf)
{
    if (!isset($_post['_1']) || !jrCore_checktype($_post['_1'], 'md5')) {
        jrCore_notice('Error', 'Invalid unique subscriber ID - please make sure you are entering the full URL from the unsubscribe link (1)');
    }
    $tbl = jrCore_db_table_name('jrUser', 'item_key');
    $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = 'user_validate' AND `value` = '" . jrCore_db_escape($_post['_1']) . "' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice('Error', 'Invalid unique subscriber ID - please make sure you are entering the full URL from the unsubscribe link (2)');
    }
    // Set special "user_notifications_disabled" flag to "on" so no notifications ever go to this user
    $_tmp = array(
        'user_notifications_disabled' => 'on'
    );
    jrCore_db_update_item('jrUser', $_rt['_item_id'], $_tmp);

    $_ln = jrUser_load_lang_strings();
    jrCore_notice_page('success', $_ln['jrUser'][94]);
}
