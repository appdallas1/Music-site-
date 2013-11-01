<?php
/**
 * Jamroom 5 jrPage module
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
// default (view a page)
//------------------------------
function view_jrPage_default($_post,$_user,$_conf)
{
    // We will get our page_id and page_url on the URL - i.e.
    // http://site.com/pages/1/this-is-the-page-title
    // so $_post['option'] will be set with our page_id (1)
    $out = '';
    if (isset($_post['option']) && jrCore_checktype($_post['option'],'number_nz')) {
        $pid = (int)$_post['option'];
        $_rt = jrCore_db_get_item('jrPage',$pid);
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_page_not_found();
        }

        // See if we are cached (non-logged in users)
        $key = '';
        if (!jrUser_is_logged_in()) {
            $key = "page-view-cache-{$_post['_uri']}";
            if ($out = jrCore_is_cached('jrPage',$key)) {
                $out .= "\n<!--c-->";
                return $out;
            }
        }

        // Set title, parse and return
        jrCore_page_title($_rt['page_title']);

        // Parse template
        $out  = jrCore_parse_template('header.tpl',$_post);
        $out .= jrCore_parse_template('item_detail.tpl',array('item' => $_rt),'jrPage');
        $out .= jrCore_parse_template('footer.tpl',$_post);

        // Caching for non-logged in users
        if (!jrUser_is_logged_in()) {
            jrCore_add_to_cache('jrPage',$key,$out,false,$_rt['_profile_id']);
        }
    }
    return $out;
}

//------------------------------
// create
//------------------------------
function view_jrPage_create($_post,$_user,$_conf)
{
    // Must be logged in to create a page
    jrUser_session_require_login();
    jrUser_check_quota_access('jrPage');

    // Bring in language
    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Start our create form
    jrCore_page_banner(1);

    // Form init
    $_tmp = array(
        'submit_value' => 2,
        'cancel'       => jrCore_is_profile_referrer()
    );
    jrCore_form_create($_tmp);

    // Page Title
    $_tmp = array(
        'name'     => 'page_title',
        'label'    => 3,
        'help'     => 4,
        'type'     => 'text',
        'validate' => 'not_empty',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Page Location (profile or main site)
    // NOTE: master admin only
    $_opt = array(
        0 => $_lang['jrPage'][15],
        1 => $_lang['jrPage'][16]
    );
    $_tmp = array(
        'name'     => 'page_location',
        'group'    => 'master',
        'label'    => 13,
        'help'     => 14,
        'type'     => 'select',
        'options'  => $_opt,
        'default'  => 1,
        'validate' => 'number_nn',
        'min'      => 0,
        'max'      => 1,
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Page Body
    $_tmp = array(
        'name'     => 'page_body',
        'label'    => 5,
        'help'     => 6,
        'type'     => 'editor',
        'theme'    => 'advanced',
        'validate' => 'allowed_html',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrPage_create_save($_post,$_user,$_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrPage');
    jrCore_form_validate($_post);

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_rt = jrCore_form_get_save_data('jrPage','create',$_post);

    // If we are NOT a master admin, page_location will not be set
    if (!jrUser_is_master()) {
        $_rt['page_location'] = 1;
    }

    // Next, we need to create the "slug" from the title and save it
    $_rt['page_title_url'] = jrCore_url_string($_rt['page_title']);

    // $aid will be the INSERT_ID (_item_id) of the created item
    $aid = jrCore_db_create_item('jrPage',$_rt);
    if (!$aid) {
        jrCore_set_form_notice('error',7);
        jrCore_form_result();
    }
    // Save any uploaded media files added in by our Page Designer
    jrCore_save_all_media_files('jrPage','create',$_user['user_active_profile_id'],$aid);

    // Add to Actions...
    jrCore_run_module_function('jrAction_save','create','jrPage',$aid);

    jrCore_form_delete_session();

    // See where we redirect here...
    if (!isset($_rt['page_location']) || $_rt['page_location'] === 1) {
        jrProfile_reset_cache();
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$_post['module_url']}/{$aid}/{$_rt['page_title_url']}");
    }
    else {
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$aid}/{$_rt['page_title_url']}");
    }
}

//------------------------------
// update
//------------------------------
function view_jrPage_update($_post,$_user,$_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrPage');

    // We should get an id on the URL
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'],'number_nz')) {
        jrCore_notice_page('error',9);
    }
    $_rt = jrCore_db_get_item('jrPage',$_post['id']);
    if (!$_rt) {
        jrCore_notice_page('error',9);
    }
    // Make sure the calling user has permission to edit this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }

    // Bring in language
    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Start output
    // Note - we're going to show different page jumpers here depending
    // on if we are modifying a SITE page or a PROFILE page
    if (isset($_rt['page_location']) && intval($_rt['page_location']) === 0) {
        $_sr = array(
            'page_location = 0'
        );
        $tmp = jrCore_page_banner_item_jumper('jrPage','page_title',$_sr,'create','update');
    }
    else {
        $_sr = array(
            "_profile_id = {$_user['user_active_profile_id']}",
            'page_location = 1'
        );
        $tmp = jrCore_page_banner_item_jumper('jrPage','page_title',$_sr,'create','update');
    }
    jrCore_page_banner(10,$tmp);

    // Form init
    $_tmp = array(
        'submit_value' => 11,
        'cancel'       => jrCore_is_profile_referrer(),
        'values'       => $_rt
    );
    jrCore_form_create($_tmp);

    // id
    $_tmp = array(
        'name'     => 'id',
        'type'     => 'hidden',
        'value'    => $_post['id'],
        'validate' => 'number_nz'
    );
    jrCore_form_field_create($_tmp);

    // Page Title
    $_tmp = array(
        'name'     => 'page_title',
        'label'    => 3,
        'help'     => 4,
        'type'     => 'text',
        'validate' => 'not_empty',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Page Location (profile or main site)
    // NOTE: master admin only
    $_opt = array(
        0 => $_lang['jrPage'][15],
        1 => $_lang['jrPage'][16]
    );
    $_tmp = array(
        'name'     => 'page_location',
        'group'    => 'master',
        'label'    => 13,
        'help'     => 14,
        'type'     => 'select',
        'options'  => $_opt,
        'default'  => 1,
        'validate' => 'number_nn',
        'min'      => 0,
        'max'      => 1,
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Page Body
    $_tmp = array(
        'name'     => 'page_body',
        'label'    => 5,
        'help'     => 6,
        'type'     => 'editor',
        'theme'    => 'advanced',
        'validate' => 'allowed_html',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// update_save
//------------------------------
function view_jrPage_update_save($_post,$_user,$_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrPage');

    // Validate all incoming posted data
    jrCore_form_validate($_post);

    // Make sure we get a good _item_id
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'],'number_nz')) {
        jrCore_notice_page('error',9);
        jrCore_form_result('referrer');
    }

    // Get data
    $_rt = jrCore_db_get_item('jrPage',$_post['id']);
    if (!isset($_rt) || !is_array($_rt)) {
        // Item does not exist....
        jrCore_notice_page('error',9);
        jrCore_form_result('referrer');
    }

    // Make sure the calling user has permission to edit this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_sv = jrCore_form_get_save_data('jrPage','update',$_post);

    // Add in our SEO URL names
    $_sv['page_title_url'] = jrCore_url_string($_sv['page_title']);

    // Save all updated fields to the Data Store
    jrCore_db_update_item('jrPage',$_post['id'],$_sv);

    // Save any uploaded media files added in by our
    jrCore_save_all_media_files('jrPage','update',$_user['user_active_profile_id'],$_post['id']);

    // Add to actions
    jrCore_run_module_function('jrAction_save','update','jrPage',$_post['id']);

    jrCore_form_delete_session();
    // See where we redirect here...
    if (!isset($_sv['page_location']) || $_sv['page_location'] === '1') {
        jrProfile_reset_cache();
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$_post['module_url']}/{$_post['id']}/{$_sv['page_title_url']}");
    }
    else {
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$_post['id']}/{$_sv['page_title_url']}");
    }
}

//------------------------------
// delete
//------------------------------
function view_jrPage_delete($_post,$_user,$_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrPage');

    // Make sure we get a good id
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'],'number_nz')) {
        jrCore_notice_page('error',9);
        jrCore_form_result('referrer');
    }
    $_rt = jrCore_db_get_item('jrPage',$_post['id']);
    if (!isset($_rt) || !is_array($_rt)) {
        // Item does not exist....
        jrCore_notice_page('error',9);
        jrCore_form_result('referrer');
    }

    // Make sure the calling user has permission to edit this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }
    // Delete item and any associated files
    jrCore_db_delete_item('jrPage',$_post['id']);
    jrProfile_reset_cache();
    jrCore_form_result('delete_referrer');
}
