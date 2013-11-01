<?php
/**
 * Jamroom 5 jrImage module
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
// cache_reset
//------------------------------
function view_jrImage_cache_reset($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrImage');
    $_mta = jrCore_module_meta_data($_post['module']);
    jrCore_page_banner("{$_mta['name']} - Cache Reset");

    // Form init
    $_tmp = array(
        'submit_value'  => 'reset image cache',
        'cancel'        => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools",
        'submit_prompt' => 'Are you sure you want to reset the image cache? On a large system this could take a few minutes to run, so please be patient'
    );
    $tok = jrCore_form_create($_tmp);

    // Cache Reet
    $_tmp = array(
        'name'       => 'image_cache_reset',
        'label'      => 'reset cache',
        'help'       => 'check this option and save the form if you would like to reset your image cache.  Note that this has no impact on the original images uploaded by an user.',
        'type'       => 'checkbox',
        'value'      => 'on',
        'validate'   => 'onoff',
        'required'   => true
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// cache_reset_save
//------------------------------
function view_jrImage_cache_reset_save($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // delete all cache sub directories.  Note that we first change the
    // "active_cache_dir" value so any images being viewed while the reset
    // tool is running will not be affected.
    if (isset($_post['image_cache_reset']) && $_post['image_cache_reset'] == 'on') {
        $cdr = jrCore_get_module_cache_dir('jrImage');
        $old = $_conf['jrImage_active_cache_dir'];
        $dir = substr(md5(microtime()),0,5);
        if (!is_dir("{$cdr}/{$dir}")) {
            mkdir("{$cdr}/{$dir}",$_conf['jrCore_dir_perms'],true);
        }
        // Update to new setting
        if (jrCore_set_setting_value('jrImage','active_cache_dir',$dir)) {
            jrCore_delete_all_cache_entries('jrCore',0);
            // Check for our old directory
            if (is_dir("{$cdr}/{$old}")) {
                sleep(3);  // time for any in-progress events to complete
                // Delete existing cache directory
                jrCore_delete_dir_contents("{$cdr}/{$old}");
                rmdir("{$cdr}/{$old}");
            }
            jrCore_set_form_notice('success','The image cache was successfully reset');
            jrCore_form_delete_session();
        }
        else {
            jrCore_set_form_notice('error','an error was encountered saving the new cache directory to the global settings');
        }
    }
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/cache_reset");
}

//------------------------------
// delete
//------------------------------
function view_jrImage_delete($_post,$_user,$_conf)
{
    jrUser_session_require_login();
    // [module_url] => image
    // [module] => jrImage
    // [option] => delete
    // [_1] => jrProfile
    // [_2] => profile_bg_image
    // [_3] => 1
    if (!isset($_post['_1']) || !jrCore_db_get_prefix($_post['_1'])) {
        jrCore_set_form_notice('error', 'Invalid module');
        jrCore_location('referrer');
    }
    if (!isset($_post['_3']) || !jrCore_checktype($_post['_3'],'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid item_id');
        jrCore_location('referrer');
    }
    // Get info about this item to be sure the requesting user is allowed
    $_rt = jrCore_db_get_item($_post['_1'],$_post['_3'],true);
    if (!isset($_rt) || !is_array($_rt) || !isset($_rt['_profile_id'])) {
        jrCore_set_form_notice('error', 'Invalid item_id (2)');
        jrCore_location('referrer');
    }
    if (!jrUser_is_admin() && !jrProfile_is_profile_owner($_rt['_profile_id'])) {
        jrUser_not_authorized();
    }

    // Remove file
    jrCore_delete_item_media_file($_post['_1'],$_post['_2'],$_rt['_profile_id'],$_post['_3']);

    // If this was a user or profile image, reload session
    switch ($_post['_1']) {
        case 'jrUser':
        case 'jrProfile':
            jrUser_session_sync($_user['_user_id']);
            break;
    }

    jrProfile_reset_cache($_rt['_profile_id']);
    jrCore_set_form_notice('success', 'The image was successfully deleted');
    jrCore_location('referrer');
}
