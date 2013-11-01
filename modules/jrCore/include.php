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

/**
 * jrCore_meta
 * Information about the Jamroom Core
 *
 * @return array
 */
function jrCore_meta()
{
    $_tmp = array(
        'name'        => 'System Core',
        'url'         => 'core',
        'version'     => '5.0.2',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Provides low level functionality for all system operations',
        'category'    => 'core',
        'locked'      => true,
        'activate'    => true
    );
    return $_tmp;
}

/**
 * jrCore_init
 *
 * @return bool
 */
function jrCore_init()
{
    global $_conf, $_urls, $_mods;

    ob_start();
    mb_internal_encoding('UTF-8');

    // Some core config
    $_conf['jrCore_base_url'] = (isset($_conf['jrCore_base_url']{0})) ? $_conf['jrCore_base_url'] : jrCore_get_base_url();
    $_conf['jrCore_base_dir'] = APP_DIR;

    // Bring in MySQL config
    if (!@include_once APP_DIR . '/data/config/config.php') {
        jrCore_location('install.php');
    }

    // Check for SSL...
    if (strpos($_conf['jrCore_base_url'], 'http:') === 0 && !empty($_SERVER['HTTPS'])) {
        $_conf['jrCore_base_url'] = 'https://' . substr($_conf['jrCore_base_url'], 7);
    }

    // Core magic views
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'admin', 'view_jrCore_admin');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'admin_save', 'view_jrCore_admin_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'skin_admin', 'view_jrCore_skin_admin');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'skin_admin_save', 'view_jrCore_skin_admin_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'stream', 'view_jrCore_stream_file');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'download', 'view_jrCore_download_file');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'template_modify', 'view_jrCore_template_modify');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'template_modify_save', 'view_jrCore_template_modify_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'browser', 'view_jrCore_browser');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'browser_item_update', 'view_jrCore_browser_item_update');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'browser_item_update_save', 'view_jrCore_browser_item_update_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'browser_item_delete', 'view_jrCore_browser_item_delete');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'license', 'view_jrCore_license');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'form_designer', 'view_jrCore_form_designer');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'form_designer_save', 'view_jrCore_form_designer_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'form_field_update', 'view_jrCore_form_field_update');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'form_field_update_save', 'view_jrCore_form_field_update_save');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'dashboard', 'view_jrCore_dashboard');
    jrCore_register_module_feature('jrCore', 'magic_view', 'jrCore', 'item_display_order', 'view_jrCore_item_display_order');

    // Core tool views
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrCore', 'activity_log', array('Activity Logs', 'Browse the system Activity, Debug and Error Logs'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrCore', 'cache_reset', array('Reset Caches', 'Reset database and filesystem caches'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrCore', 'integrity_check', array('Integrity Check', 'Validate, Optimize and Repair module and skin installs'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrCore', 'skin_menu', array('Skin Menu Editor', 'Customize the items and options that appear in the main Skin Menu'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrCore', 'system_check', array('System Check', 'Display information about your System and installed modules'));

    // Our default view for admins
    jrCore_register_module_feature('jrCore', 'default_admin_view', 'jrCore', 'activity_log');

    // Core checktype plugins
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'allowed_html');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'core_string');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'user_name');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'date');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'domain');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'email');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'float');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'hex');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'ip_address');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'is_true');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'md5');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'multi_word');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'not_empty');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'number');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'number_nn');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'number_nz');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'onoff');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'price');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'printable');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'sha1');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'string');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'url');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'url_name');
    jrCore_register_module_feature('jrCore', 'checktype', 'jrCore', 'yesno');

    // Core form fields supported
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'hidden');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'checkbox');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'checkbox_spambot');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'date');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'datetime');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'file');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'editor');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'optionlist');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'password');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'radio');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'select');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'select_and_text');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'select_multiple');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'text');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'textarea');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'custom');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'chained_select');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'chained_select_and_text');
    jrCore_register_module_feature('jrCore', 'form_field', 'jrCore', 'live_search');

    // Bring in core javascript
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'jquery-1.8.3.min.js');
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'jquery.simplemodal.1.4.3.min.js');
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'jquery.lightbox.min.js');
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'jquery.livesearch.min.js');
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'fileuploader.js');
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', "jquery.jplayer.js");
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', "jquery.jplayer.playlist.min.js");
    jrCore_register_module_feature('jrCore', 'javascript', 'jrCore', 'jrCore.js');

    // When javascript is registered, we have a function that is called
    jrCore_register_module_feature_function('jrCore', 'javascript', 'jrCore_enable_external_javascript');

    // Register our core CSS
    jrCore_register_module_feature('jrCore', 'css', 'jrCore', 'jrCore.css');
    jrCore_register_module_feature('jrCore', 'css', 'jrCore', 'jrCore_dashboard.css');
    jrCore_register_module_feature('jrCore', 'css', 'jrCore', 'fileuploader.css');
    jrCore_register_module_feature('jrCore', 'css', 'jrCore', 'jquery.lightbox.css');
    jrCore_register_module_feature('jrCore', 'css', 'jrCore', 'jquery.livesearch.css');
    // When CSS is registered, we have a function that is called
    jrCore_register_module_feature_function('jrCore', 'css', 'jrCore_enable_external_css');

    // Core plugins
    jrCore_register_system_plugin('jrCore', 'email', 'debug', 'Log Sent Email to debug log');

    // Core event triggers
    jrCore_register_event_trigger('jrCore', 'db_create_datastore', 'Fired in jrCore_db_create_datastore() - check module');
    jrCore_register_event_trigger('jrCore', 'db_create_item', 'Fired in jrCore_db_create_item() - check module');
    jrCore_register_event_trigger('jrCore', 'db_update_item', 'Fired in jrCore_db_update_item() - check module');
    jrCore_register_event_trigger('jrCore', 'db_get_item', 'Fired in jrCore_db_get_item() - check module');
    jrCore_register_event_trigger('jrCore', 'db_delete_item', 'Fired in jrCore_db_delete_item() - check module');
    jrCore_register_event_trigger('jrCore', 'db_search_items', 'Fired in jrCore_db_search_items() - check module');
    jrCore_register_event_trigger('jrCore', 'db_search_params', 'Fired in jrCore_db_search_items() for accepted params');
    jrCore_register_event_trigger('jrCore', 'form_validate_init', 'Fired at the beginning of jrCore_form_validate()');
    jrCore_register_event_trigger('jrCore', 'form_validate_exit', 'Fired at the end of jrCore_form_validate()');
    jrCore_register_event_trigger('jrCore', 'form_field_create', 'Fired when a form_field is added to a form session');
    jrCore_register_event_trigger('jrCore', 'form_display', 'Fired when a form is displayed (receives form data)');
    jrCore_register_event_trigger('jrCore', 'form_result', 'Fired when a form target view has completed');
    jrCore_register_event_trigger('jrCore', 'save_media_file', 'Fired when a media file has been saved for a profile');
    jrCore_register_event_trigger('jrCore', 'parse_url', 'Fired when the current URL has been parsed into $_url');
    jrCore_register_event_trigger('jrCore', 'run_view_function', 'Fired before a view function is run for a module');
    jrCore_register_event_trigger('jrCore', 'index_template', 'Fired when the skin index template is displayed');
    jrCore_register_event_trigger('jrCore', 'skin_template', 'Fired when a skin template is displayed');
    jrCore_register_event_trigger('jrCore', 'profile_template', 'Fired when a profile template is displayed');
    jrCore_register_event_trigger('jrCore', 'process_init', 'Fired when the core has initialized');
    jrCore_register_event_trigger('jrCore', 'view_results', 'Fired when results from a module view are displayed');
    jrCore_register_event_trigger('jrCore', 'module_view', 'Fired when a module view is going to be processed');
    jrCore_register_event_trigger('jrCore', 'process_exit', 'Fired when process exits');
    jrCore_register_event_trigger('jrCore', 'daily_maintenance', 'Fired once a day after midnight server time');
    jrCore_register_event_trigger('jrCore', 'download_file', 'Fired when a DataStore file is downloaded');
    jrCore_register_event_trigger('jrCore', 'stream_file', 'Fired when a DataStore file is streamed');
    jrCore_register_event_trigger('jrCore', 'media_playlist', 'Fired when a playlist is assembled in {jrCore_media_player}');
    jrCore_register_event_trigger('jrCore', 'verify_module', 'Fired when a module is verified during the Integrity Check');
    jrCore_register_event_trigger('jrCore', 'system_check', 'Fired in System Check so modules can run own checks');
    jrCore_register_event_trigger('jrCore', 'all_events', 'Fired once when any other trigger is fired');
    jrCore_register_event_trigger('jrCore', 'template_variables', 'Fired in jrCore_parse_template for replacement variables.');
    jrCore_register_event_trigger('jrCore', 'template_cache_reset', 'Fired when Reset Template Cache is fired.');

    // If the tracer module is installed, we have a few events for it
    jrCore_register_module_feature('jrTrace', 'trace_event', 'jrCore', 'download_file', 'A user downloads a file');
    jrCore_register_module_feature('jrTrace', 'trace_event', 'jrCore', 'stream_file', 'A user streams a file');

    // Set core directory and file permissions
    if (!isset($_conf['jrCore_dir_perms'])) {
        $umask = (int)sprintf('%03o', umask());
        $_conf['jrCore_dir_perms'] = octdec(0 . (777 - $umask));
        $_conf['jrCore_file_perms'] = octdec(0 . (666 - $umask));
    }

    // Check for install routine
    if (defined('IN_JAMROOM_INSTALLER')) {
        return true;
    }

    // Get both settings and modules in 1 shot
    $_rt = jrCore_is_cached('jrCore', 'jrcore_config_and_modules', false);
    if (!$_rt) {
        $tb1 = jrCore_db_table_name('jrCore', 'setting');
        $tb2 = jrCore_db_table_name('jrCore', 'module');
        $req = "(SELECT s.module AS m, s.name AS k, s.value AS v, m.* FROM {$tb1} s LEFT JOIN {$tb2} m ON m.module_directory = s.module ORDER BY FIELD(m.module_directory,'jrCore') ASC, m.module_priority ASC) UNION ALL
                (SELECT s.module AS m, s.name AS k, s.value AS v, m.* FROM {$tb1} s RIGHT JOIN {$tb2} m ON m.module_directory = s.module WHERE s.module IS NULL ORDER BY FIELD(m.module_directory,'jrCore') ASC, m.module_priority ASC)";
        $_rt = jrCore_db_query($req, 'NUMERIC');
        if (!isset($_rt) || !is_array($_rt)) {
            jrCore_notice('CRI', "unable to initialize any settings - verify installation");
        }
        // Config setup
        foreach ($_rt as $_s) {
            $_conf["{$_s['m']}_{$_s['k']}"] = $_s['v'];
        }

        // Module setup
        $_ina = array();
        foreach ($_rt as $_s) {
            if (!empty($_s['module_directory']) && !isset($_mods["{$_s['module_directory']}"])) {
                unset($_s['m'], $_s['k'], $_s['v']);
                $_mods["{$_s['module_directory']}"] = $_s;
                $_urls["{$_s['module_url']}"] = $_s['module_directory'];
                if ($_s['module_directory'] != 'jrCore') {
                    // jrCore is already included ;)
                    // NOTE: error redirect here for users that simply try to delete a module
                    // by removing the module directory BEFORE removing the module from the DB!
                    if ((@include_once APP_DIR . "/modules/{$_s['module_directory']}/include.php") === false) {
                        // Bad module
                        unset($_mods["{$_s['module_directory']}"]);
                    }
                    // If this module is NOT active, we add it to our inactive list of modules
                    // so we can check in the next loop down any module dependencies
                    if ($_s['module_active'] != '1') {
                        $_ina["{$_s['module_directory']}"] = 1;
                    }
                }
            }
        }
        // .. and init
        foreach ($_mods as $k => $_md) {
            if ($_md['module_directory'] != 'jrCore' && $_md['module_active'] == '1') {
                if (isset($_md['requires']{0})) {
                    // We have a module that depends on another module to be active
                    foreach (explode(',', trim($_md['requires'])) as $req_mod) {
                        if (isset($_ina[$req_mod])) {
                            continue 2;
                        }
                    }
                }
                $func = "{$_md['module_directory']}_init";
                if (function_exists($func)) {
                    $func();
                }
                $_mods[$k]['module_initialized'] = 1;
            }
        }
        unset($_ina);

        $_rt = array(
            '_conf' => $_conf,
            '_mods' => $_mods,
            '_urls' => $_urls
        );
        jrCore_add_to_cache('jrCore', 'jrcore_config_and_modules', json_encode($_rt), 0, 0, false);
    }
    else {
        // We are cached
        $_rt = json_decode($_rt, true);
        $_conf = $_rt['_conf'];
        $_mods = $_rt['_mods'];
        $_urls = $_rt['_urls'];
        // Module setup
        foreach ($_mods as $_md) {
            if ($_md['module_directory'] != 'jrCore') {
                // jrCore is already included ;)
                // NOTE: error redirect here for users that simply try to delete a module
                // by removing the module directory BEFORE removing the module from the DB!
                @include_once APP_DIR . "/modules/{$_md['module_directory']}/include.php";
            }
        }
        // .. and init
        foreach ($_mods as $k => $_md) {
            if ($_md['module_directory'] != 'jrCore' && $_md['module_active'] == '1') {
                $func = "{$_md['module_directory']}_init";
                if (function_exists($func)) {
                    $func();
                }
                $_mods[$k]['module_initialized'] = 1;
            }
        }
    }

    // Turn on error logging if developer mode is on
    if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
        error_reporting(E_ALL);
    }

    // Set our timezone...
    date_default_timezone_set($_conf['jrCore_system_timezone']);

    // Initialize active skin...
    $func = "{$_conf['jrCore_active_skin']}_skin_init";
    if (!function_exists($func)) {
        require_once APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/include.php";
        if (function_exists($func)) {
            $func();
        }
    }
    ob_end_clean();

    // Core event listeners - must come after $_mods
    jrCore_register_event_listener('jrCore', 'view_results', 'jrCore_view_results_listener');
    jrCore_register_event_listener('jrCore', 'process_exit', 'jrCore_process_exit_listener');
    jrCore_register_event_listener('jrCore', 'daily_maintenance', 'jrCore_daily_maintenance_listener');

    // Trigger our process_init event
    $_temp = array();
    jrCore_trigger_event('jrCore', 'process_init', $_temp);
    return true;
}

// Include Library
require_once APP_DIR . '/modules/jrCore/lib/mysql.php';
require_once APP_DIR . '/modules/jrCore/lib/datastore.php';
require_once APP_DIR . '/modules/jrCore/lib/module.php';
require_once APP_DIR . '/modules/jrCore/lib/media.php';
require_once APP_DIR . '/modules/jrCore/lib/checktype.php';
require_once APP_DIR . '/modules/jrCore/lib/smarty.php';
require_once APP_DIR . '/modules/jrCore/lib/cache.php';
require_once APP_DIR . '/modules/jrCore/lib/page.php';
require_once APP_DIR . '/modules/jrCore/lib/form.php';
require_once APP_DIR . '/modules/jrCore/lib/skin.php';
require_once APP_DIR . '/modules/jrCore/lib/util.php';
require_once APP_DIR . '/modules/jrCore/lib/misc.php';

/**
 * Make sure media play keys are set
 * @param array $_data incoming data array from jrCore_save_media_file()
 * @param array $_user current user info
 * @param array $_conf Global config
 * @param array $_args additional info about the module
 * @param string $event Event Trigger name
 * @return array
 */
function jrCore_view_results_listener($_data, $_user, $_conf, $_args, $event)
{
    return jrCore_media_set_play_key($_data);
}

/**
 * Run on process exit and used for cleanup/inserting
 * @param array $_data incoming data array from jrCore_save_media_file()
 * @param array $_user current user info
 * @param array $_conf Global config
 * @param array $_args additional info about the module
 * @param string $event Event Trigger name
 * @return array
 */
function jrCore_process_exit_listener($_data, $_user, $_conf, $_args, $event)
{
    // Our core process exit listener handles cleanup and insertion of
    // unique hits that are counted during a page view, as well as
    // running any queue workers after the process shutdown.

    // Cleanup hit counter ip table (5% chance)
    if (mt_rand(1, 20) === 5) {
        $tbl = jrCore_db_table_name('jrCore', 'count_ip');
        $req = "DELETE FROM {$tbl} WHERE count_time < (UNIX_TIMESTAMP() - 86400)";
        jrCore_db_query($req, 'COUNT');
    }

    // Cleanup old form sessions (older than 8 hours) - 5% chance
    if (mt_rand(1,20) === 5) {
        $old = (time() - 28800);
        $tbl = jrCore_db_table_name('jrCore', 'form_session');
        $req = "DELETE FROM {$tbl} WHERE form_updated > 0 AND form_updated < {$old}";
        jrCore_db_query($req);
    }

    // Check for Queue Workers
    $_tmp = jrCore_get_flag('jrcore_register_queue_worker');
    if ($_tmp) {
        // Make sure queues have been setup....
        $ready = jrCore_db_table_exists('jrCore', 'queue');
        if ($ready) {

            // Conversions and other queue-based work can take a long time to run
            set_time_limit(0);

            foreach ($_tmp as $mod => $_queue) {
                foreach ($_queue as $qname => $qdat) {
                    $func = $qdat[0]; // Queue Function that is going to be run
                    $qcnt = intval($qdat[1]); // Number of Queue Entries to process before exiting (set to 0 for worker to process all queue entries)
                    if (!function_exists($func)) {
                        jrCore_logger('MAJ', "registered queue worker function: {$func} for module: {$mod} does not exist");
                        continue;
                    }
                    // See if we have a queue entry
                    if ($qcnt === 0) {
                        $qcnt = 1000000; // high enough
                    }
                    while ($qcnt > 0) {
                        $_tmp = jrCore_queue_get($qname);
                        if ($_tmp) {
                            // We found a queue entry - pass it on to the worker
                            $ret = $func($_tmp['queue_data']);
                            // Our queue workers can return:
                            // 1) TRUE - everything is good, delete queue entry
                            // 2) # - indicates we should add # to our "tries" for this queue item.  When it gets to 3 the queue item will be removed.
                            // 3) FALSE - an issue was encountered processing the queue - no delete or increment
                            if (isset($ret) && jrCore_checktype($ret, 'number_nn')) {
                                // We encountered an issue processing the queue - we're going to "count"
                                // this try based on the number given as a return.  If it is 3
                                // or greater, we delete the queue entry
                                if ($ret >= 3) {
                                    // We're being told to delete this entry - no good
                                    jrCore_queue_delete($_tmp['queue_id']);
                                }
                                else {
                                    // Release and increment our queue tries
                                    jrCore_queue_release($_tmp['queue_id'], $ret);
                                }
                            }
                            elseif (isset($ret) && $ret === true) {
                                // We successfully processed our queue entry - delete it
                                jrCore_queue_delete($_tmp['queue_id']);
                            }
                            else {
                                // We got FALSE - this usually means the number of workers has
                                // been reached, and we just need to try again - no try count
                                jrCore_queue_release($_tmp['queue_id']);
                            }
                            $qcnt--;
                            jrCore_db_close();
                            sleep(3);
                        }
                        else {
                            $qcnt = 0;
                        }
                    }
                }
            }
        }
    }
    return $_data;
}

/**
 * Keep jrCore cache directory clean during daily maintenance
 * @param array $_data incoming data array from jrCore_save_media_file()
 * @param array $_user current user info
 * @param array $_conf Global config
 * @param array $_args additional info about the module
 * @param string $event Event Trigger name
 * @return array
 */
function jrCore_daily_maintenance_listener($_data, $_user, $_conf, $_args, $event)
{
    // We will delete any old upload directories not accessed in 24 hours
    $old = (time() - 86400);
    $cdr = jrCore_get_module_cache_dir('jrCore');
    if (!is_dir($cdr)) {
        jrCore_logger('CRI', 'Unable to open jrCore cache dir for cleaning');
        return true;
    }
    $c = 0;
    $f = opendir($cdr);
    if ($f) {
        while ($file = readdir($f)) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir("{$cdr}/{$file}")) {
                $_tmp = stat("{$cdr}/{$file}");
                if (isset($_tmp['mtime']) && $_tmp['mtime'] < $old) {
                    jrCore_delete_dir_contents("{$cdr}/{$file}");
                    $c++;
                }
            }
        }
        closedir($f);
    }
    if ($c > 0) {
        jrCore_logger('INF', "deleted {$c} temp upload directories created more than 24 hours ago");
    }
    return true;
}
