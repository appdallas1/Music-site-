<?php
/**
 * Jamroom 5 jrDeveloper module
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
// package_module
//------------------------------
function view_jrDeveloper_package_module($_post, $_user, $_conf)
{
    global $_mods;

    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrDeveloper');
    jrCore_page_banner('Create Module ZIP');

    // Start our output
    $dat = array();
    $dat[1]['title'] = 'icon';
    $dat[1]['width'] = '5%';
    $dat[2]['title'] = 'file';
    $dat[2]['width'] = '65%';
    $dat[3]['title'] = 'size';
    $dat[3]['width'] = '25%';
    $dat[4]['title'] = 'download';
    $dat[4]['width'] = '5%';
    $dat[5]['title'] = 'delete';
    $dat[5]['width'] = '5%';
    jrCore_page_table_header($dat);

    // Show existing module packages that can be downloaded
    $cdir = jrCore_get_module_cache_dir('jrDeveloper');
    $_mds = glob("{$cdir}/*.zip");
    if (isset($_mds) && is_array($_mds) && count($_mds) > 0) {
        foreach ($_mds as $k => $file) {
            $nam = basename($file);
            list($mod_dir,) = explode('-',$nam,2);
            $dat = array();
            $dat[1]['title'] = '<img src="'. $_conf['jrCore_base_url'] . '/modules/' . $mod_dir . '/icon.png" width="48" height="48" alt="' . $mod_dir . '">';
            $dat[2]['title'] = $nam;
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = jrCore_format_size(filesize($file));
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = jrCore_page_button("d{$k}", 'download', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/download_module/{$nam}'");
            $dat[5]['title'] = jrCore_page_button("r{$k}", 'delete', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/delete_module/{$nam}'");
            jrCore_page_table_row($dat);
        }
    }
    else {
        $dat = array();
        $dat[1]['title'] = '<p>No module ZIP files to be downloaded</p>';
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    $_opt = array();
    foreach ($_mods as $m => $v) {
        $_opt[$m] = $v['module_name'];
    }

    // Form init
    $_tmp = array(
        'submit_value'     => 'create module ZIP',
        'cancel'           => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools",
        'form_ajax_submit' => false

    );
    jrCore_form_create($_tmp);

    $_tmp = array(
        'name'     => 'zip_mod',
        'type'     => 'select',
        'options'  => $_opt,
        'required' => 'on',
        'label'    => 'Module to ZIP',
        'help'     => 'Select the module you would like to create a ZIP file for.  This ZIP file can be used in the Jamroom Marketplace.'
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// package_module_save
//------------------------------
function view_jrDeveloper_package_module_save($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (!isset($_mods["{$_post['zip_mod']}"])) {
        jrCore_set_form_notice('error','Invalid module - please select a module from the list');
        jrCore_form_field_hilight('zip_mod');
        jrCore_form_result();
    }
    // Get version
    $_mta = jrCore_module_meta_data($_post['zip_mod']);
    if (!isset($_mta['version'])) {
        jrCore_set_form_notice('error',"The module is missing the required &quot;version&quot; attribute in the {$_post['zip_mod']}_meta() function");
        jrCore_form_result();
    }
    $cdir = jrCore_get_module_cache_dir('jrDeveloper');
    if (is_file("{$cdir}/{$_post['zip_mod']}-{$_mta['version']}.zip")) {
        unlink("{$cdir}/{$_post['zip_mod']}-{$_mta['version']}.zip");
    }

    $_temp = jrCore_get_directory_files(APP_DIR ."/modules/{$_post['zip_mod']}");
    if (!$_temp || !is_array($_temp)) {
        jrCore_set_form_notice('error',"Invalid module - unable to find any module files");
        jrCore_form_result();
    }
    $_files = array();
    foreach ($_temp as $fullpath => $file) {
        $_files["modules/{$file}"] = $fullpath;
    }
    jrCore_create_zip_file("{$cdir}/{$_post['zip_mod']}-{$_mta['version']}.zip",$_files);
    jrCore_location('referrer');
}

//------------------------------
// download_module
//------------------------------
function view_jrDeveloper_download_module($_post, $_user, $_conf)
{
    jrUser_master_only();
    $cdir = jrCore_get_module_cache_dir('jrDeveloper');
    if (!isset($_post['_1']) || !is_file("{$cdir}/{$_post['_1']}")) {
        jrCore_set_form_notice('error','Invalid ZIP file');
        jrCore_location('referrer');
    }
    jrCore_send_download_file("{$cdir}/{$_post['_1']}");
    session_write_close();
    exit();
}

//------------------------------
// delete_module
//------------------------------
function view_jrDeveloper_delete_module($_post, $_user, $_conf)
{
    jrUser_master_only();
    $cdir = jrCore_get_module_cache_dir('jrDeveloper');
    if (!isset($_post['_1']) || !is_file("{$cdir}/{$_post['_1']}")) {
        jrCore_set_form_notice('error','Invalid ZIP file');
        jrCore_location('referrer');
    }
    unlink("{$cdir}/{$_post['_1']}");
    jrCore_location('referrer');
}

//------------------------------
// clone_skin
//------------------------------
function view_jrDeveloper_clone_skin($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrDeveloper');

    // Make sure the skin directory is writable by the web user
    if (!is_writable(APP_DIR . '/skins')) {
        jrCore_set_form_notice('error', 'The skin directory is not writable by the web user - unable to clone a skin');
        jrCore_page_banner('Clone Skin');
        jrCore_get_form_notice();
        jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    }
    else {
        jrCore_page_banner('Clone Skin');

        // Form init
        $_tmp = array(
            'submit_value'  => 'clone skin',
            'cancel'        => 'referrer',
            'submit_prompt' => 'Are you sure you want to clone the selected skin?',
        );
        jrCore_form_create($_tmp);

        $_tmp = array(
            'name'     => 'skin_to_clone',
            'type'     => 'select',
            'options'  => jrCore_get_skins(),
            'required' => 'on',
            'label'    => 'Skin to Clone',
            'help'     => 'Select the skin that you want to make a clone of.',
            'section'  => 'clone skin'
        );
        jrCore_form_field_create($_tmp);

        $_tmp = array(
            'name'     => 'skin_name',
            'label'    => 'New Skin Name',
            'help'     => "Enter the name you would like to save this new skin as.<br><br><b>NOTE:</b> Only letters, numbers and underscores are allowed in the name.",
            'type'     => 'text',
            'value'    => '',
            'validate' => 'core_string'
        );
        jrCore_form_field_create($_tmp);
    }
    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// clone_skin_save
//------------------------------
function view_jrDeveloper_clone_skin_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    $_rt = jrCore_get_skins();
    if (!isset($_post['skin_to_clone']) || !in_array($_post['skin_to_clone'], $_rt)) {
        jrCore_set_form_notice('error', 'You have selected an invalid Skin To Clone - please select a valid Skin To Clone from the list of available skins');
        jrCore_form_result();
    }
    if (isset($_post['skin_name']) && in_array($_post['skin_name'], $_rt)) {
        jrCore_set_form_notice('error', 'New skin already exists');
        jrCore_form_result();
    }
    // clone the skin
    $_rp = array(
        substr($_post['skin_to_clone'], 2)  => substr($_post['skin_name'], 2),
        $_post['skin_to_clone']             => $_post['skin_name'],
        strtolower($_post['skin_to_clone']) => strtolower($_post['skin_name']),
        strtoupper($_post['skin_to_clone']) => strtoupper($_post['skin_name'])
    );
    $res = jrCore_copy_dir_recursive(APP_DIR . "/skins/{$_post['skin_to_clone']}", APP_DIR . "/skins/{$_post['skin_name']}", $_rp);
    if (!$res) {
        jrCore_set_form_notice('error', "An error was encountered trying to copy the skin directory - check Error Log");
    }
    else {

        // Bring in include
        if (is_file(APP_DIR . "/skins/{$_post['skin_name']}/include.php")) {
            require_once APP_DIR . "/skins/{$_post['skin_name']}/include.php";
        }

        // Load config
        if (is_file(APP_DIR . "/skins/{$_post['skin_name']}/config.php")) {
            require_once APP_DIR . "/skins/{$_post['skin_name']}/config.php";
            $func = "{$_post['skin_name']}_skin_config";
            if (function_exists($func)) {
                $func();
            }
        }

        // install new skin
        jrUser_install_lang_strings('skin', $_post['skin_name']);

        jrCore_create_master_css($_post['skin_name']);
        jrCore_create_master_javascript($_post['skin_name']);
        jrCore_form_delete_session();
        jrCore_set_form_notice('success', "The {$_post['skin_name']} skin has been cloned from the {$_post['skin_to_clone']} skin");
    }
    jrCore_form_result();
}

