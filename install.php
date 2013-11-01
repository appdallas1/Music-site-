<?php
/**
 * Jamroom 5 Installer
 * copyright 2003 - 2012 by The Jamroom Network - All Rights Reserved
 * http://www.jamroom.net
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0.  Please see the included "license.html" file.
 *
 * Jamroom includes works that are not developed by The Jamroom Network
 * and are used under license - copies of all licenses are included and
 * can be found in the "contrib" directory within the module, as well
 * as within the "license.html" file.
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
 */

// Define our base dir
define('APP_DIR', dirname(__FILE__));
define('IN_JAMROOM_INSTALLER', 1);
define('DEFAULT_JAMROOM_SKIN', 'jrElastic');

// Typically no need to edit below here
date_default_timezone_set('UTC');
ini_set('session.auto_start', 0);
ini_set('session.use_trans_sid', 0);
ini_set('display_errors', 1);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));
session_start();

// Bring in core functionality
$_conf = array();
require_once APP_DIR . "/modules/jrCore/include.php";

$umask = (int) sprintf('%03o', umask());
$_conf['jrCore_dir_perms'] = octdec(0 . (777 - $umask));
$_conf['jrCore_file_perms'] = octdec(0 . (666 - $umask));

// Check for already being installed
if (is_file(APP_DIR . '/data/config/config.php')) {
    echo 'ERROR: Config file found - Jamroom already appears to be installed';
    exit;
}

// Check PHP version
$min = '5.3.0';
if (version_compare(phpversion(), $min) == -1) {
    echo "ERROR: Jamroom 5 requires PHP {$min} or newer - you are currently running PHP version " . phpversion() . " - contact your hosting provider and see if they can upgrade your PHP install to a newer release";
    exit;
}

// Make sure we have session support
if (!function_exists('session_start')) {
    echo 'ERROR: PHP does not appear to have Session Support - Jamroom requires PHP Session Support in order to work. Please contact your system administrator and have Session Support activated in your PHP.';
    exit;
}

// Load modules
$_mods = array('jrCore' => jrCore_meta());
$_urls = array('core' => 'jrCore');
if (is_dir(APP_DIR . "/modules")) {
    if ($h = opendir(APP_DIR . "/modules")) {
        while (($file = readdir($h)) !== false) {
            if ($file == 'index.html' || $file == '.' || $file == '..' || $file == 'jrCore') {
                continue;
            }
            if (is_file(APP_DIR . "/modules/{$file}/include.php")) {
                require_once APP_DIR . "/modules/{$file}/include.php";
            }
            $mfunc = "{$file}_meta";
            if (function_exists($mfunc)) {
                $_mods[$file] = $mfunc();
                $murl = $_mods[$file]['url'];
                $_urls[$murl] = $file;
            }
        }
    }
    closedir($h);
}

// kick off installer
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'install') {
    jrInstall_install_system();
}
else {
    jrInstall_show_install_form();
}

/**
 * jrInstall_show_install_form
 */
function jrInstall_show_install_form()
{
    jrInstall_header();
    jrInstall_install_form();
    jrInstall_footer();
}

/**
 * jrInstall_show_install_form
 */
function jrInstall_install_system()
{
    global $_conf, $_mods;
    sleep(1);

    // Setup session
    $_todo = array(
        'base_url' => 'System URL',
        'db_host'  => 'MySQL Host',
        'db_port'  => 'MySQL Port',
        'db_name'  => 'MySQL Database',
        'db_user'  => 'MySQL User',
        'db_pass'  => 'MySQL Password'
    );
    foreach ($_todo as $k => $v) {
        if (isset($_REQUEST[$k]) && strlen($_REQUEST[$k]) > 0) {
            $_SESSION[$k] = $_REQUEST[$k];
        }
        else {
            $_SESSION['install_error'] = 'You have entered an invalid value for ' . $v . ' - please enter a valid value';
            $_SESSION['install_hilight'] = $k;
            jrCore_location('install.php');
        }
    }

    // Write out our database stuff
    $config = APP_DIR . "/data/config/config.php";
    if (!is_file($config)) {
        touch($config);
        if (!is_file($config)) {
            $_SESSION['install_error'] = 'data/config/config.php does not exist, and cannot be opened for created - please create the config.php file';
            jrCore_location('install.php');
        }
        unlink($config);
    }

    // Try to connect to MySQL
    if (!function_exists('mysqli_init')) {
        $_SESSION['install_error'] = 'Unable to initialize MySQLi support - please check your PHP config for MySQLi support';
        jrCore_location('install.php');
    }
    $myi = mysqli_init();
    if (!$myi) {
        $_SESSION['install_error'] = 'Unable to initialize MySQLi support - please check your PHP config for MySQLi support';
        jrCore_location('install.php');
    }
    if (!mysqli_real_connect($myi, $_REQUEST['db_host'], $_REQUEST['db_user'], $_REQUEST['db_pass'], $_REQUEST['db_name'], $_REQUEST['db_port'], null, MYSQLI_CLIENT_FOUND_ROWS)) {
        $_SESSION['install_error'] = 'Unable to connect to the MySQL database using the credentials provided - please check:<br>MySQL error: ' . mysqli_connect_error();
        jrCore_location('install.php');
    }

    // Create config file
    $data = "<?php\n\$_conf['jrCore_db_host'] = '" . $_REQUEST['db_host'] . "';\n\$_conf['jrCore_db_port'] = '" . $_REQUEST['db_port'] . "';\n\$_conf['jrCore_db_name'] = '" . $_REQUEST['db_name'] . "';\n\$_conf['jrCore_db_user'] = '" . $_REQUEST['db_user'] . "';\n\$_conf['jrCore_db_pass'] = '" . $_REQUEST['db_pass'] . "';\n\$_conf['jrCore_base_url'] = '" . $_REQUEST['base_url'] . "';\n";
    jrCore_write_to_file($config, $data);

    // Bring it in for install
    require_once $config;

    // Init Core first
    $_conf['jrCore_active_skin'] = DEFAULT_JAMROOM_SKIN;
    jrCore_init();
    foreach ($_mods as $mod_dir => $_inf) {
        if ($mod_dir != 'jrCore') {
            $ifunc = "{$mod_dir}_init";
            if (function_exists($ifunc)) {
                $ifunc();
            }
        }
    }

    // install
    require_once APP_DIR . "/modules/jrCore/schema.php";
    jrCore_db_schema();
    foreach ($_mods as $mod_dir => $_inf) {
        if ($mod_dir != 'jrCore') {
            if (is_file(APP_DIR . "/modules/{$mod_dir}/schema.php")) {
                require_once APP_DIR . "/modules/{$mod_dir}/schema.php";
                $func = "{$mod_dir}_db_schema";
                if (function_exists($func)) {
                    $func();
                }
            }
        }
    }

    foreach ($_mods as $mod_dir => $_inf) {

        // config
        if (is_file(APP_DIR . "/modules/{$mod_dir}/config.php")) {
            require_once APP_DIR . "/modules/{$mod_dir}/config.php";
            $func = "{$mod_dir}_config";
            if (function_exists($func)) {
                $func();
            }
        }

        // quota
        if (is_file(APP_DIR . "/modules/{$mod_dir}/quota.php")) {
            require_once APP_DIR . "/modules/{$mod_dir}/quota.php";
            $func = "{$mod_dir}_quota_config";
            if (function_exists($func)) {
                $func();
            }
        }

        // lang strings
        if (is_dir(APP_DIR . "/modules/{$mod_dir}/lang")) {
            jrUser_install_lang_strings('module', $mod_dir);
        }
    }

    // Create first profile quota
    $qid = jrProfile_create_quota('example quota');

    // Build modules
    $_feat = jrCore_get_registered_module_features('jrCore', 'quota_support');
    foreach ($_mods as $mod_dir => $_inf) {
        jrCore_verify_module($mod_dir);
        // Turn on Quota if this module has quota options
        if (isset($_feat[$mod_dir])) {
            jrProfile_set_quota_value($mod_dir, $qid, 'allowed', 'on');
        }
    }

    // Setup skins
    $_skns = jrCore_get_skins();
    if (isset($_skns) && is_array($_skns)) {
        foreach ($_skns as $sk) {
            if (is_file(APP_DIR . "/skins/{$sk}/include.php")) {
                require_once APP_DIR . "/skins/{$sk}/include.php";
                $func = "{$sk}_skin_init";
                if (function_exists($func)) {
                    $func();
                }
            }
        }
        foreach ($_skns as $sk) {
            if (is_file(APP_DIR . "/skins/{$sk}/config.php")) {
                require_once APP_DIR . "/skins/{$sk}/config.php";
                $func = "{$sk}_skin_config";
                if (function_exists($func)) {
                    $func();
                }
            }
        }
        foreach ($_skns as $sk) {
            // Install Language strings for Skin
            jrUser_install_lang_strings('skin', $sk);
        }
    }

    // Turn on Sign ups for the first quota
    jrProfile_set_quota_value('jrUser', 1, 'allow_signups', 'on');

    // Activate all modules....
    $tbl = jrCore_db_table_name('jrCore', 'module');
    $req = "UPDATE {$tbl} SET module_active = '1'";
    jrCore_db_query($req);

    // Now we need to full reload conf here since we only have core
    $tbl = jrCore_db_table_name('jrCore', 'setting');
    $req = "SELECT module AS m, name AS k, value AS v FROM {$tbl}";
    $_rt = jrCore_db_query($req, 'NUMERIC');

    // Make sure we got settings
    if (!isset($_rt) || !is_array($_rt)) {
        jrCore_notice('CRI', "unable to initialize any settings - very installation");
    }
    foreach ($_rt as $_s) {
        $_conf["{$_s['m']}_{$_s['k']}"] = $_s['v'];
    }

    // Set skin CSS and JS for our default skin
    jrCore_create_master_css(DEFAULT_JAMROOM_SKIN);
    jrCore_create_master_javascript(DEFAULT_JAMROOM_SKIN);

    // On a new install we just enable all modules for all quotas
    $tbl = jrCore_db_table_name('jrProfile', 'quota_setting');
    $req = "UPDATE {$tbl} SET `default` = 'on' WHERE `name` = 'allowed'";
    jrCore_db_query($req);

    jrCore_notice_page('success', 'Jamroom has been successfully installed!', $_REQUEST['base_url'], 'Continue to your new Jamroom');
    session_destroy();
}

/**
 * jrInstall_header
 */
function jrInstall_header()
{
    echo '
    <!doctype html>
    <html lang="en" dir="ltr">
    <head>
    <title>Jamroom Installer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script type="text/javascript" src="modules/jrCore/js/jquery-1.8.3.min.js"></script>';
    // Bring in style sheets
    $_css = glob(APP_DIR . '/skins/' . DEFAULT_JAMROOM_SKIN . '/css/*.css');
    foreach ($_css as $css_file) {
        $css_name = basename($css_file);
        echo '<link rel="stylesheet" href="skins/' . DEFAULT_JAMROOM_SKIN . '/css/' . $css_name . '" media="screen" />' . "\n";
    }
    echo '</head> 
    <body>

    <div id="header">
        <div id="header_content">
            <div class="container">
                <div class="row">
                    <div class="col4">
                        <div id="main_logo">
                            <img src="skins/' . DEFAULT_JAMROOM_SKIN . '/img/logo.png" width="236" height="55" alt="Jamroom">
                        </div>
                    </div>
                    <div class="col8 last">
                        <div style="width:90%;padding:25px;text-align:right">
                            Welcome to Jamroom 5!
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="wrapper">
      <div id="content">';
    return true;
}

/**
 * jrInstall_install_notice
 */
function jrInstall_install_notice($type, $text)
{
    echo '<tr><td colspan="2" class="page_notice_drop"><div id="page_notice" class="page_notice ' . $type . '">' . $text . '</div></td></tr>';
    return true;
}

/**
 * jrInstall_install_form
 */
function jrInstall_install_form()
{
    global $_conf;
    $disabled = '';
    echo '
    <div class="container">
      <div class="row">
        <div class="col12 last">
          <div>
            <form id="install" method="post" action="install.php?action=install" accept-charset="utf-8" enctype="multipart/form-data">
            <table class="page_content">
              <tr>
                <td colspan="2" class="element page_note">
                  Welcome to the Jamroom 5 Installer!
                </td>
              </tr>
              <tr>
                <td class="element_left form_input_left">
                  Jamroom license
                </td>
                <td class="element_right form_input_right" style="height:160px">
                  <iframe src="modules/jrCore/license.html" style="width:100%;height:160px;border:2px solid #ccc;"></iframe>
                </td>
              </tr>';

    // Test to make sure our server is setup properly
    if (!is_dir(APP_DIR . '/data')) {
        jrInstall_install_notice('error', "&quot;data&quot; directory does not exist - create data directory and permission so web user can write to it");
        $disabled = ' disabled="disabled"';
    }
    // Check each dir
    $_dirs = array('cache', 'config', 'logs', 'media');
    $error = array();
    foreach ($_dirs as $dir) {
        $fdir = APP_DIR . "/data/{$dir}";
        if (!is_dir($fdir)) {
            mkdir($fdir, $_conf['jrCore_dir_perms']);
            if (!is_dir($fdir)) {
                $error[] = "data/{$dir}";
            }
        }
        elseif (!is_writable($fdir)) {
            chmod($fdir, $_conf['jrCore_dir_perms']);
            if (!is_writable($fdir)) {
                $error[] = "data/{$dir}";
            }
        }
    }
    if (isset($error) && is_array($error) && count($error) > 0) {
        jrInstall_install_notice('error', "The following directories are not writable:<br>" . implode('<br>', $error) . "<br>ensure they are permissioned so the web user can write to them");
        $disabled = ' disabled="disabled"';
    }

    // mod_rewrite check
    if (function_exists('apache_get_modules') && function_exists('php_sapi_name') && stristr(php_sapi_name(), 'apache')) {
        if (!in_array('mod_rewrite', apache_get_modules())) {
            jrInstall_install_notice('error', 'mod_rewrite does not appear to be enabled on your server - mod_rewrite is required for Jamroom 5 to function.  Contact your hosting provider and ensure mod_rewrite is active in your account.');
        }
    }

    // Check for disabled functions
    $_funcs = array('system', 'json_encode', 'json_decode', 'ob_start', 'ob_end_clean', 'curl_init', 'gd_info');
    $_flist = array();
    foreach ($_funcs as $rfunc) {
        if (!function_exists($rfunc)) {
            $_flist[] = $rfunc;
        }
    }
    if (isset($_flist) && is_array($_flist) && count($_flist) > 0) {
        jrInstall_install_notice('error', "The following function(s) are not enabled in your PHP install:<br><br><b>" . implode('</b><br><b>', $_flist) . "</b><br><br>Jamroom will not function properly without these functions enabled so contact your hosting provider and make sure they are enabled.");
        $disabled = ' disabled="disabled"';
    }

    // Check that ffmpeg works
    if (!jrCore_check_ffmpeg_install(false)) {
        jrInstall_install_notice('error', "The FFMpeg binary located at modules/jrCore/tools/ffmpeg does not appear to be executable - FFMpeg is required for audio and video support in Jamroom. After installation ensure FFMpeg can be executed via a system() function call.");
    }

    // Make sure .htaccess exists
    if (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') && !is_file(APP_DIR . "/.htaccess")) {
        jrInstall_install_notice('error', "Unable to find the .htaccess file - please ensure the .htaccess from the Jamroom 5 Core ZIP file is uploaded to your server.");
        $disabled = ' disabled="disabled"';
    }

    // Check for session errors
    if (isset($_SESSION['install_error'])) {
        jrInstall_install_notice('error', $_SESSION['install_error']);
        unset($_SESSION['install_error']);
    }

    if (!isset($_SESSION['base_url']{1})) {
        $_SESSION['base_url'] = preg_replace('/\/$/', '', 'http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']));
    }
    if (!isset($_SESSION['db_host'])) {
        $_SESSION['db_host'] = 'localhost';
    }
    if (!isset($_SESSION['db_port'])) {
        $_SESSION['db_port'] = '3306';
    }

    jrInstall_text_field('text', 'System URL', 'base_url', $_SESSION['base_url']);
    jrInstall_text_field('text', 'MySQL Host', 'db_host', $_SESSION['db_host']);
    jrInstall_text_field('text', 'MySQL Port <span class="sublabel">(optional)</span>', 'db_port', $_SESSION['db_port']);
    jrInstall_text_field('text', 'MySQL Database', 'db_name', $_SESSION['db_name']);
    jrInstall_text_field('text', 'MySQL Username', 'db_user', $_SESSION['db_user']);
    jrInstall_text_field('password', 'MySQL Password', 'db_pass', $_SESSION['db_pass']);

    $refresh = '';
    $disclass = '';
    if (isset($disabled) && strlen($disabled) > 0) {
        $disclass = ' form_button_disabled';
        $refresh = '<input type="button" value="Check Again" class="form_button" onclick="location.reload();">';
    }
    echo '    <tr>
                <td colspan="2" class="element form_submit_section">
                  <img id="form_submit_indicator" src="skins/' . DEFAULT_JAMROOM_SKIN . '/img/submit.gif" width="24" height="24" alt="working...">' . $refresh . '
                  <input type="button" value="Install Jamroom" class="form_button' . $disclass . '"' . $disabled . ' onclick="if (confirm(\'Please be patient - the installion can take up to 30 seconds to run. Are you ready to install?\')){$(\'#form_submit_indicator\').show(300,function(){ $(\'#install\').submit(); });}">
                </td>
              </tr>  
            </table>
            </form>
          </div>
        </div>
      </div>
    </div>';
    return true;
}

/**
 * jrInstall_text_field
 */
function jrInstall_text_field($type, $label, $name, $value = '')
{
    $cls = '';
    if (isset($_SESSION['install_hilight']) && $_SESSION['install_hilight'] == $name) {
        $cls = ' field-hilight';
        unset($_SESSION['install_hilight']);
    }
    echo '<tr><td class="element_left form_input_left">' . $label . '</td><td class="element_right form_input_right">';
    switch ($type) {
        case 'text':
            echo '<input type="text" name="' . $name . '" value="' . $value . '" class="form_text' . $cls . '"></td></tr>';
            break;
        case 'password':
            echo '<input type="password" name="' . $name . '" value="' . $value . '" class="form_text' . $cls . '"></td></tr>';
            break;
    }
    return true;
}

/**
 * jrInstall_footer
 */
function jrInstall_footer()
{
    echo '</div>
    <div id="footer">
        <div id="footer_content">
            <div class="container">
                <div class="row">
                    <div class="col6">
                        <div id="footer_logo">
                            &nbsp;
                        </div>
                    </div>
                    <div class="col6 last">
                        <div id="footer_text">
                            &copy;2003 - ' . strftime('%Y') . ' The Jamroom Network
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    </body>
    </html>';
    return true;
}

?>
