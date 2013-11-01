<?php
/**
 * Jamroom 5 jrBanned module
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

//------------------------------
// browse
//------------------------------
function view_jrBanned_browse($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrBanned');

    // construct our query
    $tbl = jrCore_db_table_name('jrBanned','banned');
    $req = "SELECT * FROM {$tbl} ";
    $_ex = false;
    $add = '';
    $num = jrCore_db_number_rows('jrBanned','banned');
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $_post['search_string'] = trim(urldecode($_post['search_string']));
        $str = jrCore_db_escape($_post['search_string']);
        $req .= "WHERE (ban_type LIKE '%{$str}%' OR ban_value LIKE '%{$str}%') ";
        $_ex = array('search_string' => $_post['search_string']);
        $add = '/search_string='. urlencode($_post['search_string']);
        $num = false;
    }
    $req .= 'ORDER BY ban_id DESC';

    // find how many lines we are showing
    if (!isset($_post['p']) || !jrCore_checktype($_post['p'],'number_nz')) {
        $_post['p'] = 1;
    }
    $_rt = jrCore_db_paged_query($req,$_post['p'],12,'NUMERIC',$num);

    // start our html output
    jrCore_page_banner('banned items');
    jrCore_get_form_notice();
    jrCore_page_search('search',"{$_conf['jrCore_base_url']}/{$_post['module_url']}/banned");

    $dat = array();
    $dat[1]['title'] = 'type';
    $dat[1]['width'] = '10%;';
    $dat[2]['title'] = 'value';
    $dat[2]['width'] = '65%;';
    $dat[3]['title'] = 'updated';
    $dat[3]['width'] = '20%;';
    $dat[4]['title'] = 'delete';
    $dat[4]['width'] = '5%;';
    jrCore_page_table_header($dat);
    unset($dat);

    if (isset($_rt['_items']) && is_array($_rt['_items'])) {

        // Each Entry
        foreach($_rt['_items'] as $_ban) {

            $dat = array();
            $dat[1]['title'] = $_ban['ban_type'];
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $_ban['ban_value'];
            $dat[3]['title'] = jrCore_format_time($_ban['ban_updated']);
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = jrCore_page_button("d{$_ban['ban_id']}",'delete',"if (confirm('Are you sure you want to delete this entry?')) {window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/ban_item_delete_save/id={$_ban['ban_id']}/p={$_post['p']}{$add}'}");
            $dat[3]['class'] = 'center';
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_pager($_rt,$_ex);
    }
    else {
        $dat = array();
        if (!empty($_post['search_string'])) {
            $dat[1]['title'] = '<p>There were no Banned Items found to match your search criteria</p>';
        }
        else {
            $dat[1]['title'] = '<p>There are no banned items</p>';
        }
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    $_tmp = array(
        'submit_value'     => 'create new banned item',
        'cancel'           => 'referrer',
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // Type
    $_opt = array(
        'ip'   => 'IP Address',
        'name' => 'Profile or User Name',
        'word' => 'Forbidden Word'
    );
    $_tmp = array(
        'name'       => 'ban_type',
        'label'      => 'item type',
        'help'       => 'Select the Type of Banned Item you would like to add.',
        'type'       => 'select',
        'options'    => $_opt,
        'value'      => 'ip',
        'required'   => true
    );
    jrCore_form_field_create($_tmp);

    // New Form Field
    $_tmp = array(
        'name'       => 'ban_value',
        'label'      => 'item value',
        'help'       => 'Enter the value for this new Banned Item.<br><br><b>IP Address:</b> Enter a valid IP Address or partial IP Address to block all system requests to your system.<br><b>Profile or User Name:</b> Enter words or partial words that cannot be used in a User Name, Profile Name or Profile URL.<br><b>Forbidden Word:</b> Any word entered as a forbidden word will not be allowed in any item created by a User.',
        'type'       => 'text',
        'validate'   => 'not_empty'
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// browse_save
//------------------------------
function view_jrBanned_browse_save($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // See what we are adding
    switch ($_post['ban_type']) {
        case 'ip':
            $val = 'ip_address';
            // Make sure we are not blocking our own IP
            if ($_post['ban_value'] == jrCore_get_ip()) {
                jrCore_set_form_notice('error','You cannot ban your own IP Address!');
                jrCore_form_field_hilight('ban_value');
                jrCore_form_result();
            }
            break;
        case 'name':
            $val = 'user_name';
            break;
        case 'word':
            $val = 'printable';
            break;
        default:
            jrCore_set_form_notice('error','Invalid Ban Type');
            jrCore_form_result();
            return true;
            break;
    }
    if (!isset($_post['ban_value']) || !jrCore_checktype($_post['ban_value'],$val)) {
        $err = jrCore_checktype(null,$val,true);
        jrCore_set_form_notice('error','You have entered an invalid item value - please enter '. $err);
        jrCore_form_field_hilight('ban_value');
        jrCore_form_result();
    }
    $tbl = jrCore_db_table_name('jrBanned','banned');
    $req = "INSERT INTO {$tbl} (ban_updated,ban_type,ban_value)
            VALUES (UNIX_TIMESTAMP(),'". jrCore_db_escape($_post['ban_type']) ."','". jrCore_db_escape($_post['ban_value']) ."')
            ON DUPLICATE KEY UPDATE ban_updated = UNIX_TIMESTAMP()";
    jrCore_db_query($req);
    jrCore_set_form_notice('success','The Banned Item has been successfully created');
    jrCore_form_delete_session();
    jrCore_form_result('referrer');
    return true;
}

//------------------------------
// banned_item_delete_save
//------------------------------
function view_jrBanned_ban_item_delete_save($_post,$_user,$_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'],'number_nz')) {
        jrCore_set_form_notice('error','Invalid ban item id');
        jrCore_form_result('referrer');
    }
    $tbl = jrCore_db_table_name('jrBanned','banned');
    $req = "DELETE FROM {$tbl} WHERE ban_id = '". intval($_post['id']) ."' LIMIT 1";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_set_form_notice('success','The banned item was successfully deleted');
        jrCore_form_result('referrer');
    }
    jrCore_set_form_notice('error','An error was encountered deleting the banned item - please try again');
    jrCore_form_result();
}
