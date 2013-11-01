<?php
/**
 * Jamroom 5 jrChainedSelect module
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
 * @copyright 2013 Talldude Networks, LLC.
 * @author Paul Asher <paul [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

//------------------------------
// Manage
//------------------------------
function view_jrChainedSelect_manage($_post,$_user,$_conf)
{
    // Must be logged in as admin
    jrUser_session_require_login();
    jrUser_master_only();

    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrChainedSelect');

    // Create form
    jrCore_page_banner('Chained Select field options manager');

    // Form init
    $_tmp = array(
        'submit_value' => 'Submit',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools"
    );
    jrCore_form_create($_tmp);

    // Name
    $_tmp2 = jrChainedSelect_names();
    if (isset($_tmp2) && is_array($_tmp2) && count($_tmp2) > 0) {
        $_tmp2['-'] = '-';
        asort($_tmp2);
        $_tmp = array(
            'name'       => 'cs_name_select',
            'label'      => 'Select Set Name',
            'help'       => 'Select or create a name for a set of chained select options',
            'type'       => 'select',
            'options'    => $_tmp2,
            'value'      => $_post['cs_name'],
            'onchange'   => "var csn=this.options[this.selectedIndex].value;self.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/manage/cs_name='+ csn;",
            'validate'   => 'core_string',
            'required'   => true
        );
        jrCore_form_field_create($_tmp);
    }
    $_tmp = array(
        'name'       => 'cs_name_text',
        'label'      => 'Enter New Set Name',
        'help'       => '',
        'type'       => 'text',
        'validate'   => 'core_string',
        'required'   => false
    );
    jrCore_form_field_create($_tmp);
    $_tmp = array(
        'name'       => 'cs_name_url',
        'type'       => 'hidden',
        'value'      => $_post['cs_name'],
        'validate'   => 'core_string',
        'required'   => false
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_divider();

    // Levels
    for ($i=0;$i<$_conf['jrChainedSelect_levels'];$i++) {
        $_tmp = array(
            'name'       => "cs_{$_post['cs_name']}_{$i}",
            'label'      => "Level {$i} Option",
            'help'       => 'Select or create a new option for level ' . $i,
            'type'       => 'chained_select_and_text',
            'validate'   => 'printable',
            'required'   => true
        );
        jrCore_form_field_create($_tmp);
    }

    jrCore_page_note("NOTE that if the above selected or specified options already exist as a set, on submit, that set will be deleted");

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// Manage Save
//------------------------------
function view_jrChainedSelect_manage_save($_post,$_user,$_conf)
{
    // Must be logged in as admin
    jrUser_session_require_login();
    jrUser_master_only();

    jrCore_form_validate($_post);

    $_rt = jrCore_form_get_save_data('jrChainedSelect','manage',$_post);
    if ($_rt['cs_name_text'] != '') {
        $_rt['cs_name'] = $_rt['cs_name_text'];
    }
    elseif ($_rt['cs_name_select'] != '') {
        $_rt['cs_name'] = $_rt['cs_name_select'];
    }
    else {
        jrCore_set_form_notice('error','Set name required');
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/manage/cs_name={$_rt['cs_name']}");
    }
    unset($_rt['cs_name_select']);
    unset($_rt['cs_name_text']);

    for ($i=0;$i<$_conf['jrChainedSelect_levels'];$i++) {
        if (isset($_rt["cs__{$i}"])) {
            $_rt["cs_{$_rt['cs_name']}_{$i}"] = $_rt["cs__{$i}"];
            unset($_rt["cs__{$i}"]);
        }
        if (isset($_rt['cs_name_url']) && $_rt['cs_name_url'] != '' && !isset($_rt["cs_{$_rt['cs_name_url']}_{$i}"])) {
            $_rt["cs_{$_rt['cs_name']}_{$i}"] = $_rt["cs_{$_rt['cs_name_url']}_{$i}"];
            unset($_rt["cs_{$_rt['cs_name_url']}_{$i}"]);
        }
        if ($_rt["cs_{$_rt['cs_name']}_{$i}"] == '') {
            jrCore_set_form_notice('error','All options are required');
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/manage");
        }
    }

    // See if option set already exists
    $_s = array();
    $_s['search'][] = "cs_name = {$_rt['cs_name']}";
    for ($i=0;$i<$_conf['jrChainedSelect_levels'];$i++) {
        $value = $_post["cs_{$_rt['cs_name']}_{$i}"];
        $_s['search'][] = "cs_{$_rt['cs_name']}_{$i} = {$value}";
    }
    $_st = jrCore_db_search_items('jrChainedSelect',$_s);
    if (isset($_st['_items'][0]) && is_array($_st['_items'][0])) {
        // Yes - Delete it
        jrCore_db_delete_item('jrChainedSelect',$_st['_items'][0]['_item_id']);
        jrCore_set_form_notice('success','Option set deleted');
    }
    else {
        // No - Create it
        $id = jrCore_db_create_item('jrChainedSelect',$_rt);
        if (!$id) {
            jrCore_set_form_notice('error','Something went wrong when saving to the datastore - please try again');
        }
        else {
            jrCore_set_form_notice('success','New option set created');
        }
    }

    jrCore_form_delete_session();
    jrProfile_reset_cache();
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/manage/cs_name={$_rt['cs_name']}");
}

//------------------------------
// Export
// Downloads Set Name and all Options as a CSV file
//------------------------------
function view_jrChainedSelect_export($_post,$_user,$_conf)
{
    // Must be logged in as admin
    jrUser_session_require_login();
    jrUser_master_only();

    $_s = array("limit"=>1000000,"order_by"=>array("cs_name"=>"ASC"));
    $_rt = jrCore_db_search_items('jrChainedSelect',$_s);
    if (isset($_rt['_items'][0]) && is_array($_rt['_items'][0])) {
        $today = date("Ymd");
        $fn = "JR5_ChainedSelect_Table_{$today}.csv";
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=\"{$fn}\"");
        $cr = "\n";
        $data = '';
        foreach ($_rt['_items'] as $rt) {
            $_csl = array();
            foreach ($rt as $k=>$v) {
                if ($k == 'cs_name') {
                    $cs_name = str_replace('"','',$v);
                }
                elseif (substr($k,0,3) == 'cs_') {
                    $x = strrpos($k,'_');
                    $level = substr($k,$x+1);
                    if (jrCore_checktype($level,'number_nn')) {
                        $_csl[$level] = str_replace('"','',$v);
                    }
                }
            }
            ksort($_csl);
            if (isset($cs_name) && $cs_name != '' && isset($_csl) && is_array($_csl) && count($_csl) > 0) {
                $line = "\"{$cs_name}\",";
                foreach ($_csl as $csl) {
                    $line .= "\"{$csl}\",";
                }
            }
            $data .= substr($line,0,-1) . $cr;
        }
        echo $data;
        exit;
    }
    else {
        jrCore_set_form_notice('error','No ChainedSelect options to export');
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    }
}

//------------------------------
// Import
//------------------------------
function view_jrChainedSelect_import($_post,$_user,$_conf)
{
    // Must be logged in as admin
    jrUser_session_require_login();
    jrUser_master_only();

    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrChainedSelect');

    // Create form
    jrCore_page_banner('Chained Select field import CSV file');

    // Form init
    $_tmp = array(
        'submit_value' => 'Submit',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools",
        'submit_modal'  => 'update',
        'modal_width'   => 600,
        'modal_height'  => 400,
        'modal_note'    => 'Please be patient whilst option sets are imported'
    );
    jrCore_form_create($_tmp);

    // File
    $_tmp = array(
        'name'     => 'cs_csv',
        'label'    => 'CSV File',
        'help'     => 'Select the CSV file to upload',
        'text'     => 'select',
        'type'     => 'file',
        'extensions' => 'csv',
        'required' => TRUE
    );
    jrCore_form_field_create($_tmp);

    // Delete DS
    $_tmp = array(
        'name'     => 'cs_ds_del',
        'label'    => 'Delete Existing Options',
        'help'     => 'When this is checked, all existing option sets will be deleted and new ones created from the uploaded CSV file. If un-checked, options from the file will be appended to the existing options.',
        'type'     => 'checkbox',
        'default' => 'on',
        'required' => TRUE
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// Import Save
//------------------------------
function view_jrChainedSelect_import_save($_post,$_user,$_conf)
{
    // Must be logged in as admin
    jrUser_session_require_login();
    jrUser_master_only();

    // Get our posted data - the jrCore_form_get_save_data function will
    // return just those fields that were presented in the form.
    $_rt = jrCore_form_get_save_data('jrChainedSelect','import',$_post);

    // $id will be the INSERT_ID (_item_id) of the created item
    $id = jrCore_db_create_item('jrChainedSelect',$_rt);
    if (!$id) {
        jrCore_set_form_notice('error','Something went wrong - Please try again');
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    }
    jrCore_save_all_media_files('jrChainedSelect','import',$_user['_profile_id'],$id);

    if ($_rt['cs_ds_del'] == 'on') {
        // Truncate DS tables
        $tbl = jrCore_db_table_name("jrChainedSelect",'item');
        $req = "TRUNCATE TABLE `{$tbl}`";
        jrCore_db_query($req);
        $tbl = jrCore_db_table_name("jrChainedSelect",'item_key');
        $req = "TRUNCATE TABLE `{$tbl}`";
        jrCore_db_query($req);
        jrCore_db_delete_item_key('jrProfile',$_user['_profile_id'],'profile_jrChainedSelect_item_count');
    }
    else {
        jrCore_db_delete_item('jrChainedSelect',$id,false);
    }

    // Get imported file (File name is 'jrChainedSelect_{id}_cs_csv.csv')
    $csv_dir = jrCore_get_media_directory($_user['_profile_id']);
    $csv_file = "{$csv_dir}/jrChainedSelect_{$id}_cs_csv.csv";

    // Add option sets to DS
    $ctr = 0;
    $fh = fopen($csv_file, "r");
    while (!feof($fh)) {
        $line = fgets($fh);
        $_line = explode(',',$line);
        if (isset($_line) && is_array($_line)) {
            $_tmp = array();
            foreach ($_line as $k=>$v) {
                $v = str_replace('"','',$v);
                $v = trim($v);
                if ($k == 0) {
                    $_tmp['cs_name'] = $v;
                }
            }
            if (isset($_tmp['cs_name']) && $_tmp['cs_name'] != '') {
                foreach ($_line as $k=>$v) {
                    $v = str_replace('"','',$v);
                    $v = trim($v);
                    if ($k > 0) {
                        $l = $k - 1;
                        $_tmp["cs_{$_tmp['cs_name']}_{$l}"] = $v;
                    }
                }
                if (count($_tmp) > 1) {
                    if ($_rt['cs_ds_del'] == 'on') {
                        jrCore_db_create_item('jrChainedSelect',$_tmp);
                    }
                    else {
                        $_s = array();
                        foreach ($_tmp as $k=>$v) {
                            $_s['search'][] = "{$k} = {$v}";
                        }
                        $_st = jrCore_db_search_items('jrChainedSelect',$_s);
                        if (isset($_st['_items'][0]) && is_array($_st['_items'][0])) {
                        }
                        else {
                            jrCore_db_create_item('jrChainedSelect',$_tmp);
                        }
                    }
                    $ctr++;
                    if ($ctr % 100 == 0) {
                        jrCore_form_modal_notice('update',"{$ctr} option sets imported");
                        sleep(1);
                    }
                }
            }
        }
    }
    fclose($fh);

    // Delete imported file
    unlink($csv_file);

    jrCore_form_delete_session();
    jrProfile_reset_cache();

    jrCore_form_modal_notice('complete',"{$ctr} option sets imported");
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
}

