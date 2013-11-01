<?php
/**
 * Jamroom 5 jrCore module
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
// icon_css
//------------------------------
function view_jrCore_icon_css($_post, $_user, $_conf)
{
    $width = 64;
    if (isset($_post['_1']) && jrCore_checktype($_post['_1'], 'number_nz') && $_post['_1'] < 64) {
        $width = intval($_post['_1']);
    }
    $dir = jrCore_get_media_directory(0);
    if (!is_file("{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.css")) {
        $_tmp = jrCore_get_registered_module_features('jrCore', 'icon_color');
        if (isset($_tmp["{$_conf['jrCore_active_skin']}"])) {
            $color = array_keys($_tmp["{$_conf['jrCore_active_skin']}"]);
            $color = reset($color);
        }
        else {
            $color = 'black';
        }
        jrCore_create_css_sprite($_conf['jrCore_active_skin'], $color, $width);
    }
    header("Content-type: text/css");
    header('Content-Disposition: inline; filename="sprite_' . $width . '.css"');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 8640000));
    echo file_get_contents("{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.css");
    session_write_close();
    exit();
}

//------------------------------
// icon_sprite
//------------------------------
function view_jrCore_icon_sprite($_post, $_user, $_conf)
{
    $width = 64;
    if (isset($_post['_1']) && jrCore_checktype($_post['_1'], 'number_nz') && $_post['_1'] < 64) {
        $width = intval($_post['_1']);
    }
    $dir = jrCore_get_media_directory(0);
    $color = 'black';
    if (!is_file("{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.png")) {
        $_tmp = jrCore_get_registered_module_features('jrCore', 'icon_color');
        if (isset($_tmp["{$_conf['jrCore_active_skin']}"])) {
            $color = array_keys($_tmp["{$_conf['jrCore_active_skin']}"]);
            $color = reset($color);
        }
        jrCore_create_css_sprite($_conf['jrCore_active_skin'], $color, $width);
    }
    header("Content-type: image/png");
    header('Content-Disposition: inline; filename="sprite_' . $width . '.png"');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 8640000));
    echo file_get_contents("{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.png");
    session_write_close();
    exit();
}

//------------------------------
// form_validate
//------------------------------
function view_jrCore_form_validate($_post, $_user, $_conf)
{
    return jrCore_form_validate($_post);
}

//------------------------------
// form_modal_status
//------------------------------
function view_jrCore_form_modal_status($_post, $_user, $_conf)
{
    if (!isset($_post['k'])) {
        $_tmp = array('t' => 'error', 'm' => 'invalid key');
        jrCore_json_response($_tmp);
    }
    // Get the results from the DB of our status
    $tbl = jrCore_db_table_name('jrCore', 'modal');
    $req = "SELECT modal_id AS i, modal_value AS m FROM {$tbl} WHERE modal_key = '" . jrCore_db_escape($_post['k']) . "' ORDER BY modal_id ASC";
    $_rt = jrCore_db_query($req, 'i', false, 'm');
    if (isset($_rt) && is_array($_rt)) {
        $req = "DELETE FROM {$tbl} WHERE modal_id IN(" . implode(',', array_keys($_rt)) . ")";
        jrCore_db_query($req);
        foreach ($_rt as $k => $v) {
            $_rt[$k] = json_decode($v, true);
        }
        jrCore_json_response($_rt);
    }
    $_tmp = array(array('t' => 'empty', 'm' => 'no results found for key'));
    jrCore_json_response($_tmp);
    exit;
}

//------------------------------
// form_modal_cleanup
//------------------------------
function view_jrCore_form_modal_cleanup($_post, $_user, $_conf)
{
    if (!isset($_post['k'])) {
        $_tmp = array(array('t' => 'error', 'm' => 'invalid key'));
        jrCore_json_response($_tmp);
    }
    jrCore_form_modal_cleanup($_post['k']);
    exit;
}

//------------------------------
// system_check
//------------------------------
function view_jrCore_system_check($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore', 'tools');
    jrCore_page_banner('system check');
    jrCore_get_form_notice();

    $pass = jrCore_get_option_image('pass');
    $fail = jrCore_get_option_image('fail');

    jrCore_page_section_header('core');

    $dat = array();
    $dat[1]['title'] = 'checked';
    $dat[1]['width'] = '20%';
    $dat[2]['title'] = 'value';
    $dat[2]['width'] = '32%';
    $dat[3]['title'] = 'result';
    $dat[3]['width'] = '8%';
    $dat[4]['title'] = 'note';
    $dat[4]['width'] = '40%';
    jrCore_page_table_header($dat);

    $dat = array();
    $dat[1]['title'] = "<a href=\"{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/info\">{$_mods['jrCore']['module_name']}</a>";
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = $_mods['jrCore']['module_version'];
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $pass;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = $_mods['jrCore']['module_version'];
    jrCore_page_table_row($dat);

    // Server
    $dat = array();
    $dat[1]['title'] = 'Server OS';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = @php_uname();
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $pass;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = 'Linux or Mac OS X based server.';
    jrCore_page_table_row($dat);

    // Web Server
    $dat = array();
    $dat[1]['title'] = 'Web Server';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = php_sapi_name();
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $pass;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = 'Apache Web Server required.';
    jrCore_page_table_row($dat);

    // PHP Version
    $result = $fail;
    if (version_compare(phpversion(), '5.3.0') != -1) {
        $result = $pass;
    }
    $dat = array();
    $dat[1]['title'] = 'PHP Version';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = phpversion() . ' <a href="' . $_conf['jrCore_base_url'] . '/' . $_post['module_url'] . '/phpinfo" target="_blank">[phpinfo]</a>';
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $result;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = 'PHP 5.3+ required.';
    jrCore_page_table_row($dat);

    // MySQL Version
    $msi = jrCore_db_connect();
    $ver = mysqli_get_server_info($msi);
    $result = $pass;
    if (strpos($ver, '3.') === 0 || strpos($ver, '4.') === 0) {
        $result = $fail;
    }
    $dat = array();
    $dat[1]['title'] = 'MySQL Version';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = $ver;
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $result;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = 'MySQL 5.0+ required, 5.1.51+ recommended.';
    jrCore_page_table_row($dat);

    // Disabled Functions
    $dis_funcs = ini_get('disable_functions');
    if (isset($dis_funcs) && $dis_funcs != '') {
        $dis_funcs = explode(',', $dis_funcs);
        if (isset($dis_funcs) && is_array($dis_funcs)) {
            foreach ($dis_funcs as $k => $fnc) {
                // We don't care about disabled process control functions
                $fnc = trim($fnc);
                if (strlen($fnc) === 0 || strpos($fnc, 'pcntl') === 0) {
                    unset($dis_funcs[$k]);
                }
            }
        }
        if (isset($dis_funcs) && count($dis_funcs) > 0) {
            $dis_funcs = implode('<br>', $dis_funcs);
            $result = $fail;

            $dat = array();
            $dat[1]['title'] = 'Disabled Functions';
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $dis_funcs;
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = $result;
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = 'Disabled PHP Functions can impact system functionality.';
            jrCore_page_table_row($dat);
        }
    }

    // FFMPeg install
    $dat = array();
    $dat[1]['title'] = 'FFMpeg binary';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = 'executable';
    $dat[2]['class'] = 'center';
    if ($ffmpeg = jrCore_check_ffmpeg_install(false)) {

        $dir = jrCore_get_module_cache_dir('jrCore');
        $tmp = tempnam($dir, 'system_check_');
        ob_start();
        system("nice -n 9 {$ffmpeg} >{$tmp} 2>&1", $ret);
        ob_end_clean();
        if (is_file($tmp) && strpos(file_get_contents($tmp), 'usage: ffmpeg')) {
            $dat[3]['title'] = $pass;
            $dat[4]['title'] = 'FFMpeg binary is working properly';
        }
        else {
            $dat[3]['title'] = $fail;
            $dat[4]['title'] = "FFMpeg binary is not working<br>{$ffmpeg}";
        }
        unlink($tmp);
    }
    else {
        $dat[3]['title'] = $fail;
        $dat[4]['title'] = 'FFMpeg binary is not executable<br>modules/jrCore/tools/ffmpeg';
    }
    $dat[3]['class'] = 'center';
    jrCore_page_table_row($dat);

    // Directories
    $_to_check = array('cache', 'logs', 'media');
    $_bad = array();
    foreach ($_to_check as $dir) {
        if (!is_dir(APP_DIR . "/data/{$dir}")) {
            // See if we can create it
            if (!mkdir(APP_DIR . "/data/{$dir}", $_conf['jrCore_dir_perms'], true)) {
                $_bad[] = "data/{$dir} does not exist";
            }
        }
        elseif (!is_writable(APP_DIR . "/data/{$dir}")) {
            chmod(APP_DIR . "/data/{$dir}", $_conf['jrCore_dir_perms']);
            if (!is_writable(APP_DIR . "/data/{$dir}")) {
                $_bad[] = "data/{$dir} is not writable";
            }
        }
    }
    if (isset($_bad) && is_array($_bad) && count($_bad) > 0) {
        $note = 'All directories <b>must be writable</b> by web user!';
        $dirs = implode('<br>', $_bad);
        $result = $fail;
    }
    else {
        $note = 'All directories are writable';
        $dirs = 'all writable';
        $result = $pass;
    }
    $dat = array();
    $dat[1]['title'] = 'Data Directories';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = $dirs;
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = $result;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = $note;
    jrCore_page_table_row($dat);

    $upl = jrCore_get_max_allowed_upload();
    $dat = array();
    $dat[1]['title'] = 'Max Upload';
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = jrCore_format_size($upl);
    $dat[2]['class'] = 'center';
    $dat[3]['title'] = ($upl <= 2097152) ? $fail : $pass;
    $dat[3]['class'] = 'center';
    $dat[4]['title'] = ($upl <= 2097152) ? 'increase post_max_size and upload_max_filesize in your php.ini to allow larger uploads' : 'post_max_size and upload_max_filesize are set properly';
    jrCore_page_table_row($dat);

    // Apache rlimits
    if (function_exists('posix_getrlimit')) {
        $_rl = posix_getrlimit();

        // Apache RlimitMEM
        if ((jrCore_checktype($_rl['soft totalmem'], 'number_nz') && $_rl['soft totalmem'] < 67108864) || (jrCore_checktype($_rl['hard totalmem'], 'number_nz') && $_rl['hard totalmem'] < 67108864)) {
            $apmem = $_rl['soft totalmem'];
            if (jrCore_checktype($_rl['hard totalmem'], 'number_nz') && $_rl['hard totalmem'] < $_rl['soft totalmem']) {
                $apmem = $_rl['hard totalmem'];
            }
            $show = (($apmem / 1024) / 1024);
            $dat = array();
            $dat[1]['title'] = 'Apache Memory Limit';
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $show . 'MB';
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = $fail;
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = "Apache is limiting the  memory you can use - this could cause issues, especially when doing Media Conversions. Apache Memory Limits are put in place by your hosting provider, and cannot be modified - contact your hosting provider and have them increase the limit, or set it to &quot;unlimited&quot;.";
            jrCore_page_table_row($dat);
        }
        // Apache RlimitCPU
        if (jrCore_checktype($_rl['soft cpu'], 'number_nz') && $_rl['soft cpu'] < 20) {
            $dat = array();
            $dat[1]['title'] = 'Apache Soft CPU Limit';
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $_rl['soft cpu'];
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = $fail;
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = "Apache is limiting the amount of CPU you can use - this could cause issues, especially when doing Media Conversions. Apache CPU Limits are put in place by your hosting provider, and cannot be modified - you will want to contact your hosting provider and have them set the soft cpu limit to &quot;unlimited&quot;.";
            jrCore_page_table_row($dat);
        }
        elseif (jrCore_checktype($_rl['hard cpu'], 'number_nz') && $_rl['hard cpu'] < 40) {
            $dat = array();
            $dat[1]['title'] = 'Apache Hard CPU Limit';
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $_rl['hard cpu'];
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = $fail;
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = "Apache is limiting the amount of CPU you can use - this could cause issues, especially when doing Media Conversions. Apache CPU Limits are put in place by your hosting provider, and cannot be modified - you will want to contact your hosting provider and have them set the soft cpu limit to &quot;unlimited&quot;.";
            jrCore_page_table_row($dat);
        }

        // Apache RlimitNPROC
        if ((jrCore_checktype($_rl['soft maxproc'], 'number_nz') && $_rl['soft maxproc'] < 200) || (jrCore_checktype($_rl['hard maxproc'], 'number_nz') && $_rl['hard maxproc'] < 200)) {
            $approc = $_rl['soft maxproc'];
            if (jrCore_checktype($_rl['hard maxproc'], 'number_nz') && $_rl['hard maxproc'] < $_rl['soft maxproc']) {
                $approc = $_rl['hard maxproc'];
            }
            $dat = array();
            $dat[1]['title'] = 'Apache Process Limit';
            $dat[1]['class'] = 'center';
            $dat[2]['title'] = $approc;
            $dat[2]['class'] = 'center';
            $dat[3]['title'] = $fail;
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = "Apache is limiting the amount of Processes you can use - this could cause issues, especially when doing Media Conversions. Apache PROC Limits are put in place by your hosting provider, and cannot be modified - you will want to contact your hosting provider and have them set the soft and hard maxproc limits to &quot;unlimited&quot;.";
            jrCore_page_table_row($dat);
        }
    }
    jrCore_page_table_footer();

    jrCore_page_section_header('modules');

    $dat = array();
    $dat[1]['title'] = 'checked';
    $dat[1]['width'] = '20%';
    $dat[2]['title'] = 'value';
    $dat[2]['width'] = '32%';
    $dat[3]['title'] = 'result';
    $dat[3]['width'] = '8%';
    $dat[4]['title'] = 'note';
    $dat[4]['width'] = '40%';
    jrCore_page_table_header($dat);

    // Go through installed modules
    foreach ($_mods as $mod => $_inf) {
        if ($mod == 'jrCore') {
            continue;
        }
        // Check if this module requires other modules to function - make sure they exist and are activated
        if (isset($_inf['module_requires']{1})) {
            $_req = explode(',', $_inf['module_requires']);
            if (is_array($_req)) {
                foreach ($_req as $rmod) {
                    if (!jrCore_module_is_active($rmod)) {
                        $dat = array();
                        $dat[1]['title'] = $_mods[$mod]['module_name'];
                        $dat[1]['class'] = 'center';
                        $dat[2]['title'] = 'required module: ' . $rmod;
                        $dat[2]['class'] = 'center';
                        $dat[3]['title'] = $fail;
                        $dat[3]['class'] = 'center';
                        $dat[4]['title'] = "The <b>{$rmod}</b> module is missing or not active";
                        jrCore_page_table_row($dat);
                    }
                }
            }
        }
        // TODO: This needs to reach out and check for updates
        // See if this module has any additional checks to add
        $_inf['pass'] = $pass;
        $_inf['fail'] = $fail;
        jrCore_trigger_event('jrCore', 'system_check', array(), $_inf, $mod);
    }
    jrCore_page_table_footer();
    jrCore_page_display();
}

//------------------------------
// phpinfo
//------------------------------
function view_jrCore_phpinfo($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (function_exists('phpinfo')) {
        phpinfo();
        exit;
    }
    jrCore_notice_page('error', 'The phpinfo() function has been disabled in your install');
}

//------------------------------
// skin_menu
//------------------------------
function view_jrCore_skin_menu($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore', 'tools');

    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "SELECT * FROM {$tbl} ORDER BY menu_order ASC";
    $_rt = jrCore_db_query($req, 'NUMERIC');

    jrCore_page_banner('skin menu');
    jrCore_get_form_notice();

    $_lang = jrUser_load_lang_strings();

    $dat = array();
    $dat[1]['title'] = '';
    $dat[1]['width'] = '5%;';
    $dat[2]['title'] = 'module';
    $dat[2]['width'] = '10%;';
    $dat[3]['title'] = 'label';
    $dat[3]['width'] = '25%;';
    $dat[4]['title'] = 'URL';
    $dat[4]['width'] = '30%;';
    $dat[5]['title'] = 'active';
    $dat[5]['width'] = '5%;';
    $dat[6]['title'] = 'groups';
    $dat[6]['width'] = '15%;';
    $dat[7]['title'] = 'modify';
    $dat[7]['width'] = '5%;';
    $dat[8]['title'] = 'action';
    $dat[8]['width'] = '5%;';
    jrCore_page_table_header($dat);

    if (isset($_rt) && is_array($_rt)) {

        $top = 0;
        $_qt = jrProfile_get_quotas();
        foreach ($_rt as $k => $_v) {
            $dat = array();
            if (isset($k) && $k > 0) {
                $dat[1]['title'] = jrCore_page_button("u{$k}", '^', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_menu_move_save/id={$_v['menu_id']}/top={$top}'");
            }
            else {
                $dat[1]['title'] = '';
            }
            $top = $_v['menu_id'];
            if (isset($_v['menu_module']) && isset($_mods["{$_v['menu_module']}"])) {
                $dat[2]['title'] = $_v['menu_module'];
            }
            else {
                $dat[2]['title'] = '-';
            }
            $dat[2]['class'] = 'center';
            if (isset($_lang["{$_v['menu_module']}"]["{$_v['menu_label']}"])) {
                $dat[3]['title'] = $_lang["{$_v['menu_module']}"]["{$_v['menu_label']}"] . ' (id: ' . $_v['menu_label'] . ')';
            }
            else {
                $dat[3]['title'] = $_v['menu_label'];
            }
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = $_v['menu_action'];
            $dat[4]['class'] = 'center';
            $dat[5]['title'] = (isset($_v['menu_active']) && $_v['menu_active'] === 'on') ? '<b>yes</b>' : 'no';
            $dat[5]['class'] = 'center';
            if (strpos($_v['menu_groups'], ',')) {
                $_ot = array();
                foreach (explode(',', $_v['menu_groups']) as $grp) {
                    if (isset($grp) && is_numeric($grp) && isset($_qt[$grp])) {
                        $_ot[] = $_qt[$grp];
                    }
                    else {
                        $_ot[] = $grp;
                    }
                }
                $dat[6]['title'] = implode('<br>', $_ot);
            }
            else {
                $dat[6]['title'] = $_v['menu_groups'];
            }
            $dat[6]['class'] = 'center';
            $dat[7]['title'] = jrCore_page_button("m{$k}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_menu_modify/id={$_v['menu_id']}'");

            // We can only delete entries that we have created
            if (isset($_v['menu_module']) && isset($_mods["{$_v['menu_module']}"])) {
                $dat[8]['title'] = jrCore_page_button("d{$k}", 'delete', 'disabled');
            }
            else {
                $dat[8]['title'] = jrCore_page_button("d{$k}", 'delete', "if(confirm('Are you sure you want to delete this entry?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_menu_delete_save/id={$_v['menu_id']}'}");
            }
            $dat[8]['class'] = 'center';
            jrCore_page_table_row($dat);
        }
    }
    else {
        $dat = array();
        $dat[1]['title'] = '<p>There are no custom skin menu entries</p>';
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    $_tmp = array(
        'submit_value' => 'create new entry',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools"
    );
    jrCore_form_create($_tmp);

    // New Menu Entry
    $_tmp = array(
        'name'     => 'new_menu_label',
        'label'    => 'new menu label',
        'help'     => 'Enter the label you would like to appear on this new Menu Entry.',
        'type'     => 'text',
        'validate' => 'printable',
        'required' => true
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// skin_menu_save
//------------------------------
function view_jrCore_skin_menu_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "INSERT INTO {$tbl} (menu_module,menu_active,menu_label,menu_order)
            VALUES ('CustomEntry','0','" . jrCore_db_escape($_post['new_menu_label']) . "',100)";
    $mid = jrCore_db_query($req, 'INSERT_ID');
    if (isset($mid) && jrCore_checktype($mid, 'number_nz')) {
        jrCore_set_form_notice('success', 'The new menu item was successfully created');
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_menu_modify/id={$mid}");
    }
    jrCore_set_form_notice('error', 'Unable to create new menu entry in database - please try again');
    jrCore_form_result();
}

//------------------------------
// skin_menu_move_save
//------------------------------
function view_jrCore_skin_menu_move_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_location('referrer');
    }
    if (!isset($_post['top']) || !jrCore_checktype($_post['top'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid top id - please try again');
        jrCore_location('referrer');
    }
    $pid = (int)$_post['id'];
    $tid = (int)$_post['top'];
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "SELECT * FROM {$tbl} WHERE menu_id IN('{$pid}','{$tid}')";
    $_rt = jrCore_db_query($req, 'menu_id');
    if (isset($_rt) && is_array($_rt)) {
        if (!isset($_rt[$pid])) {
            jrCore_set_form_notice('error', 'invalid menu_id - please try again');
            jrCore_location('referrer');
        }
        if (!isset($_rt[$tid])) {
            jrCore_set_form_notice('error', 'invalid top id - please try again');
            jrCore_location('referrer');
        }
        // Move Up
        if ($_rt[$pid]['menu_order'] == $_rt[$tid]['menu_order']) {
            $ord = $_rt[$tid]['menu_order'] - 1;
        }
        else {
            $ord = $_rt[$tid]['menu_order'];
        }
        $req = "UPDATE {$tbl} SET menu_order = '{$ord}' WHERE menu_id = '{$pid}' LIMIT 1";
        jrCore_db_query($req);

        $ord = $_rt[$pid]['menu_order'];
        $req = "UPDATE {$tbl} SET menu_order = '{$ord}' WHERE menu_id = '{$tid}' LIMIT 1";
        jrCore_db_query($req);
    }
    jrCore_location('referrer');
    return true;
}

//------------------------------
// skin_menu_disable_save
//------------------------------
function view_jrCore_skin_menu_disable_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_location('referrer');
    }
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "UPDATE {$tbl} SET menu_active = 'off' WHERE menu_id = '{$_post['id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_set_form_notice('success', 'The menu item was successfully disabled');
    }
    else {
        jrCore_set_form_notice('error', 'Unable to disable menu entry in database - please try again');
    }
    jrCore_location('referrer');
}

//------------------------------
// skin_menu_delete_save
//------------------------------
function view_jrCore_skin_menu_delete_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_location('referrer');
    }
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "DELETE FROM {$tbl} WHERE menu_id = '{$_post['id']}' AND menu_module = 'CustomEntry' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_set_form_notice('success', 'The menu item was successfully deleted');
    }
    else {
        jrCore_set_form_notice('error', 'Unable to delete menu entry from database - please try again');
    }
    jrCore_location('referrer');
}

//------------------------------
// skin_menu_modify
//------------------------------
function view_jrCore_skin_menu_modify($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_location('referrer');
    }
    // Get info
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "SELECT * FROM {$tbl}";
    $_me = jrCore_db_query($req, 'NUMERIC');

    $_rt = array();
    $_ct = array();
    foreach ($_me as $_v) {
        if (isset($_v['menu_id']) && $_v['menu_id'] == $_post['id']) {
            $_rt = $_v;
        }
        if (isset($_v['menu_category']) && strlen($_v['menu_category']) > 0) {
            $_ct["{$_v['menu_category']}"] = $_v['menu_category'];
        }
    }
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_location('referrer');
    }

    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore', 'tools');
    jrCore_page_banner('modify menu entry');

    $_tmp = array(
        'submit_value'     => 'save changes',
        'cancel'           => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_menu",
        'values'           => $_rt,
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // ID
    $_tmp = array(
        'name'  => 'id',
        'type'  => 'hidden',
        'value' => $_post['id']
    );
    jrCore_form_field_create($_tmp);

    // Label
    $_tmp = array(
        'name'     => 'menu_label',
        'label'    => 'label',
        'help'     => 'This is the text that will appear as the label for the menu entry.<br><br><b>Note:</b> You can enter a language index ID here to use a language entry in place of a text label.',
        'type'     => 'text',
        'validate' => 'printable',
        'required' => true
    );
    jrCore_form_field_create($_tmp);

    // Category
    $_tmp = array(
        'name'     => 'menu_category',
        'label'    => 'category',
        'help'     => 'If your skin menu supports grouping menu entries into categories, you can enter the category for this link here.',
        'type'     => 'select_and_text',
        'options'  => $_ct,
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // URL
    if (isset($_rt['menu_module']) && $_rt['menu_module'] == 'CustomEntry') {
        $_pt = array();
        if (jrCore_module_is_active('jrPage')) {
            $_sp = array(
                'search'   => array(
                    'page_location = 0'
                ),
                'order_by' => array(
                    'page_title' => 'asc'
                ),
                'limit'    => 250
            );
            $_pg = jrCore_db_search_items('jrPage', $_sp);
            if (isset($_pg) && is_array($_pg) && isset($_pg['_items']) && is_array($_pg['_items'])) {
                $purl = jrCore_get_module_url('jrPage');
                foreach ($_pg['_items'] as $_page) {
                    $_pt["{$purl}/{$_page['_item_id']}/{$_page['page_title_url']}"] = $_page['page_title'];
                }
            }
        }
        if (isset($_pt) && is_array($_pt) && count($_pt) > 0) {
            $_tmp = array(
                'name'     => 'menu_action',
                'label'    => 'linked URL',
                'help'     => 'This is the module/view or page that will be loaded when the menu item is clicked on',
                'type'     => 'select_and_text',
                'options'  => $_pt,
                'validate' => 'printable',
                'required' => true
            );
            jrCore_form_field_create($_tmp);
        }
        else {
            $_tmp = array(
                'name'     => 'menu_action',
                'label'    => 'linked URL',
                'help'     => 'This is the module/view or page that will be loaded when the menu item is clicked on',
                'type'     => 'text',
                'validate' => 'printable',
                'required' => true
            );
            jrCore_form_field_create($_tmp);
        }
    }

    // Group
    $_grp = array(
        'all'     => 'Everyone',
        'master'  => 'Master Admins',
        'admin'   => 'Admin Users',
        'power'   => 'Power Users',
        'multi'   => 'Multi Profile Users',
        'user'    => 'Users Only (logged in)',
        'visitor' => 'Visitors Only (not logged in)'
    );
    $_qt = jrProfile_get_quotas();
    if (isset($_qt) && is_array($_qt)) {
        foreach ($_qt as $qid => $qname) {
            $_grp[$qid] = "Quota: {$qname}";
        }
    }
    $_tmp = array(
        'name'     => 'menu_groups',
        'label'    => 'visible to',
        'sublabel' => 'select multiple',
        'help'     => 'Select the group(s) of users that will be able to see this menu entry.',
        'type'     => 'select_multiple',
        'options'  => $_grp,
        'required' => true,
        'size'     => count($_grp)
    );
    jrCore_form_field_create($_tmp);

    $_tmp = array(
        'name'     => 'menu_active',
        'label'    => 'active',
        'help'     => 'Is this menu entry active?',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'default'  => 'on',
        'required' => true
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// skin_menu_modify_save
//------------------------------
function view_jrCore_skin_menu_modify_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_form_result('referrer');
    }
    $tbl = jrCore_db_table_name('jrCore', 'menu');
    $req = "SELECT * FROM {$tbl} WHERE menu_id = '{$_post['id']}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'invalid menu_id - please try again');
        jrCore_form_result('referrer');
    }
    // Update...
    $act = '';
    if (isset($_rt['menu_module']) && $_rt['menu_module'] == 'CustomEntry') {
        $sav = jrCore_db_escape($_post['menu_action']);
        $act = "menu_unique = '{$sav}',menu_action = '{$sav}',";
    }
    $req = "UPDATE {$tbl} SET
              menu_label    = '" . jrCore_db_escape($_post['menu_label']) . "',{$act}
              menu_category = '" . jrCore_db_escape($_post['menu_category']) . "',
              menu_groups   = '" . jrCore_db_escape(implode(',', $_post['menu_groups'])) . "',
              menu_active   = '" . jrCore_db_escape($_post['menu_active']) . "'
             WHERE menu_id = '{$_post['id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (!isset($cnt) || $cnt !== 1) {
        jrCore_set_form_notice('error', 'Error updating menu entry in the database - please try again');
    }
    else {
        jrCore_set_form_notice('success', 'The menu entry was successfully updated');
        jrCore_form_delete_session();
    }
    jrCore_form_result('referrer');
}

//------------------------------
// search
//------------------------------
function view_jrCore_search($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_page_include_admin_menu();

    if (isset($_post['sa']) && $_post['sa'] = 'skin') {
        jrCore_page_admin_tabs($_post['skin'], 'global');
    }
    else {
        jrCore_page_admin_tabs('jrCore', 'global');
    }

    $subtitle = '<input type="text" value="search" name="ss" class="form_text form_admin_search" onfocus="if(this.value==\'search\'){this.value=\'\';}" onblur="if(this.value==\'\'){this.value=\'search\';}" onkeypress="if(event && event.keyCode == 13 && this.value.length > 0){ window.location=\'' . $_conf['jrCore_base_url'] . '/' . $_post['module_url'] . '/search/ss=\'+ jrE(this.value); }">';
    jrCore_page_banner('search results', $subtitle);

    if (!isset($_post['ss']) || strlen($_post['ss']) === 0) {
        jrCore_set_form_notice('error', 'You forgot to enter a search string');
        jrCore_get_form_notice();
    }
    else {
        $fnd = false;
        $src = jrCore_db_escape($_post['ss']);

        // Check if we are searching modules or skins
        $tbl = jrCore_db_table_name('jrCore', 'setting');
        if (isset($_post['sa']) && $_post['sa'] = 'skin') {
            $_sk = jrCore_get_skins();
            $req = "SELECT * FROM {$tbl} WHERE (`module` LIKE '%{$src}%' OR `name` LIKE '%{$src}%' OR `label` LIKE '%{$src}%') AND `type` != 'hidden' AND module IN('" . implode("','", $_sk) . "') ORDER BY `label` ASC";
        }
        else {
            $req = "SELECT * FROM {$tbl} WHERE (`module` LIKE '%{$src}%' OR `name` LIKE '%{$src}%' OR `label` LIKE '%{$src}%') AND `type` != 'hidden' AND module IN('" . implode("','", array_keys($_mods)) . "') ORDER BY `label` ASC";
        }
        $_cf = jrCore_db_query($req, 'NUMERIC');

        if (isset($_cf) && is_array($_cf)) {

            $fnd = true;
            jrCore_page_section_header('Global Settings');

            $dat = array();
            $dat[1]['title'] = 'module';
            $dat[1]['width'] = '5%;';
            $dat[2]['title'] = 'label';
            $dat[2]['width'] = '25%;';
            $dat[3]['title'] = 'help';
            $dat[3]['width'] = '60%;';
            $dat[4]['title'] = 'modify';
            $dat[4]['width'] = '10%;';
            jrCore_page_table_header($dat);

            foreach ($_cf as $_fld) {

                $dat = array();
                if (isset($_post['sa']) && $_post['sa'] = 'skin') {
                    if (!is_dir(APP_DIR . "/skins/{$_fld['module']}")) {
                        continue;
                    }
                    $dat[1]['title'] = '<img src="' . $_conf['jrCore_base_url'] . '/skins/' . $_fld['module'] . '/icon.png" alt="' . $_fld['module'] . '" title="' . $_fld['module'] . '" width="48" height="48">';
                    $dat[4]['title'] = jrCore_page_button("m{$_fld['name']}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/global/skin={$_fld['module']}/hl={$_fld['name']}#ff-{$_fld['name']}'");
                }
                else {
                    if (!is_dir(APP_DIR . "/modules/{$_fld['module']}")) {
                        continue;
                    }
                    $murl = jrCore_get_module_url($_fld['module']);
                    $dat[1]['title'] = '<img src="' . $_conf['jrCore_base_url'] . '/modules/' . $_fld['module'] . '/icon.png" alt="' . $_fld['module'] . '" title="' . $_fld['module'] . '" width="48" height="48">';
                    $dat[4]['title'] = jrCore_page_button("m{$_fld['name']}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$murl}/admin/global/hl={$_fld['name']}#ff-{$_fld['name']}'");
                }
                $dat[1]['class'] = 'center';
                $dat[2]['title'] = '<h3>' . ucwords($_fld['label']) . '</h3>';
                $dat[3]['title'] = $_fld['help'];
                $dat[4]['class'] = 'center';
                jrCore_page_table_row($dat);
            }
            jrCore_page_table_footer();
        }

        $tbl = jrCore_db_table_name('jrProfile', 'quota_setting');
        $req = "SELECT * FROM {$tbl} WHERE (`module` LIKE '%{$src}%' OR `name` LIKE '%{$src}%' OR `label` LIKE '%{$src}%') AND `type` != 'hidden' AND module IN('" . implode("','", array_keys($_mods)) . "') ORDER BY `label` ASC";
        $_cf = jrCore_db_query($req, 'NUMERIC');

        if (isset($_cf) && is_array($_cf)) {

            $fnd = true;
            jrCore_page_section_header('Quota Settings');

            $dat = array();
            $dat[1]['title'] = 'module';
            $dat[1]['width'] = '5%;';
            $dat[2]['title'] = 'label';
            $dat[2]['width'] = '25%;';
            $dat[3]['title'] = 'help';
            $dat[3]['width'] = '60%;';
            $dat[4]['title'] = 'modify';
            $dat[4]['width'] = '10%;';
            jrCore_page_table_header($dat);

            foreach ($_cf as $_fld) {
                if (!is_dir(APP_DIR . "/modules/{$_fld['module']}")) {
                    continue;
                }
                $dat = array();
                $dat[1]['title'] = '<img src="' . $_conf['jrCore_base_url'] . '/modules/' . $_fld['module'] . '/icon.png" alt="' . $_fld['module'] . '" title="' . $_fld['module'] . '" width="48" height="48">';
                $dat[1]['class'] = 'center';
                $dat[2]['title'] = '<h3>' . ucwords($_fld['label']) . '</h3>';
                $dat[3]['title'] = $_fld['help'];
                $murl = jrCore_get_module_url($_fld['module']);
                $dat[4]['title'] = jrCore_page_button("m{$_fld['name']}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$murl}/admin/quota/hl={$_fld['name']}#ff-{$_fld['name']}'");
                $dat[4]['class'] = 'center';
                jrCore_page_table_row($dat);
            }
            jrCore_page_table_footer();
        }

        // Tools
        $_tool = jrCore_get_registered_module_features('jrCore', 'tool_view');
        $_show = array();
        if (isset($_tool) && is_array($_tool)) {
            foreach ($_tool as $tool_mod => $_tools) {
                foreach ($_tools as $view => $_inf) {
                    if (stristr($_inf[0], $_post['ss']) || stristr($_inf[1], $_post['ss'])) {
                        $fnd = true;
                        $_show[] = array(
                            'module' => $tool_mod,
                            'view'   => $view,
                            'label'  => $_inf[0],
                            'help'   => $_inf[1]
                        );
                    }
                }
            }
            if (isset($_show) && is_array($_show) && count($_show) > 0) {

                jrCore_page_section_header('Module Tools');

                $dat = array();
                $dat[1]['title'] = 'module';
                $dat[1]['width'] = '5%;';
                $dat[2]['title'] = 'tool name';
                $dat[2]['width'] = '25%;';
                $dat[3]['title'] = 'help';
                $dat[3]['width'] = '60%;';
                $dat[4]['title'] = 'view';
                $dat[4]['width'] = '10%;';
                jrCore_page_table_header($dat);

                foreach ($_show as $k => $_fld) {
                    $dat = array();
                    $dat[1]['title'] = '<img src="' . $_conf['jrCore_base_url'] . '/modules/' . $_fld['module'] . '/icon.png" alt="' . $_fld['module'] . '" title="' . $_fld['module'] . '" width="48" height="48">';
                    $dat[1]['class'] = 'center';
                    $dat[2]['title'] = '<h3>' . ucwords($_fld['label']) . '</h3>';
                    $dat[3]['title'] = $_fld['help'];
                    $murl = jrCore_get_module_url($_fld['module']);
                    if (!strpos($_fld['view'], 'http')) {
                        $dat[4]['title'] = jrCore_page_button("m{$k}", 'view', "window.location='{$_conf['jrCore_base_url']}/{$murl}/{$_fld['view']}'");
                    }
                    else {
                        $dat[4]['title'] = jrCore_page_button("m{$k}", 'view', "window.location='{$_fld['view']}'");
                    }
                    $dat[4]['class'] = 'center';
                    jrCore_page_table_row($dat);
                }
                jrCore_page_table_footer();
            }
        }

        if (!$fnd) {
            $dat = array();
            $dat[1]['title'] = '';
            jrCore_page_table_header($dat);
            $dat = array();
            $dat[1]['title'] = '<p>No results found to match your search</p>';
            $dat[1]['class'] = 'center';
            jrCore_page_table_row($dat);
            jrCore_page_table_footer();
        }
    }
    jrCore_page_display();
}

//------------------------------
// license (magic)
//------------------------------
function view_jrCore_license($_post, $_user, $_conf)
{
    jrUser_master_only();
    // Check for license file
    $_mta = jrCore_module_meta_data($_post['module']);
    jrCore_page_banner("{$_mta['name']}: license");

    $lic_file = APP_DIR . "/modules/{$_post['module']}/license.html";
    if (is_file($lic_file)) {
        $temp = file_get_contents($lic_file);
        jrCore_page_custom($temp);
    }
    else {
        jrCore_set_form_notice('error', 'NO LICENSE FILE FOUND - contact developer');
        jrCore_get_form_notice();
    }
    jrCore_page_close_button();
    jrCore_page_set_meta_header_only();
    jrCore_page_display();
}

//------------------------------
// dashboard
//------------------------------
function view_jrCore_dashboard($_post, $_user, $_conf)
{
    jrUser_admin_only();
    // http://www.site.com/core/dashboard/online
    // http://www.site.com/core/dashboard/pending
    // http://www.site.com/core/dashboard/browser
    $title = '';
    if (!isset($_post['_1'])) {
        $_post['_1'] = 'bigview';
    }

    jrCore_page_dashboard_tabs($_post['_1']);
    switch ($_post['_1']) {

        //------------------------------
        // BIGVIEW
        //------------------------------
        case 'bigview':
            $title = 'Dashboard';
            $refresh = jrCore_page_button('refresh', 'refresh', "location.reload();");
            jrCore_page_banner('dashboard', $refresh);
            jrCore_get_form_notice();
            jrCore_dashboard_bigview($_post, $_user, $_conf);

            break;

        //------------------------------
        // USERS ONLINE
        //------------------------------
        case 'online':
            $title = 'Users Online';
            $m_url = jrCore_get_module_url('jrUser');
            $nuser = jrCore_page_button('newuser', 'new user account', "window.location='{$_conf['jrCore_base_url']}/{$m_url}/create'");
            jrCore_page_banner('users online', $nuser);
            jrCore_get_form_notice();
            jrUser_online_users($_post, $_user, $_conf);
            break;

        //------------------------------
        // PENDING ITEMS
        //------------------------------
        case 'pending':
            $title = 'Pending Items';
            jrCore_page_banner('pending items');
            jrCore_get_form_notice();
            jrCore_dashboard_pending($_post, $_user, $_conf);
            break;

        //------------------------------
        // ACTIVITY LOG
        //------------------------------
        case 'activity':
            $title = 'Activity Log';
            jrCore_show_activity_log($_post, $_user, $_conf);
            break;

        //------------------------------
        // DATA BROWSER
        //------------------------------
        case 'browser':
            $title = 'Data Browser';
            jrCore_dashboard_browser('dashboard', $_post, $_user, $_conf);
            break;
    }
    jrCore_page_title($title);
    jrCore_page_display();
}

//------------------------------
// form_designer (magic)
//------------------------------
function view_jrCore_form_designer($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    if (!isset($_post['m']) || !isset($_mods["{$_post['m']}"])) {
        jrCore_notice_page('error', 'invalid module');
    }
    if (!isset($_post['v']) || strlen($_post['v']) === 0) {
        jrCore_notice_page('error', 'invalid view');
    }
    $_fields = jrCore_get_designer_form_fields($_post['m'], $_post['v']);
    if (!isset($_fields) || !is_array($_fields)) {
        jrCore_notice_page('error', 'This form has not been setup properly to work with the custom form designer');
    }

    // Save our referring URL so we can easily jump back there
    $tmp = jrCore_get_local_referrer();
    if (isset($tmp) && !strpos($tmp, 'form_field')) {
        $_SESSION['designer_referral_url'] = $tmp;
    }

    $mod = $_post['m'];
    $opt = $_post['v'];
    $url = jrCore_get_module_url('jrCore');

    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    // Show our table of options
    $subtitle = '';
    $tbl = jrCore_db_table_name('jrCore', 'form');
    $req = "SELECT `view` FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($mod) . "' GROUP BY `view` ORDER by `view` ASC";
    $_rt = jrCore_db_query($req, 'view', false, 'view');
    if (isset($_rt) && is_array($_rt)) {
        if (count($_rt) > 1) {
            $jump_url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/form_designer/m={$_post['module']}/v=";
            // Create a Quick Jump list for custom forms for this module
            $subtitle .= '<select name="designer_form" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $jump_url . "'+ v\">\n";
            foreach ($_rt as $option) {
                if ($option == $_post['v']) {
                    $subtitle .= '<option value="' . $option . '" selected="selected"> ' . $_post['module_url'] . '/' . $option . "</option>\n";
                }
                else {
                    $subtitle .= '<option value="' . $option . '"> ' . $_post['module_url'] . '/' . $option . "</option>\n";
                }
            }
            $subtitle .= '</select>';
        }
        else {
            $subtitle = "{$_post['module_url']}/{$_post['v']}";
        }
    }

    // Check for additional views that have been registered by this module, but have
    // not been setup for customization yet...
    $_tmp = jrCore_get_registered_module_features('jrCore', 'designer_form');
    foreach ($_rt as $option) {
        unset($_tmp[$mod][$option]);
    }
    if (isset($_tmp[$mod]) && count($_tmp[$mod]) > 0) {
        $text = "The following designer forms have not been setup yet for this module:<br><br>";
        foreach ($_tmp[$mod] as $view => $prefix) {
            $text .= "{$_post['module_url']}/{$view}<br>";
        }
        $text .= "<br>These forms will be initialized the first time they are viewed.  It is recommended that you view all forms for this module before using the Form Designer.";
        jrCore_set_form_notice('notice', $text, false);
    }

    jrCore_page_banner('form designer', $subtitle);
    jrCore_get_form_notice();

    $dat = array();
    $dat[1]['title'] = 'order';
    $dat[1]['width'] = '2%;';
    $dat[2]['title'] = 'label';
    $dat[2]['width'] = '38%;';
    $dat[3]['title'] = 'name';
    $dat[3]['width'] = '15%;';
    $dat[4]['title'] = 'type';
    $dat[4]['width'] = '15%;';
    $dat[5]['title'] = 'active';
    $dat[5]['width'] = '10%;';
    $dat[6]['title'] = 'required';
    $dat[6]['width'] = '10%;';
    $dat[7]['title'] = 'modify';
    $dat[7]['width'] = '5%;';
    $dat[8]['title'] = 'delete';
    $dat[8]['width'] = '5%;';
    jrCore_page_table_header($dat);

    foreach ($_fields as $_fld) {

        $dat = array();
        if ($_fld['order'] > 1) {
            $dat[1]['title'] = jrCore_page_button("o{$_fld['name']}", '^', "window.location='{$_conf['jrCore_base_url']}/{$url}/form_field_order/m={$mod}/v={$opt}/n={$_fld['name']}/o={$_fld['order']}'");
        }
        else {
            $dat[1]['title'] = '';
        }
        $dat[2]['title'] = (is_numeric($_fld['label']) && isset($_lang[$mod]["{$_fld['label']}"])) ? '&nbsp;' . $_lang[$mod]["{$_fld['label']}"] : '&nbsp;*' . $_fld['label'] . '*';
        $dat[3]['title'] = $_fld['name'];
        $dat[3]['class'] = 'center';
        $dat[4]['title'] = $_fld['type'];
        $dat[4]['class'] = 'center';
        $dat[5]['title'] = (isset($_fld['active']) && $_fld['active'] == '1') ? 'yes' : '<b>no</b>';
        $dat[5]['class'] = 'center';
        $dat[6]['title'] = (isset($_fld['required']) && $_fld['required'] == '1') ? 'yes' : 'no';
        $dat[6]['class'] = 'center';
        $dat[7]['title'] = jrCore_page_button("m{$_fld['name']}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/form_field_update/m={$mod}/v={$opt}/n={$_fld['name']}'");
        if (isset($_fld['locked']) && $_fld['locked'] == '1') {
            $dat[8]['title'] = jrCore_page_button("d{$_fld['name']}", 'delete', 'disabled');
        }
        else {
            $dat[8]['title'] = jrCore_page_button("d{$_fld['name']}", 'delete', "if (confirm('Are you sure you want to delete this form field?')){window.location='{$_conf['jrCore_base_url']}/{$url}/form_field_delete/m={$mod}/v={$opt}/n={$_fld['name']}'}");
        }
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
    jrCore_page_divider();

    // We need to record where we come in from
    $ckey = md5(json_encode($_post));
    if (!isset($_SESSION["form_designer_{$ckey}"])) {
        $_SESSION["form_designer_{$ckey}"] = jrCore_get_local_referrer();
    }
    $_tmp = array(
        'submit_value' => 'create new field',
        'cancel'       => $_SESSION["form_designer_{$ckey}"]
    );
    jrCore_form_create($_tmp);

    // Module
    $_tmp = array(
        'name'     => 'field_module',
        'type'     => 'hidden',
        'value'    => $mod,
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // View
    $_tmp = array(
        'name'     => 'field_view',
        'type'     => 'hidden',
        'value'    => $opt,
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // New Form Field
    $_tmp = array(
        'name'     => 'new_name',
        'label'    => 'new field name',
        'help'     => 'If you would like to create a new field in this form, enter the field name here.',
        'type'     => 'text',
        'value'    => jrCore_db_get_prefix($mod) . '_',
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    if (isset($_post['v']) && ($_post['v'] == 'create' || $_post['v'] == 'update')) {
        $opp = ($_post['v'] == 'create') ? 'update' : 'create';
        // See if this module defines the opposite view
        require_once APP_DIR . "/modules/{$mod}/index.php";
        if (function_exists("view_{$mod}_{$opp}")) {
            if (isset($_rt[$opp])) {
                // Link to Update/Create
                $_tmp = array(
                    'name'     => "linked_form_field",
                    'label'    => "add to {$opp} form",
                    'help'     => "If you would like the same field name created for the &quot;{$opp}&quot; form view, check this option",
                    'type'     => 'checkbox',
                    'value'    => 'on',
                    'validate' => 'onoff'
                );
                jrCore_form_field_create($_tmp);
            }
        }
    }
    jrCore_page_display();
}

//------------------------------
// form_designer_save
//------------------------------
function view_jrCore_form_designer_save($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (!isset($_post['field_module']) || !isset($_mods["{$_post['field_module']}"])) {
        jrCore_set_form_notice('error', 'Invalid module');
        jrCore_form_result();
    }
    if (!isset($_post['field_view']) || strlen($_post['field_view']) === 0) {
        jrCore_set_form_notice('error', 'Invalid view');
        jrCore_form_result();
    }
    $mod = $_post['field_module'];
    $opt = $_post['field_view'];
    $_fields = jrCore_get_designer_form_fields($mod, $opt);
    if (!isset($_fields) || !is_array($_fields)) {
        jrCore_set_form_notice('error', 'This form has not been setup properly to work with the custom form designer');
        jrCore_form_result();
    }
    $nam = strtolower($_post['new_name']);
    // Make sure we don't already exist
    if (isset($_fields[$nam]) && is_array($_fields[$nam])) {
        jrCore_set_form_notice('error', 'The name you entered is already being used in this form - please enter a different name.');
        jrCore_form_field_hilight('new_name');
        jrCore_form_result();
    }
    // Now our new field MUST begin with the DataStore prefix
    $prfx = jrCore_db_get_prefix($mod);
    if (strpos($_post['new_name'], $prfx) !== 0) {
        jrCore_set_form_notice('error', "The new field name must begin with &quot;{$prfx}&quot;");
        jrCore_form_field_hilight('new_name');
        jrCore_form_result();
    }
    // We can't just use the prefix
    if ($_post['new_name'] == $prfx || $_post['new_name'] == "{$prfx}_") {
        jrCore_set_form_notice('error', "Please enter a valid field name beyond just the prefix");
        jrCore_form_field_hilight('new_name');
        jrCore_form_result();
    }
    // Looks good - create new form field
    $_field = array(
        'name'   => $_post['new_name'],
        'type'   => 'text',
        'label'  => $_post['new_name'],
        'locked' => '0'
    );
    jrCore_set_flag('jrcore_designer_create_custom_field', 1);
    $tmp = jrCore_verify_designer_form_field($mod, $opt, $_field);
    if ($tmp) {
        // See if we are also adding it to the create/update view
        if (isset($_post['linked_form_field']) && $_post['linked_form_field'] == 'on') {
            $opp = ($opt == 'create') ? 'update' : 'create';
            $tmp = jrCore_verify_designer_form_field($mod, $opp, $_field);
            if (!$tmp) {
                jrCore_set_form_notice('error', "An error was encountered inserting the new field into the {$opp} form - please try again");
                jrCore_form_result();
            }
        }
        $url = jrCore_get_module_url($mod);
        jrCore_form_delete_session();

        // Insert defaults into each existing record - note that this is required otherwise
        // these records may not be searchable
        $tbl = jrCore_db_table_name($_post['field_module'], 'item_key');
        $key = jrCore_db_escape($_post['new_name']);
        // Make sure this field has not already been setup by another view in the same module
        $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = '{$key}' LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            $req = "INSERT INTO {$tbl} (`_item_id`,`key`,`value`) SELECT DISTINCT(`_item_id`),'{$key}','' FROM {$tbl} WHERE `_item_id` > 0";
            jrCore_db_query($req);
        }

        jrCore_form_result("{$_conf['jrCore_base_url']}/{$url}/form_field_update/m={$mod}/v={$opt}/n={$_post['new_name']}");
        return true;
    }
    jrCore_set_form_notice('error', 'An error was encountered saving the new for field to the database - please try again');
    jrCore_form_result();
    return true;
}

//------------------------------
// form_field_delete
//------------------------------
function view_jrCore_form_field_delete($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    if (!isset($_post['m']) || !isset($_mods["{$_post['m']}"])) {
        jrCore_set_form_notice('error', 'Invalid module');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['v']) || strlen($_post['v']) === 0) {
        jrCore_set_form_notice('error', 'Invalid view');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['n']) || strlen($_post['n']) === 0) {
        jrCore_set_form_notice('error', 'Invalid name');
        jrCore_form_result('referrer');
    }
    $mod = jrCore_db_escape($_post['m']);
    $opt = jrCore_db_escape($_post['v']);
    $nam = jrCore_db_escape($_post['n']);
    $tbl = jrCore_db_table_name('jrCore', 'form');
    $req = "DELETE FROM {$tbl} WHERE `module` = '{$mod}' AND `view` = '{$opt}' and `name` = '{$nam}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        // We need to reset any existing Form Sessions for this view
        jrCore_form_delete_session_view($_post['m'], $_post['v']);
        jrCore_set_form_notice('success', 'The form field was successfully deleted');
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered trying to delete the form field - please try again');
    }
    jrCore_form_result('referrer');
}

//------------------------------
// form_field_order
//------------------------------
function view_jrCore_form_field_order($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    if (!isset($_post['m']) || !isset($_mods["{$_post['m']}"])) {
        jrCore_set_form_notice('error', 'Invalid module');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['v']) || strlen($_post['v']) === 0) {
        jrCore_set_form_notice('error', 'Invalid view');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['n']) || strlen($_post['n']) === 0) {
        jrCore_set_form_notice('error', 'Invalid name');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['o']) || !jrCore_checktype($_post['o'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid order');
        jrCore_form_result('referrer');
    }
    $ord = intval($_post['o'] - 1);
    // Okay - we need to MOVE UP the name we got, and MOVE DOWN the one above it
    jrCore_set_form_designer_field_order($_post['m'], $_post['v'], $_post['n'], $ord);
    jrCore_form_delete_session_view($_post['m'], $_post['v']);
    jrCore_form_result('referrer');
}

//------------------------------
// form_field_update (magic)
//------------------------------
function view_jrCore_form_field_update($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    if (!isset($_post['m']) || !isset($_mods["{$_post['m']}"])) {
        jrCore_notice_page('error', 'invalid module');
    }
    if (!isset($_post['v']) || strlen($_post['v']) === 0) {
        jrCore_notice_page('error', 'invalid view');
    }
    if (!isset($_post['n']) || strlen($_post['n']) === 0) {
        jrCore_notice_page('error', 'invalid name');
    }
    $mod = $_post['m'];
    $opt = $_post['v'];
    $_fields = jrCore_get_designer_form_fields($mod, $opt);
    if (!isset($_fields) || !is_array($_fields)) {
        jrCore_notice_page('error', 'This form has not been setup properly to work with the custom form designer');
    }
    $nam = $_post['n'];
    if (!isset($_fields[$nam]) || !is_array($_fields[$nam])) {
        jrCore_notice_page('error', 'This form field has not been setup properly to work with the custom form designer');
    }
    $_fld = $_fields[$nam];

    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    jrCore_page_banner("field: <span style=\"text-transform:lowercase;\">{$_fld['name']}</span>", "{$_post['module_url']}/{$_post['v']}");

    // Some fields will BREAK if they are changed - warn about this
    switch ($nam) {
        case 'user_passwd1':
        case 'user_passwd2':
            jrCore_set_form_notice('warning', 'This field is required for proper functionality - do not <b>make inactive</b> or change the field <b>type</b>, <b>validation</b> or <b>group</b> fields!', false);
            break;
    }
    jrCore_get_form_notice();

    // Show our table of options
    $_tmp = array(
        'submit_value' => 'save changes',
        'cancel'       => 'referrer'
    );
    jrCore_form_create($_tmp);

    // Module
    $_tmp = array(
        'name'     => 'field_module',
        'type'     => 'hidden',
        'value'    => $mod,
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // View
    $_tmp = array(
        'name'     => 'field_view',
        'type'     => 'hidden',
        'value'    => $opt,
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // Name
    $_tmp = array(
        'name'     => 'name',
        'type'     => 'hidden',
        'value'    => $nam,
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // Fields can have the following attributes:
    // label
    // sublabel
    // help
    // name
    // type
    // validate
    // options
    // min
    // max
    // required

    // Field Label
    $_tmp = array(
        'name'     => 'label',
        'label'    => 'label',
        'help'     => 'This is the Label name that will appear to the left of the field.<br><br><b>NOTE:</b> If you see *change* in the field it means this text label has not been created yet - enter a label and save your changes.',
        'type'     => 'text',
        'value'    => (isset($_lang[$mod]["{$_fld['label']}"])) ? $_lang[$mod]["{$_fld['label']}"] : $_fld['label'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Field Sub Label
    $_tmp = array(
        'name'     => 'sublabel',
        'label'    => 'sub label',
        'help'     => 'This is the text that will be appear UNDER the Label in smaller type. Use this to let the user know about any restrictions in the field. This is an optional field - if left empty it will not show.',
        'type'     => 'text',
        'value'    => (isset($_lang[$mod]["{$_fld['sublabel']}"])) ? $_lang[$mod]["{$_fld['sublabel']}"] : $_fld['sublabel'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Field Help
    $_tmp = array(
        'name'     => 'help',
        'label'    => 'help',
        'help'     => 'The Help text will appear in the small drop down area when the user clicks on the Question button (like you are viewing right now). Leave this empty to not show a help drop down.',
        'type'     => 'text',
        'value'    => (isset($_lang[$mod]["{$_fld['help']}"])) ? $_lang[$mod]["{$_fld['help']}"] : $_fld['help'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Some field types have their own internal validation, so we "disable"
    // this field if those types are the selected one
    $_dis = array(
        'optionlist',
        'select',
        'select_multiple',
        'radio',
        'audio',
        'image',
        'video',
        'file',
        'checkbox',
        'checkbox_spambot',
        'chained_select',
        'chained_select_and_text'
    );

    // Disabled Options for some fields
    $_dop = array(
        'checkbox',
        'checkbox_spambot',
        'date',
        'datetime',
        'editor',
        'password',
        'text',
        'textarea'
    );

    // Disabled Default for some fields
    $_def = array(
        'file',
        'password',
        'audio',
        'image',
        'video'
    );

    // Disabled Min/Max for some fields
    $_dmx = array(
        'file',
        'audio',
        'image',
        'video',
        'optionlist',
        'select',
        'select_multiple',
        'checkbox',
        'checkbox_spambot',
        'chained_select',
        'chained_select_and_text'
    );

    // Field Type
    $_opt = array();
    $_tmp = jrCore_get_registered_module_features('jrCore', 'form_field');
    if (isset($_tmp) && is_array($_tmp)) {
        foreach ($_tmp as $_v) {
            foreach ($_v as $k => $v) {
                $_opt[$k] = $k;
            }
        }
        unset($_opt['hidden'], $_opt['custom'], $_opt['live_search']);
    }
    $_tmp = array(
        'name'     => 'type',
        'label'    => 'type',
        'help'     => 'The Field Type defines the type of form element that will be displayed for this field.<br><br><b>Note</b> that that if <b>chained_select</b> or <b>chained_select_and_text</b> are selected, the Field Names for all chained fields must be the same and end with _0, _1, _2 etc. for successive links.',
        'type'     => 'select',
        'options'  => $_opt,
        'value'    => $_fld['type'],
        'validate' => 'core_string',
        'onchange' => "var a=this.options[this.selectedIndex].value;var b={'" . implode("':1,'", $_dis) . "':1};if(typeof b[a] !== 'undefined' && b[a] == 1){\$('.validate_element_right select').fadeTo(250,0.3).attr('disabled','disabled').addClass('form_element_disabled')} else {\$('.validate_element_right select').fadeTo(100,1).removeAttr('disabled').removeClass('form_element_disabled')};var c={'" . implode("':1,'", $_dop) . "':1};if(typeof c[a] !== 'undefined' && c[a] == 1){\$('.options_element_right textarea').fadeTo(250,0.3).attr('disabled','disabled').addClass('form_element_disabled')} else {\$('.options_element_right textarea').fadeTo(100,1).removeAttr('disabled').removeClass('form_element_disabled')};var d={'" . implode("':1,'", $_def) . "':1};if(typeof d[a] !== 'undefined' && d[a] == 1){\$('.default_element_right #default').fadeTo(250,0.3).attr('disabled','disabled').addClass('form_element_disabled')} else {\$('.default_element_right #default').fadeTo(100,1).removeAttr('disabled').removeClass('form_element_disabled')};var e={'" . implode("':1,'", $_dmx) . "':1};if(typeof e[a] !== 'undefined' && e[a] == 1){\$('.min_element_right #min').fadeTo(250,0.3).attr('disabled','disabled').addClass('form_element_disabled');\$('.max_element_right #max').fadeTo(250,0.3).attr('disabled','disabled').addClass('form_element_disabled')} else {\$('.min_element_right #min').fadeTo(100,1).removeAttr('disabled').removeClass('form_element_disabled');\$('.max_element_right #max').fadeTo(100,1).removeAttr('disabled').removeClass('form_element_disabled')}"

    );
    jrCore_form_field_create($_tmp);

    // Options
    $_opt = array();
    if (isset($_fld['options']) && strpos($_fld['options'], '{') === 0) {
        $_tmp = json_decode($_fld['options'], true);
        if (isset($_tmp) && is_array($_tmp)) {
            foreach ($_tmp as $k => $v) {
                $_opt[] = "{$k}|{$v}";
            }
            $_fld['options'] = implode("\n", $_opt);
        }
    }
    $_tmp = array(
        'name'     => 'options',
        'label'    => 'options',
        'sublabel' => 'see <b>help</b> for what is allowed here',
        'help'     => '&bull; If this field is a <b>select</b>, <b>select_multiple</b>, <b>radio</b> or <b>optionlist</b> field, you can enter the form options ONE PER LINE, in the following format:<br><br><b>Option Value|Option Text</b><br><br>You may also enter a valid module FUNCTION name that will return the options dynamically.<br><br>&bull; If this is a <b>file</b> field, you can enter the allowed file extensions as a comma separated list - i.e. &quot;txt,pdf,doc,xls&quot; - only files of these types will be allowed to be uploaded.<br><br>&bull; If this is a <b>chained_select</b> field, the options are created using the Chained Select module - set this to the <b>Option Set Name</b> you have created in the module.',
        'type'     => 'textarea',
        'value'    => $_fld['options'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Field Default
    $_tmp = array(
        'name'     => 'default',
        'label'    => 'default',
        'help'     => 'If you would like a default value to be used for this field, enter the default value here.',
        'type'     => 'text',
        'value'    => $_fld['default'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Validate
    $_opt = array();
    $_tmp = jrCore_get_registered_module_features('jrCore', 'checktype');
    if (isset($_tmp) && is_array($_tmp)) {
        foreach ($_tmp as $mod => $_entries) {
            foreach ($_entries as $type => $ignore) {
                $func = $mod . '_checktype_' . $type;
                if (function_exists($func)) {
                    $check_type = jrCore_checktype('', $type, false, true);
                    $_opt[$type] = '(' . $check_type . ') ' . jrCore_checktype('', $type, true, true);
                }
            }
        }
    }
    $_tmp = array(
        'name'     => 'validate',
        'label'    => 'validation',
        'help'     => 'Select the type of field validation you would like to have for this field. The following field types:<br><br>optionlist<br>select<br>select_multiple<br>radio<br>image<br>file<br>audio<br>checkbox<br><br>are automatically validated internally, so the validation option will be grayed out if these field types are selected.',
        'type'     => 'select',
        'options'  => $_opt,
        'value'    => $_fld['validate'],
        'validate' => 'core_string'
    );
    // See if we have selected a disabled type
    if (in_array($_fld['type'], $_dis)) {
        $_js = array("$('.validate_element_right select').fadeTo(250,0.3).attr('disabled','disabled')");
        jrCore_create_page_element('javascript_ready_function', $_js);
    }
    jrCore_form_field_create($_tmp);

    // Field Min
    $_tmp = array(
        'name'     => 'min',
        'label'    => 'minimum',
        'help'     => 'The Field Minimum Value will validate that any entered value is greater than or equal to the minimum value.<br><br><b>For (number) Fields:</b> This is the minimum value accepted.<br><b>For (string) Fields:</b> This is the minimum <b>character length</b> for the string.<br><b>For (date) Fields:</b> This is the minimum accepted date (in YYYYMMDD[HHMMSS] format).',
        'type'     => 'text',
        'value'    => (isset($_fld['min']) && $_fld['min'] == '0') ? '' : (int)$_fld['min'],
        'validate' => 'number_nn'
    );
    jrCore_form_field_create($_tmp);

    // Field Max
    $_tmp = array(
        'name'     => 'max',
        'label'    => 'maximum',
        'help'     => 'The Field Maximum Value will validate that any entered value is less than or equal to the maximum value.<br><br><b>For (number) Fields:</b> This is the maximum value accepted.<br><b>For (string) Fields:</b> This is the maximum <b>character length</b> for the string.<br><b>For (date) Fields:</b> This is the maximum accepted date (in YYYYMMDD[HHMMSS] format).',
        'type'     => 'text',
        'value'    => (isset($_fld['max']) && $_fld['max'] == '0') ? '' : (int)$_fld['max'],
        'validate' => 'number_nz'
    );
    jrCore_form_field_create($_tmp);

    // Field Group
    $_opt = array(
        'master' => '(group) Master Admins Only',
        'admin'  => '(group) Profile Admins Only',
        'user'   => '(group) Normal Users'
    );
    $_qta = jrProfile_get_quotas();
    if (isset($_qta) && is_array($_qta)) {
        $_opt = $_opt + $_qta;
    }
    $_tmp = array(
        'name'     => 'group',
        'label'    => 'group',
        'help'     => 'If you would like this field to only be visible to User in specific Profile Quotas, Profile Admins or Master Admins, select the group here.',
        'type'     => 'select',
        'options'  => $_opt,
        'value'    => $_fld['group'],
        'default'  => 'user',
        'validate' => 'core_string'
    );
    jrCore_form_field_create($_tmp);

    // Field Required
    $_tmp = array(
        'name'     => 'required',
        'label'    => 'required',
        'help'     => 'If you would like to ensure a valid value is always received for this field, check the Field Required option.',
        'type'     => 'checkbox',
        'value'    => (isset($_fld['required']) && $_fld['required'] == '1') ? 'on' : 'off',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Field Active
    $_tmp = array(
        'name'     => 'active',
        'label'    => 'active',
        'help'     => 'If Field Active is not checked, this field will not appear in the form.',
        'type'     => 'checkbox',
        'value'    => (isset($_fld['active']) && $_fld['active'] == '1') ? 'on' : 'off',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    if (isset($_post['v']) && ($_post['v'] == 'create' || $_post['v'] == 'update')) {

        // Make sure this module supplies the create/update view
        $opp = ($_post['v'] == 'create') ? 'update' : 'create';
        require_once APP_DIR . "/modules/{$mod}/index.php";
        if (function_exists("view_{$mod}_{$opp}")) {
            // Link to Update/Create
            $_tmp = array(
                'name'     => "linked_form_field",
                'label'    => "change {$opp} field",
                'help'     => "If you would like your changes to be saved to the same field in the &quot;{$opp}&quot; form, check here.",
                'type'     => 'checkbox',
                'value'    => 'on',
                'validate' => 'onoff'
            );
            jrCore_form_field_create($_tmp);
        }
    }

    jrCore_page_display();
}

//------------------------------
// form_field_update_save (magic)
//------------------------------
function view_jrCore_form_field_update_save($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_form_validate($_post);
    if (!isset($_post['field_module']) || !isset($_mods["{$_post['field_module']}"])) {
        jrCore_set_form_notice('error', 'Invalid module');
        jrCore_form_result();
    }
    if (!isset($_post['field_view']) || strlen($_post['field_view']) === 0) {
        jrCore_set_form_notice('error', 'Invalid view');
        jrCore_form_result();
    }
    if (isset($_post['required']) && $_post['required'] == 'on') {
        $_post['required'] = 1;
    }
    else {
        $_post['required'] = 0;
    }
    if (isset($_post['active']) && $_post['active'] == 'on') {
        $_post['active'] = 1;
    }
    else {
        $_post['active'] = 0;
    }
    $mod = $_post['field_module'];
    $opt = $_post['field_view'];
    $nam = $_post['name'];

    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');
    $_save = array();

    // Update Lang Strings
    $_tm = jrCore_get_designer_form_fields($mod, $opt);
    $tbl = jrCore_db_table_name('jrUser', 'language');
    if (isset($_tm[$nam]) && is_array($_tm[$nam])) {
        $_todo = array('label', 'sublabel', 'help');
        foreach ($_todo as $do) {
            $num = (isset($_tm[$nam][$do]) && jrCore_checktype($_tm[$nam][$do], 'number_nz')) ? (int)$_tm[$nam][$do] : 0;
            if (isset($num) && jrCore_checktype($num, 'number_nz')) {
                if (isset($_lang[$mod][$num])) {
                    if ($do === 'label') {
                        $_post[$do] = strtolower($_post[$do]);
                    }
                    $req = "UPDATE {$tbl} SET lang_text = '" . jrCore_db_escape($_post[$do]) . "' WHERE lang_module = '" . jrCore_db_escape($mod) . "' AND lang_code = '" . jrCore_db_escape($_user['user_language']) . "' AND lang_key = '{$num}' LIMIT 1";
                    jrCore_db_query($req);
                    $_save[$do] = $_post[$do];
                    $_post[$do] = $num;
                }
            }
        }
        jrCore_delete_all_cache_entries('jrUser');
    }

    // See if we are Create/Update Linked
    if (isset($_post['linked_form_field']) && $_post['linked_form_field'] == 'on') {
        $opp = ($_post['field_view'] == 'create') ? 'update' : 'create';
        $_tm = jrCore_get_designer_form_fields($mod, $opp);
        if (isset($_tm[$nam]) && is_array($_tm[$nam])) {
            $_todo = array('label', 'sublabel', 'help');
            foreach ($_todo as $do) {
                $num = (isset($_tm[$nam][$do]) && jrCore_checktype($_tm[$nam][$do], 'number_nz')) ? (int)$_tm[$nam][$do] : 0;
                if (isset($num) && jrCore_checktype($num, 'number_nz')) {
                    if (isset($_lang[$mod][$num])) {
                        $req = "UPDATE {$tbl} SET lang_text = '" . jrCore_db_escape($_save[$do]) . "' WHERE lang_module = '" . jrCore_db_escape($mod) . "' AND lang_code = '" . jrCore_db_escape($_user['user_language']) . "' AND lang_key = '{$num}' LIMIT 1";
                        jrCore_db_query($req);
                    }
                }
            }
        }
        jrCore_delete_all_cache_entries('jrUser');
    }

    // Check validation.  Some fields (such as checkbox) have specific validation
    // requirements - set this here so they cannot be set wrong.
    switch ($_post['type']) {
        case 'date':
        case 'datetime':
            $_post['validate'] = 'date';
            break;
        case 'select_date':
            $_post['validate'] = 'number_nz';
            break;
        case 'checkbox':
            $_post['validate'] = 'onoff';
            break;
        case 'select':
        case 'select_multiple':
        case 'radio':
        case 'optionlist':
            // For a select field, our OPTIONS will come in either as a FUNCTION or as individual options on each line
            if (isset($_post['options']) && strlen($_post['options']) > 0) {
                $cfunc = $_post['options'];
                if (!function_exists($cfunc)) {
                    // okay - we're not a function
                    $_tmp = explode("\n", $_post['options']);
                    if (!isset($_tmp) || !is_array($_tmp)) {
                        jrCore_set_form_notice('error', 'You have entered an invalid value for Options - must be a valid function or a set of options, one per line.');
                        jrCore_form_result();
                    }
                    $_post['options'] = array();
                    foreach ($_tmp as $v) {
                        $v = trim($v);
                        if (strpos($v, '|')) {
                            list($k, $v) = explode('|', $v, 2);
                        }
                        else {
                            $k = $v;
                        }
                        $_post['options'][$k] = $v;
                    }
                }
            }
            else {
                jrCore_set_form_notice('error', 'You must enter valid Options for a Select form field');
                jrCore_form_result();
            }
            break;
    }

    // First - get existing default value for use below
    $def = '';
    $tbl = jrCore_db_table_name('jrCore', 'form');
    $req = "SELECT `default` FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($_post['field_module']) . "' AND `name` = '" . jrCore_db_escape($_post['name']) . "' LIMIT 1";
    $_ev = jrCore_db_query($req, 'SINGLE');
    if (isset($_ev) && is_array($_ev) && isset($_ev['default']) && strlen($_ev['default']) > 0) {
        $def = jrCore_db_escape($_ev['default']);
    }

    $cnt = jrCore_verify_designer_form_field($_post['field_module'], $_post['field_view'], $_post);
    if (isset($cnt) && $cnt == '1') {
        if (isset($_post['linked_form_field']) && $_post['linked_form_field'] == 'on') {
            // The linked lang strings are handled above - don't change them here
            unset($_post['label'], $_post['sublabel'], $_post['help']);
            $opp = ($_post['field_view'] == 'create') ? 'update' : 'create';
            $cnt = jrCore_verify_designer_form_field($_post['field_module'], $opp, $_post);
            if (!isset($cnt) || $cnt != '1') {
                jrCore_set_form_notice('error', "An error was encountered updating the linked form field in the {$opp} form view - please try again");
                jrCore_form_result();
            }
        }
        jrCore_form_delete_session();
        jrCore_form_delete_session_view($_post['field_module'], $_post['field_view']);
        jrCore_set_form_notice('success', 'The field settings were successfully updated');

        // Next, we need to update any existing values in the DB
        // with the new default value, but only for those that have not
        // been set, or are still set to the previous default value (if set)
        $tbl = jrCore_db_table_name($_post['field_module'], 'item_key');
        $key = jrCore_db_escape($_post['name']);
        $val = jrCore_db_escape($_post['default']);
        $req = "UPDATE {$tbl} SET `value` = '{$val}' WHERE `key` = '{$key}' AND (`value` IS NULL OR `value` = '' OR `value` = '{$def}')";
        jrCore_db_query($req);
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered saving the form field - please try again');
    }
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/form_designer/m={$mod}/v={$opt}");
}

//------------------------------
// skin_admin (magic)
//------------------------------
function view_jrCore_skin_admin($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_create_media_directory(0);
    jrUser_load_lang_strings();
    $_lang = jrCore_get_flag('jr_lang');

    if (!isset($_post['_1'])) {
        $_post['_1'] = 'info';
    }
    if (!isset($_post['skin']{0})) {
        $_post['skin'] = $_conf['jrCore_active_skin'];
    }

    $admin = '';
    $title = '';
    // See if we are getting an INDEX page for this module.  The Index
    // Page will tell us what "view" for the module config they are showing.
    // This can be either a config page for the module (i.e. global settings,
    // quota settings, language, etc.) OR it can be a tool.
    // Our URL will be like:
    // http://www.site.com/core/config/global
    // http://www.site.com/core/config/quota
    // http://www.site.com/core/config/language
    // http://www.site.com/core/config/tools
    switch ($_post['_1']) {

        //------------------------------
        // GLOBAL SETTINGS
        //------------------------------
        case 'global':
            $title = 'Global Config';
            $admin = jrCore_show_global_settings('skin', $_post['skin'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // STYLE
        //------------------------------
        case 'style':
            $title = 'Style';
            $admin = jrCore_show_skin_style($_post['skin'], $_post, $_user, $_conf);

            // Bring in our Color Picker if needed
            $_tmp = jrCore_get_flag('style_color_picker');
            if ($_tmp) {
                $_inc = array('source' => "{$_conf['jrCore_base_url']}/modules/jrCore/js/jquery.colorpicker.js");
                jrCore_create_page_element('javascript_href', $_inc);
                foreach ($_tmp as $v) {
                    jrCore_create_page_element('javascript_ready_function', $v);
                }
            }
            break;

        //------------------------------
        // IMAGES
        //------------------------------
        case 'images':
            $title = 'Images';
            $admin = jrCore_show_skin_images('skin', $_post['skin'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // LANGUAGE STRINGS
        //------------------------------
        case 'language':
            $title = 'Language Strings';
            $admin = jrUser_show_module_lang_strings('skin', $_post['skin'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // TEMPLATES
        //------------------------------
        case 'templates':
            $title = 'Templates';
            $admin = jrCore_show_skin_templates($_post['skin'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // INFO
        //------------------------------
        case 'info':
            $title = 'Info';
            $admin = jrCore_show_skin_info($_post['skin'], $_post, $_user, $_conf);
            break;
    }

    // Expand our skins
    $_rt = jrCore_get_skins();
    $_sk = array();
    foreach ($_rt as $skin_dir) {
        $func = "{$skin_dir}_skin_meta";
        if (!function_exists($func)) {
            require_once APP_DIR . "/skins/{$skin_dir}/include.php";
        }
        if (function_exists($func)) {
            $_sk[$skin_dir] = $func();
        }
    }

    // Process view
    $_rep = array(
        'active_tab'         => 'skins',
        'admin_page_content' => $admin,
        '_skins'             => $_sk
    );

    // We need to go through each module and get it's default page
    foreach ($_rep['_skins'] as $k => $_v) {
        if (is_file(APP_DIR . "/skins/{$k}/config.php")) {
            $_rep['_skins'][$k]['skin_index_page'] = 'global';
        }
        elseif (isset($_lang[$k])) {
            $_rep['_skins'][$k]['skin_index_page'] = 'language';
        }
        else {
            // info
            $_rep['_skins'][$k]['skin_index_page'] = 'info';
        }
    }

    // See if our skin is overriding our core admin template
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/admin.tpl")) {
        $html = jrCore_parse_template('admin.tpl', $_rep);
    }
    else {
        $html = jrCore_parse_template('admin.tpl', $_rep, 'jrCore');
    }

    // Output
    jrCore_page_title("{$title} - {$_sk["{$_post['skin']}"]['name']}");
    jrCore_page_custom($html);
    jrCore_page_display();
}

//------------------------------
// skin_admin_save (magic)
//------------------------------
function view_jrCore_skin_admin_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Post will look like:
    // [_uri] => core/admin_save/global/__ajax=1
    // [jr_html_form_token] => b7bd8223a6f82333b5350396409556af
    // [date_format] => %D
    // [hour_format] => %I:%M:%S%p
    // [system_timezone] => 0
    // [active_email_system] => jrCore_debug
    // [active_skin] => jrSuperNova
    // [maintenance_notice] => The System is currently undergoing system maintenance. We are working to get the system back online as soon as possible. Thank you for your patience.
    // [system_name] => 192.168.1.123
    // [tempsid] => 13779234ad5c007ee15542366754d8f3
    // [autologin] => 1-2bf7e64ff4ed39e4dce1960a22f407df
    // [module_url] => core
    // [module] => jrCore
    // [option] => admin_save
    // [__ajax] => 1
    // [dls_adjust] => off
    // [maintenance_mode] => off

    // Make sure we get a good skin
    if (!isset($_post['skin'])) {
        $_post['skin'] = $_conf['jrCore_active_skin'];
    }

    // See what we are saving...
    switch ($_post['_1']) {

        case 'global':

            // See if this module is presenting us with a validate function
            if (is_file(APP_DIR . "/skins/{$_post['skin']}/config.php")) {
                $vfunc = "{$_post['skin']}_config_validate";
                if (!function_exists($vfunc)) {
                    require_once APP_DIR . "/skins/{$_post['skin']}/config.php";
                }
                if (function_exists($vfunc)) {
                    $_post = $vfunc($_post);
                }
            }
            // Update
            foreach ($_post as $k => $v) {
                if (isset($_conf["{$_post['skin']}_{$k}"]) && $v != $_conf["{$_post['skin']}_{$k}"]) {
                    jrCore_set_setting_value($_post['skin'], $k, $v);
                }
            }
            jrCore_delete_all_cache_entries('jrCore', 0);
            jrCore_set_form_notice('success', 'The settings have been successfully saved');
            break;

        case 'language':

            // Get all the lang strings for this module
            $tbl = jrCore_db_table_name('jrUser', 'language');
            $mod = jrCore_db_escape($_post['skin']);
            $req = "SELECT * FROM {$tbl} WHERE lang_module = '{$mod}' AND lang_code = '" . jrCore_db_escape($_post['lang_code']) . "'";
            $_rt = jrCore_db_query($req, 'lang_id');
            if (!isset($_rt) || !is_array($_rt)) {
                jrCore_set_form_notice('error', "Unable to retrieve skin language settings from language table - check debug_log errors");
                jrCore_form_result();
            }
            $req = "UPDATE {$tbl} SET lang_text = CASE lang_id\n";
            foreach ($_rt as $key => $_lng) {
                if (isset($_post["lang_{$key}"])) {
                    $req .= "WHEN {$key} THEN '" . jrCore_db_escape($_post["lang_{$key}"]) . "'\n";
                }
            }
            if (isset($req) && strpos($req, 'THEN')) {
                $req .= "ELSE lang_text END";
                jrCore_db_query($req, 'COUNT');
            }
            jrCore_delete_all_cache_entries('jrUser');
            jrCore_set_form_notice('success', 'The language strings have been successfully saved');
            jrCore_form_delete_session();
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/{$_post['_1']}/skin={$_post['skin']}/lang_code={$_post['lang_code']}/p={$_post['p']}");
            break;

        case 'images':

            jrCore_create_media_directory(0);
            // Get existing skin info to see what images we have customized
            $_im = array();
            if (isset($_conf["jrCore_{$_post['skin']}_custom_images"]{2})) {
                $_im = json_decode($_conf["jrCore_{$_post['skin']}_custom_images"], true);
            }
            // Check for new custom files being uploaded
            $_up = jrCore_get_uploaded_meter_files($_post['upload_token']);
            if (isset($_up) && is_array($_up)) {
                foreach ($_up as $_info) {
                    jrCore_copy_media_file(0, $_info['tmp_name'], "{$_post['skin']}_{$_info['name']}");
                    $_im["{$_info['name']}"] = array($_info['size'], 'on');
                }
            }
            // Go through and save our uploaded images (if any)
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $k => $_info) {
                    $num = (int)str_replace('file_', '', $k);
                    $nam = $_post["name_{$num}"];
                    if (isset($_info['size']) && jrCore_checktype($_info['size'], 'number_nz')) {
                        // Image extensions must match
                        $ext = jrCore_file_extension($_info['name']);
                        switch ($ext) {
                            case 'jpg':
                            case 'png':
                            case 'gif':
                                break;
                            default:
                                jrCore_set_form_notice('error', 'Invalid image type for ' . $_post["name_{$num}"] . ' - only JPG, PNG and GIF images are allowed');
                                jrCore_form_result();
                                break;
                        }
                        if (isset($_post["name_{$num}"]{0})) {
                            jrCore_copy_media_file(0, $_info['tmp_name'], "{$_post['skin']}_{$nam}");
                            $_im[$nam] = array($_info['size']);
                        }
                    }
                }
            }
            // Update setting with new values
            // [name_0_active] => on
            // [name_0] => bckgrd.png
            foreach ($_post as $k => $v) {
                if (strpos($k, 'name_') === 0 && strpos($k, '_active')) {
                    $num = (int)substr($k, 5, strrpos($k, '_'));
                    $nam = $_post["name_{$num}"];
                    if (isset($_im[$nam][0])) {
                        $_im[$nam][1] = $v;
                    }
                    else {
                        unset($_im[$nam]);
                    }
                }
            }
            jrCore_set_setting_value('jrCore', "{$_post['skin']}_custom_images", json_encode($_im));
            jrCore_delete_all_cache_entries('jrCore', 0);
            break;

        case 'style':

            // We need to save our updates to the database so they "override" the defaults...
            $_out = array();
            $_com = array();
            foreach ($_post as $k => $v) {
                // all of our custom style entries will start with "jrse"....
                if (strpos($k, 'jrse') === 0) {
                    // We have a style entry.  the key for this entry will in position 4
                    $key = $k;
                    if (strpos($key, '_')) {
                        list($key,) = explode('_', $k);
                    }
                    $key = (int)substr($key, 4);
                    if (!isset($_com[$key])) {
                        // Now we can get our Name, Selector and New Value.
                        list($selector, $rule) = explode('~', $_post["jrse{$key}_s"], 2);
                        // See if we have a color...
                        if (isset($_post["jrse{$key}_hex"])) {
                            $_out[$selector][$rule] = trim($_post["jrse{$key}_hex"]);
                        }
                        else {
                            $_out[$selector][$rule] = trim($_post["jrse{$key}"]);
                        }
                        $_com[$key] = 1;
                    }
                }
            }
            // Save out to database
            $tbl = jrCore_db_table_name('jrCore', 'skin');
            $req = "SELECT skin_custom_css FROM {$tbl} WHERE skin_directory = '" . jrCore_db_escape($_post['skin']) . "'";
            $_rt = jrCore_db_query($req, 'SINGLE');
            if (isset($_rt) && is_array($_rt) && isset($_rt['skin_custom_css']{2})) {
                $_css = json_decode($_rt['skin_custom_css'], true);
                $_css = array_merge($_css, $_out);
            }
            else {
                $_css = $_out;
            }
            $_css = json_encode($_css);
            $skn = jrCore_db_escape($_post['skin']);
            $req = "INSERT INTO {$tbl} (skin_directory, skin_updated, skin_custom_css) VALUES ('{$skn}',UNIX_TIMESTAMP(),'" . jrCore_db_escape($_css) . "')
                    ON DUPLICATE KEY UPDATE skin_updated = UNIX_TIMESTAMP(), skin_custom_css = '" . jrCore_db_escape($_css) . "'";
            $cnt = jrCore_db_query($req, 'COUNT');
            if (!isset($cnt) || $cnt === 0) {
                jrCore_set_form_notice('error', 'An error was enountered saving the custom style to the database - please try again');
                jrCore_form_result();
            }
            // Recreate our site CSS
            jrCore_create_master_css($_post['skin']);
            jrCore_form_delete_session();
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/{$_post['_1']}/skin={$_post['skin']}/file={$_post['file']}");
            break;

        case 'templates':

            //  [form_begin_template_active] => on
            $_act = array();
            $_off = array();
            $_all = array();
            foreach ($_post as $k => $v) {
                if (strpos($k, '_template_active')) {
                    $tpl = str_replace('_template_active', '.tpl', $k);
                    // See if we are turning this template on or off
                    if ($v == 'on') {
                        $_act[] = $tpl;
                        $_all[] = $tpl;
                    }
                    else {
                        $_off[] = $tpl;
                        $_all[] = $tpl;
                    }
                }
            }

            // Set active/inactive
            if (isset($_all) && is_array($_all) && count($_all) > 0) {
                $tbl = jrCore_db_table_name('jrCore', 'template');
                $mod = jrCore_db_escape($_post['skin']);
                if (isset($_act) && is_array($_act) && count($_act) > 0) {
                    $req = "UPDATE {$tbl} SET template_active = '1', template_updated = UNIX_TIMESTAMP() WHERE template_module = '{$mod}' AND template_name IN('" . implode("','", $_act) . "')";
                    jrCore_db_query($req);
                }
                if (isset($_off) && is_array($_off) && count($_off) > 0) {
                    $req = "UPDATE {$tbl} SET template_active = '0', template_updated = UNIX_TIMESTAMP() WHERE template_module = '{$mod}' AND template_name IN('" . implode("','", $_off) . "')";
                    jrCore_db_query($req);
                }
                // Reset cache for any that changed
                foreach ($_all as $tpl) {
                    jrCore_get_template_file($tpl, $_post['skin'], 'reset');
                }
            }
            jrCore_set_form_notice('success', 'The template settings have been successfully saved');
            break;

        case 'info':

            // Update
            if (isset($_post['skin_active']) && $_post['skin_active'] == 'on') {
                jrCore_set_setting_value('jrCore', 'active_skin', $_post['skin']);
                $dir = jrCore_get_module_cache_dir($_post['skin']);
                jrCore_delete_dir_contents($dir);
                jrCore_delete_all_cache_entries();
            }
            jrCore_set_form_notice('success', 'The settings have been successfully saved');
            break;

    }
    jrCore_form_delete_session();
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/{$_post['_1']}/skin={$_post['skin']}");
}

//------------------------------
// admin (magic)
//------------------------------
function view_jrCore_admin($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_install_new_modules();

    $admin = '';
    $title = '';
    // See if we are getting an INDEX page for this module.  The Index
    // Page will tell us what "view" for the module config they are showing.
    // This can be either a config page for the module (i.e. global settings,
    // quota settings, language, etc.) OR it can be a tool.
    // Our URL will be like:
    // http://www.site.com/core/config/global
    // http://www.site.com/core/config/quota
    // http://www.site.com/core/config/language
    // http://www.site.com/core/config/tools
    if (!isset($_post['_1'])) {
        $_post['_1'] = 'global';
    }
    switch ($_post['_1']) {

        //------------------------------
        // GLOBAL SETTINGS
        //------------------------------
        case 'global':
            $title = 'Global Config';
            $admin = jrCore_show_global_settings('module', $_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // QUOTA SETTINGS
        //------------------------------
        case 'quota':
            $title = 'Quota Config';
            $admin = jrProfile_show_module_quota_settings($_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // TOOLS
        //------------------------------
        case 'tools':
            $title = 'Tools';
            $admin = jrCore_show_module_tools($_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // LANGUAGE STRINGS
        //------------------------------
        case 'language':
            $title = 'Language Strings';
            $admin = jrUser_show_module_lang_strings('module', $_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // TEMPLATES
        //------------------------------
        case 'templates':
            $title = 'Templates';
            $admin = jrCore_show_module_templates($_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // IMAGES
        //------------------------------
        case 'images':
            $title = 'Images';
            $admin = jrCore_show_skin_images('module', $_post['module'], $_post, $_user, $_conf);
            break;

        //------------------------------
        // INFO
        //------------------------------
        case 'info':
            $title = 'Info';
            $admin = jrCore_show_module_info($_post['module'], $_post, $_user, $_conf);
            break;

    }

    // Process view
    $_rep = array(
        'active_tab'         => 'modules',
        'admin_page_content' => $admin
    );

    $_tmp = array();
    foreach ($_mods as $mod_dir => $_inf) {
        $_tmp["{$_inf['module_name']}"] = $mod_dir;
    }
    ksort($_tmp);

    $_out = array();
    foreach ($_tmp as $mod_dir) {
        if (!isset($_mods[$mod_dir]['module_category'])) {
            $_mods[$mod_dir]['module_category'] = 'utilities';
        }
        $cat = $_mods[$mod_dir]['module_category'];
        if (!isset($_out[$cat])) {
            $_out[$cat] = array();
        }
        $_out[$cat][$mod_dir] = $_mods[$mod_dir];
    }
    $_rep['_modules']['core'] = $_out['core'];
    unset($_out['core']);
    $_rep['_modules'] = $_rep['_modules'] + $_out;
    ksort($_rep['_modules']);
    unset($_out);

    // See if our skin is overriding our core admin template
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/admin.tpl")) {
        $html = jrCore_parse_template('admin.tpl', $_rep);
    }
    else {
        $html = jrCore_parse_template('admin.tpl', $_rep, 'jrCore');
    }

    // Output
    $_mta = jrCore_module_meta_data($_post['module']);
    jrCore_page_title("{$title} - {$_mta['name']}");
    jrCore_admin_menu_accordion_js();
    jrCore_page_custom($html);
    jrCore_page_display();
}

//------------------------------
// admin_save (magic)
//------------------------------
function view_jrCore_admin_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Post will look like:
    // [_uri] => core/admin_save/global/__ajax=1
    // [jr_html_form_token] => b7bd8223a6f82333b5350396409556af
    // [date_format] => %D
    // [hour_format] => %I:%M:%S%p
    // [system_timezone] => 0
    // [active_email_system] => jrCore_debug
    // [active_skin] => jrSuperNova
    // [maintenance_notice] => The System is currently undergoing system maintenance. We are working to get the system back online as soon as possible. Thank you for your patience.
    // [system_name] => 192.168.1.123
    // [tempsid] => 13779234ad5c007ee15542366754d8f3
    // [autologin] => 1-2bf7e64ff4ed39e4dce1960a22f407df
    // [module_url] => core
    // [module] => jrCore
    // [option] => admin_save
    // [__ajax] => 1
    // [dls_adjust] => off
    // [maintenance_mode] => off

    // See what we are saving...
    switch ($_post['_1']) {

        case 'global':

            // See if this module is presenting us with a validate function
            if (is_file(APP_DIR . "/modules/{$_post['module']}/config.php")) {
                $vfunc = "{$_post['module']}_config_validate";
                if (!function_exists($vfunc)) {
                    require_once APP_DIR . "/modules/{$_post['module']}/config.php";
                }
                if (function_exists($vfunc)) {
                    $_temp = $vfunc($_post);
                    if (!$_temp) {
                        // Error in validation
                        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/{$_post['_1']}");
                        return true;
                    }
                    $_post = $_temp;
                    unset($_temp);
                }
            }
            // Update
            foreach ($_post as $k => $v) {
                if (isset($_conf["{$_post['module']}_{$k}"]) && $v != $_conf["{$_post['module']}_{$k}"]) {
                    jrCore_set_setting_value($_post['module'], $k, $v);
                }
            }
            jrCore_delete_all_cache_entries('jrCore', 0);
            jrCore_set_form_notice('success', 'The settings have been successfully saved');
            break;

        case 'quota':

            // See if this module is presenting us with a validate function
            if (is_file(APP_DIR . "/modules/{$_post['module']}/quota.php")) {
                $vfunc = "{$_post['module']}_quota_config_validate";
                if (!function_exists($vfunc)) {
                    require_once APP_DIR . "/modules/{$_post['module']}/quota.php";
                }
                if (function_exists($vfunc)) {
                    $_temp = $vfunc($_post);
                    if (!$_temp) {
                        // Error in validation
                        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/{$_post['_1']}");
                        return true;
                    }
                    $_post = $_temp;
                    unset($_temp);
                }
            }

            // See if we are doing a single quota or ALL quotas
            if (isset($_post['apply_to_all_quotas']) && $_post['apply_to_all_quotas'] == 'on') {
                $_aq = jrProfile_get_quotas();
                foreach ($_aq as $qid => $qname) {
                    $_qt = jrProfile_get_quota($_post['id']);
                    foreach ($_post as $k => $v) {
                        if (isset($_qt["quota_{$_post['module']}_{$k}"])) {
                            jrProfile_set_quota_value($_post['module'], $qid, $k, $v);
                        }
                    }
                }
            }
            else {
                if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
                    jrCore_set_form_notice('error', 'Invalid quota_id');
                    jrCore_form_result();
                }
                // Get current settings for this Quota
                $_qt = jrProfile_get_quota($_post['id']);
                if (!isset($_qt) || !is_array($_qt)) {
                    jrCore_set_form_notice('error', 'Invalid quota_id - unable to retrieve settings');
                    jrCore_form_result();
                }
                // Update
                foreach ($_post as $k => $v) {
                    if (isset($_qt["quota_{$_post['module']}_{$k}"])) {
                        jrProfile_set_quota_value($_post['module'], $_post['id'], $k, $v);
                    }
                }
            }

            // Set our session sync flag so users in this quota that are logged in will re-sync
            // NOTE: This is done here as a FLAG instead of a direct update to the DB, as the
            // update is handled in jrCore_form_session_delete
            jrCore_set_flag('session_sync_quota_id', $_post['id']);

            // Empty caches
            jrCore_delete_all_cache_entries();

            jrCore_form_delete_session();
            jrCore_set_form_notice('success', 'The settings have been successfully saved');
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/{$_post['_1']}/id={$_post['id']}");
            break;

        case 'info':

            // Update
            $tbl = jrCore_db_table_name('jrCore', 'module');

            if (isset($_post['module_delete']) && $_post['module_delete'] === 'on') {

                // There are some modules we cannot delete
                switch ($_post['module']) {
                    case 'jrCore':
                    case 'jrUser':
                    case 'jrProfile':
                        jrCore_set_form_notice('error', "The {$_post['module']} module cannot be deleted - it is a required core module");
                        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/info");
                        break;
                }

                // Try to recursively remove the module
                $mod = jrCore_db_escape($_post['module']);
                $req = "DELETE FROM {$tbl} WHERE module_directory = '{$mod}' LIMIT 1";
                $cnt = jrCore_db_query($req, 'COUNT');
                if (!isset($cnt) || $cnt !== 1) {
                    jrCore_set_form_notice('error', 'An error was encountered deleting the module from the database - please try again');
                    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/info");
                }
                if (jrCore_delete_dir_contents(APP_DIR . "/modules/{$_post['module']}", false)) {
                    rmdir(APP_DIR . "/modules/{$_post['module']}");
                    jrCore_set_form_notice('success', 'The module was successfully deleted');
                    jrCore_form_delete_session();
                }
                else {
                    jrCore_set_form_notice('error', 'An error was encountered deleting the module - please try again');
                }
                $url = jrCore_get_module_url('jrCore');
                jrCore_delete_all_cache_entries('jrCore', 0);
                jrCore_form_result("{$_conf['jrCore_base_url']}/{$url}/admin/global");
            }
            else {
                $url = jrCore_db_escape($_post['new_module_url']);
                $cat = jrCore_db_escape($_post['new_module_category']);
                $act = (isset($_post['module_active']) && $_post['module_active'] == 'off') ? '0' : '1';
                $mod = jrCore_db_escape($_post['module']);
                $req = "UPDATE {$tbl} SET module_updated = UNIX_TIMESTAMP(), module_url = '{$url}', module_active = '{$act}', module_category = '{$cat}' WHERE module_directory = '{$mod}' LIMIT 1";
                $cnt = jrCore_db_query($req, 'COUNT');
                if (!isset($cnt) || $cnt !== 1) {
                    jrCore_set_form_notice('error', 'An error was encountered saving the module settings - please try again');
                    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/info");
                }
                $_post['module_url'] = $_post['new_module_url'];
                // Verify the module if we are turning it on
                if ((!isset($_mods[$mod]['module_active']) || $_mods[$mod]['module_active'] != '1') && $act == '1') {
                    sleep(1);
                    jrCore_verify_module($mod);
                }
                jrCore_delete_all_cache_entries('jrCore', 0);
                jrCore_set_form_notice('success', 'The settings have been successfully saved');
            }

            // Reset core module/config cache
            jrCore_delete_cache('jrCore', 'jrcore_config_and_modules', false);

            break;

        case 'language':

            // Get all the lang strings for this module
            $tbl = jrCore_db_table_name('jrUser', 'language');
            $mod = jrCore_db_escape($_post['module']);
            $req = "SELECT * FROM {$tbl} WHERE lang_module = '{$mod}' AND lang_code = '" . jrCore_db_escape($_post['lang_code']) . "'";
            $_rt = jrCore_db_query($req, 'lang_id');
            if (!isset($_rt) || !is_array($_rt)) {
                jrCore_set_form_notice('error', "Unable to retrieve language settings for module from language table - check debug_log errors");
                jrCore_form_result();
            }
            $req = "UPDATE {$tbl} SET lang_text = CASE lang_id\n";
            foreach ($_rt as $key => $_lng) {
                if (isset($_post["lang_{$key}"])) {
                    $req .= "WHEN {$key} THEN '" . jrCore_db_escape($_post["lang_{$key}"]) . "'\n";
                }
            }
            if (isset($req) && strpos($req, 'THEN')) {
                $req .= "ELSE lang_text END";
                jrCore_db_query($req);
            }
            jrCore_delete_all_cache_entries('jrUser');
            jrCore_set_form_notice('success', 'The language strings have been successfully saved');
            jrCore_form_delete_session();
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/{$_post['_1']}/lang_code={$_post['lang_code']}/p={$_post['p']}");
            break;

        case 'images':

            jrCore_create_media_directory(0);
            // Get existing module info to see what images we have customized
            $_im = array();
            if (isset($_conf["jrCore_{$_post['module']}_custom_images"]{2})) {
                $_im = json_decode($_conf["jrCore_{$_post['module']}_custom_images"], true);
            }
            // Check for new custom files being uploaded
            $_up = jrCore_get_uploaded_meter_files($_post['upload_token']);
            if (isset($_up) && is_array($_up)) {
                foreach ($_up as $_info) {
                    jrCore_copy_media_file(0, $_info['tmp_name'], "mod_{$_post['module']}_{$_info['name']}");
                    $_im["{$_info['name']}"] = array($_info['size'], 'on');
                }
            }
            // Go through and save our uploaded images (if any)
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $k => $_info) {
                    $num = (int)str_replace('file_', '', $k);
                    $nam = $_post["name_{$num}"];
                    if (isset($_info['size']) && jrCore_checktype($_info['size'], 'number_nz')) {
                        // Image extensions must match
                        $ext = jrCore_file_extension($_info['name']);
                        switch ($ext) {
                            case 'jpg':
                            case 'png':
                            case 'gif':
                                break;
                            default:
                                jrCore_set_form_notice('error', 'Invalid image type for ' . $_post["name_{$num}"] . ' - only JPG, PNG and GIF images are allowed');
                                jrCore_form_result();
                                break;
                        }
                        if (isset($_post["name_{$num}"]{0})) {
                            jrCore_copy_media_file(0, $_info['tmp_name'], "mod_{$_post['module']}_{$nam}");
                            $_im[$nam] = array($_info['size']);
                        }
                    }
                }
            }
            // Update setting with new values
            // [name_0_active] => on
            // [name_0] => bckgrd.png
            foreach ($_post as $k => $v) {
                if (strpos($k, 'name_') === 0 && strpos($k, '_active')) {
                    $num = (int)substr($k, 5, strrpos($k, '_'));
                    $nam = $_post["name_{$num}"];
                    if (isset($_im[$nam][0])) {
                        $_im[$nam][1] = $v;
                    }
                    else {
                        unset($_im[$nam]);
                    }
                }
            }
            jrCore_set_setting_value('jrCore', "{$_post['module']}_custom_images", json_encode($_im));
            jrCore_delete_all_cache_entries('jrCore', 0);
            break;

        case 'templates':

            //  [form_begin_template_active] => on
            $_act = array();
            $_off = array();
            $_all = array();
            foreach ($_post as $k => $v) {
                if (strpos($k, '_template_active')) {
                    $tpl = str_replace('_template_active', '.tpl', $k);
                    // See if we are turning this template on or off
                    if ($v == 'on') {
                        $_act[] = $tpl;
                        $_all[] = $tpl;
                    }
                    else {
                        $_off[] = $tpl;
                        $_all[] = $tpl;
                    }
                }
            }

            // Set active/inactive
            if (isset($_all) && is_array($_all) && count($_all) > 0) {
                $mod = jrCore_db_escape($_post['module']);
                $tbl = jrCore_db_table_name('jrCore', 'template');
                if (isset($_act) && is_array($_act) && count($_act) > 0) {
                    $req = "UPDATE {$tbl} SET template_active = '1', template_updated = UNIX_TIMESTAMP() WHERE template_module = '{$mod}' AND template_name IN('" . implode("','", $_act) . "')";
                    jrCore_db_query($req);
                }
                if (isset($_off) && is_array($_off) && count($_off) > 0) {
                    $req = "UPDATE {$tbl} SET template_active = '0', template_updated = UNIX_TIMESTAMP() WHERE template_module = '{$mod}' AND template_name IN('" . implode("','", $_off) . "')";
                    jrCore_db_query($req);
                }

                // Reset cache for any that were changed
                foreach ($_all as $tpl) {
                    jrCore_get_template_file($tpl, $_post['module'], 'reset');
                }
            }
            jrCore_set_form_notice('success', 'The template settings have been successfully saved');
            break;
    }
    jrCore_form_delete_session();
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/{$_post['_1']}");
}

//------------------------------
// template_modify (magic)
//------------------------------
function view_jrCore_template_modify($_post, $_user, $_conf)
{
    jrUser_master_only();

    // Setup Code Mirror
    $_tmp = array('source' => "{$_conf['jrCore_base_url']}/modules/jrCore/contrib/codemirror/lib/codemirror.css");
    jrCore_create_page_element('css_href', $_tmp);
    $_tmp = array('source' => "{$_conf['jrCore_base_url']}/modules/jrCore/contrib/codemirror/lib/codemirror.js");
    jrCore_create_page_element('javascript_href', $_tmp);
    $_tmp = array('source' => "{$_conf['jrCore_base_url']}/modules/jrCore/contrib/codemirror/mode/smarty/smarty.js");
    jrCore_create_page_element('javascript_href', $_tmp);
    $_tmp = array('var editor = CodeMirror.fromTextArea(document.getElementById("template_body"), { lineNumbers: true, matchBrackets: true });');
    jrCore_create_page_element('javascript_ready_function', $_tmp);

    jrCore_page_include_admin_menu();

    if (isset($_post['skin'])) {
        jrCore_page_skin_tabs($_post['skin'], 'templates');
        $cancel_url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/templates/skin={$_post['skin']}";
        $t_type = 'skin';
    }
    else {
        jrCore_page_admin_tabs($_post['module'], 'templates');
        $cancel_url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/templates";
        $t_type = 'module';
    }

    // our page banner
    jrCore_page_banner('Template Editor');

    $_tmp = array(
        'submit_value'     => 'save changes',
        'cancel'           => $cancel_url,
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    // Template ID
    $_tmp = array(
        'name'  => 'template_type',
        'type'  => 'hidden',
        'value' => $t_type
    );
    jrCore_form_field_create($_tmp);

    if (isset($_post['skin']{0})) {
        $_tmp = array(
            'name'  => 'skin',
            'type'  => 'hidden',
            'value' => $_post['skin']
        );
        jrCore_form_field_create($_tmp);
    }

    // Get info about this template...
    $tpl_body = '';
    if (isset($_post['id']) && jrCore_checktype($_post['id'], 'number_nz')) {
        // Database template
        $tbl = jrCore_db_table_name('jrCore', 'template');
        $req = "SELECT * FROM {$tbl} WHERE template_id = '{$_post['id']}'";
        $_tp = jrCore_db_query($req, 'SINGLE');
        if (!isset($_tp) || !is_array($_tp)) {
            jrCore_set_form_notice('error', 'Invalid template_id - please try again');
            jrCore_location("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/templates");
        }
        $tpl_body = $_tp['template_body'];

        // Template ID
        $_tmp = array(
            'name'  => 'template_id',
            'type'  => 'hidden',
            'value' => $_post['id']
        );
        jrCore_form_field_create($_tmp);
    }
    // From file
    elseif (isset($_post['template']{1}) && jrCore_checktype($_post['template'], 'printable')) {

        // Make sure this is a good file
        $_post['template'] = basename($_post['template']);
        if (isset($_post['skin']{0})) {
            $tpl_file = APP_DIR . "/skins/{$_post['skin']}/{$_post['template']}";
        }
        else {
            $tpl_file = APP_DIR . "/modules/{$_post['module']}/templates/{$_post['template']}";
        }
        if (!is_file($tpl_file)) {
            jrCore_set_form_notice('error', 'Template file not found - please try again');
            jrCore_location("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/templates");
        }
        $tpl_body = file_get_contents($tpl_file);

        $_tmp = array(
            'name'  => 'template_name',
            'type'  => 'hidden',
            'value' => $_post['template']
        );
        jrCore_form_field_create($_tmp);
    }
    else {
        jrCore_set_form_notice('error', 'Invalid template - please try again');
        jrCore_location($cancel_url);
    }

    // Show template
    if (isset($_SESSION['template_body_save']) && strlen($_SESSION['template_body_save']) > 0) {
        $tpl_body = $_SESSION['template_body_save'];
        unset($_SESSION['template_body_save']);
    }
    $html = '<div class="form_template"><textarea id="template_body" name="template_body" class="form_template_editor">' . htmlspecialchars($tpl_body) . '</textarea></div>';
    jrCore_page_custom($html);
    jrCore_page_display();
}

//------------------------------
// test_template
//------------------------------
function view_jrCore_test_template($_post, $_user, $_conf)
{
    global $_mods;
    if (!isset($_post['_1']) || strlen($_post['_1']) === 0) {
        echo "error: invalid template";
        exit;
    }
    $cdr = jrCore_get_module_cache_dir('jrCore');
    $nam = $_post['_1'];
    if (!is_file("{$cdr}/{$nam}")) {
        echo "error : unable to open template file for testing";
        exit;
    }
    ini_set('display_errors', 1);
    ini_set('log_errors', 0);

    if (!class_exists('Smarty')) {
        require_once APP_DIR .'/modules/jrCore/contrib/smarty/libs/Smarty.class.php';
    }

    // Set our compile dir
    $temp = new Smarty;
    $temp->compile_dir = APP_DIR .'/data/cache/'. $_conf['jrCore_active_skin'];

    // Get plugin directories
    $_dir = array(APP_DIR .'/modules/jrCore/contrib/smarty/libs/plugins');
    $temp->plugins_dir = $_dir;
    $temp->force_compile = true;

    $_data['page_title']  = jrCore_get_flag('jrcore_html_page_title');
    $_data['jamroom_dir'] = APP_DIR;
    $_data['jamroom_url'] = $_conf['jrCore_base_url'];
    $_data['_conf']       = $_conf;
    $_data['_post']       = $_post;
    $_data['_mods']       = $_mods;
    $_data['_user']       = $_SESSION;

    // Remove User and MySQL info - we don't want this to ever leak into a template
    unset($_data['_user']['user_password'],$_data['_user']['user_old_password'],$_data['_user']['user_forgot_key']);
    unset($_data['_conf']['jrCore_db_host'],$_data['_conf']['jrCore_db_user'],$_data['_conf']['jrCore_db_pass'],$_data['_conf']['jrCore_db_name'],$_data['_conf']['jrCore_db_port']);

    $temp->assign($_data);
    ob_start();
    $temp->display("{$cdr}/{$nam}");
    $html = ob_get_contents();
    ob_end_clean();
    echo $html;
    exit;
}

//------------------------------
// template_modify_save (magic)
//------------------------------
function view_jrCore_template_modify_save($_post, $_user, $_conf)
{
    jrUser_master_only();

    // See if we are doing a skin or module
    $tid = false;
    $crt = false;
    $mod = (isset($_post['skin'])) ? $_post['skin'] : $_post['module'];

    // We need to test this template and make sure it does not cause any Smarty errors
    $cdr = jrCore_get_module_cache_dir('jrCore');
    $nam = time() . ".tpl";
    jrCore_write_to_file("{$cdr}/{$nam}", $_post['template_body']);
    $out = jrCore_load_url("{$_conf['jrCore_base_url']}/{$_post['module_url']}/test_template/{$nam}");
    if (isset($out) && strlen($out) === 0 || strpos($out,'error:') === 0 || stristr($out,'fatal error')) {
        $_SESSION['template_body_save'] = $_post['template_body'];
        unlink("{$cdr}/{$nam}");
        jrCore_set_form_notice('error', 'There is a syntax error in your template - please fix and try again');
        jrCore_form_result();
    }
    unlink("{$cdr}/{$nam}");

    $tbl = jrCore_db_table_name('jrCore', 'template');
    // See if we are updating a DB template or first time file
    if (isset($_post['template_id']) && jrCore_checktype($_post['template_id'], 'number_nz')) {
        // Make sure we have a valid template
        $req = "SELECT * FROM {$tbl} WHERE template_id = '{$_post['template_id']}'";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            $_SESSION['template_body_save'] = $_post['template_body'];
            jrCore_set_form_notice('error', 'Invalid template_id - please try again');
            jrCore_form_result();
        }
        $req = "UPDATE {$tbl} SET
                  template_updated = UNIX_TIMESTAMP(),
                  template_user    = '" . jrCore_db_escape($_user['user_name']) . "',
                  template_body    = '" . jrCore_db_escape($_post['template_body']) . "'
                 WHERE template_id = '{$_post['template_id']}'";
        $cnt = jrCore_db_query($req, 'COUNT');
        // Reset the template cache
        jrCore_get_template_file($_rt['template_name'], $mod, 'reset');
    }
    else {
        if (!isset($_post['template_name']{1})) {
            $_SESSION['template_body_save'] = $_post['template_body'];
            jrCore_set_form_notice('error', 'Invalid template_name - please try again');
            jrCore_form_result();
        }
        // See if we already exist - this can happen when the user FIRST modifies the template
        // and does not leave the screen, and modifies again
        $nam = jrCore_db_escape($_post['template_name']);
        $mod = jrCore_db_escape($mod);
        $req = "INSERT INTO {$tbl} (template_created,template_updated,template_user,template_active,template_name,template_module,template_body)
                VALUES(UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'" . jrCore_db_escape($_user['user_name']) . "','0','{$nam}','{$mod}','" . jrCore_db_escape($_post['template_body']) . "')";
        $tid = jrCore_db_query($req, 'INSERT_ID');
        if (isset($tid) && jrCore_checktype($tid, 'number_nz')) {
            $cnt = 1;
            // Reset the template cache
            jrCore_get_template_file($_post['template_name'], $mod, 'reset');
        }
        $crt = true;
    }
    if (isset($cnt) && $cnt === 1) {
        jrCore_set_form_notice('success', 'The template has been successfully updated');
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered saving the template update - please try again');
    }
    jrCore_form_delete_session();
    // If we have just CREATED a new template, we must refresh on the ID
    if ($tid && $crt) {
        if (isset($_post['skin'])) {
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/skin={$_post['skin']}/id={$tid}");
        }
        else {
            jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/id={$tid}");
        }
    }
    jrCore_form_result();
}

//------------------------------
// cache_reset
//------------------------------
function view_jrCore_cache_reset($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore');
    jrCore_page_banner('Reset Caches');

    // Form init
    $_tmp = array(
        'submit_value' => 'reset selected caches',
        'cancel'       => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools"
    );
    jrCore_form_create($_tmp);

    // Reset Smarty cache
    $_tmp = array(
        'name'     => 'reset_template_cache',
        'label'    => 'Reset Template Cache',
        'help'     => 'Check this box to delete the compiled skin templates, CSS and Javascript - these items will be rebuilt as needed.',
        'type'     => 'checkbox',
        'value'    => 'on',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Reset Database Cache
    $_tmp = array(
        'name'     => 'reset_database_cache',
        'label'    => 'Reset Database Cache',
        'help'     => 'Check this box to delete cached skin and profile pages in the database.',
        'type'     => 'checkbox',
        'value'    => 'on',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// cache_reset_save
//------------------------------
function view_jrCore_cache_reset_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);

    // Reset smarty cache
    if (isset($_post['reset_template_cache']) && $_post['reset_template_cache'] == 'on') {
        $_dirs = array('jrCore', $_conf['jrCore_active_skin']);
        $_dirs = jrCore_trigger_event('jrCore', 'template_cache_reset', $_dirs); // "template_cache_reset" event trigger
        foreach ($_dirs as $mod) {
            $dir = jrCore_get_module_cache_dir($mod);
            jrCore_delete_dir_contents($dir);
        }
    }

    // Reset database cache
    if (isset($_post['reset_database_cache']) && $_post['reset_database_cache'] == 'on') {
        $tbl = jrCore_db_table_name('jrCore', 'cache');
        $req = "TRUNCATE TABLE {$tbl}";
        jrCore_db_query($req);
    }

    // Remove any generated Sprite images and Spire CSS files
    $dir = jrCore_get_media_directory(0);
    $_fl = glob("{$dir}/*sprite*");
    if (isset($_fl) && is_array($_fl)) {
        foreach ($_fl as $file) {
            unlink($file);
        }
    }

    jrCore_set_form_notice('success', 'The selected caches were successfully reset');
    jrCore_form_result();
}

//------------------------------
// skin_image_delete_save
//------------------------------
function view_jrCore_skin_image_delete_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['skin']{0}) && !isset($_post['mod']{0})) {
        jrCore_set_form_notice('error', 'Invalid skin or module - please try again');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['name']{0})) {
        jrCore_set_form_notice('error', 'Invalid image name - please try again');
        jrCore_form_result('referrer');
    }
    if (isset($_post['mod']{0})) {
        $nam = $_post['mod'];
        $tag = 'mod_';
    }
    else {
        $nam = $_post['skin'];
        $tag = '';
    }
    // Remove from custom image info
    if (isset($_conf["jrCore_{$nam}_custom_images"]{2})) {
        $_im = json_decode($_conf["jrCore_{$nam}_custom_images"], true);
        unset($_im["{$_post['name']}"]);
        // Update setting with new values
        jrCore_set_setting_value('jrCore', "{$nam}_custom_images", json_encode($_im));
        jrCore_delete_all_cache_entries('jrCore', 0);
        jrCore_delete_media_file(0, "{$tag}{$nam}_{$_post['name']}");
    }
    jrCore_set_form_notice('success', 'The custom image was successfully deleted');
    jrCore_form_result('referrer');
}

//------------------------------
// template_reset_save
//------------------------------
function view_jrCore_template_reset_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    // Reset smarty cache
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid template_id - please try again');
        jrCore_form_result('referrer');
    }
    // Get info about this template first so we can reset
    $tbl = jrCore_db_table_name('jrCore', 'template');
    $req = "SELECT template_name, template_module FROM {$tbl} WHERE template_id = '{$_post['id']}'";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'Invalid template_id - please try again');
        jrCore_form_result('referrer');
    }
    $req = "DELETE FROM {$tbl} WHERE template_id = '{$_post['id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_get_template_file($_rt['template_name'], $_rt['template_module'], 'reset');
        jrCore_set_form_notice('success', 'The template has been reset to use the default version');
    }
    else {
        jrCore_set_form_notice('error', 'An error was encountered deleting the modified template from the database - please try again');
    }
    jrCore_form_result();
}

//------------------------------
// css_reset_save
//------------------------------
function view_jrCore_css_reset_save($_post, $_user, $_conf)
{
    jrUser_master_only();
    // Reset CSS elements
    if (!isset($_post['skin']{0})) {
        jrCore_set_form_notice('error', 'Invalid skin - please try again');
        jrCore_form_result('referrer');
    }
    if (!isset($_post['tag']{0})) {
        jrCore_set_form_notice('error', 'Invalid element tag - please try again');
        jrCore_form_result('referrer');
    }
    // Remove info about this element from the custom css
    $tbl = jrCore_db_table_name('jrCore', 'skin');
    $req = "SELECT skin_custom_css FROM {$tbl} WHERE skin_directory = '" . jrCore_db_escape($_post['skin']) . "'";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt) && strlen($_rt['skin_custom_css']) > 3) {
        $_new = json_decode($_rt['skin_custom_css'], true);
        if (isset($_new) && is_array($_new)) {
            if (isset($_new["{$_post['tag']}"])) {
                unset($_new["{$_post['tag']}"]);
                $_new = json_encode($_new);
                $req = "UPDATE {$tbl} SET skin_updated = UNIX_TIMESTAMP(), skin_custom_css = '" . jrCore_db_escape($_new) . "' WHERE skin_directory = '" . jrCore_db_escape($_post['skin']) . "'";
                $cnt = jrCore_db_query($req, 'COUNT');
                if (!isset($cnt) || $cnt === 0) {
                    jrCore_set_form_notice('error', 'An error was enountered saving the custom style to the database - please try again');
                    jrCore_form_result('referrer');
                }
            }
        }
    }
    jrCore_form_delete_session();
    // Cleanup any cached CSS files so it is rebuilt
    $cdir = jrCore_get_module_cache_dir($_conf['jrCore_active_skin']);
    $_tmp = glob("{$cdir}/*.css");
    if (isset($_tmp) && is_array($_tmp)) {
        foreach ($_tmp as $tmp_file) {
            unlink($tmp_file);
        }
    }
    jrCore_form_result('referrer');
}

//------------------------------
// integrity_check
//------------------------------
function view_jrCore_integrity_check($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore');
    jrCore_page_banner("Integrity Check");

    // Form init
    $_tmp = array(
        'submit_value'  => 'run integrity check',
        'cancel'        => 'referrer',
        'submit_prompt' => 'Are you sure you want to run the Integrity Check? Please be patient - on large systems this could take some time.',
        'submit_modal'  => 'update',
        'modal_width'   => 600,
        'modal_height'  => 400,
        'modal_note'    => 'Please be patient while the Integrity Check runs'
    );
    jrCore_form_create($_tmp);

    // Validate Modules
    $_tmp = array(
        'name'     => 'validate_modules',
        'label'    => 'validate modules',
        'help'     => 'Check this box so the system will validate active modules and the structure of your database tables.',
        'type'     => 'checkbox',
        'value'    => 'on',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Validate Skins
    $_tmp = array(
        'name'     => 'validate_skins',
        'label'    => 'validate skins',
        'help'     => 'Check this box so the system will validate active skins and and skin config options.',
        'type'     => 'checkbox',
        'value'    => 'on',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Repair Tables
    $_tmp = array(
        'name'     => 'repair_tables',
        'label'    => 'repair tables',
        'help'     => 'If you suspect that some of your MySQL tables are corrupt, check this box and REPAIR TABLE will be run on each of your database tables.<br><br><b>WARNING:</b> While a repair is running on a table, access to that table will be locked. The repair operation could take several minutes for very large tables.',
        'type'     => 'checkbox',
        'value'    => 'off',
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// integrity_check_save
//------------------------------
function view_jrCore_integrity_check_save($_post, $_user, $_conf)
{
    global $_mods;
    jrUser_master_only();
    jrCore_form_validate($_post);
    jrCore_logger('INF', 'integrity check started');

    // Check for Repair Tables first
    if (isset($_post['repair_tables']) && $_post['repair_tables'] == 'on') {
        $_rt = jrCore_db_query('SHOW TABLES','NUMERIC');
        if (isset($_rt) && is_array($_rt)) {
            foreach ($_rt as $tbl) {
                $tbl = reset($tbl);
                jrCore_form_modal_notice('update', "repairing table: {$tbl}");
                jrCore_db_query("REPAIR TABLE {$tbl}");
            }
        }
    }

    // Module install validation
    if (isset($_post['validate_modules']) && $_post['validate_modules'] == 'on') {

        // Make sure our Core schema is updated first
        require_once APP_DIR . '/modules/jrCore/schema.php';
        jrCore_db_schema();

        // Check for new modules
        jrCore_install_new_modules();

        //----------------------
        // MODULES
        //----------------------
        // Make sure module is setup
        foreach ($_mods as $mod_dir => $_inf) {
            if (!is_dir(APP_DIR . "/modules/{$mod_dir}")) {
                // Looks like this module was removed from the filesystem - let's do a cleanup
                $tbl = jrCore_db_table_name('jrCore', 'module');
                $req = "DELETE FROM {$tbl} WHERE module_directory = '" . jrCore_db_escape($mod_dir) . "' LIMIT 1";
                $cnt = jrCore_db_query($req, 'COUNT');
                if (!isset($cnt) || $cnt !== 1) {
                    jrCore_form_modal_notice('error', "unable to cleanup deleted module: {$mod_dir}");
                }
                // Cleanup any cache
                $cdr = jrCore_get_module_cache_dir($mod_dir);
                if (is_dir($cdr)) {
                    jrCore_delete_dir_contents($cdr);
                    rmdir($cdr);
                }
            }
            if (!jrCore_module_is_active($mod_dir)) {
                continue;
            }
            jrCore_form_modal_notice('update', "verifying module: {$mod_dir}");
            jrCore_verify_module($mod_dir);
        }
    }

    // Skin install validation
    if (isset($_post['validate_skins']) && $_post['validate_skins'] == 'on') {

        //----------------------
        // SKINS
        //----------------------
        $_rt = jrCore_get_skins();
        if (isset($_rt) && is_array($_rt)) {
            foreach ($_rt as $skin_dir) {

                jrCore_form_modal_notice('update', "verifying skin: {$skin_dir}");
                // config
                if (is_file(APP_DIR . "/skins/{$skin_dir}/config.php")) {
                    require_once APP_DIR . "/skins/{$skin_dir}/config.php";
                    $func = "{$skin_dir}_skin_config";
                    if (function_exists($func)) {
                        $func();
                    }
                }
                // quota
                if (is_file(APP_DIR . "/skins/{$skin_dir}/quota.php")) {
                    require_once APP_DIR . "/skins/{$skin_dir}/quota.php";
                    $func = "{$skin_dir}_skin_quota_config";
                    if (function_exists($func)) {
                        $func();
                    }
                }
                // lang strings
                if (is_dir(APP_DIR . "/skins/{$skin_dir}/lang")) {
                    jrUser_install_lang_strings('skin', $skin_dir);
                }
            }
        }
    }
    jrCore_form_delete_session();
    jrCore_logger('INF', 'integrity check completed');
    jrCore_form_modal_notice('complete', 'The integrity check options were successfully completed');
    exit;
}

//------------------------------
// activity_log
//------------------------------
function view_jrCore_activity_log($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_install_new_modules();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore');
    jrCore_master_log_tabs('activity');
    jrCore_show_activity_log($_post, $_user, $_conf);
    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    jrCore_page_display();
}

/**
 * jrCore_show_activity_log
 */
function jrCore_show_activity_log($_post, $_user, $_conf)
{
    $url = jrCore_get_module_url('jrCore');
    // construct our query
    $tbl = jrCore_db_table_name('jrCore', 'log');
    $req = "SELECT * FROM {$tbl} ";
    $_ex = false;
    $add = '';
    $num = jrCore_db_number_rows('jrCore', 'log');
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $_post['search_string'] = trim(urldecode($_post['search_string']));
        $str = jrCore_db_escape($_post['search_string']);
        $req .= "WHERE (log_text LIKE '%{$str}%' OR log_ip LIKE '%{$str}%' OR log_priority LIKE '%{$str}%') ";
        $_ex = array('search_string' => $_post['search_string']);
        $add = '/search_string=' . urlencode($_post['search_string']);
        $num = false;
    }
    $req .= 'ORDER BY log_id DESC';

    // find how many lines we are showing
    if (!isset($_post['p']) || !jrCore_checktype($_post['p'], 'number_nz')) {
        $_post['p'] = 1;
    }
    $_rt = jrCore_db_paged_query($req, $_post['p'], 12, 'NUMERIC', $num);

    // start our html output
    $buttons = jrCore_page_button('download', 'download', "if(confirm('Do you want to download the activity log as a CSV file?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/activity_log_download'}");
    if (jrUser_is_master()) {
        $buttons .= jrCore_page_button('delete', 'empty', "if(confirm('Delete all activity log entries?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/activity_log_delete_all'}");
    }
    jrCore_page_banner('activity log', $buttons);
    jrCore_get_form_notice();
    jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$url}/activity_log");

    $dat = array();
    if (jrUser_is_master()) {
        $dat[1]['title'] = '&nbsp;';
        $dat[1]['width'] = '2%;';
        $dat[2]['title'] = 'date';
        $dat[2]['width'] = '4%;';
    }
    else {
        $dat[2]['title'] = 'date';
        $dat[2]['width'] = '6%;';
    }
    $dat[3]['title'] = 'IP';
    $dat[3]['width'] = '5%;';
    $dat[4]['title'] = 'text';
    $dat[4]['width'] = '89%;';
    jrCore_page_table_header($dat);
    unset($dat);

    if (isset($_rt['_items']) && is_array($_rt['_items'])) {

        // LOG LINE
        foreach ($_rt['_items'] as $_log) {

            $dat = array();
            if (jrUser_is_master()) {
                $dat[1]['title'] = jrCore_page_button("d{$_log['log_id']}", '&times;', "window.location='{$_conf['jrCore_base_url']}/{$url}/activity_log_delete/id={$_log['log_id']}/p={$_post['p']}{$add}'");
            }
            $dat[2]['title'] = jrCore_format_time($_log['log_created']);
            $dat[2]['class'] = 'center nowrap';
            $dat[3]['title'] = $_log['log_ip'];
            if (isset($_post['search_string']{0})) {
                $dat[4]['title'] = jrCore_hilight_string($_log['log_text'], $_post['search_string']);
            }
            else {
                $dat[4]['title'] = $_log['log_text'];
            }
            $dat[4]['class'] = "log-{$_log['log_priority']}";
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_pager($_rt, $_ex);
    }
    else {
        $dat = array();
        if (!empty($_post['search_string'])) {
            $dat[1]['title'] = '<p>There were no Activity Logs found to match your search criteria</p>';
        }
        else {
            $dat[1]['title'] = '<p>There does not appear to be any Activity Logs</p>';
        }
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
}

//------------------------------
// activity_log_delete
//------------------------------
function view_jrCore_activity_log_delete($_post, $_user, $_conf)
{
    jrUser_master_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid log id received - verify usage');
        jrCore_form_result();
    }
    $tbl = jrCore_db_table_name('jrCore', 'log');
    $req = "DELETE FROM {$tbl} WHERE log_id = '{$_post['id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (isset($cnt) && $cnt === 1) {
        jrCore_form_result();
    }
    jrCore_set_form_notice('error', 'An error was encountered deleting the log entry - please try again');
    jrCore_form_result();
}

//------------------------------
// activity_log_download
//------------------------------
function view_jrCore_activity_log_download($_post, $_user, $_conf)
{
    jrUser_master_only();
    $tbl = jrCore_db_table_name('jrCore', 'log');
    $req = "SELECT * FROM {$tbl} ORDER BY `log_id` ASC";
    $_rt = jrCore_db_query($req, 'NUMERIC');
    if (isset($_rt[0]) && is_array($_rt[0])) {
        $today = date("Ymd");
        $fn = "Activity_Log_{$today}.csv";
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=\"{$fn}\"");
        $data = '"ID","Created","Priority","IP","Text"' . "\n";
        foreach ($_rt as $_x) {
            $_x['log_created'] = jrCore_format_time($_x['log_created']);
            $_x['log_text'] = str_replace('"', '', $_x['log_text']);
            $data .= '"' . $_x['log_id'] . '","' . $_x['log_created'] . '","' . $_x['log_priority'] . '","' . $_x['log_ip'] . '","' . $_x['log_text'] . '"' . "\n";
        }
        echo $data;
    }
    else {
        jrCore_notice_page('error', 'No activity logs to download');
    }
}

//------------------------------
// activity_log_delete_all
//------------------------------
function view_jrCore_activity_log_delete_all($_post, $_user, $_conf)
{
    jrUser_master_only();
    $tbl = jrCore_db_table_name('jrCore', 'log');
    $req = "TRUNCATE {$tbl}";
    jrCore_db_query($req);
    jrCore_form_result();
}

//------------------------------
// browser (datastore)
//------------------------------
function view_jrCore_browser($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs($_post['module']);

    // start our html output
    jrCore_dashboard_browser('master', $_post, $_user, $_conf);

    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    jrCore_page_display();
}

//------------------------------
// browser_item_update
//------------------------------
function view_jrCore_browser_item_update($_post, $_user, $_conf)
{
    jrUser_admin_only();
    // See if we are an admin or master user...
    $url = jrCore_get_local_referrer();
    if (jrUser_is_master() && !strpos($url, 'dashboard')) {
        jrCore_page_include_admin_menu();
        jrCore_page_admin_tabs($_post['module']);
    }
    else {
        jrCore_page_dashboard_tabs('browser');
    }
    jrCore_page_banner('modify datastore item', "item id: {$_post['id']}");
    jrCore_get_form_notice();

    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_form_result('referrer');
    }
    $_rt = jrCore_db_get_item($_post['module'], $_post['id'], true);
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'Unable to retrieve item from DataStore - please try again');
    }
    // Go through each field and show it on a form
    $_tmp = array(
        'submit_value' => 'save changes',
        'cancel'       => 'referrer'
    );
    jrCore_form_create($_tmp);

    // Item ID
    $_tmp = array(
        'name'     => 'id',
        'type'     => 'hidden',
        'value'    => $_rt['_item_id'],
        'validate' => 'number_nz'
    );
    jrCore_form_field_create($_tmp);

    $pfx = jrCore_db_get_prefix($_post['module']);
    foreach ($_rt as $k => $v) {
        if (strpos($k, $pfx) !== 0) {
            continue;
        }
        switch ($k) {
            case 'user_group':
            case 'user_password':
            case 'user_old_password':
                break;
            default:
                if (strpos($v, '{') === 0) {
                    // JSON - skin
                    continue;
                }
                // New Form Field
                if (strlen($v) > 128 || strpos(' ' . $v, "\n")) {
                    $_tmp = array(
                        'name'  => "ds_key_{$k}",
                        'label' => '<span style="text-transform:lowercase">' . $k . '</span>',
                        'type'  => 'textarea',
                        'value' => $v
                    );
                }
                else {
                    $_tmp = array(
                        'name'  => "ds_key_{$k}",
                        'label' => '<span style="text-transform:lowercase">' . $k . '</span>',
                        'type'  => 'text',
                        'value' => $v
                    );
                }
                jrCore_form_field_create($_tmp);
                break;
        }
    }

    // New Field...
    $err = '';
    if (isset($_SESSION['jr_form_field_highlight']['ds_browser_new_key'])) {
        unset($_SESSION['jr_form_field_highlight']['ds_browser_new_key']);
        $err = ' field-hilight';
    }
    $text = '<input type="text" class="form_text' . $err . '" id="ds_browser_new_key" name="ds_browser_new_key" value="">';
    $html = '<input type="text" class="form_text" id="ds_browser_new_value" name="ds_browser_new_value" value="">';
    $_tmp = array(
        'type'     => 'page_link_cell',
        'label'    => $text,
        'url'      => $html,
        'module'   => 'jrCore',
        'template' => 'page_link_cell.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    jrCore_page_display();
}

//---------------------- -------
// browser_item_update_save
//---------------------- -------
function view_jrCore_browser_item_update_save($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_form_result();
    }
    $_rt = jrCore_db_get_item($_post['module'], $_post['id'], true);
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'Unable to retrieve item from DataStore - please try again');
        jrCore_form_result();
    }
    $refresh = false;
    $_upd = array();
    foreach ($_post as $k => $v) {
        if (strpos($k, 'ds_key_') === 0) {
            switch ($k) {
                // Only the Master Admin can change the user_group
                case 'ds_key_user_group':
                    if (!jrUser_is_master()) {
                        continue 2;
                    }
                    break;
                case 'ds_key_user_password':
                    continue 2;
                    break;
            }
            $k = substr($k, 7);
            if (isset($_rt[$k]) && ($_rt[$k] != $v || strlen($v) === 0)) {
                // See if we are removing fields....
                if (strlen($v) === 0) {
                    // Remove field
                    $refresh = true;
                    jrCore_db_delete_item_key($_post['module'], $_post['id'], $k);
                }
                else {
                    $_upd[$k] = $v;
                }
            }
        }
    }

    // Check for new Value..
    if (isset($_post['ds_browser_new_key']{0})) {
        // Make sure it begins with our DS prefix
        $pfx = jrCore_db_get_prefix($_post['module']);
        if (strpos($_post['ds_browser_new_key'], $pfx) !== 0) {
            jrCore_set_form_notice('error', "Invalid new key name - must begin with <b>{$pfx}_</b>", false);
            jrCore_form_field_hilight('ds_browser_new_key');
            jrCore_form_result();
        }
        elseif (!jrCore_checktype($_post['ds_browser_new_key'], 'core_string')) {
            $err = jrCore_checktype_core_string(null, true);
            jrCore_set_form_notice('error', "Invalid new key name - must contain {$err} only");
            jrCore_form_field_hilight('ds_browser_new_key');
            jrCore_form_result();
        }
        // Make sure it is NOT a restricted key
        switch ($_post['ds_browser_new_key']) {
            case 'user_group':
            case 'user_password':
                jrCore_set_form_notice('error', "Invalid new key name - {$_post['ds_browser_new_key']} cannot be set using the Data Browser");
                jrCore_form_field_hilight('ds_browser_new_key');
                jrCore_form_result();
                break;
        }
        $_upd["{$_post['ds_browser_new_key']}"] = $_post['ds_browser_new_value'];
        $refresh = true;
    }

    if (isset($_upd) && count($_upd) > 0) {
        if (!jrCore_db_update_item($_post['module'], $_post['id'], $_upd)) {
            jrCore_set_form_notice('error', 'An error was encountered saving the updates to the item - please try again');
            jrCore_form_result();
        }
    }
    jrCore_set_form_notice('success', 'The changes were successfully saved');
    if ($refresh) {
        jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser_item_update/id={$_post['id']}");
    }
    else {
        jrCore_form_result();
    }
}

//------------------------------
// browser_item_delete
//------------------------------
function view_jrCore_browser_item_delete($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_form_result('referrer');
    }
    if (!jrCore_db_delete_item($_post['module'], $_post['id'])) {
        jrCore_set_form_notice('error', 'Unable to delete item from DataStore - please try again');
    }
    jrCore_form_result('referrer');
}

//------------------------------
// stream_file
//------------------------------
function view_jrCore_stream_file($_post, $_user, $_conf)
{
    // When a stream request comes in, it will look like:
    // http://www.site.com/song/stream/audio_file/5
    // so we have URL / module / option / _1 / _2
    if (!isset($_post['_2']) || !is_numeric($_post['_2'])) {
        jrCore_notice('Error', 'Invalid media id provided');
    }
    // Make sure this is a DataStore module
    if (!jrCore_db_get_prefix($_post['module'])) {
        jrCore_notice('Error', 'Invalid module - no datastore');
    }
    // Make sure we have a valid play key
    if (!jrUser_is_admin() && (!isset($_post['key']) || !isset($_SESSION['JRCORE_PLAY_KEYS']["{$_post['key']}"]))) {
        jrCore_notice('Error', 'Invalid play key');
    }
    // Make sure referrer is allowed if we get one
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_conf['jrCore_base_url']) !== 0) {
        // We are not local - check for allowed domains
        if (isset($_conf['jrCore_allowed_domains']{0})) {
            $domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if (!strpos(' ' . $_conf['jrCore_allowed_domains'], 'ALLOW_ALL_DOMAINS') && !strpos(' ' . $_conf['jrCore_allowed_domains'], $domain)) {
                jrCore_notice('Error', 'Media streams are blocked outside of players');
            }
        }
        else {
            jrCore_notice('Error', 'Media streams are blocked outside of players');
        }
    }

    $_rt = jrCore_db_get_item($_post['module'], intval($_post['_2']));
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice('Error', 'Invalid media id - no data found');
    }
    if (!isset($_rt["{$_post['_1']}_size"]) || $_rt["{$_post['_1']}_size"] < 1) {
        jrCore_notice('Error', 'Invalid media id - no media item found');
    }
    // Make sure quota access is allowed
    if (isset($_rt["quota_{$_post['module']}_allowed"]) && $_rt["quota_{$_post['module']}_allowed"] != 'on') {
        jrCore_notice('Error', 'Invalid access - requested media item is not allowed under profile quota');
    }

    // Privacy Checking for this profile
    if (!jrUser_is_admin() && $_rt['profile_private'] != '1') {
        // Privacy Check (Sub Select) - non admin users
        // 0 = Private
        // 1 = Global
        // 2 = Shared
        if ($_rt['profile_private'] == '0' && !jrProfile_is_profile_owner($_rt['_profile_id'])) {
            // We have a private profile and this is not the owner
            header('HTTP/1.0 403 Forbidden');
            header('Connection: close');
            exit();
        }

        // We're shared - viewer must be a follower of the profile
        if (jrCore_module_is_active('jrFollower')) {
            if (jrFollower_is_follower($_user['_user_id'], $_rt['_profile_id']) === false) {
                // We are not a follower of this profile - not allowed
                header('HTTP/1.0 403 Forbidden');
                header('Connection: close');
                exit();
            }
        }
        else {
            // Shared by followers not enabled
            header('HTTP/1.0 403 Forbidden');
            header('Connection: close');
            exit();
        }
    }

    // Check that file exists
    $nam = "{$_post['module']}_{$_post['_2']}_{$_post['_1']}." . $_rt["{$_post['_1']}_extension"];
    if (!jrCore_media_file_exists($_rt['_profile_id'], $nam)) {
        jrCore_notice('Error', 'Invalid media id - no file found');
    }
    // See if we have a SAMPLE for streaming - always overrides fill full stream
    $dir = jrCore_get_media_directory($_rt['_profile_id']);
    if (is_file("{$dir}/{$nam}.sample." . $_rt["{$_post['_1']}_extension"])) {
        $nam = "{$nam}.sample." . $_rt["{$_post['_1']}_extension"];
    }

    // "stream_file" event trigger
    $_args = array(
        'module'    => $_post['module'],
        'file_name' => $_post['_2']
    );
    $_rt = jrCore_trigger_event('jrCore', 'stream_file', $_rt, $_args);

    // Increment our counter
    jrCore_counter($_post['module'], $_post['_2'], "{$_post['_1']}_stream");

    // Download the file to the client
    jrCore_media_file_stream($_rt['_profile_id'], $nam, $_rt["{$_post['_1']}_original_name"]);
    session_write_close();
    exit();
}

//------------------------------
// download_file
//------------------------------
function view_jrCore_download_file($_post, $_user, $_conf)
{
    // When a download request comes in, it will look like:
    // http://www.site.com/song/download/audio_file/5
    // so we have URL / module / option / _1 / _2
    if (!isset($_post['_2']) || !is_numeric($_post['_2'])) {
        jrCore_notice('Error', 'Invalid media id provided');
    }
    // Make sure this is a DataStore module
    if (!jrCore_db_get_prefix($_post['module'])) {
        jrCore_notice('Error', 'Invalid module - no datastore');
    }
    // Make sure download link is local
    if (!isset($_SERVER['HTTP_REFERER'])) {
        jrCore_notice('Error', 'Offsite media downloads are blocked');
    }
    // Make sure referrer is allowed
    elseif (strpos($_SERVER['HTTP_REFERER'], $_conf['jrCore_base_url']) !== 0) {
        // We are not local - check for allowed domains
        if (isset($_conf['jrCore_allowed_domains']{0})) {
            $domain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
            if (!strpos(' ' . $_conf['jrCore_allowed_domains'], 'ALLOW_ALL_DOMAINS') && !strpos(' ' . $_conf['jrCore_allowed_domains'], $domain)) {
                jrCore_notice('Error', 'Offsite media downloads are blocked');
            }
        }
        else {
            jrCore_notice('Error', 'Offsite media downloads are blocked');
        }
    }
    $_rt = jrCore_db_get_item($_post['module'], intval($_post['_2']));
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice('Error', 'Invalid media id - no data found');
    }
    if (!isset($_rt["{$_post['_1']}_size"]) || $_rt["{$_post['_1']}_size"] < 1) {
        jrCore_notice('Error', 'Invalid media id - no media item found');
    }

    // Non admin checks
    if (!jrUser_is_admin()) {
        // Make sure quota access is allowed
        if (isset($_rt["quota_{$_post['module']}_allowed"]) && $_rt["quota_{$_post['module']}_allowed"] != 'on') {
            jrCore_notice('Error', 'Invalid access - requested media item is not allowed under profile quota');
        }
        // Make sure file is NOT for sale
        if (isset($_rt["{$_post['_1']}_item_price"]) && $_rt["{$_post['_1']}_item_price"] > 0) {
            jrCore_notice('Error', 'Invalid media item - item must be purchased to be downloaded');
        }

        // Privacy Checking for this profile
        if ($_rt['profile_private'] != '1') {
            // Privacy Check (Sub Select) - non admin users
            // 0 = Private
            // 1 = Global
            // 2 = Shared
            if ($_rt['profile_private'] == '0' && !jrProfile_is_profile_owner($_rt['_profile_id'])) {
                // We have a private profile and this is not the owner
                header('HTTP/1.0 403 Forbidden');
                header('Connection: close');
                exit();
            }

            // We're shared - viewer must be a follower of the profile
            if (jrCore_module_is_active('jrFollower')) {
                if (jrFollower_is_follower($_user['_user_id'], $_rt['_profile_id']) === false) {
                    // We are not a follower of this profile - not allowed
                    header('HTTP/1.0 403 Forbidden');
                    header('Connection: close');
                    exit();
                }
            }
            else {
                // Shared by followers not enabled
                header('HTTP/1.0 403 Forbidden');
                header('Connection: close');
                exit();
            }
        }
    }

    // Check that file exists
    $nam = "{$_post['module']}_{$_post['_2']}_{$_post['_1']}." . $_rt["{$_post['_1']}_extension"];
    if (!jrCore_media_file_exists($_rt['_profile_id'], $nam)) {
        jrCore_notice('Error', 'Invalid media id - no file found');
    }

    // "download_file" event trigger
    $_args = array(
        'module'    => $_post['module'],
        'file_name' => $_post['_2']
    );
    $_rt = jrCore_trigger_event('jrCore', 'download_file', $_rt, $_args);

    // Increment our counter
    jrCore_counter($_post['module'], $_post['_2'], "{$_post['_1']}_download");

    $fname = $nam;
    if (isset($_rt["{$_post['_1']}_original_name"])) {
        $fname = $_rt["{$_post['_1']}_original_name"];
    }
    elseif (isset($_rt["{$_post['_1']}_name"])) {
        $fname = $_rt["{$_post['_1']}_name"];
    }

    // Download the file to the client
    jrCore_media_file_download($_rt['_profile_id'], $nam, $fname);
    session_write_close();
    exit();
}

//------------------------------
// upload_file
//------------------------------
function view_jrCore_upload_file($_post, $_user, $_conf)
{
    // Upload progress
    jrUser_session_require_login();
    if (!jrCore_checktype($_post['upload_token'],'md5')) {
        exit;
    }

    // Bring in meter backend
    require_once APP_DIR . '/modules/jrCore/contrib/meter/server.php';

    // Determine max allowed upload size
    $max = (isset($_user['quota_jrCore_max_upload_size'])) ? intval($_user['quota_jrCore_max_upload_size']) : 2097152;
    if (!isset($max) || $max < 2097152) {
        $max = 2097152;
    }
    $ext = explode(',', $_post['extensions']);
    $mtr = new qqFileUploader($ext, jrCore_get_max_allowed_upload($max));
    $dir = jrCore_get_module_cache_dir('jrCore') . '/' . $_post['upload_token'];
    @mkdir($dir, $_conf['jrCore_dir_perms'], true);
    $res = $mtr->handleUpload($dir . '/');
    echo htmlspecialchars(json_encode($res), ENT_NOQUOTES);
    exit;
}

//--------------------------------
// PHP Error Log
//--------------------------------
function view_jrCore_php_error_log($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore');
    jrCore_master_log_tabs('error');

    $clear = null;
    $out = "<div id=\"error_log\" class=\"center\"><p>No PHP Errors at this time</p></div>";
    if (is_file(APP_DIR . "/data/logs/error_log")) {
        $_er = file(APP_DIR . "/data/logs/error_log");
        $_nm = array();
        $_ln = array();
        if (isset($_er) && is_array($_er)) {
            $cnt = count($_er);
            $idx = 0;
            while ($cnt > 0) {
                $index = md5(substr($_er[$idx], 27));
                if (!isset($_ln[$index])) {
                    $level = str_replace(':', '', jrCore_string_field($_er[$idx], 5));
                    $_ln[$index] = "<span class=\"php_{$level}\">" . $_er[$idx];
                    $_nm[$index] = 1;
                }
                else {
                    $_nm[$index]++;
                }
                unset($_er[$idx]);
                $cnt--;
                $idx++;
            }
            $out = '<div id="error_log"><br>';
            foreach ($_ln as $k => $v) {
                $out .= $v . ' [x ' . $_nm[$k] . ']</span><br><br>';
            }
            $out .= '</div>';
            $clear = jrCore_page_button('clear', 'Delete Error Log', "if(confirm('really delete the error log?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/php_error_log_delete'}");
        }
    }
    jrCore_page_banner('PHP Error Log', $clear);
    jrCore_page_custom($out);
    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    jrCore_page_display();
}

//------------------------------
// php_error_log_delete
//------------------------------
function view_jrCore_php_error_log_delete($_post, $_user, $_conf)
{
    jrUser_master_only();
    unlink(APP_DIR . "/data/logs/error_log");
    jrCore_location('referrer');
}

//--------------------------------
// Debug Log
//--------------------------------
function view_jrCore_debug_log($_post, $_user, $_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrCore');
    jrCore_master_log_tabs('debug');

    $clear = null;
    $out = "<div id=\"debug_log\" class=\"center\"><p>No Debug Log entries at this time</p></div>";
    if (is_file(APP_DIR . "/data/logs/debug_log")) {
        $out = '<div id="debug_log">' . file_get_contents(APP_DIR . "/data/logs/debug_log") . '</div>';
        $clear = jrCore_page_button('clear', 'Delete Debug Log', "if(confirm('really delete the debug log?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/debug_log_delete'}");
    }
    jrCore_page_banner('Debug Log', $clear);
    jrCore_page_custom($out);
    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/tools");
    jrCore_page_display();
}

//------------------------------
// debug_log_delete
//------------------------------
function view_jrCore_debug_log_delete($_post, $_user, $_conf)
{
    jrUser_master_only();
    unlink(APP_DIR . "/data/logs/debug_log");
    jrCore_location('referrer');
}

//------------------------------
// pending_item_approve
//------------------------------
function view_jrCore_pending_item_approve($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['id']) || strlen($_post['id']) === 0) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_location('referrer');
    }

    // See if we are doing ONE or multiple
    if (isset($_post['id']) && jrCore_checktype($_post['id'], 'number_nz')) {
        $_todo = array($_post['id']);
        $title = 'item has';
    }
    else {
        $_todo = explode(',', $_post['id']);
        $title = 'items have';
    }

    $tbl = jrCore_db_table_name('jrCore', 'pending');
    foreach ($_todo as $pid) {
        $req = "SELECT * FROM {$tbl} WHERE pending_item_id = '{$pid}' LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_set_form_notice('error', 'Invalid pending id');
            jrCore_location('referrer');
        }

        // approve this item and remove the pending
        $pfx = jrCore_db_get_prefix($_rt['pending_module']);
        $tb2 = jrCore_db_table_name($_rt['pending_module'], 'item_key');
        $req = "UPDATE {$tb2} SET `value` = '0' WHERE _item_id = '{$_rt['pending_item_id']}' AND `key` = '{$pfx}_pending' LIMIT 1";
        $cnt = jrCore_db_query($req, 'COUNT');
        if (!isset($cnt) || $cnt !== 1) {
            jrCore_set_form_notice('error', "unable to update {$pfx}_pending in the {$_rt['pending_module']} datastore");
            jrCore_location('referrer');
        }

        // Cleanup pending entry
        $req = "DELETE FROM {$tbl} WHERE pending_id = '{$_rt['pending_id']}' LIMIT 1";
        $cnt = jrCore_db_query($req, 'COUNT');
        if (!isset($cnt) || $cnt !== 1) {
            jrCore_set_form_notice('error', "unable to delete pending entry for {$_rt['pending_module']} item_id {$_rt['pending_item_id']}");
            jrCore_location('referrer');
        }

        // Next, let's see if there is an associated ACTION that was created for
        // this item - of so, we want to approve it as well.
        $req = "SELECT * FROM {$tbl} WHERE pending_linked_item_module = '" . jrCore_db_escape($_rt['pending_module']) . "' AND pending_linked_item_id = '" . intval($_rt['pending_item_id']) . "'";
        $_pa = jrCore_db_query($req, 'SINGLE');
        if (isset($_pa) && is_array($_pa)) {
            // We've found a linked action - approve
            $pfx = jrCore_db_get_prefix('jrAction');
            $tb2 = jrCore_db_table_name('jrAction', 'item_key');
            $req = "UPDATE {$tb2} SET `value` = '0' WHERE _item_id = '{$_pa['pending_item_id']}' AND `key` = '{$pfx}_pending' LIMIT 1";
            $cnt = jrCore_db_query($req, 'COUNT');
            if (!isset($cnt) || $cnt !== 1) {
                jrCore_set_form_notice('error', "unable to update {$pfx}_pending in the jrAction datastore");
                jrCore_location('referrer');
            }
        }
        jrCore_logger('INF', "pending item id {$pfx}/{$_rt['pending_item_id']} has been approved");
    }
    jrCore_set_form_notice('success', "The pending {$title} been approved");
    jrCore_location('referrer');
}

//------------------------------
// pending_item_reject
//------------------------------
function view_jrCore_pending_item_reject($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['id']) || !jrCore_checktype($_post['id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_location('referrer');
    }
    $tbl = jrCore_db_table_name('jrCore', 'pending');
    $req = "SELECT * FROM {$tbl} WHERE pending_item_id = '{$_post['id']}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'Invalid pending id');
        jrCore_location('referrer');
    }
    // Get item
    $_it = jrCore_db_get_item($_rt['pending_module'], $_rt['pending_item_id']);
    if (!isset($_it) || !is_array($_it)) {
        jrCore_set_form_notice('error', 'Invalid item - unable to retrieve data from DataStore');
        jrCore_form_result();
    }

    // Show our tabs if we are from the dashboard
    $url = jrCore_get_local_referrer();
    if (strpos($url, 'dashboard') || strpos($url, 'pending')) {
        jrCore_page_dashboard_tabs('pending');
    }

    // Show reject notice page
    jrCore_page_banner('reject item');
    $pfx = jrCore_db_get_prefix($_rt['pending_module']);
    $seo = '';
    if (isset($_it["{$pfx}_title_url"])) {
        $seo = '/' . $_it["{$pfx}_title_url"];
    }
    $url = jrCore_get_module_url($_rt['pending_module']);

    // Form init
    $_tmp = array(
        'submit_value' => 'sending rejection email',
        'cancel'       => 'referrer'
    );
    jrCore_form_create($_tmp);

    // Module
    $_tmp = array(
        'name'  => 'pending_id',
        'type'  => 'hidden',
        'value' => $_rt['pending_id']
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_link_cell('rejected item url', "{$_conf['jrCore_base_url']}/{$_it['profile_url']}/{$url}/{$_it['_item_id']}{$seo}");

    // Create an item list of our custom "quick reject" options
    $lbl = 'reject reason';
    $tbl = jrCore_db_table_name('jrCore', 'pending_reason');
    $req = "SELECT * FROM {$tbl} ORDER BY reason_text ASC";
    $_pr = jrCore_db_query($req, 'reason_key', false, 'reason_text');
    if (isset($_pr) && is_array($_pr)) {
        // Add in our delete button
        $_att = array('class' => '');
        foreach ($_pr as $k => $v) {
            $_pr[$k] .= '&nbsp;&nbsp;' . jrCore_page_button("d{$k}", 'x', "if(confirm('delete this reason?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/pending_reason_delete/key={$k}'}", $_att);
        }
        $_tmp = array(
            'name'     => 'reject_reason',
            'label'    => 'reject reasons',
            'sublabel' => 'select all that apply',
            'help'     => 'Select predefined reasons for rejecting this item',
            'type'     => 'optionlist',
            'validate' => 'hex',
            'options'  => $_pr,
            'required' => false
        );
        jrCore_form_field_create($_tmp);
        $lbl = 'new reject reason';
    }

    $_tmp = array(
        'name'     => 'new_reject_reason',
        'label'    => $lbl,
        'help'     => 'Enter a reject reason here and it will be saved after it is submitted',
        'type'     => 'text',
        'validate' => 'printable',
        'required' => false
    );
    jrCore_form_field_create($_tmp);

    $_tmp = array(
        'name'     => 'reject_message',
        'label'    => 'reject message',
        'sublabel' => '(optional)',
        'help'     => 'Enter a custom message to send to the profile owner(s) that explains why this item has been rejected',
        'type'     => 'textarea',
        'validate' => 'printable',
        'required' => false
    );
    jrCore_form_field_create($_tmp);
    jrCore_page_display();
}

//------------------------------
// pending_item_reject_save
//------------------------------
function view_jrCore_pending_item_reject_save($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['pending_id']) || !jrCore_checktype($_post['pending_id'], 'number_nz')) {
        jrCore_set_form_notice('error', 'Invalid pending_id');
        jrCore_form_result();
    }
    $tbl = jrCore_db_table_name('jrCore', 'pending');
    $req = "SELECT * FROM {$tbl} WHERE pending_id = '{$_post['pending_id']}' LIMIT 1";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_set_form_notice('error', 'Invalid pending_id');
        jrCore_form_result();
    }
    // Get item
    $_it = jrCore_db_get_item($_rt['pending_module'], $_rt['pending_item_id']);
    if (!isset($_it) || !is_array($_it)) {
        jrCore_set_form_notice('error', 'Invalid item - unable to retrieve data from DataStore');
        jrCore_form_result();
    }

    // Save any new reject message
    if (isset($_post['new_reject_reason']) && strlen($_post['new_reject_reason']) > 0) {
        $tb2 = jrCore_db_table_name('jrCore', 'pending_reason');
        $req = "INSERT INTO {$tb2} (reason_key,reason_text) VALUES ('" . md5($_post['new_reject_reason']) . "','" . jrCore_db_escape($_post['new_reject_reason']) . "')";
        $cnt = jrCore_db_query($req, 'COUNT');
        if (!isset($cnt) || $cnt !== 1) {
            jrCore_set_form_notice('error', 'Unable to store new pending reason - please try again');
            jrCore_form_result();
        }
    }

    // [pending_id] => 17
    // [reject_reason_d86c579c827fec297d69e58e4c06cfa2] => on
    // [reject_reason_e37bbbb8065ecdc1d34cf3e98f37e8a3] => on
    // [new_reject_reason] => NEW REASON
    // [reject_message] => MESSAGE

    // Send Reject email
    if (isset($_it['user_email']) && jrCore_checktype($_it['user_email'], 'email')) {

        $tb2 = jrCore_db_table_name('jrCore', 'pending_reason');
        $req = "SELECT * FROM {$tb2}";
        $_pr = jrCore_db_query($req, 'reason_key', false, 'reason_text');

        $_msg = array();
        // See if we received any canned rejection notices
        foreach ($_post as $k => $v) {
            if (strpos($k, 'reject_reason_') === 0 && $v == 'on') {
                $key = substr($k, 14);
                if (isset($_pr[$key])) {
                    $_msg[] = $_pr[$key];
                }
            }
        }
        if (isset($_post['new_reject_reason']) && strlen($_post['new_reject_reason']) > 0) {
            $_msg[] = $_post['new_reject_reason'];
        }
        if (isset($_post['reject_message']) && strlen($_post['reject_message']) > 0) {
            $_msg[] = $_post['reject_message'];
        }
        $message = implode("\n", $_msg);

        $_rp = array(
            'system_name'    => $_conf['jrCore_system_name'],
            'reject_message' => $message
        );
        list($sub, $msg) = jrCore_parse_email_templates('jrCore', 'pending_reject', $_rp);

        // Get all email addresses associated with this profile
        jrCore_send_email($_it['user_email'], $sub, $msg);
    }

    // Cleanup pending entry
    $req = "DELETE FROM {$tbl} WHERE pending_id = '{$_rt['pending_id']}' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (!isset($cnt) || $cnt !== 1) {
        jrCore_set_form_notice('error', "unable to delete pending entry for {$_rt['pending_module']} item_id {$_rt['pending_item_id']}");
        jrCore_form_result();
    }
    $url = jrCore_get_module_url($_rt['pending_module']);
    jrCore_logger('INF', "{$_it['profile_url']}/{$url}/{$_it['_item_id']} has been rejected");
    jrCore_form_result("{$_conf['jrCore_base_url']}/{$_post['module_url']}/dashboard/pending");
}

//------------------------------
// pending_item_delete
//------------------------------
function view_jrCore_pending_item_delete($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['id']) || strlen($_post['id']) === 0) {
        jrCore_set_form_notice('error', 'Invalid item id');
        jrCore_location('referrer');
    }

    // See if we are doing ONE or multiple
    if (isset($_post['id']) && jrCore_checktype($_post['id'], 'number_nz')) {
        $_todo = array($_post['id']);
        $title = 'item has';
    }
    else {
        $_todo = explode(',', $_post['id']);
        $title = 'items have';
    }

    $tbl = jrCore_db_table_name('jrCore', 'pending');
    foreach ($_todo as $pid) {

        $req = "SELECT * FROM {$tbl} WHERE pending_item_id = '{$pid}' LIMIT 1";
        $_rt = jrCore_db_query($req, 'SINGLE');
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_set_form_notice('error', 'Invalid pending id');
            jrCore_location('referrer');
        }

        // delete this item
        jrCore_db_delete_item($_rt['pending_module'], $_rt['pending_item_id']);

        // Cleanup pending entry
        $req = "DELETE FROM {$tbl} WHERE pending_id = '{$_rt['pending_id']}' LIMIT 1";
        $cnt = jrCore_db_query($req, 'COUNT');
        if (!isset($cnt) || $cnt !== 1) {
            jrCore_set_form_notice('error', "unable to delete pending entry for {$_rt['pending_module']} item_id {$_rt['pending_item_id']}");
            jrCore_location('referrer');
        }

        // Next, let's see if there is an associated ACTION that was created for
        // this item - of so, we want to remove it as well.
        $req = "SELECT * FROM {$tbl} WHERE pending_linked_item_module = '" . jrCore_db_escape($_rt['pending_module']) . "' AND pending_linked_item_id = '" . intval($_rt['pending_item_id']) . "'";
        $_pa = jrCore_db_query($req, 'SINGLE');
        if (isset($_pa) && is_array($_pa)) {
            // We've found a linked action - approve
            $tb2 = jrCore_db_table_name('jrAction', 'item_key');
            $req = "DELETE FROM {$tb2} WHERE _item_id = '{$_pa['pending_item_id']}'";
            $cnt = jrCore_db_query($req, 'COUNT');
            if (!isset($cnt) || $cnt === 0) {
                jrCore_logger('CRI', "unable to delete _item_id {$_pa['pending_item_id']} in the jrAction datastore");
            }
            // And remove the pending entry
            $req = "DELETE FROM {$tbl} WHERE pending_id = '{$_pa['pending_id']}' LIMIT 1";
            $cnt = jrCore_db_query($req, 'COUNT');
            if (!isset($cnt) || $cnt !== 1) {
                jrCore_logger('CRI', "unable to delete pending entry for {$_rt['pending_module']} item_id {$_rt['pending_item_id']}");
            }
        }
        $pfx = jrCore_db_get_prefix($_rt['pending_module']);
        jrCore_logger('INF', "pending item id {$pfx}/{$_rt['pending_item_id']} has been deleted");
    }
    // See if we are deleting from a media item's page or the dashboard
    $url = jrCore_get_local_referrer();
    if (strpos($url, 'dashboard')) {
        jrCore_set_form_notice('success', "The pending {$title} been deleted");
        jrCore_location('referrer');
    }
    else {
        // We're coming in from an individual item's page.
        // $url = jrCore_get_module_url($_rt['pending_module']);
        jrCore_location('referrer');
        // jrCore_location("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$url}");
    }
}

//------------------------------
// pending_reason_delete
//------------------------------
function view_jrCore_pending_reason_delete($_post, $_user, $_conf)
{
    jrUser_admin_only();
    if (!isset($_post['key']) || !jrCore_checktype($_post['key'], 'md5')) {
        jrCore_set_form_notice('error', 'Invalid pending reason key');
        jrCore_location('referrer');
    }
    $tbl = jrCore_db_table_name('jrCore', 'pending_reason');
    $req = "DELETE FROM {$tbl} WHERE reason_key = '" . jrCore_db_escape($_post['key']) . "' LIMIT 1";
    $cnt = jrCore_db_query($req, 'COUNT');
    if (!isset($cnt) || $cnt !== 1) {
        jrCore_set_form_notice('error', 'unable to delete pending reason from database - please try again');
    }
    jrCore_location('referrer');
}

//------------------------------
// SKIN VIEW FUNCTIONS
//------------------------------

/**
 * jrCore_show_skin_style
 */
function jrCore_show_skin_style($skin, $_post, $_user, $_conf)
{
    // Generate our output
    jrCore_page_skin_tabs($skin, 'style');

    $url = jrCore_get_module_url('jrCore');
    $subtitle = '<select name="skin_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/{$url}/skin_admin/style/skin='+ v\">";
    $_tmpm = jrCore_get_skins();
    ksort($_tmpm);
    foreach ($_tmpm as $skin_dir => $_skin) {
        if ($skin_dir == $_post['skin']) {
            $subtitle .= '<option value="' . $_post['skin'] . '" selected="selected"> ' . $_post['skin'] . "</option>\n";
        }
        else {
            $subtitle .= '<option value="' . $skin_dir . '"> ' . $skin_dir . "</option>\n";
        }
    }
    jrCore_page_banner('Style', $subtitle);
    jrCore_get_form_notice();

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => "skin_admin_save/style/skin={$skin}"
    );
    jrCore_form_create($_tmp);

    $_files = glob(APP_DIR . "/skins/{$skin}/css/*.css");
    // Files?
    if (!isset($_files) || !is_array($_files)) {
        jrCore_notice_page('error', 'There do not appear to be aby CSS files for this skin!');
        return false;
    }
    $_fl = array();
    foreach ($_files as $full_file) {
        if (strpos(file_get_contents($full_file), '@title')) {
            $nam = basename($full_file);
            $_fl[$nam] = $nam;
        }
    }

    // We also need to add in any module CSS files so they can be tweaked
    $_tm = jrCore_get_registered_module_features('jrCore', 'css');
    if ($_tm) {
        foreach ($_tm as $_v) {
            foreach ($_v as $full_file => $ignore) {
                if (strpos(file_get_contents($full_file), '@title')) {
                    $nam = basename($full_file);
                    $_fl[$nam] = $nam;
                }
            }
        }
    }

    // See if we have been given a file to edit - if not, use first in list
    if (!isset($_post['file']{0})) {
        $_post['file'] = basename(reset($_fl));
    }

    $_tmp = array(
        'name'  => 'file',
        'type'  => 'hidden',
        'value' => $_post['file']
    );
    jrCore_form_field_create($_tmp);

    // Style Jumper...
    if (isset($_fl) && is_array($_fl) && count($_fl) > 1) {
        $_tmp = array(
            'name'     => 'file',
            'label'    => 'selected style',
            'type'     => 'select',
            'options'  => $_fl,
            'value'    => $_post['file'],
            'onchange' => "var fid=this.options[this.selectedIndex].value;self.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/style/skin={$skin}/file='+ fid"
        );
        jrCore_form_field_create($_tmp);
        jrCore_page_divider();
    }
    $full_file = APP_DIR . "/skins/{$skin}/css/{$_post['file']}";
    if (!is_file($full_file) && $_tm) {
        // See if this is a module CSS file...
        foreach ($_tm as $file) {
            if (basename($file) == $_post['file']) {
                $full_file = $file;
            }
        }
    }

    $_tmp = jrCore_parse_css_file($full_file);
    // $_tmp is now our default - we make a copy of this so no matter
    // the customizations the user makes they can always revert to default
    // $_def = $_tmp;

    // Now we have the "base" CSS - we next need to load in the customizations
    // from the database if they have any
    $tbl = jrCore_db_table_name('jrCore', 'skin');
    $req = "SELECT skin_custom_css FROM {$tbl} WHERE skin_directory = '" . jrCore_db_escape($skin) . "'";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt) && strlen($_rt['skin_custom_css']) > 3) {
        $_new = json_decode($_rt['skin_custom_css'], true);
        if (isset($_new) && is_array($_new)) {
            foreach ($_new as $cname => $_cinf) {
                if (isset($_tmp[$cname])) {
                    $_tmp[$cname]['rules'] = array_merge($_tmp[$cname]['rules'], $_cinf);
                }
            }
        }
    }
    $color_opts = '<option value="transparent">transparent</option>';
    // Generate web safe colors
    $cs = array('00', '33', '66', '99', 'CC', 'FF');
    for ($i = 0; $i < 6; $i++) {
        for ($j = 0; $j < 6; $j++) {
            for ($k = 0; $k < 6; $k++) {
                $c = $cs[$i] . $cs[$j] . $cs[$k];
                $color_opts .= "<option value=\"{$c}\">#{$c}</option>\n";
            }
        }
    }

    // Padding/margins
    $_pixels = array('0px', '1px', '2px', '3px', '4px', '5px', '6px', '7px', '8px', '9px', '10px', '12px', '15px', '18px', '20px', '24px', '32px');

    // Width/Height
    $_width_perc = array();
    foreach (range(1, 100) as $pix) {
        $_width_perc["{$pix}%"] = "{$pix}%";
    }
    $_width_pix = array();
    foreach (range(10, 600, 10) as $pix) {
        $_width_pix["{$pix}px"] = "{$pix}px";
    }

    $_css_opts = array();

    // Our fonts
    $_css_opts['font-family'] = array(
        'Arial'                 => 'Arial',
        'Arial Black'           => 'Arial Black',
        'Courier New'           => 'Courier New',
        'Georgia'               => 'Georgia',
        'Impact'                => 'Impact',
        'Lucida Console,Monaco' => 'monospace',
        'Times New Roman'       => 'Times New Roman',
        'Trebuchet MS'          => 'Trebuchet MS',
        'Verdana'               => 'Verdana',
        'MS Sans Serif,Geneva'  => 'sans-serif'
    );

    // Our sizes
    $_css_opts['font-size'] = array(
        '8px'  => '8px',
        '10px' => '10px',
        '12px' => '12px',
        '14px' => '14px',
        '16px' => '16px',
        '18px' => '18px',
        '20px' => '20px',
        '24px' => '24px',
        '28px' => '28px',
        '32px' => '32px',
        '40px' => '40px',
        '48px' => '48px'
    );

    // Weights
    $_css_opts['font-weight'] = array(
        'normal'  => 'normal',
        'bold'    => 'bold',
        'bolder'  => 'bolder',
        'lighter' => 'lighter',
        'inherit' => 'inherit'
    );

    // Style
    $_css_opts['font-style'] = array(
        'normal' => 'normal',
        'italic' => 'italic'
    );

    // Variant
    $_css_opts['font-variant'] = array(
        'normal'     => 'normal',
        'small-caps' => 'small-caps'
    );

    // Text-Transform
    $_css_opts['text-transform'] = array(
        'none'       => 'none',
        'capitalize' => 'capitalize',
        'uppercase'  => 'uppercase',
        'lowercase'  => 'lowercase',
        'inherit'    => 'inherit'
    );

    // Text-Align
    $_css_opts['text-align'] = array(
        'left'    => 'left',
        'right'   => 'right',
        'center'  => 'center',
        'justify' => 'justify',
        'inherit' => 'inherit'
    );

    // Text-Decoration
    $_css_opts['text-decoration'] = array(
        'none'         => 'none',
        'underline'    => 'underline',
        'overline'     => 'overline',
        'line-through' => 'line-through',
        'blink'        => 'blink',
        'inherit'      => 'inherit'
    );

    // $_tmp will now contain what we are editing
    if (isset($_tmp) && is_array($_tmp)) {

        $r_id = 0;
        $key = false;
        foreach ($_tmp as $name => $_inf) {
            // Process each rule...

            $_out = array();
            if (isset($_inf['rules']) && is_array($_inf['rules'])) {
                foreach ($_inf['rules'] as $rule => $val) {

                    $val = str_replace(array('"', "'"), '', $val);

                    // Pass this in as a hidden form field so we can line them back up on submission
                    $key = 'jrse' . ++$r_id;
                    $hid = '<input type="hidden" name="' . $key . '_s" value="' . $name . '~' . $rule . '">';

                    // Our tag is used te let the user know what they are changing
                    $tag = $rule;

                    // See what we are doing
                    switch ($rule) {

                        //------------------------
                        // background-color
                        //------------------------
                        case 'color':
                            $tag = 'font-color';
                        case 'border-color':
                        case 'border-top-color':
                        case 'border-right-color':
                        case 'border-bottom-color':
                        case 'border-left-color':
                        case 'background-color':
                            // Show color selector
                            if ($val == 'transparent') {
                                $color_opts .= "<option value=\"" . str_replace('#', '', $val) . "\" selected=\"selected\">{$val}</option>";
                            }
                            else {
                                $color_opts .= "<option value=\"" . strtoupper(str_replace('#', '', $val)) . "\" selected=\"selected\">{$val}</option>";
                            }
                            $_out[] = $hid . '<p class="style-label">' . $tag . '</p><select id="' . $key . '" name="' . $key . '" class="style-select">' . $color_opts . '</select>';
                            $_tmp = jrCore_get_flag('style_color_picker');
                            if (!$_tmp) {
                                $_tmp = array();
                            }
                            $_tmp[] = array('$(\'#' . $key . '\').colourPicker();');
                            jrCore_set_flag('style_color_picker', $_tmp);
                            break;

                        //------------------------
                        // fonts
                        //------------------------
                        case 'font-family':
                        case 'font-size':
                        case 'font-weight':
                        case 'font-style':
                        case 'font-variant':
                        case 'text-transform':
                        case 'text-align':
                        case 'text-decoration':
                            $opts = array();
                            foreach ($_css_opts[$rule] as $fcss => $fname) {
                                switch ($rule) {
                                    case 'font-family':
                                        $style = ' style="font-family:' . $fcss . '"';
                                        break;
                                    default:
                                        $style = '';
                                        break;
                                }
                                if (isset($fcss) && $fcss == $val) {
                                    $opts[] = '<option value="' . $fcss . '"' . $style . ' selected="selected">' . $fname . '</option>';
                                }
                                else {
                                    $opts[] = '<option value="' . $fcss . '"' . $style . '>' . $fname . '</option>';
                                }
                            }
                            // Show font family select
                            $_out[] = "\n" . $hid . '<p class="style-label">' . $rule . '</p><select id="' . $key . '" name="' . $key . '" class="style-select">' . implode("\n", $opts) . '</select>';
                            break;

                        //------------------------
                        // border-style
                        //------------------------
                        case 'border-style':
                        case 'border-top-style':
                        case 'border-right-style':
                        case 'border-bottom-style':
                        case 'border-left-style':
                            $opts = array();
                            $_brd = array('none', 'dotted', 'dashed', 'solid', 'double', 'groove', 'ridge', 'inset', 'outset');
                            foreach ($_brd as $v) {
                                if (isset($v) && $v == $val) {
                                    $opts[] = '<option selected="selected" value="' . $v . '">' . $v . '</option>';
                                }
                                else {
                                    $opts[] = '<option value="' . $v . '">' . $v . '</option>';
                                }
                            }
                            // Show select
                            $_out[] = $hid . '<p class="style-label">' . $rule . '</p><select id="' . $key . '" name="' . $key . '" class="style-select">' . implode("\n", $opts) . '</select>';
                            break;

                        //------------------------
                        // padding/margin/border
                        //------------------------
                        case 'border-width':
                        case 'border-top-width':
                        case 'border-right-width':
                        case 'border-bottom-width':
                        case 'border-left-width':
                        case 'border-radius':
                        case 'border-top-left-radius':
                        case 'border-top-right-radius':
                        case 'border-bottom-left-radius':
                        case 'border-bottom-right-radius':
                        case 'padding':
                        case 'padding-top':
                        case 'padding-bottom':
                        case 'padding-left':
                        case 'padding-right':
                        case 'margin':
                        case 'margin-top':
                        case 'margin-bottom':
                        case 'margin-left':
                        case 'margin-right':
                            $opts = array();
                            foreach ($_pixels as $size) {
                                if (isset($size) && $size == $val) {
                                    $opts[] = '<option selected="selected" value="' . $size . '">' . $size . '</option>';
                                }
                                else {
                                    $opts[] = '<option value="' . $size . '">' . $size . '</option>';
                                }
                            }
                            // Show font family select
                            $_out[] = $hid . '<p class="style-label">' . $rule . '</p><select id="' . $key . '" name="' . $key . '" class="style-select">' . implode("\n", $opts) . '</select>';
                            break;

                        case 'width':
                        case 'height':
                            $opts = array();
                            if (strpos($val, '%')) {
                                if (!in_array($val,$_width_perc)) {
                                    $_width_perc[] = $val;
                                    sort($_width_perc,SORT_NUMERIC);
                                }
                                foreach ($_width_perc as $size) {
                                    if (isset($size) && $size == $val) {
                                        $opts[] = '<option selected="selected" value="' . $size . '">' . $size . '</option>';
                                    }
                                    else {
                                        $opts[] = '<option value="' . $size . '">' . $size . '</option>';
                                    }
                                }
                            }
                            else {
                                // Make sure the value we HAVE is always set
                                if (!in_array($val,$_width_pix)) {
                                    $_width_pix[] = $val;
                                    sort($_width_pix,SORT_NUMERIC);
                                }
                                foreach ($_width_pix as $size) {
                                    if (isset($size) && $size == $val) {
                                        $opts[] = '<option selected="selected" value="' . $size . '">' . $size . '</option>';
                                    }
                                    else {
                                        $opts[] = '<option value="' . $size . '">' . $size . '</option>';
                                    }
                                }
                            }
                            // Show font family select
                            $_out[] = $hid . '<p class="style-label">' . $rule . '</p><select id="' . $key . '" name="' . $key . '" class="style-select">' . implode("\n", $opts) . '</select>';
                            break;
                    }
                }
            }
            if (isset($_out) && is_array($_out) && count($_out) > 0) {
                $_field = array(
                    'name'  => $key,
                    'type'  => 'custom',
                    'html'  => '<div class="style-box">' . implode('<br>', $_out) . '</div><div style="float:right">' . jrCore_page_button("r{$key}", 'reset', "if (confirm('Are you sure you want to reset this element to the default?')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/css_reset_save/skin={$skin}/tag={$name}'}") . '</div>',
                    'label' => $_inf['title'],
                    'help'  => $_inf['help']
                );
                jrCore_form_field_create($_field);
            }
        }
    }
    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_skin_images
 */
function jrCore_show_skin_images($type, $skin, $_post, $_user, $_conf)
{
    global $_mods;
    $_tmp = array("$('.lightbox').lightBox();");
    jrCore_create_page_element('javascript_ready_function', $_tmp);

    // Generate our output
    if (isset($type) && $type == 'module') {
        jrCore_page_admin_tabs($skin, 'images');
        $action = "admin_save/images/module={$skin}";
    }
    else {
        jrCore_page_skin_tabs($skin, 'images');
        $action = "skin_admin_save/images/skin={$skin}";
    }

    if ($type == 'module') {
        // Setup our module jumper
        $subtitle = '<select name="mod_select" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/images'\">";
        $_tmpm = array();
        foreach ($_mods as $mod_dir => $_info) {
            $_tmpm[$mod_dir] = $_info['module_name'];
        }
        asort($_tmpm);
        foreach ($_tmpm as $mod_dir => $title) {
            if (!jrCore_module_is_active($mod_dir)) {
                continue;
            }
            if (is_dir(APP_DIR . "/modules/{$mod_dir}/img")) {
                if ($mod_dir == $_post['module']) {
                    $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
                }
                else {
                    $murl = jrCore_get_module_url($mod_dir);
                    $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
                }
            }
        }
        $subtitle .= '</select>';
    }
    else {
        $url = jrCore_get_module_url('jrCore');
        $subtitle = '<select name="skin_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/{$url}/skin_admin/images/skin='+ v\">";
        $_tmpm = jrCore_get_skins();
        ksort($_tmpm);
        foreach ($_tmpm as $skin_dir => $_skin) {
            if (is_dir(APP_DIR . "/skins/{$skin_dir}/img")) {
                if ($skin_dir == $_post['skin']) {
                    $subtitle .= '<option value="' . $_post['skin'] . '" selected="selected"> ' . $_post['skin'] . "</option>\n";
                }
                else {
                    $subtitle .= '<option value="' . $skin_dir . '"> ' . $skin_dir . "</option>\n";
                }
            }
        }
    }

    jrCore_page_banner('Images', $subtitle);
    // See if we are disabled
    if (!jrCore_module_is_active($_post['module'])) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }
    jrCore_get_form_notice();

    if (!isset($_conf["jrCore_{$skin}_custom_images"])) {
        // Custom image container (per skin)
        $_tmp = array(
            'name'     => "{$skin}_custom_images",
            'default'  => '',
            'type'     => 'hidden',
            'required' => 'on',
            'validate' => 'false',
            'label'    => "{$skin} custom images",
            'help'     => 'this hidden field holds the names of images that have been customized'
        );
        jrCore_register_setting('jrCore', $_tmp);
    }

    // Form init
    $_tmp = array(
        'submit_value'     => 'save changes',
        'action'           => $action,
        'form_ajax_submit' => false
    );
    jrCore_form_create($_tmp);

    $dat = array();
    $dat[1]['title'] = 'default';
    $dat[1]['width'] = '30%';
    $dat[2]['title'] = 'active';
    $dat[2]['width'] = '5%';
    $dat[3]['title'] = 'custom';
    $dat[3]['width'] = '30%';
    $dat[4]['title'] = 'upload custom';
    $dat[4]['width'] = '35%';
    jrCore_page_table_header($dat);

    // Get any custom images
    $_cust = (isset($_conf["jrCore_{$skin}_custom_images"]{2})) ? json_decode($_conf["jrCore_{$skin}_custom_images"], true) : array();

    // Get all of our actual template files...
    // See if we are doing a module or a skin...
    if (isset($type) && $type == 'module') {
        $t_url = 'modules';
        $t_tag = 'mod_';
        $_imgs = glob(APP_DIR . "/modules/{$skin}/img/*.{png,jpg,gif}", GLOB_BRACE);
        $u_tag = 'mod';
    }
    else {
        $t_url = 'skins';
        $t_tag = '';
        $_imgs = glob(APP_DIR . "/skins/{$skin}/img/*.{png,jpg,gif}", GLOB_BRACE);
        $u_tag = 'skin';
    }
    $curl = jrCore_get_module_url('jrCore');

    if (isset($_imgs) && is_array($_imgs)) {
        foreach ($_imgs as $k => $full_file) {
            $dat = array();
            $img = basename($full_file);
            $_is = getimagesize($full_file);
            $url = "{$_conf['jrCore_base_url']}/{$t_url}/{$skin}/img/{$img}";

            $w = $_is[0];
            $h = $_is[1];
            $l = false;
            if (isset($h) && $h > 100) {
                $w = (($w / $h) * 100);
                $h = 100;
                $l = true;
                // See if our width is greater than 100 here...
                if (isset($w) && $w > 100) {
                    $w = 100;
                }
            }
            elseif (isset($w) && $w > 100) {
                $h = (($h / $w) * 100);
                $w = 100;
                $l = true;
            }
            if ($l) {
                $dat[1]['title'] = "<a href=\"{$url}\" class=\"lightbox\"><img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" alt=\"{$img}\" title=\"{$img}\"></a>";
            }
            else {
                $dat[1]['title'] = "<img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" alt=\"{$img}\" title=\"{$img}\">";
            }
            $dat[1]['class'] = 'center';

            if (isset($_cust[$img])) {
                $chk = '';
                if (isset($_cust[$img][1]) && $_cust[$img][1] == 'on') {
                    $chk = ' checked="checked"';
                }
                $dat[2]['title'] = "<input type=\"hidden\" name=\"name_{$k}_active\" value=\"off\"><input type=\"checkbox\" name=\"name_{$k}_active\" class=\"form-checkbox\"{$chk}>";
                $dat[2]['class'] = 'center';
            }
            else {
                $dat[2]['title'] = '&nbsp;';
            }

            if (isset($_cust[$img])) {
                // We have a custom image
                $url = "{$_conf['jrCore_base_url']}/data/media/0/0/{$t_tag}{$skin}_{$img}";
                $_is = getimagesize(APP_DIR . "/data/media/0/0/{$t_tag}{$skin}_{$img}");

                $w = $_is[0];
                $h = $_is[1];
                $l = false;
                if (isset($h) && $h > 100) {
                    $w = (($w / $h) * 100);
                    $h = 100;
                    $l = true;
                    // See if our width is greater than 100 here...
                    if (isset($w) && $w > 100) {
                        $w = 100;
                    }
                }
                elseif (isset($w) && $w > 100) {
                    $h = (($h / $w) * 100);
                    $w = 100;
                    $l = true;
                }
                $dat[3]['title'] = '<div style="width:120px;display:inline-block">';
                if ($l) {
                    $dat[3]['title'] .= "<a href=\"{$url}\" class=\"lightbox\"><img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" alt=\"{$img}\" title=\"{$img}\"></a>";
                }
                else {
                    $dat[3]['title'] .= "<img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" height=\"{$_is[1]}\" alt=\"{$img}\" title=\"{$img}\">";
                }
                $dat[3]['title'] .= "</div>&nbsp;" . jrCore_page_button("d{$k}", 'delete', "if (confirm('Are you sure you want to delete this custom image?')){window.location='{$_conf['jrCore_base_url']}/{$curl}/skin_image_delete_save/{$u_tag}={$skin}/name={$img}'}");
                unset($_cust[$img]);
            }
            else {
                $dat[3]['title'] = '&nbsp;';
            }
            $dat[3]['class'] = 'center';
            $dat[4]['title'] = "<input type=\"hidden\" name=\"name_{$k}\" value=\"{$img}\"><input type=\"file\" name=\"file_{$k}\"><br><span class=\"sublabel\"><b>{$img}</b> - <b>{$_is[0]} x {$_is[1]}</b></span>";
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_footer();

        // Check for any custom images left over - not part of the skin
        if (isset($_cust) && is_array($_cust) && count($_cust) > 0) {

            jrCore_page_divider();

            $dat = array();
            $dat[1]['title'] = 'custom image';
            $dat[1]['width'] = '65%';
            $dat[2]['title'] = 'upload new custom image';
            $dat[2]['width'] = '35%';
            jrCore_page_table_header($dat);

            $dir = jrCore_get_media_directory(0);
            $num = 0;
            foreach ($_cust as $img => $size) {
                $dat = array();
                $_is = getimagesize("{$dir}/{$t_tag}{$skin}_{$img}");
                $w = $_is[0];
                $h = $_is[1];
                $l = false;
                if (isset($h) && $h > 100) {
                    $w = (($w / $h) * 100);
                    $h = 100;
                    $l = true;
                }
                elseif (isset($w) && $w > 100) {
                    $h = (($h / $w) * 100);
                    $w = 100;
                    $l = true;
                }
                $url = "{$_conf['jrCore_base_url']}/data/media/0/0/{$t_tag}{$skin}_{$img}";
                $dat[1]['title'] = '<div style="width:120px;display:inline-block;vertical-align:middle;">';
                if ($l) {
                    $dat[1]['title'] .= "<a href=\"{$url}\" class=\"lightbox\"><img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" alt=\"{$img}\" title=\"{$img}\" style=\"margin-bottom:6px\"></a>";
                }
                else {
                    $dat[1]['title'] .= "<img src=\"{$url}?r=" . mt_rand() . "\" height=\"{$h}\" width=\"{$w}\" alt=\"{$img}\" title=\"{$img}\" style=\"margin-bottom:6px\">";
                }

                $dat[1]['title'] .= "</div><div style=\"float:right;\">" . jrCore_page_button("d{$num}", 'delete', "if (confirm('Are you sure you want to delete this custom image?')){window.location='{$_conf['jrCore_base_url']}/{$curl}/skin_image_delete_save/{$u_tag}={$skin}/name={$img}'}") . '</div>';
                $dat[1]['class'] = 'center';
                $dat[2]['title'] = "<input type=\"hidden\" name=\"name_{$k}\" value=\"{$img}\"><input type=\"file\" name=\"file_{$k}\"><br><span class=\"sublabel\"><b>{$img}</b> - <b>{$_is[0]} x {$_is[1]}</b></span>";
                jrCore_page_table_row($dat);
                $num++;
            }
            jrCore_page_table_footer();
        }
    }

    // Upload new image
    $imax = array_keys(jrImage_get_allowed_image_sizes());
    $imax = end($imax);
    $_tmp = array(
        'name'       => "new_images",
        'type'       => 'file',
        'label'      => 'additional images',
        'help'       => 'Upload custom images for use in your templates',
        'text'       => 'Select Images to Upload',
        'extensions' => 'png,gif,jpg,jpeg',
        'multiple'   => true,
        'required'   => false,
        'max'        => $imax
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_skin_templates
 */
function jrCore_show_skin_templates($skin, $_post, $_user, $_conf)
{
    unset($_SESSION['template_cancel_url']);
    // Generate our output
    jrCore_page_skin_tabs($skin, 'templates');

    $murl = jrCore_get_module_url('jrCore');
    $subtitle = '<select name="skin_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/{$murl}/skin_admin/templates/skin='+ v\">";
    $_tmpm = jrCore_get_skins();
    ksort($_tmpm);
    foreach ($_tmpm as $skin_dir => $_skin) {
        if ($skin_dir == $_post['skin']) {
            $subtitle .= '<option value="' . $_post['skin'] . '" selected="selected"> ' . $_post['skin'] . "</option>\n";
        }
        else {
            $subtitle .= '<option value="' . $skin_dir . '"> ' . $skin_dir . "</option>\n";
        }
    }
    jrCore_page_banner('Templates', $subtitle);
    jrCore_get_form_notice();

    // See if we have a search string
    $_tpls = glob(APP_DIR . "/skins/{$skin}/*.tpl");
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        // Search through templates
        foreach ($_tpls as $k => $full_file) {
            $temp = file_get_contents($full_file);
            if (!stristr(' ' . $temp, $_post['search_string'])) {
                unset($_tpls[$k]);
            }
        }
    }

    jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$_post['module_url']}/skin_admin/templates/skin={$skin}");

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => "skin_admin_save/templates/skin={$skin}"
    );
    jrCore_form_create($_tmp);

    // Start our output
    $dat = array();
    $dat[1]['title'] = 'name';
    $dat[1]['width'] = '60%';
    $dat[2]['title'] = 'active';
    $dat[2]['width'] = '5%';
    $dat[3]['title'] = 'updated';
    $dat[3]['width'] = '25%';
    $dat[4]['title'] = 'modify';
    $dat[4]['width'] = '5%';
    $dat[5]['title'] = 'reset';
    $dat[5]['width'] = '5%';
    jrCore_page_table_header($dat);

    // Get all of our actual template files...
    if (isset($_tpls) && is_array($_tpls)) {

        // Get templates from database to see if we have customized any of them
        $tbl = jrCore_db_table_name('jrCore', 'template');
        $req = "SELECT template_id, template_module, template_updated, template_user, template_active, template_name FROM {$tbl} WHERE template_module = '" . jrCore_db_escape($skin) . "'";
        $_tp = jrCore_db_query($req, 'template_name');
        $url = jrCore_get_module_url('jrCore');

        // Go through templates on file system
        foreach ($_tpls as $full_file) {
            $dat = array();
            $tpl_name = basename($full_file);
            $dat[1]['title'] = $tpl_name;
            if (isset($_tp[$tpl_name])) {
                $checked = '';
                if (isset($_tp[$tpl_name]['template_active']) && $_tp[$tpl_name]['template_active'] == '1') {
                    $checked = ' checked="checked"';
                }
                $chk_name = str_replace('.tpl', '', $tpl_name);
                $dat[2]['title'] = "<input type=\"hidden\" name=\"{$chk_name}_template_active\" value=\"off\"><input type=\"checkbox\" name=\"{$chk_name}_template_active\" class=\"form-checkbox\"{$checked}>";
                $dat[3]['title'] = jrCore_format_time($_tp[$tpl_name]['template_updated']) . '<br>' . $_tp[$tpl_name]['template_user'];
                $dat[3]['class'] = 'center nowrap';
            }
            else {
                $dat[2]['title'] = '&nbsp;';
                $dat[3]['title'] = '&nbsp;';
            }
            $dat[2]['class'] = 'center';
            if (isset($_tp[$tpl_name])) {
                $dat[4]['title'] = jrCore_page_button("m{$tpl_name}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/skin={$skin}/id=" . $_tp[$tpl_name]['template_id'] . "'");
                $dat[5]['title'] = jrCore_page_button("r{$tpl_name}", 'reset', "if (confirm('Are you sure you want to reset this template to the default?')){window.location='{$_conf['jrCore_base_url']}/{$url}/template_reset_save/skin={$skin}/id=" . $_tp[$tpl_name]['template_id'] . "'}");
            }
            else {
                $dat[4]['title'] = jrCore_page_button("m{$tpl_name}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/skin={$skin}/template={$tpl_name}'");
                $dat[5]['title'] = '&nbsp;';
            }
            jrCore_page_table_row($dat);
        }
    }
    else {
        $dat = array();
        $dat[1]['title'] = '<p>There were no templates found to match your search criteria!</p>';
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    // Save Template Updates - this small hidden field needs to be here
    // otherwise the form will not work - this is due to the fact the checkbox
    // elements in the table were created outside of jrCore_form_field_create
    $_tmp = array(
        'name'     => "save_template_updates",
        'type'     => 'hidden',
        'required' => 'true',
        'validate' => 'onoff',
        'value'    => 'on'
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_skin_info
 */
function jrCore_show_skin_info($skin, $_post, $_user, $_conf)
{
    $_mta = jrCore_skin_meta_data($skin);

    // Generate our output
    jrCore_page_skin_tabs($skin, 'info');
    $murl = jrCore_get_module_url('jrCore');
    $subtitle = '<select name="skin_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/{$murl}/skin_admin/info/skin='+ v\">";
    $_tmpm = jrCore_get_skins();
    ksort($_tmpm);
    foreach ($_tmpm as $skin_dir => $_skin) {
        if ($skin_dir == $_post['skin']) {
            $subtitle .= '<option value="' . $_post['skin'] . '" selected="selected"> ' . $_post['skin'] . "</option>\n";
        }
        else {
            $subtitle .= '<option value="' . $skin_dir . '"> ' . $skin_dir . "</option>\n";
        }
    }
    jrCore_page_banner($_mta['name'], $subtitle);

    $_opt = array('description', 'version', 'developer', 'support');
    $onum = count($_opt);
    if (is_file(APP_DIR . "/skins/{$skin}/readme.html")) {
        $onum++;
    }
    $temp = '<table><tr><td rowspan="' . $onum . '" style="width:10%" class="info_img"><img src="' . $_conf['jrCore_base_url'] . '/skins/' . $skin . '/icon.png" width="128" height="128" alt="' . $_mta['name'] . '"></td>';
    foreach ($_opt as $k => $key) {
        $text = (isset($_mta[$key])) ? $_mta[$key] : 'undefined';
        if ($k > 0) {
            $temp .= '<tr>';
        }
        $temp .= '<td style="width:10%" class="right">&nbsp;&nbsp;<b>' . $key . ':</b>&nbsp;&nbsp;</td><td style="width:80%">' . $text . '</td>';
        if ($k > 0) {
            $temp .= '</tr>';
        }
    }
    // See if this module has a readme associated with it
    if (is_file(APP_DIR . "/skins/{$skin}/readme.html")) {
        $text = jrCore_page_button('rm', 'admin skin notes', "popwin('{$_conf['jrCore_base_url']}/skins/{$skin}/readme.html','readme',600,500,'yes');");
        $temp .= '<tr><td class="right">&nbsp;&nbsp;<b>notes:</b>&nbsp;&nbsp;</td><td>' . $text . '</td></tr>';
    }
    $temp .= '</table>';

    // Check for screen shots
    foreach (range(1, 4) as $n) {
        if (is_file(APP_DIR . "/skins/{$skin}/img/screenshot{$n}.jpg")) {
            if (!isset($_img)) {
                $_img = array();
            }
            $_img[] = "{$_conf['jrCore_base_url']}/skins/{$skin}/img/screenshot{$n}.jpg";
        }
    }
    if (isset($_img) && is_array($_img)) {
        $perc = round(100 / count($_img), 2);
        $temp .= '<br><table><tr>';
        foreach ($_img as $k => $shot) {
            $temp .= "<td style=\"width:{$perc}%;padding:6px;\"><img src=\"{$shot}\" class=\"img_scale\" alt=\"screenshot {$k}\"></td>";
        }
        $temp .= '</tr></table>';
    }
    $temp .= '<br>';

    jrCore_page_custom($temp);

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => "skin_admin_save/info/skin={$skin}"
    );
    jrCore_form_create($_tmp);

    // Active Skin
    $act = 'off';
    if (isset($_conf['jrCore_active_skin']) && $_conf['jrCore_active_skin'] == $skin) {
        $act = 'on';
    }
    $_tmp = array(
        'name'     => 'skin_active',
        'label'    => 'set as active skin',
        'help'     => "If you would like to use this skin for your site, check this option and save.",
        'type'     => 'checkbox',
        'value'    => $act,
        'validate' => 'onoff'
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_parse_css_file
 */
function jrCore_parse_css_file($file)
{
    if (!is_file($file)) {
        return false;
    }
    $_tmp = file($file);
    if (!isset($_tmp) || !is_array($_tmp)) {
        return false;
    }
    $_out = array();

    // Characters we strip from title and help lines
    $ignore_next_item = false;
    $_strip = array('@title', '@help', '/*', '*/');
    foreach ($_tmp as $line) {

        $line = trim($line);
        // End comment on separate line
        if (strlen($line) < 1 || strpos($line, '*') === 0 || strpos($line, '@ignore')) {
            continue;
        }

        // Comment
        elseif (strpos($line, '/*') === 0) {
            if (!strpos($line, '@')) {
                continue;
            }
            // We have a comment with info..
            if (strpos($line, '@title')) {
                $title = trim(str_replace($_strip, '', $line));
            }
            elseif (strpos($line, '@help')) {
                $help = trim(str_replace($_strip, '', $line));
            }
            elseif (strpos($line, '@ignore')) {
                $ignore_next_item = true;
            }
            continue;
        }

        // Element/Class/ID - begin
        elseif (strpos($line, '{') && !strpos($line, '{$jamroom')) {
            if (!isset($title) || $ignore_next_item) {
                continue;
            }
            $name = trim(substr($line, 0, strpos($line, '{')));
            if (!$ignore_next_item) {
                $_out[$name] = array(
                    'title' => isset($title) ? $title : '',
                    'help'  => isset($help) ? $help : '',
                    'rules' => array()
                );
            }
        }

        // Element/Class/ID - end
        elseif (strpos($line, '}') === 0) {
            if ($ignore_next_item) {
                $ignore_next_item = false;
                continue;
            }
            if (!isset($title)) {
                continue;
            }
            if (isset($name)) {
                unset($name);
            }
            if (isset($title)) {
                unset($title);
            }
            if (isset($help)) {
                unset($help);
            }
        }

        // Rules
        elseif (isset($name) && strpos($line, ':')) {
            if ($ignore_next_item) {
                continue;
            }
            if (!isset($title)) {
                continue;
            }
            list($rule, $value) = explode(':', $line, 2);
            $rule = trim($rule);
            $value = ltrim(rtrim(trim($value), ';'), '#');
            $_out[$name]['rules'][$rule] = $value;
        }
    }
    return $_out;
}

//------------------------------
// MODULE VIEW FUNCTIONS
//------------------------------

/**
 * Set display order for items on a profile
 * @param $_post array Global $_post
 * @param $_user array Viewing user array
 * @param $_conf array Global config
 * @return bool
 */
function view_jrCore_item_display_order($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    // Make sure the requested module has a registered DS
    $pfx = jrCore_db_get_prefix($_post['module']);
    if (!$pfx) {
        jrCore_notice_page('error','Invalid module - module does not use a DataStore');
        return false;
    }
    // Make sure this module has registered for item_order
    $_md = jrCore_get_registered_module_features('jrCore','item_order_support');
    if (!isset($_md["{$_post['module']}"])) {
        jrCore_notice_page('error','Invalid module - module is not registered for item_order support');
        return false;
    }
    // Get all items of this type
    $_sc = array(
        'search'        => array("_profile_id = {$_user['user_active_profile_id']}"),
        'return_keys'   => array('_item_id', "{$pfx}_title"),
        'order_by'      => array("{$pfx}_display_order" => 'numerical_asc'),
        'skip_triggers' => true,
        'limit'         => 500
    );
    $_rt = jrCore_db_search_items($_post['module'],$_sc);
    if (!isset($_rt['_items']) || !is_array($_rt['_items'])) {
        jrCore_notice_page('notice','There are no items to set the order for!');
        return false;
    }
    jrCore_page_banner('set item order');

    $tmp = '<ul class="item_sortable list">';
    foreach ($_rt['_items'] as $_item) {
        $tmp .= "<li data-id=\"{$_item['_item_id']}\">". $_item["{$pfx}_title"] ."</li>";
    }
    $tmp .= '</ul>';
    jrCore_page_custom($tmp,'set order','drag and drop entries to set order');

    $url = "{$_conf['jrCore_base_url']}/". jrCore_get_module_url('jrCore') ."/item_display_order_update/m={$_post['module']}/__ajax=1";
    $tmp = array('$(function() {
           $(\'.item_sortable\').sortable().bind(\'sortupdate\', function(event,ui) {
               var o = $(\'ul.item_sortable li\').map(function(){ return $(this).data("id"); }).get();
               $.post(\''. $url .'\', { iid: o });
           });
       });');
    jrCore_create_page_element('javascript_footer_function', $tmp);
    jrCore_page_cancel_button("{$_conf['jrCore_base_url']}/{$_user['profile_url']}/{$_post['module_url']}",'continue');
    return jrCore_page_display(true);
}

/**
 * Update item order in Datastore
 * @param $_post array Global $_post
 * @param $_user array Viewing user array
 * @param $_conf array Global config
 * @return bool
 */
function view_jrCore_item_display_order_update($_post, $_user, $_conf)
{
    jrUser_session_require_login();
    if (!isset($_post['m']) || !jrCore_module_is_active($_post['m'])) {
        return jrCore_json_response(array('error','Invalid module'));
    }
    // Make sure the requested module has a registered DS
    $pfx = jrCore_db_get_prefix($_post['m']);
    if (!$pfx) {
        return jrCore_json_response(array('error','Invalid module - module does not use a DataStore'));
    }
    // Make sure this module has registered for item_order
    $_md = jrCore_get_registered_module_features('jrCore','item_order_support');
    if (!isset($_md["{$_post['m']}"])) {
        return jrCore_json_response(array('error','Invalid module - module is not registered for item_order support'));
    }

    // Get our items that are being re-ordered and make sure
    // the calling user has access to them
    if (!jrUser_is_admin()) {
        $_rt = jrCore_db_get_multiple_items($_post['m'],$_post['iid']);
        if (!isset($_rt) || !is_array($_rt)) {
            return jrCore_json_response(array('error','unable to retrieve item entries from DataStore'));
        }
        foreach ($_rt as $_v) {
            if (!jrUser_can_edit_item($_v)) {
                return jrCore_json_response(array('error','permission denied'));
            }
        }
    }

    // Looks good - set item order
    $tbl = jrCore_db_table_name($_post['m'],'item_key');
    $req = "INSERT INTO {$tbl} (`_item_id`,`key`,`index`,`value`) VALUES ";
    foreach ($_post['iid'] as $ord => $iid) {
        $ord = (int) $ord;
        $iid = (int) $iid;
        $req .= "('{$iid}','{$pfx}_display_order',0,'{$ord}'),";
    }
    $req = substr($req,0,strlen($req) - 1) ." ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)";
    jrCore_db_query($req);
    jrProfile_reset_cache();
    return jrCore_json_response(array('success','item order successfully updated'));
}

/**
 * jrCore_show_global_settings
 */
function jrCore_show_global_settings($type, $module, $_post, $_user, $_conf)
{
    global $_mods;
    // Get this module's config entries from settings
    $tbl = jrCore_db_table_name('jrCore', 'setting');
    $req = "SELECT * FROM {$tbl} WHERE `module` = '" . jrCore_db_escape($module) . "' AND `type` != 'hidden' ORDER BY `order` ASC, `section` ASC, `name` ASC";
    $_rt = jrCore_db_query($req, 'NUMERIC');
    if (!isset($_rt) || !is_array($_rt)) {
        return false;
    }

    // See if we have a custom config display function
    if (!function_exists("{$module}_config") && is_file(APP_DIR . "/modules/{$module}/config.php")) {
        require_once APP_DIR . "/modules/{$module}/config.php";
        $func = "{$module}_config_display";
        if (function_exists($func)) {
            $func($_post, $_user, $_conf);
        }
    }

    if (isset($_post['hl']) && strlen($_post['hl']) > 0) {
        jrCore_form_field_hilight($_post['hl']);
    }

    // Generate our output
    if ($type == 'module') {
        jrCore_page_admin_tabs($module, 'global');
        $action = 'admin_save/global';
    }
    else {
        jrCore_page_skin_tabs($module, 'global');
        $action = 'skin_admin_save/global';
    }

    // Setup our module jumper
    $url = jrCore_get_module_url('jrCore');
    $add = '';
    if ($type == 'skin') {
        $subtitle = '<select name="designer_form" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/{$url}/skin_admin/global/skin='+ v\">";
        $_tmpm = jrCore_get_skins();
        ksort($_tmpm);
        foreach ($_tmpm as $skin_dir => $_skin) {
            if (is_file(APP_DIR . "/skins/{$skin_dir}/config.php")) {
                if ($skin_dir == $_post['skin']) {
                    $subtitle .= '<option value="' . $_post['skin'] . '" selected="selected"> ' . $_post['skin'] . "</option>\n";
                }
                else {
                    $subtitle .= '<option value="' . $skin_dir . '"> ' . $skin_dir . "</option>\n";
                }
            }
        }
        $add = 'sa=skin/skin=' . $_post['module'] . '/';
    }
    else {
        $subtitle = '<select name="designer_form" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/global'\">";
        $_tmpm = array();
        foreach ($_mods as $mod_dir => $_info) {
            $_tmpm[$mod_dir] = $_info['module_name'];
        }
        asort($_tmpm);
        foreach ($_tmpm as $mod_dir => $title) {
            if (!jrCore_module_is_active($mod_dir)) {
                continue;
            }
            if (is_file(APP_DIR . "/modules/{$mod_dir}/config.php")) {
                if ($mod_dir == $_post['module']) {
                    $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
                }
                else {
                    $murl = jrCore_get_module_url($mod_dir);
                    $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
                }
            }
        }
    }
    $subtitle .= '</select>';
    $subtitle .= '<input type="text" value="search" name="ss" class="form_text form_admin_search" onfocus="if(this.value==\'search\'){this.value=\'\';}" onblur="if(this.value==\'\'){this.value=\'search\';}" onkeypress="if(event && event.keyCode == 13 && this.value.length > 0){window.location=\'' . $_conf['jrCore_base_url'] . '/' . $url . '/search/' . $add . 'ss=\'+ jrE(this.value);return false; };">';
    jrCore_page_banner('Global Settings', $subtitle);

    // See if we are disabled
    if ($type == 'module' && !jrCore_module_is_active($module)) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }
    jrCore_get_form_notice();

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => $action
    );
    jrCore_form_create($_tmp);

    foreach ($_rt as $_field) {
        jrCore_form_field_create($_field);
    }
    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_module_tools
 */
function jrCore_show_module_tools($module, $_post, $_user, $_conf)
{
    global $_mods;

    // Get registered tool views
    $_tool = jrCore_get_registered_module_features('jrCore', 'tool_view');

    // Generate our output
    jrCore_page_admin_tabs($module, 'tools');

    // Setup our module jumper
    $subtitle = '<select name="module_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/tools'\">";
    $_tmpm = array();
    foreach ($_mods as $mod_dir => $_info) {
        $_tmpm[$mod_dir] = $_info['module_name'];
    }
    asort($_tmpm);
    foreach ($_tmpm as $mod_dir => $title) {
        if (!jrCore_module_is_active($mod_dir)) {
            continue;
        }
        if (isset($_tool[$mod_dir]) || jrCore_db_get_prefix($mod_dir)) {
            if ($mod_dir == $_post['module']) {
                $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
            }
            else {
                $murl = jrCore_get_module_url($mod_dir);
                $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
            }
        }
    }
    $subtitle .= '</select>';
    $subtitle .= '<input type="text" value="search" name="ss" class="form_text form_admin_search" onfocus="if(this.value==\'search\'){this.value=\'\';}" onblur="if(this.value==\'\'){this.value=\'search\';}" onkeypress="if(event && event.keyCode == 13 && this.value.length > 0){ window.location=\'' . $_conf['jrCore_base_url'] . '/' . jrCore_get_module_url('jrCore') . '/search/ss=\'+ jrE(this.value); }">';

    jrCore_page_banner("Tools", $subtitle);
    if (!jrCore_module_is_active($module)) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }
    jrCore_get_form_notice();

    if ((!isset($_tool[$module]) || !is_array($_tool[$module])) && !jrCore_db_get_prefix($module)) {
        jrCore_notice_page('error', 'there are no registered tool views for this module!');
    }
    // Check for DataStore browser
    if (jrCore_db_get_prefix($module)) {
        // DataStore enabled - check to see if this module is already registering a browser
        $_tmp = jrCore_get_registered_module_features('jrCore', 'tool_view');
        if (!isset($_tmp[$module]) || !isset($_tmp[$module]['browser'])) {
            jrCore_page_tool_entry("{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser", 'DataStore Browser', "Modify and Delete items in this module's DataStore");
        }
    }
    if (isset($_tool) && is_array($_tool) && isset($_tool[$module])) {
        foreach ($_tool[$module] as $view => $_inf) {
            $onc = (isset($_inf[2])) ? $_inf[2] : null;
            if (strpos($view, $_conf['jrCore_base_url']) === 0) {
                jrCore_page_tool_entry($view, $_inf[0], $_inf[1], $onc, '_blank');
            }
            else {
                jrCore_page_tool_entry("{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$view}", $_inf[0], $_inf[1], $onc);
            }
        }
    }
    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_module_templates
 */
function jrCore_show_module_templates($module, $_post, $_user, $_conf)
{
    global $_mods;
    unset($_SESSION['template_cancel_url']);
    // Generate our output
    jrCore_page_admin_tabs($module, 'templates');

    // Setup our module jumper
    $subtitle = '<select name="designer_form" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/templates'\">";
    $_tmpm = array();
    foreach ($_mods as $mod_dir => $_info) {
        $_tmpm[$mod_dir] = $_info['module_name'];
    }
    asort($_tmpm);
    foreach ($_tmpm as $mod_dir => $title) {
        if (!jrCore_module_is_active($mod_dir)) {
            continue;
        }
        if (is_dir(APP_DIR . "/modules/{$mod_dir}/templates")) {
            if ($mod_dir == $_post['module']) {
                $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
            }
            else {
                $murl = jrCore_get_module_url($mod_dir);
                $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
            }
        }
    }
    $subtitle .= '</select>';
    jrCore_page_banner('Templates', $subtitle);
    if (!jrCore_module_is_active($module)) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }
    jrCore_get_form_notice();

    // Get templates
    $_tpls = glob(APP_DIR . "/modules/{$module}/templates/*.tpl");

    // Get templates from database to see if we have customized any of them
    $tbl = jrCore_db_table_name('jrCore', 'template');
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $req = "SELECT template_id, template_module, template_updated, template_user, template_active, template_name, template_body FROM {$tbl} WHERE template_module = '" . jrCore_db_escape($module) . "'";
    }
    else {
        $req = "SELECT template_id, template_module, template_updated, template_user, template_active, template_name FROM {$tbl} WHERE template_module = '" . jrCore_db_escape($module) . "'";
    }
    $_tp = jrCore_db_query($req, 'template_name');

    // See if we have a search string
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        // Search through templates
        foreach ($_tpls as $k => $full_file) {
            $fname = basename($full_file);
            $found = false;

            // Match in file name
            if (stripos(' ' . $fname, $_post['search_string'])) {
                $found = true;
            }

            // Match in custom contents
            if (isset($_tp[$fname]['template_body']{0})) {
                $temp = file_get_contents($_tp[$fname]['template_body']);
                if (stristr(' ' . $temp, $_post['search_string'])) {
                    $found = true;
                }
            }

            // Match in actual file contents
            $temp = file_get_contents($full_file);
            if (stristr(' ' . $temp, $_post['search_string'])) {
                $found = true;
            }
            if (!$found) {
                unset($_tpls[$k]);
            }
        }
    }
    jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$_post['module_url']}/admin/templates");

    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => 'admin_save/templates'
    );
    jrCore_form_create($_tmp);

    // Start our output
    $dat = array();
    $dat[1]['title'] = 'name';
    $dat[1]['width'] = '60%';
    $dat[2]['title'] = 'active';
    $dat[2]['width'] = '5%';
    $dat[3]['title'] = 'updated';
    $dat[3]['width'] = '25%';
    $dat[4]['title'] = 'modify';
    $dat[4]['width'] = '5%';
    $dat[5]['title'] = 'reset';
    $dat[5]['width'] = '5%';
    jrCore_page_table_header($dat);

    // Get all of our actual template files...
    if (isset($_tpls) && is_array($_tpls) && count($_tpls) > 0) {

        $url = jrCore_get_module_url('jrCore');

        // Go through templates on file system
        foreach ($_tpls as $full_file) {
            $dat = array();
            $tpl_name = basename($full_file);
            $dat[1]['title'] = $tpl_name;
            if (isset($_tp[$tpl_name])) {
                $checked = '';
                if (isset($_tp[$tpl_name]['template_active']) && $_tp[$tpl_name]['template_active'] == '1') {
                    $checked = ' checked="checked"';
                }
                $chk_name = str_replace('.tpl', '', $tpl_name);
                $dat[2]['title'] = "<input type=\"hidden\" name=\"{$chk_name}_template_active\" value=\"off\"><input type=\"checkbox\" name=\"{$chk_name}_template_active\" class=\"form-checkbox\"{$checked}>";
                $dat[3]['title'] = jrCore_format_time($_tp[$tpl_name]['template_updated']) . '<br>' . $_tp[$tpl_name]['template_user'];
                $dat[3]['class'] = 'center nowrap';
            }
            else {
                $dat[2]['title'] = '&nbsp;';
                $dat[3]['title'] = '&nbsp;';
            }
            $dat[2]['class'] = 'center';
            if (isset($_tp[$tpl_name])) {
                $dat[4]['title'] = jrCore_page_button("m{$tpl_name}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/id=" . $_tp[$tpl_name]['template_id'] . "'");
                $dat[5]['title'] = jrCore_page_button("r{$tpl_name}", 'reset', "if (confirm('Are you sure you want to reset this template to the default?')){window.location='{$_conf['jrCore_base_url']}/{$url}/template_reset_save/id=" . $_tp[$tpl_name]['template_id'] . "'}");
            }
            else {
                $dat[4]['title'] = jrCore_page_button("m{$tpl_name}", 'modify', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/template_modify/template={$tpl_name}'");
                $dat[5]['title'] = '&nbsp;';
            }
            jrCore_page_table_row($dat);
        }
    }
    else {
        $dat = array();
        $dat[1]['title'] = '<p>There were no templates found to match your search criteria!</p>';
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();

    // Save Template Updates - this small hidden field needs to be here
    // otherwise the form will not work - this is due to the fact the checkbox
    // elements in the table were created outside of jrCore_form_field_create
    $_tmp = array(
        'name'     => "save_template_updates",
        'type'     => 'hidden',
        'required' => 'true',
        'validate' => 'onoff',
        'value'    => 'on'
    );
    jrCore_form_field_create($_tmp);

    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_show_module_info
 */
function jrCore_show_module_info($module, $_post, $_user, $_conf)
{
    global $_mods;

    // Generate our output
    jrCore_page_admin_tabs($module, 'info');

    // Setup our module jumper
    $subtitle = '<select name="module_jumper" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/admin/info'\">";
    $_tmpm = array();
    foreach ($_mods as $mod_dir => $_info) {
        $_tmpm[$mod_dir] = $_info['module_name'];
    }
    asort($_tmpm);
    foreach ($_tmpm as $mod_dir => $title) {
        if ($mod_dir == $_post['module']) {
            $subtitle .= '<option value="' . $_post['module_url'] . '" selected="selected"> ' . $title . "</option>\n";
        }
        else {
            $murl = jrCore_get_module_url($mod_dir);
            $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
        }
    }
    $subtitle .= '</select>';
    $_mta = jrCore_module_meta_data($module);
    jrCore_page_banner($_mta['name'], $subtitle);

    // See if we exist
    if (!is_dir(APP_DIR . "/modules/{$module}")) {
        jrCore_set_form_notice('error', 'Unable to find module files - re-install or delete from system');
    }
    // See if we are locked
    elseif (isset($_mta['locked']) && $_mta['locked'] == '1') {
        jrCore_set_form_notice('notice', 'This module is an integral part of the Core system and cannot be disabled or removed');
    }
    // See if we are disabled
    elseif (!jrCore_module_is_active($module)) {
        jrCore_set_form_notice('notice', 'This module is currently disabled');
    }

    jrCore_get_form_notice();

    // Show information about this module
    $pass = jrCore_get_option_image('pass');
    $fail = jrCore_get_option_image('fail');
    $_opt = array('description', 'version', 'requires', 'developer', 'license');
    $onum = count($_opt);
    if (is_file(APP_DIR . "/modules/{$module}/readme.html")) {
        $onum++;
    }
    $temp = '<table><tr><td rowspan="' . $onum . '" style="width:10%" class="p10 info_img"><img src="' . $_conf['jrCore_base_url'] . '/modules/' . $module . '/icon.png" width="128" height="128" alt="' . $_mta['name'] . '"></td>';
    foreach ($_opt as $k => $key) {

        $text = '';
        switch ($key) {

            case 'requires':
                if (isset($_mta['requires']{0})) {
                    $_req = explode(',', $_mta[$key]);
                    foreach ($_req as $rmod) {
                        $rmod = trim($rmod);
                        if (jrCore_module_is_active($rmod)) {
                            $text .= $pass . '&nbsp;' . $_mods[$rmod]['module_name'];
                        }
                        elseif (isset($_mods[$rmod])) {
                            $text .= $fail . '&nbsp;' . $_mods[$rmod]['module_name'] . '&nbsp;not active!';
                        }
                        else {
                            $text .= $fail . '&nbsp;' . $rmod . '&nbsp;not found!';
                        }
                    }
                }
                break;

            case 'license':
                $murl = jrCore_get_module_url($module);
                $text = "<a href=\"{$_conf['jrCore_base_url']}/{$murl}/license\" onclick=\"popwin('{$_conf['jrCore_base_url']}/{$murl}/license','license',800,500,'yes');return false\"><u>Click to View License</u></a>";
                break;

            default:
                $text = (isset($_mta[$key])) ? $_mta[$key] : 'undefined';
                break;
        }

        if (strlen($text) > 0) {
            if ($k > 0) {
                $temp .= '<tr>';
            }
            $temp .= '<td style="width:10%" class="page_table_cell p3 right">&nbsp;&nbsp;<b>' . $key . ':</b>&nbsp;&nbsp;</td><td style="width:80%" class="page_table_cell p3 left">' . $text . '</td>' . "\n";
            if ($k > 0) {
                $temp .= '</tr>';
            }
        }
    }
    // See if this module has a readme associated with it
    if (is_file(APP_DIR . "/modules/{$module}/readme.html")) {
        $text = jrCore_page_button('rm', 'module notes', "popwin('{$_conf['jrCore_base_url']}/modules/{$module}/readme.html','readme',600,500,'yes');");
        $temp .= '<tr><td class="page_table_cell p3 right">&nbsp;&nbsp;<b>notes:</b>&nbsp;&nbsp;</td><td>' . $text . '</td></tr>';
    }
    $temp .= '</table>';
    jrCore_page_custom($temp);

    // Module settings
    // Form init
    $_tmp = array(
        'submit_value' => 'save changes',
        'action'       => 'admin_save/info'
    );
    jrCore_form_create($_tmp);

    // Module URL
    $_tmp = array(
        'name'     => 'new_module_url',
        'label'    => 'module URL',
        'help'     => "The Module URL setting determines how the module will be accessed - i.e. {$_conf['jrCore_base_url']}/<b>{$_mods[$module]['module_url']}</b>/",
        'type'     => 'text',
        'value'    => $_mods[$module]['module_url'],
        'validate' => 'url_name'
    );
    jrCore_form_field_create($_tmp);

    // Module Category
    $_tmp = array(
        'name'     => 'new_module_category',
        'label'    => 'module category',
        'help'     => "If you would like to change the category for this module, enter a new category name here.<br><br><b>NOTE:</b> Category name must consist of letters, numbers and spaces only.",
        'type'     => 'text',
        'value'    => $_mods[$module]['module_category'],
        'validate' => 'printable'
    );
    jrCore_form_field_create($_tmp);

    // Module Active
    if (!isset($_mta['locked']) || $_mta['locked'] != '1') {
        $act = 'on';
        if (!jrCore_module_is_active($module)) {
            $act = 'off';
        }
        $_tmp = array(
            'name'     => 'module_active',
            'label'    => 'module active',
            'help'     => "You can enable/disable this module by setting this checking this option",
            'type'     => 'checkbox',
            'value'    => $act,
            'validate' => 'onoff'
        );
        jrCore_form_field_create($_tmp);

        // Show delete option if module directory is writable by the web user
        // and the module is currently disabled
        if ((!is_dir(APP_DIR . "/modules/{$module}") || is_writable(APP_DIR . "/modules/{$module}")) && $act === 'off') {
            $_tmp = array(
                'name'     => 'module_delete',
                'label'    => 'delete module',
                'help'     => "If you would like to remove this module from your system, check this option and save.<br><br><b>WARNING!</b> This will <b>permanently</b> delete the module files from your system!",
                'type'     => 'checkbox',
                'value'    => 'off',
                'validate' => 'onoff'
            );
            jrCore_form_field_create($_tmp);
        }
    }

    // See if we are showing developer information
    if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {

        // EVENTS

        // First - get any event triggers we are providing
        $_tmp = jrCore_get_flag('jrcore_event_triggers');
        $_out = array();
        if (isset($_tmp) && is_array($_tmp)) {
            foreach ($_tmp as $k => $v) {
                if (strpos($k, "{$module}_") === 0) {
                    $name = str_replace("{$module}_", '', $k);
                    $_out[$name] = array('desc' => $v);
                }
            }
        }

        // Next, find out how many listeners we have
        if (isset($_out) && is_array($_out) && count($_out) > 0) {
            $_tmp = jrCore_get_flag('jrcore_event_listeners');
            if (isset($_tmp) && is_array($_tmp)) {
                foreach ($_tmp as $k => $v) {
                    if (strpos($k, "{$module}_") === 0) {
                        $name = str_replace("{$module}_", '', $k);
                        $_out[$name]['listeners'] = implode('<br>', $v);
                    }
                }
            }
        }

        if (isset($_out) && is_array($_out) && count($_out) > 0) {
            ksort($_out);
            jrCore_page_section_header('available event triggers');
            $dat = array();
            $dat[1]['title'] = 'trigger name';
            $dat[1]['width'] = '16%';
            $dat[2]['title'] = 'description';
            $dat[2]['width'] = '56%';
            $dat[3]['title'] = 'listeners';
            $dat[3]['width'] = '28%';
            jrCore_page_table_header($dat);

            foreach ($_out as $event => $_params) {
                $dat = array();
                $dat[1]['title'] = $event;
                $dat[2]['title'] = (isset($_params['desc'])) ? $_params['desc'] : '-';
                $dat[2]['class'] = 'center';
                $dat[3]['title'] = (isset($_params['listeners'])) ? $_params['listeners'] : '-';
                $dat[3]['class'] = 'center';
                jrCore_page_table_row($dat);
            }
            jrCore_page_table_footer();
        }
    }
    jrCore_page_set_no_header_or_footer();
    return jrCore_page_display(true);
}

/**
 * jrCore_dashboard_bigview
 */
function jrCore_dashboard_bigview($_post, $_user, $_conf)
{
    $dat = array();
    $dat[1]['title'] = 'total profiles';
    $dat[1]['width'] = '25%;';
    $dat[2]['title'] = 'signups today';
    $dat[2]['width'] = '25%;';
    $dat[3]['title'] = 'users online';
    $dat[3]['width'] = '25%;';
    $dat[4]['title'] = 'queue depth';
    $dat[4]['width'] = '25%;';
    jrCore_page_table_header($dat, 'bigtable');

    // Profiles
    $dat = array();
    $dat[1]['title'] = number_format(jrCore_db_number_rows('jrProfile', 'item'));
    $dat[1]['class'] = 'bignum bignum1';

    // Sign ups today
    $num = 0;
    $old = strtotime(strftime('%m/%d/%y 00:00'));
    $tbl = jrCore_db_table_name('jrProfile', 'item_key');
    $req = "SELECT COUNT(`_item_id`) AS signups FROM {$tbl} WHERE `key` = '_created' AND `value` > {$old}";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt) && isset($_rt['signups']) && jrCore_checktype($_rt['signups'], 'number_nz')) {
        $num = (int)$_rt['signups'];
    }
    $dat[2]['title'] = number_format($num);
    $dat[2]['class'] = 'bignum bignum2';

    // Users Online
    $num = 0;
    $upd = (time() - 900);
    $tbl = jrCore_db_table_name('jrUser', 'session');
    $req = "SELECT COUNT(DISTINCT(session_user_ip)) AS online FROM {$tbl} WHERE session_updated > {$upd}";
    $_rt = jrCore_db_query($req, 'SINGLE');
    if (isset($_rt) && is_array($_rt) && isset($_rt['online']) && jrCore_checktype($_rt['online'], 'number_nz')) {
        $num = (int)$_rt['online'];
    }
    $dat[3]['title'] = number_format($num);
    $dat[3]['class'] = 'bignum bignum3';

    // Queue Depth
    $dat[4]['title'] = number_format(jrCore_db_number_rows('jrCore', 'queue'));
    $dat[4]['class'] = 'bignum bignum4';
    jrCore_page_table_row($dat);
    jrCore_page_table_footer();

    // System Load and Memory
    $dat = array();
    $dat[1]['title'] = 'memory used';
    $dat[1]['width'] = '25%;';
    $dat[2]['title'] = 'disk usage';
    $dat[2]['width'] = '25%;';
    $dat[3]['title'] = 'CPU count';
    $dat[3]['width'] = '25%;';
    $dat[4]['title'] = '5 minute load';
    $dat[4]['width'] = '25%;';
    jrCore_page_table_header($dat, 'bigtable');

    $_rm = jrCore_get_system_memory();
    $dat = array();
    $dat[1]['title'] = $_rm['percent_used'] . '%';
    $dat[1]['class'] = 'bignum ' . $_rm['class'];

    $_ds = jrCore_get_disk_usage();
    $dat[2]['title'] = $_ds['percent_used'] . '%';
    $dat[2]['class'] = 'bignum ' . $_ds['class'];

    $_pc = jrCore_get_proc_info();
    if (isset($_pc) && is_array($_pc)) {
        $dat[3]['title'] = count($_pc);
        $dat[3]['class'] = 'bignum bigsystem-inf';
        $cpu_num = count($_pc);
    }
    else {
        $dat[3]['title'] = '?';
        $dat[3]['class'] = 'bignum bigsystem-inf';
        $cpu_num = 1;
    }

    $_ll = jrCore_get_system_load($cpu_num);
    if (isset($_ll) && is_array($_ll)) {
        $dat[4]['title'] = $_ll[5]['level'];
        $dat[4]['class'] = 'bignum ' . $_ll[5]['class'];
    }
    else {
        $dat[4]['title'] = '?';
        $dat[4]['class'] = 'bignum bigsystem-inf';
    }
    jrCore_page_table_row($dat);

    $dat = array();
    $dat[1]['title'] = 'of ' . jrCore_format_size($_rm['memory_total']);
    $dat[1]['class'] = 'center';
    $dat[2]['title'] = 'of ' . jrCore_format_size($_ds['disk_total']);
    $dat[2]['class'] = 'center';

    if (isset($_pc) && is_array($_pc)) {
        $dat[3]['title'] = '@ ' . $_pc[1]['mhz'];
    }
    else {
        $dat[3]['title'] = '?';
    }
    $dat[3]['class'] = 'center';

    if (isset($_ll) && is_array($_ll)) {
        $dat[4]['title'] = '15 minute: ' . $_ll[15]['level'];
    }
    else {
        $dat[4]['title'] = '15 minute: ?';
    }
    $dat[4]['class'] = 'center';
    jrCore_page_table_row($dat);

    jrCore_page_table_footer();
}

/**
 * jrCore_dashboard_pending
 */
function jrCore_dashboard_pending($_post, $_user, $_conf)
{
    // Get our pending items
    $tbl = jrCore_db_table_name('jrCore', 'pending');
    $req = "SELECT * FROM {$tbl} WHERE pending_module != 'jrAction'";
    $_ex = false;
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $_post['search_string'] = trim(urldecode($_post['search_string']));
        $str = jrCore_db_escape($_post['search_string']);
        $req .= " AND pending_data LIKE '%{$str}%' ";
        $_ex = array('search_string' => $_post['search_string']);
    }
    $req .= 'ORDER BY pending_id ASC';

    // find how many lines we are showing
    if (!isset($_post['p']) || !jrCore_checktype($_post['p'], 'number_nz')) {
        $_post['p'] = 1;
    }
    $_rt = jrCore_db_paged_query($req, $_post['p'], 12, 'NUMERIC');

    // start our html output
    jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$_post['module_url']}/pending");

    $dat = array();
    $dat[1]['title'] = '<input type="checkbox" class="form_checkbox" onclick="$(\'.pending_checkbox\').prop(\'checked\',$(this).prop(\'checked\'));">';
    $dat[1]['width'] = '1%;';
    $dat[2]['title'] = 'date';
    $dat[2]['width'] = '10%;';
    $dat[3]['title'] = 'item';
    $dat[3]['width'] = '36%;';
    $dat[4]['title'] = 'profile';
    $dat[4]['width'] = '12%;';
    $dat[5]['title'] = 'user';
    $dat[5]['width'] = '12%;';
    $dat[6]['title'] = 'approve';
    $dat[6]['width'] = '3%;';
    $dat[7]['title'] = 'reject';
    $dat[7]['width'] = '3%;';
    $dat[8]['title'] = 'delete';
    $dat[8]['width'] = '3%;';
    jrCore_page_table_header($dat);
    unset($dat);

    $url = jrCore_get_module_url('jrCore');
    if (isset($_rt['_items']) && is_array($_rt['_items'])) {

        foreach ($_rt['_items'] as $_pend) {
            $_data = json_decode($_pend['pending_data'], true);
            $murl = jrCore_get_module_url($_pend['pending_module']);
            $dat = array();
            $dat[1]['title'] = '<input type="checkbox" class="form_checkbox pending_checkbox" name="' . $_pend['pending_item_id'] . '">';
            $dat[2]['title'] = jrCore_format_time($_pend['pending_created']);
            $dat[2]['class'] = 'nowrap';
            $dat[3]['title'] = "<a href=\"{$_conf['jrCore_base_url']}/{$_data['user']['profile_url']}/{$murl}/{$_pend['pending_item_id']}\" target=\"_blank\">{$_data['user']['profile_url']}/{$murl}/{$_pend['pending_item_id']}</a>";
            $dat[4]['title'] = $_data['user']['profile_name'];
            $dat[4]['class'] = 'center';
            $dat[5]['title'] = $_data['user']['user_name'];
            $dat[5]['class'] = 'center';
            $dat[6]['title'] = jrCore_page_button("a{$_pend['pending_id']}", 'approve', "window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_approve/id={$_pend['pending_item_id']}'");
            $dat[7]['title'] = jrCore_page_button("r{$_pend['pending_id']}", 'reject', "window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_reject/id={$_pend['pending_item_id']}'");
            $dat[8]['title'] = jrCore_page_button("d{$_pend['pending_id']}", 'delete', "if(confirm('Are you sure you want to delete this item? No notice will be sent.')){window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_delete/id={$_pend['pending_item_id']}'}");
            jrCore_page_table_row($dat);
        }

        $sjs = "var v = $('input:checkbox.pending_checkbox:checked').map(function(){ return this.name; }).get().join(',')";
        $tmp = jrCore_page_button("all", 'approve checked', "{$sjs};window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_approve/id='+ v");
        $tmp .= '&nbsp;' . jrCore_page_button("delete", 'delete checked', "if (confirm('Are you sure you want to delete all checked items?')){ {$sjs};window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_delete/id='+ v }");

        $dat = array();
        $dat[1]['title'] = $tmp;
        jrCore_page_table_row($dat);

        jrCore_page_table_pager($_rt, $_ex);
    }
    else {
        $dat = array();
        if (!empty($_post['search_string'])) {
            $dat[1]['title'] = '<p>There were no Pending Items found to match your search criteria</p>';
        }
        else {
            $dat[1]['title'] = '<p>There does not appear to be any Pending Items</p>';
        }
        $dat[1]['class'] = 'center';
        jrCore_page_table_row($dat);
    }
    jrCore_page_table_footer();
}

/**
 * Display DS Browser
 * @param $mode string dashboard|admin where browser is being run from
 * @param $_post array Global $_post
 * @param $_user array Viewing user array
 * @param $_conf array Global config
 * @return bool
 */
function jrCore_dashboard_browser($mode, $_post, $_user, $_conf)
{
    global $_mods;
    // Create a Quick Jump list for custom forms for this module
    $j_url = 'browser';
    if (strpos(jrCore_get_local_referrer(), 'dashboard')) {
        $j_url = 'dashboard/browser';
    }
    $subtitle = '<select name="data_browser" class="form_select form_select_item_jumper" onchange="var v=this.options[this.selectedIndex].value;window.location=\'' . $_conf['jrCore_base_url'] . "/'+ v +'/{$j_url}'\">\n";
    $_tmpm = array();
    foreach ($_mods as $mod_dir => $_inf) {
        if (isset($_inf['module_prefix']) && strlen($_inf['module_prefix']) > 0) {
            $_tmpm[$mod_dir] = $_inf['module_name'];
        }
    }
    asort($_tmpm);
    foreach ($_tmpm as $module => $title) {
        $murl = jrCore_get_module_url($module);
        if ($module == $_post['module']) {
            $subtitle .= '<option value="' . $murl . '" selected="selected"> ' . $title . "</option>\n";
        }
        else {
            $subtitle .= '<option value="' . $murl . '"> ' . $title . "</option>\n";
        }
    }
    $subtitle .= '</select>';

    $val = '';
    if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
        $val = $_post['search_string'];
    }

    jrCore_page_banner('data browser', $subtitle);
    jrCore_get_form_notice();
    if (isset($mode) && $mode == 'dashboard') {
        jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$_post['module_url']}/dashboard/browser", $val);
    }
    else {
        jrCore_page_search('search', "{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser", $val);
    }

    // See if this module has registered it's own Browser
    $_tmp = jrCore_get_registered_module_features('jrCore', 'data_browser');
    if (isset($_tmp["{$_post['module']}"])) {
        $func = array_keys($_tmp["{$_post['module']}"]);
        $func = (string) reset($func);
        if (function_exists($func)) {
            $func($_post, $_user, $_conf);
        }
        else {
            jrCore_page_notice('error', "invalid custom browser function defined for {$_post['module']}");
        }
    }
    else {

        // get our items
        $_pr = array(
            'search'         => array(
                '_created > 0'
            ),
            'pagebreak'      => 6,
            'page'           => 1,
            'order_by'       => array(
                '_item_id' => 'desc'
            ),
            'skip_triggers'  => true,
            'ignore_pending' => true,
            'privacy_check'  => false
        );
        if (isset($_post['p']) && jrCore_checktype($_post['p'], 'number_nz')) {
            $_pr['page'] = (int)$_post['p'];
        }
        // See we have a search condition
        $_ex = false;
        if (isset($_post['search_string']) && strlen($_post['search_string']) > 0) {
            $_ex = array('search_string' => $_post['search_string']);
            // Check for passing in a specific key name for search
            if (strpos($_post['search_string'], ':')) {
                list($sf, $ss) = explode(':', $_post['search_string'], 2);
                $_post['search_string'] = $ss;
                $_pr['search'][] = "{$sf} like {$ss}";
            }
            else {
                $_pr['search'][] = "% like {$_post['search_string']}";
            }
        }
        $_us = jrCore_db_search_items($_post['module'], $_pr);

        // Start our output
        $dat = array();
        $dat[1]['title'] = 'id';
        $dat[1]['width'] = '5%';
        $dat[2]['title'] = 'info';
        $dat[2]['width'] = '78%';
        $dat[3]['title'] = 'modify';
        $dat[3]['width'] = '2%';
        jrCore_page_table_header($dat);

        if (isset($_us['_items']) && is_array($_us['_items'])) {
            foreach ($_us['_items'] as $_itm) {
                $dat = array();
                switch ($_post['module']) {
                    case 'jrUser':
                        $iid = $_itm['_user_id'];
                        break;
                    case 'jrProfile':
                        $iid = $_itm['_profile_id'];
                        break;
                    default:
                        $iid = $_itm['_item_id'];
                        break;
                }
                $pfx = jrCore_db_get_prefix($_post['module']);
                $dat[1]['title'] = $iid;
                $dat[1]['class'] = 'center';
                $_tm = array();
                ksort($_itm);
                $master_user = false;
                $admin_user = false;
                $_rep = array("\n", "\r", "\n\r");
                foreach ($_itm as $k => $v) {
                    if (strpos($k, $pfx) !== 0) {
                        continue;
                    }
                    switch ($k) {
                        case '_user_id':
                        case '_profile_id':
                        case '_item_id':
                        case 'user_password':
                        case 'user_old_password':
                        case 'user_validate':
                            break;
                        case 'user_group':
                            switch ($v) {
                                case 'master':
                                    $master_user = true;
                                    break;
                                case 'admin':
                                    $admin_user = true;
                                    break;
                            }
                        // NOTE: We fall through on purpose here!
                        default:
                            if (isset($v) && is_array($v)) {
                                $v = json_encode($v);
                            }
                            if (is_numeric($v) && strlen($v) === 10) {
                                $v = jrCore_format_time($v);
                            }
                            if (strlen($v) > 90) {
                                $v = substr($v, 0, 90) . '...';
                            }
                            if (isset($_post['search_string'])) {
                                // See if we are searching a specific field
                                if (isset($sf)) {
                                    if ($k == $sf) {
                                        $v = jrCore_hilight_string($v, str_replace('%', '', $_post['search_string']));
                                    }
                                }
                                else {
                                    $v = jrCore_hilight_string($v, str_replace('%', '', $_post['search_string']));
                                }
                            }
                            $v = strip_tags(str_replace($_rep, ' ', $v));
                            $_tm[] = "<span class=\"ds_browser_key\">{$k}:</span> <span class=\"ds_browser_value\">{$v}</span>";
                            break;
                    }
                }
                $dat[3]['title'] = implode('<br>', $_tm);
                $_att = array(
                    'style' => 'width:70px;'
                );

                switch ($_post['module']) {
                    case 'jrUser':
                        $url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/account/user_id={$iid}";
                        break;
                    case 'jrProfile':
                        $url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/settings/profile_id={$iid}";
                        break;
                    default:
                        $url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser_item_update/id={$iid}";
                        break;
                }
                $dat[4]['title'] = jrCore_page_button("m{$iid}", 'modify', "window.location='{$url}'", $_att) . '<br><br>';

                // Check and see if we are browsing User Accounts - if so, admin users cannot delete
                // admin or master accounts.  Master cannot delete other master accounts.
                $add = false;
                if (jrUser_is_master() && !$master_user) {
                    $add = true;
                }
                elseif (jrUser_is_admin() && !$master_user && !$admin_user) {
                    $add = true;
                }
                if ($add) {
                    $dat[4]['title'] .= jrCore_page_button("d{$iid}", 'delete', "if (confirm('Are you sure you want to delete this item? The item will be permanently DELETED!')){window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/browser_item_delete/id={$iid}'}", $_att);
                }
                $dat[4]['class'] = 'center';
                jrCore_page_table_row($dat);
            }
            jrCore_page_table_pager($_us, $_ex);
        }
        else {
            $dat = array();
            if (isset($_post['search_string'])) {
                $dat[1]['title'] = '<p>No Results found for your Search Criteria.</p>';
            }
            else {
                $dat[1]['title'] = '<p>No Items found in DataStore!</p>';
            }
            $dat[1]['class'] = 'center';
            jrCore_page_table_row($dat);
        }
        jrCore_page_table_footer();
    }
}

function jrCore_master_log_tabs($active)
{
    global $_conf, $_post;
    $_tabs = array();
    $_tabs['activity'] = array(
        'label' => 'activity log',
        'url'   => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/activity_log"
    );
    $_tabs['debug'] = array(
        'label' => 'debug log',
        'url'   => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/debug_log"
    );
    $_tabs['error'] = array(
        'label' => 'PHP error log',
        'url'   => "{$_conf['jrCore_base_url']}/{$_post['module_url']}/php_error_log"
    );
    $_tabs[$active]['active'] = true;
    jrCore_page_tab_bar($_tabs);
}

/**
 * jrCore_chained_select_get
 */
function view_jrCore_chained_select_get($_post, $_user, $_conf)
{
    // $_post['_1'] = base_name
    // $_post['_2'] = level
    // $_post['_3'] = values string
    // $_post['_4'] = name
    // $_post['_5'] = prefix

    // If prefix, set up for jrChainedSelect module
    if ($_post['_5'] != 'null') {
        $_post['_1'] = "cs_{$_post['_5']}";
        $_post['_4'] = "jrChainedSelect";
    }
    if (isset($_post['_1']) && $_post['_1'] != '' && jrCore_checktype($_post['_2'], 'number_nn')) {
        $_out = array();
        $_sc = array();
        $name = "{$_post['_1']}_{$_post['_2']}";
        $_sc['limit'] = 10000;
        $_sc['search'][] = "_item_id > 0";
        $_sc['group_by'] = "{$_post['_1']}_{$_post['_2']}";
        $_sc['order_by'] = array("{$_post['_1']}_{$_post['_2']}" => 'ASC');
        $_sc['return_keys'] = array($name);
        if ($_post['_3'] != 'null') {
            $_value = explode('|', $_post['_3']);
        }
        if (isset($_value) && is_array($_value)) {
            foreach ($_value as $k => $v) {
                if ($k > 0) {
                    $l = $k - 1;
                    $_sc['search'][] = "{$_post['_1']}_{$l} = {$_value[$l]}";
                }
            }
        }
        $_rt = jrCore_db_search_items($_post['_4'], $_sc);
        if (isset($_rt) && is_array($_rt)) {
            foreach ($_rt['_items'] as $_v) {
                $val = trim($_v[$name]);
                if ($val != '') {
                    $_out[$val] = $val;
                }
            }
        }
        if (!isset($_out['-'])) {
            $_out = array_merge(array('-' => '-'), $_out);
        }
        return json_encode(array('OK' => 1, 'VALUE' => json_encode($_out)));
    }
    else {
        return json_encode(array('error' => 'System error when getting options'));
    }
}

/**
 * jrCore_chained_select_set
 */
function view_jrCore_chained_select_set($_post, $_user, $_conf)
{
    // $_post['_1'] = base_name
    // $_post['_2'] = level
    // $_post['_3'] = values string
    // $_post['_4'] = name
    // $_post['_5'] = selected value
    // $_post['_6'] = prefix

    // If prefix, set up for jrChainedSelect module
    if ($_post['_6'] != 'null') {
        $_post['_1'] = "cs_{$_post['_6']}";
        $_post['_4'] = "jrChainedSelect";
    }
    // Update flag
    $_value = jrCore_get_flag("jrCore_chained_select_{$_post['_1']}_value");
    $_value[$_post['_2']] = $_post['_5'];
    jrCore_set_flag("jrCore_chained_select_{$_post['_1']}_value", $_value);
    $_post['_3'] = '';
    for ($i = 0; $i <= $_post['_2']; $i++) {
        if (isset($_value[$i])) {
            $_post['_3'] .= $_value[$i] . '|';
        }
    }
    $_post['_3'] = substr($_post['_3'], 0, -1);
    if (isset($_post['_1']) && $_post['_1'] != '' && jrCore_checktype($_post['_2'], 'number_nn')) {
        $_out = array();
        $l = $_post['_2'] + 1;
        $name = "{$_post['_1']}_{$l}";
        $_sc = array();
        $_sc['limit'] = 10000;
        $_sc['search'][] = "_item_id > 0";
        $_sc['group_by'] = "{$_post['_1']}_{$l}";
        $_sc['order_by'] = array("{$_post['_1']}_{$l}" => 'ASC');
        $_sc['return_keys'] = array($name);
        if ($_post['_3'] != 'null') {
            $_value = explode('|', $_post['_3']);
        }
        $_value[$_post['_2']] = $_post['_5'];
        if (isset($_value) && is_array($_value)) {
            foreach ($_value as $k => $v) {
                $_sc['search'][] = "{$_post['_1']}_{$k} = {$_value[$k]}";
            }
        }
        $_rt = jrCore_db_search_items($_post['_4'], $_sc);
        if (isset($_rt) && is_array($_rt)) {
            foreach ($_rt['_items'] as $_v) {
                $val = trim($_v[$name]);
                if ($val != '') {
                    $_out[$val] = $val;
                }
            }
        }
        if (!isset($_out['-'])) {
            $_out = array_merge(array('-' => '-'), $_out);
        }
        return json_encode(array('OK' => 1, 'VALUE' => json_encode($_out)));
    }
    else {
        return json_encode(array('error' => 'System error when getting options'));
    }
}
