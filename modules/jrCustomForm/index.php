<?php
/**
 * Jamroom 5 jrCustomForm module
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

//------------------------------
// default
//------------------------------
function view_jrCustomForm_default($_post, $_user, $_conf)
{
    if (empty($_post['option'])) {
        jrCore_notice_page('error', 'You must specific the form');
    }

    // See if we are SAVING or DISPLAYING a form
    if (isset($_post['jr_html_form_token']{31}) && strpos($_post['option'], '_save')) {

        // Save data to datastore
        $nam = str_replace('_save', '', $_post['option']);
        // make sure this is a good form
        $tbl = jrCore_db_table_name('jrCustomForm', 'form');
        $req = "SELECT * FROM {$tbl} WHERE form_name = '" . jrCore_db_escape($nam) . "' LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_notice_page('error', 'Invalid form');
        }
        if (isset($_rt['form_login']) && $_rt['form_login'] == 'on') {
            jrUser_session_require_login();
        }

        $_sv = jrCore_form_get_save_data('jrCustomForm', $nam, $_post);
        $_sv['form_name'] = $nam;
        $_sv['form_created'] = time();
        $_sv['form_user_ip'] = jrCore_get_ip();
        if (jrUser_is_logged_in()) {
            $_sv['form_user_id'] = $_user['_user_id'];
            $_sv['form_user_name'] = $_user['user_name'];
            $_sv['form_profile_name'] = $_user['profile_name'];

            // Check for form unique
            if (isset($_rt['form_unique']) && $_rt['form_unique'] == 'on') {
                // Make sure they have not submitted before
                $_sp = array(
                    'search'        => array(
                        "form_user_id = {$_user['_user_id']}",
                        "form_name = {$nam}"
                    ),
                    'return_count'  => true,
                    'skip_triggers' => true,
                    'privacy_check' => false // disable privacy check
                );
                $cnt = jrCore_db_search_items('jrCustomForm', $_sp);
                if (isset($cnt) && $cnt > 0) {
                    jrCore_notice_page('error', 5);
                }
            }

        }
        $fid = jrCore_db_create_item('jrCustomForm', $_sv);
        if (isset($fid) && jrCore_checktype($fid, 'number_nz')) {

            // Save any uploaded media files added in by our
            if (jrUser_is_logged_in()) {
                jrCore_save_all_media_files('jrCustomForm', $nam, $_user['user_active_profile_id'], $fid);
            }

            // Update response count
            $req = "UPDATE {$tbl} SET form_responses = (form_responses + 1) WHERE form_id = '{$_rt['form_id']}' LIMIT 1";
            jrCore_db_query($req);

            // Check for notifications
            switch ($_rt['form_notify']) {

                case 'master_email':
                case 'admin_email':
                    $_sp = array(
                        'search'        => array(
                            "user_group = master",
                        ),
                        'order_by'      => array(
                            'user_name' => 'desc'
                        ),
                        'limit'         => 100,
                        'return_keys'   => array('_user_id', 'user_email'),
                        'skip_triggers' => true,
                        'privacy_check' => false // disable privacy check
                    );
                    if ($_rt['form_notify'] == 'admin_email') {
                        $_sp['search'][0] = 'user_group IN master,admin';
                    }
                    $_us = jrCore_db_search_items('jrUser', $_sp);
                    if (isset($_us) && isset($_us['_items']) && is_array($_us['_items'])) {
                        $_rp = array(
                            'system_name'      => $_conf['jrCore_system_name'],
                            'form_browser_url' => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser_item_update/id={$fid}",
                            'user_ip'          => $_sv['form_user_ip']
                        );
                        $_rp = array_merge($_rt, $_rp);
                        list($sub, $msg) = jrCore_parse_email_templates('jrCustomForm', 'form_received', $_rp);
                        foreach ($_us['_items'] as $_v) {
                            jrCore_send_email($_v['user_email'], $sub, $msg);
                        }
                    }
                    break;
            }
            jrCore_form_delete_session();
            jrCore_notice_page('success', 2, $_conf['jrCore_base_url'], 4);
        }
        else {
            jrCore_notice_page('error', 3);
        }
    }
    else {

        // Check for form
        $tbl = jrCore_db_table_name('jrCustomForm', 'form');
        $req = "SELECT * FROM {$tbl} WHERE form_name = '" . jrCore_db_escape($_post['option']) . "' LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_notice_page('error', 'Invalid form');
        }
        if (isset($_rt['form_login']) && $_rt['form_login'] == 'on') {
            jrUser_session_require_login();
        }
        if (jrUser_is_logged_in() && isset($_rt['form_unique']) && $_rt['form_unique'] == 'on') {
            // Make sure they have not submitted before
            $_sp = array(
                'search'        => array(
                    "form_user_id = {$_user['_user_id']}",
                    "form_name = {$_post['option']}"
                ),
                'return_count'  => true,
                'skip_triggers' => true,
                'privacy_check' => false // disable privacy check
            );
            $cnt = jrCore_db_search_items('jrCustomForm', $_sp);
            if (isset($cnt) && $cnt > 0) {
                jrCore_notice_page('error', 5);
            }
        }
        jrCore_register_module_feature('jrCore', 'designer_form', 'jrCustomForm', $_post['option']);
        $_lng = jrUser_load_lang_strings();

        jrCore_page_banner($_rt['form_title']);
        if (!empty($_rt['form_message'])) {
            jrCore_page_note($_rt['form_message']);
        }

        // Form init
        $_tmp = array(
            'submit_value'     => 1,
            'cancel'           => jrCore_is_profile_referrer(),
            'form_ajax_submit' => false
        );
        jrCore_form_create($_tmp);

        $_fields = jrCore_get_designer_form_fields('jrCustomForm', $_post['option']);
        if (!isset($_fields) || !is_array($_fields)) {
            jrCore_notice_page('error', 'This form has not been setup yet');
        }
        foreach ($_fields as $_tmp) {
            // If we have any file based form types, user must be logged in
            switch ($_tmp['type']) {
                case 'file':
                case 'audio':
                case 'video':
                case 'image':
                    if (jrUser_is_logged_in()) {
                        jrCore_form_field_create($_tmp);
                    }
                    break;
                default:
                    jrCore_form_field_create($_tmp);
                    break;
            }
        }

        if (!jrUser_is_logged_in()) {
            // Spam Bot Check
            $_tmp = array(
                'name'          => 'form_is_human',
                'label'         => $_lng['jrUser'][90],
                'help'          => $_lng['jrUser'][91],
                'type'          => 'checkbox_spambot',
                'error_msg'     => $_lng['jrUser'][92],
                'validate'      => 'onoff',
                'order'         => 50,
                'form_designer' => false
            );
            jrCore_form_field_create($_tmp);
        }
        jrCore_page_display();
    }
}

//------------------------------
// browse
//------------------------------
function view_jrCustomForm_browse($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCustomForm', 'tools');

    $num = jrCore_db_number_rows('jrCustomForm', 'form');
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $req = "SELECT * FROM {$tbl} ORDER BY form_updated DESC";
    // find how many lines we are showing
    if (!isset($_post['p']) || !jrCore_checktype($_post['p'], 'number_nz')) {
        $_post['p'] = 1;
    }
    $_rt = jrCore_db_paged_query($req, $_post['p'], 12, 'NUMERIC', $num);

    $create = jrCore_page_button('form_create', 'create new form', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/create'");
    jrCore_page_banner('custom forms', $create);
    jrCore_get_form_notice();

    $dat = array();
    $dat[1]['title'] = 'name';
    $dat[1]['width'] = '20%;';
    $dat[2]['title'] = 'title';
    $dat[2]['width'] = '40%;';
    $dat[3]['title'] = 'responses';
    $dat[3]['width'] = '10%;';
    $dat[4]['title'] = 'login';
    $dat[4]['width'] = '10%;';
    $dat[5]['title'] = 'unique';
    $dat[5]['width'] = '10%;';
    $dat[6]['title'] = 'modify';
    $dat[6]['width'] = '5%;';
    $dat[7]['title'] = 'delete';
    $dat[7]['width'] = '5%;';
    jrCore_page_table_header($dat);

    if (isset($_rt['_items']) && is_array($_rt['_items'])) {
        foreach ($_rt['_items'] as $k => $_form) {
            $dat = array();
            $dat[1]['title'] = "<a href=\"{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$_form['form_name']}\" target=\"_blank\"><u>{$_form['form_name']}</u></a>";
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $_form['form_title'];
            if (isset($_form['form_responses']) && $_form['form_responses'] > 0) {
                $dat[3]['title'] = jrCore_page_button("c{$k}", $_form['form_responses'], "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser/search_string=form_name%3A{$_form['form_name']}'");
            }
            else {
                $dat[3]['title'] = '0';
            }
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = $_form['form_login'];
            $dat[4]['class'] = 'center';
            $dat[5]['title'] = $_form['form_unique'];
            $dat[5]['class'] = 'center';
            $dat[6]['title'] = jrCore_page_button("m{$k}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/update/id={$_form['form_id']}'");
            $dat[7]['title'] = jrCore_page_button("d{$k}", 'delete', "if (confirm('Are you sure you want to delete this form?')) { window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/delete_save/id={$_form['form_id']}' }");
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_pager($_rt);
    }
    else {
        $dat = array();
        $dat[1]['title'] = '<p>There are no custom forms to show.</p>';
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    jrCore_page_display();
}

//------------------------------
// create
//------------------------------
function view_jrCustomForm_create($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCustomForm', 'tools');

    jrCore_page_banner('create new custom form');

    // Form init
    $_tmp = array(
        'submit_value' => 'create form',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/browse"
    );
    jrCore_form_create($_tmp);

    // Form Name
    $_tmp = array(
        'name'     => 'form_name',
        'label'    => 'form name',
        'help'     => 'Enter a unique form name for this new form. It should be lowercase and consist of letters and underscores only.',
        'type'     => 'text',
        'validate' => 'core_string',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Title
    $_tmp = array(
        'name'     => 'form_title',
        'label'    => 'form title',
        'help'     => 'Enter a title for this form - it will be used in the form header as well as the page title.',
        'type'     => 'text',
        'validate' => 'printable',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Message
    $_tmp = array(
        'name'     => 'form_message',
        'label'    => 'form message',
        'help'     => 'Enter an optional message for this form - it will appear above the form and can be used for form instructions, etc.',
        'type'     => 'textarea',
        'validate' => 'allowed_html',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Form Login
    $_tmp = array(
        'name'     => 'form_login',
        'label'    => 'form login',
        'help'     => 'Check this option to only allow logged in users to view this form.',
        'type'     => 'checkbox',
        'default'  => 'off',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Unique
    $_tmp = array(
        'name'     => 'form_unique',
        'label'    => 'form unique',
        'help'     => 'Check this option to ensure that a user can only fill out this form one time (only works if <b>form login</b> is checked too)',
        'type'     => 'checkbox',
        'default'  => 'off',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Notification
    $_sel = array(
        'master_email' => 'Send email to Master Admins',
        'admin_email'  => 'Send email to Admin Users',
        'none'         => 'Store in DataStore only'
    );
    $_tmp = array(
        'name'     => 'form_notify',
        'label'    => 'form notify',
        'help'     => 'Select the type of Notification you would like to be sent out on a successful form submission',
        'type'     => 'select',
        'default'  => 'master_email',
        'options'  => $_sel,
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrCustomForm_create_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Make sure this new form is unique
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $req = "SELECT * FROM {$tbl} WHERE form_name = '" . jrCore_db_escape($_post['form_name']) . "' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt)) {
        jrCore_set_form_notice('error', 'There is already a form using that form name - please enter another');
        jrCore_form_field_hilight('form_name');
        jrCore_form_result();
    }
    $nam = jrCore_db_escape($_post['form_name']);
    $ttl = jrCore_db_escape($_post['form_title']);
    $msg = jrCore_db_escape($_post['form_message']);
    $req = "INSERT INTO {$tbl} (form_created,form_updated,form_name,form_title,form_message,form_unique,form_login)
            VALUES (UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'{$nam}','{$ttl}','{$msg}','{$_post['form_unique']}','{$_post['form_login']}')";
    $fid = jrCore_db_query($req, 'INSERT_ID');
    if (isset($fid) && jrCore_checktype($fid, 'number_nz')) {
        // Create our single default field
        $_field = array(
            'name'     => 'form_content',
            'type'     => 'textarea',
            'label'    => 'Content',
            'help'     => 'change this',
            'validate' => 'printable',
            'required' => true
        );
        jrCore_verify_designer_form_field('jrCustomForm', $_post['form_name'], $_field);

        // Activate it or it won't show
        $tbl = jrCore_db_table_name('jrCore', 'form');
        $req = "UPDATE {$tbl} SET `active` = 1 WHERE `module` = 'jrCustomForm' AND `view` = '" . jrCore_db_escape($_post['form_name']) . "' AND `name` = 'form_content' LIMIT 1";
        jrCore_db_query($req);

        // Redirect to form designer
        jrCore_register_module_feature('jrCore', 'designer_form', 'jrCustomForm', $_post['form_name']);
        jrCore_location("{$_conf['jrCore_base_url']}/{$_post['module_url']}/form_designer/m=jrCustomForm/v=" . $_post['form_name']);
    }
    jrCore_set_form_notice('error', 'An error was encountered creating the new form - please try again');
    jrCore_form_result();
}

//------------------------------
// update
//------------------------------
function view_jrCustomForm_update($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCustomForm', 'tools');

    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid form id');
        jrCore_form_result('referrer');
    }
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $req = "SELECT * FROM {$tbl} WHERE form_id = '{$_post['id']}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'invalid form id');
        jrCore_form_result('referrer');
    }

    jrCore_page_banner('update custom form');

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/browse",
        'values'       => $_rt
    );
    jrCore_form_create($_tmp);

    // Form ID
    $_tmp = array(
        'name'  => 'id',
        'type'  => 'hidden',
        'value' => $_post['id']
    );
    jrCore_form_field_create($_tmp);

    // Form Name
    $_tmp = array(
        'name'     => 'form_name',
        'label'    => 'form name',
        'help'     => 'Enter a unique form name for this new form. It should be lowercase and consist of letters and underscores only.',
        'type'     => 'text',
        'validate' => 'core_string',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Title
    $_tmp = array(
        'name'     => 'form_title',
        'label'    => 'form title',
        'help'     => 'Enter a title for this form - it will be used in the form header as well as the page title.',
        'type'     => 'text',
        'validate' => 'printable',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Message
    $_tmp = array(
        'name'     => 'form_message',
        'label'    => 'form message',
        'help'     => 'Enter an optional message for this form - it will appear above the form and can be used for form instructions, etc.',
        'type'     => 'textarea',
        'validate' => 'allowed_html',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Form Login
    $_tmp = array(
        'name'     => 'form_login',
        'label'    => 'form login',
        'help'     => 'Check this option to only allow logged in users to view this form.',
        'type'     => 'checkbox',
        'default'  => 'off',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Unique
    $_tmp = array(
        'name'     => 'form_unique',
        'label'    => 'form unique',
        'help'     => 'Check this option to ensure that a user can only fill out this form one time (only works if <b>form login</b> is checked too)',
        'type'     => 'checkbox',
        'default'  => 'off',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Form Notification
    $_sel = array(
        'master_email' => 'Send email to Master Admins',
        'admin_email'  => 'Send email to Admin Users',
        'none'         => 'Store in DataStore only'
    );
    $_tmp = array(
        'name'     => 'form_notify',
        'label'    => 'form notify',
        'help'     => 'Select the type of Notification you would like to be sent out on a successful form submission',
        'type'     => 'select',
        'default'  => 'master_email',
        'options'  => $_sel,
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// update_save
//------------------------------
function view_jrCustomForm_update_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Update
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $nam = jrCore_db_escape($_post['form_name']);
    $ttl = jrCore_db_escape($_post['form_title']);
    $msg = jrCore_db_escape($_post['form_message']);
    $req = "UPDATE {$tbl} SET
              form_updated = UNIX_TIMESTAMP(),
              form_name    = '{$nam}',
              form_title   = '{$ttl}',
              form_message = '{$msg}',
              form_unique  = '{$_post['form_unique']}',
              form_login   = '{$_post['form_login']}',
              form_notify  = '{$_post['form_notify']}'
            WHERE form_id = '{$_post['id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_set_form_notice('success', 'The changes were successfully saved');
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered updating the form - please try again');
    }
    jrCore_form_result();
}

//------------------------------
// delete_save
//------------------------------
function view_jrCustomForm_delete_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid form id');
        jrCore_form_result('referrer');
    }
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $req = "SELECT * FROM {$tbl} WHERE form_id = '{$_post['id']}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'invalid form id');
        jrCore_form_result('referrer');
    }
    // Delete it
    $req = "DELETE FROM {$tbl} WHERE form_id = '" . jrCore_db_escape($_post['id']) . "' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {

        // Delete any form designer fields for this
        $tbl = jrCore_db_table_name('jrCore', 'form');
        $req = "DELETE FROM {$tbl} WHERE `module` = 'jrCustomForm' AND `view` = '" . jrCore_db_escape($_rt['form_name']) . "'";
        jrCore_db_query($req);

        jrCore_set_form_notice('success', 'The form was successfully deleted');
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered deleting the form - please try again');
    }
    jrCore_form_result('referrer');
}
