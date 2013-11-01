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

// Define our base dir
define('APP_DIR', dirname(dirname(dirname(__FILE__))));

// prep our PHP environment
ini_set('session.auto_start', 0);
ini_set('session.use_trans_sid', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', APP_DIR . '/data/logs/error_log');
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

// Disable bad ideas
// deprecated @ 5.3.0, not in 5.4.x so @ here
@ini_set('magic_quotes_runtime', 0);
if (ini_get('register_globals')) {
    foreach ($_REQUEST as $k => $v) {
        unset($$k);
    }
    if (isset($_FILES) && is_array($_FILES)) {
        foreach ($_FILES as $k => $v) {
            unset($$k);
        }
    }
}

// Bring in the core
require APP_DIR . '/modules/jrCore/include.php';

// Cleanup $_REQUEST
// deprecated @ 5.3.0, not in 5.4.x so @ here
if (@get_magic_quotes_gpc()) {
    $_REQUEST = jrCore_stripslashes($_REQUEST);
}

$_mods = array();
$_conf = array();
$_urls = array();

// Init Core - note that $_mods, $_conf and $_urls will be set after
jrCore_init();

// Parse out URL
$_post = jrCore_parse_url();

// Start User session
$_user = jrUser_session_start();

//------------------------------------
// Maintenance Mode Check
//------------------------------------
if (jrCore_is_maintenance_mode($_conf, $_post)) {
    $out = jrCore_parse_template('maintenance.tpl', $_post, 'jrCore');
}

//------------------------------------
// System Index
//------------------------------------
elseif (!isset($_post['module']{0}) && !isset($_post['option']{0}) && !isset($_post['module_url'])) {

    // Check cache
    jrUser_load_lang_strings();
    $key = $_conf['jrCore_active_skin'] . '-index.tpl';
    $out = jrCore_is_cached('jrCore', $key);
    if ($out) {
        $out .= "\n<!--c-->";
    }
    else {
        $out = jrCore_trigger_event('jrCore', 'index_template', $_post);
        // Pick up header elements set by plugins
        $_tmp = jrCore_get_flag('jrcore_page_elements');
        if ($_tmp) {
            unset($_tmp['page']);
            $_post = array_merge($_tmp, $_post);
        }
        if (!isset($out) || is_array($out) || strlen($out) === 0) {
            $out = jrCore_parse_template('index.tpl', $_post);
        }
        jrCore_add_to_cache('jrCore', $key, $out);
    }
    unset($_SESSION['jruser_save_location']);
}

//------------------------------------
// Skin Template Override
//------------------------------------
elseif (isset($_post['module']{0}) && isset($_post['option']{0}) && is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/override_{$_post['module']}_{$_post['option']}.tpl")) {

    // Pick up header elements set by plugins
    $_tmp = jrCore_get_flag('jrcore_page_elements');
    if ($_tmp) {
        unset($_tmp['page']);
        $_post = array_merge($_tmp, $_post);
    }
    // This is a SKIN template call
    $_st = array(
        'template_path' => APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/override_{$_post['module']}_{$_post['option']}.tpl",
        'template_name' => "override_{$_post['module']}_{$_post['option']}.tpl"
    );
    jrCore_trigger_event('jrCore', 'skin_template', $_post, $_st);
    $out = jrCore_parse_template("override_{$_post['module']}_{$_post['option']}.tpl", $_post);
}

else {

    //------------------------------------
    // Module controller
    //------------------------------------
    if (isset($_post['module']{0}) && isset($_mods["{$_post['module']}"])) {

        // If we are NOT the admin user, and a request comes in
        // for an inactive module, show error
        if (!jrCore_module_is_active($_post['module']) && !jrUser_is_master()) {
            jrCore_page_not_found();
        }

        // Trigger our module view
        jrCore_trigger_event('jrCore', 'module_view', $_post);

        // Our order of precedence is:
        // - Template Override @ Skin/override_[module]_[option].tpl
        // - EXACT MATCH on view_[module]_[option]
        // - MAGIC VIEW on view_magic_[option]
        // - Template @ Skin/[module]_[option].tpl
        // - Default view view_[module]_default
        // - Not Found (404.tpl)

        // Check for module controlled View (exact match)
        if (isset($_post['option']{0}) && is_file(APP_DIR . "/modules/{$_post['module']}/index.php")) {

            $func = "view_{$_post['module']}_{$_post['option']}";
            if (!function_exists($func)) {
                ob_start();
                require_once APP_DIR . "/modules/{$_post['module']}/index.php";
                ob_end_clean();
            }
            // If it exists, run it - otherwise fall through for other handlers
            if (function_exists($func)) {
                $out = jrCore_run_module_view_function($func);
            }
        }

        // Check for registered Magic View function
        if (isset($_post['option']{0}) && !isset($out)) {

            $_vw = jrCore_get_registered_module_features('jrCore', 'magic_view');
            if (isset($_vw) && is_array($_vw)) {
                foreach ($_vw as $m => $_e) {
                    if (isset($_e["{$_post['option']}"])) {
                        $func = $_e["{$_post['option']}"];
                        // Bring in magic view module's view functions
                        if (!function_exists($func)) {
                            ob_start();
                            require_once APP_DIR . "/modules/{$m}/index.php";
                            ob_end_clean();
                        }
                        if (function_exists($func)) {
                            $out = jrCore_run_module_view_function($func);
                        }
                        else {
                            // log error and show 404
                            jrCore_logger('CRI', "magic view function: {$func} registered in module: {$m} does not exist!");
                            jrCore_page_not_found();
                        }
                        break;
                    }
                }
            }
        }

        // Skin Template
        if (isset($_post['option']{0}) && !isset($out)) {
            // Check for Skin Template specific to this module/view
            if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['module']}_{$_post['option']}.tpl")) {
                $out = jrCore_parse_template("{$_post['module']}_{$_post['option']}.tpl", $_post);
                unset($_SESSION['jruser_save_location']);
            }
        }

        // Module Default View
        if (!isset($out)) {
            $func = "view_{$_post['module']}_default";
            if (function_exists($func)) {
                // default view function
                $out = jrCore_run_module_view_function($func);
            }
            elseif (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['module']}_index.tpl")) {
                // Skin overriding module index
                $out = jrCore_parse_template('index.tpl', $_post, $_post['module']);
            }
            elseif (is_file(APP_DIR . "/modules/{$_post['module']}/templates/index.tpl")) {
                // default module index
                $out = jrCore_parse_template('index.tpl', $_post, $_post['module']);
            }
            else {
                // page/module/option not found
                jrCore_page_not_found();
            }
        }
    }

    //------------------------------------
    // Template / Profile
    //------------------------------------
    if (!isset($out) || strlen($out) === 0) {

        // Fall through after modules have had their chance means
        // we are loading a skin or profile index. Note that skin
        // templates always take precedence.
        if (isset($_post['module_url']{0}) && is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['module_url']}.tpl")) {
            // This is a SKIN template call
            $_st = array(
                'template_path' => APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_post['module_url']}.tpl",
                'template_name' => "{$_post['module_url']}.tpl",
            );
            jrCore_trigger_event('jrCore', 'skin_template', $_post, $_st);

            jrUser_load_lang_strings();
            $key = "{$_conf['jrCore_active_skin']}-" . json_encode($_post);
            $out = jrCore_is_cached('jrCore', $key);
            if ($out) {
                $out .= "\n<!--c-->";
            }
            else {
                // Pick up header elements set by plugins
                $_tmp = jrCore_get_flag('jrcore_page_elements');
                if ($_tmp) {
                    unset($_tmp['page']);
                    $_post = array_merge($_tmp, $_post);
                }
                $out = jrCore_parse_template("{$_post['module_url']}.tpl", $_post);
                jrCore_add_to_cache('jrCore', $key, $out);
            }
            unset($_SESSION['jruser_save_location']);
        }
        // Profile...
        else {
            jrCore_trigger_event('jrCore', 'profile_template', $_post);
            // This is a profile call - load profile info and display
            $out = jrProfile_show_profile($_post, $_user, $_conf);
        }
    }
}

// view results trigger
$out = jrCore_trigger_event('jrCore', 'view_results', $out);

// Send response
if (!headers_sent()) {
    $cont = false;
    $_tmp = jrCore_get_flag('jrcore_set_custom_header');
    if (isset($_tmp) && is_array($_tmp)) {
        foreach ($_tmp as $header) {
            header($header);
            if (stripos($header, 'Content-Type') === 0) {
                $cont = true;
            }
        }
    }
    header('Connection: close');
    if (!$cont) {
        header("Content-Type: text/html; charset=utf-8");
    }
}

// Required for the process_exit (shutdown function) to detach properly from the client
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
ini_set('zlib.output_compression', 0);
ini_set('implicit_flush', 1);

// Send output
ignore_user_abort();
@ob_end_clean();
ob_start();
echo $out;
header('Content-Length: ' . ob_get_length());
ob_end_flush();
flush();

// Process Exit trigger
jrCore_trigger_event('jrCore', 'process_exit', $_post);

// Check for daily maintenance run
$now = (time() + date_offset_get(new DateTime));
$now = gmstrftime('%Y%m%d', $now);
if (!isset($_conf['jrCore_last_daily_maint_run']) || $_conf['jrCore_last_daily_maint_run'] < $now) {
    // Make sure first caller gets it - we're going to set this
    // lock up to give daily maintenance up to 10 minutes to run
    ini_set('max_execution_time', 1200);
    $cdr = jrCore_get_module_cache_dir('jrCore');
    if (!is_file("{$cdr}/daily.{$now}")) {

        // Set lock file so we can try hard to only run this once...
        jrCore_write_to_file("{$cdr}/daily.{$now}", time());

        jrCore_set_flag('jr_daily_maintenance_is_active', 1);
        jrCore_logger('INF', 'daily_maintenance started', false);

        jrCore_set_setting_value('jrCore', 'last_daily_maint_run', $now);
        jrCore_delete_all_cache_entries('jrCore', 0);
        jrCore_trigger_event('jrCore', 'daily_maintenance', $_post);

        // Cleanup and exit
        unlink("{$cdr}/daily.{$now}");
        jrCore_logger('INF', 'daily_maintenance completed', false);
    }
}
?>
