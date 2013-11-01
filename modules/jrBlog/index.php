<?php
/**
 * Jamroom 5 jrBlog module
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
// create
//------------------------------
function view_jrBlog_create($_post, $_user, $_conf)
{
    // Must be logged in to create a new blog
    jrUser_session_require_login();
    jrUser_check_quota_access('jrBlog');
    jrProfile_check_disk_usage();

    // Get language strings
    $_lang = jrUser_load_lang_strings();

    // Start our create form
    $_sr = array(
        "_profile_id = {$_user['user_active_profile_id']}",
    );
    $tmp = jrCore_page_banner_item_jumper('jrBlog', 'blog_title', $_sr, 'create', 'update');
    jrCore_page_banner($_lang['jrBlog'][1], $tmp);

    // Form init
    $_tmp = array(
        'submit_value' => 2,
        'cancel'       => jrCore_is_profile_referrer()
    );
    jrCore_form_create($_tmp);

    // Blog Title
    $_tmp = array(
        'name'      => 'blog_title',
        'label'     => 3,
        'help'      => 4,
        'type'      => 'text',
        'ban_check' => 'word',
        'validate'  => 'printable',
        'required'  => true
    );
    jrCore_form_field_create($_tmp);

    // Blog Category
    $_tmp = array(
        'name'      => 'blog_category',
        'label'     => 5,
        'help'      => 6,
        'type'      => 'select_and_text',
        'ban_check' => 'word',
        'validate'  => 'not_empty',
        'required'  => false
    );
    jrCore_form_field_create($_tmp);

    // Blog Publish Date
    $_tmp = array(
        'name'     => 'blog_publish_date',
        'label'    => 22,
        'help'     => 23,
        'type'     => 'datetime',
        'validate' => 'date',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Blog Text
    $_tmp = array(
        'name'     => 'blog_text',
        'label'    => 7,
        'help'     => 8,
        'type'     => 'editor',
        'theme'    => 'advanced',
        'validate' => 'allowed_html',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Blog Image
    $_tmp = array(
        'name'     => 'blog_image',
        'label'    => 9,
        'help'     => 10,
        'text'     => 11,
        'type'     => 'image',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrBlog_create_save($_post, $_user, $_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrCore_form_validate($_post);
    jrUser_check_quota_access('jrBlog');

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_rt = jrCore_form_get_save_data('jrBlog', 'create', $_post);

    // Add in our SEO URL names
    $_rt['blog_title_url'] = jrCore_url_string($_rt['blog_title']);
    $_rt['blog_category_url'] = jrCore_url_string($_rt['blog_category']);

    // $bid will be the INSERT_ID (_item_id) of the created item
    $bid = jrCore_db_create_item('jrBlog', $_rt);
    if (!$bid) {
        jrCore_set_form_notice('error', 12);
        jrCore_form_result();
    }
    // Save any uploaded media files added in by our
    jrCore_save_all_media_files('jrBlog', 'create', $_user['user_active_profile_id'], $bid);

    // Add to Actions...
    jrCore_run_module_function('jrAction_save', 'create', 'jrBlog', $bid);

    jrCore_form_delete_session();
    jrProfile_reset_cache();

    // redirect to the actual blog page, not the update page.
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$_post['module_url']}/{$bid}/{$_rt['blog_title_url']}");
}

//------------------------------
// update
//------------------------------
function view_jrBlog_update($_post, $_user, $_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrBlog');

    // We should get an id on the URL
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_notice_page('error', 14);
    }
    $_rt = jrCore_db_get_item('jrBlog', $_post['id']);
    if (!$_rt) {
        jrCore_notice_page('error', 15);
    }
    // Make sure the calling user has permission to edit this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }

    // Start output
    $_sr = array(
        "_profile_id = {$_user['user_active_profile_id']}",
    );
    $tmp = jrCore_page_banner_item_jumper('jrBlog', 'blog_title', $_sr, 'create', 'update');
    jrCore_page_banner(16, $tmp);

    // Form init
    $_tmp = array(
        'submit_value' => 17,
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

    // Blog Title
    $_tmp = array(
        'name'      => 'blog_title',
        'label'     => 3,
        'help'      => 4,
        'type'      => 'text',
        'ban_check' => 'word',
        'validate'  => 'printable',
        'required'  => true
    );
    jrCore_form_field_create($_tmp);

    // Blog Category
    $_tmp = array(
        'name'      => 'blog_category',
        'label'     => 5,
        'help'      => 6,
        'type'      => 'select_and_text',
        'ban_check' => 'word',
        'validate'  => 'not_empty',
        'required'  => false
    );
    jrCore_form_field_create($_tmp);

    // Blog Publish Date
    $_tmp = array(
        'name'     => 'blog_publish_date',
        'label'    => 22,
        'help'     => 23,
        'type'     => 'datetime',
        'validate' => 'date',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Blog Text
    $_tmp = array(
        'name'     => 'blog_text',
        'label'    => 7,
        'help'     => 8,
        'type'     => 'editor',
        'theme'    => 'advanced',
        'validate' => 'allowed_html',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Blog Image
    $_tmp = array(
        'name'     => 'blog_image',
        'label'    => 9,
        'help'     => 10,
        'text'     => 11,
        'type'     => 'image',
        'value'    => $_rt,
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// update_save
//------------------------------
function view_jrBlog_update_save($_post, $_user, $_conf)
{
    // Must be logged in
    jrUser_session_require_login();

    // Validate all incoming posted data
    jrCore_form_validate($_post);
    jrUser_check_quota_access('jrBlog');

    // Make sure we get a good _item_id
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_notice_page('error', 14);
        jrCore_form_result('referrer');
    }

    // Get data
    $_rt = jrCore_db_get_item('jrBlog', $_post['id']);
    if (!isset($_rt) || !is_array($_rt)) {
        // Item does not exist....
        jrCore_notice_page('error', 15);
        jrCore_form_result('referrer');
    }

    // Make sure the calling user has permission to edit this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_sv = jrCore_form_get_save_data('jrBlog', 'update', $_post);

    // Add in our SEO URL names
    $_sv['blog_title_url'] = jrCore_url_string($_sv['blog_title']);
    $_sv['blog_category_url'] = jrCore_url_string($_sv['blog_category']);

    // Save all updated fields to the Data Store
    jrCore_db_update_item('jrBlog', $_post['id'], $_sv);

    // Save any uploaded media file
    jrCore_save_all_media_files('jrBlog', 'update', $_user['user_active_profile_id'], $_post['id']);

    // Add to Actions...
    jrCore_run_module_function('jrAction_save', 'update', 'jrBlog', $_post['id']);

    jrCore_form_delete_session();
    jrProfile_reset_cache();
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$_post['module_url']}/{$_post['id']}/{$_sv['blog_title_url']}");
}

//------------------------------
// delete
//------------------------------
function view_jrBlog_delete($_post, $_user, $_conf)
{
    // Must be logged in
    jrUser_session_require_login();
    jrUser_check_quota_access('jrBlog');

    // Make sure we get a good id
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_notice_page('error', 14);
        jrCore_form_result('referrer');
    }
    $_rt = jrCore_db_get_item('jrBlog', $_post['id']);

    // Make sure the calling user has permission to delete this item
    if (!jrUser_can_edit_item($_rt)) {
        jrUser_not_authorized();
    }
    // Delete item and any associated files
    jrCore_db_delete_item('jrBlog', $_post['id']);
    jrProfile_reset_cache();
    jrCore_form_result('delete_referrer');
}

//------------------------------
// RSS Feed
//------------------------------
function view_jrBlog_feed($_post, $_user, $_conf)
{
    $pid = (int) $_post['_1'];
    // get the blog posts
    $_sp = array(
        'search'   => array(
            "_profile_id = {$pid}"
        ),
        'order_by' => array(
            'blog_publish_date' => 'desc'
        )
    );
    $_rt = jrCore_db_search_items('jrBlog', $_sp);
    $_rep = array(
        'blog_profile_id' => $pid,
        'lastBuildDate'   => date('D, d M Y H:i:s O')
    );
    if (isset($_rt) && is_array($_rt)) {
        $_rep['items'] = $_rt['_items'];
    }
    // xml RSS feed
    header("Content-Type: text/xml");
    echo jrCore_parse_template('rss_feed.tpl', $_rep, 'jrBlog');
    exit();
}

