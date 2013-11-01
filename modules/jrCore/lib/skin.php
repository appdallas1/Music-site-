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
 * @package Skin
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Generate a CSS Sprite background image from existing Icon images
 * @param $skin string Skin to use for overriding default icon images
 * @param $color string Icon color set to use (black||white)
 * @param $width int Pixel width for Icons
 * @return bool
 */
function jrCore_create_css_sprite($skin,$color = 'black',$width = 64)
{
    global $_conf;
    // Our ICON sprites live in jrCore/img/icons, and each can
    // be overridden by the skin with it's own version of the sprite
    $swidth = 0;
    $_icons = glob(APP_DIR ."/modules/jrCore/img/icons_{$color}/*.png");
    if (isset($_icons) && is_array($_icons)) {
        foreach ($_icons as $k => $v) {
            $name = basename($v);
            $_icons[$name] = $v;
            unset($_icons[$k]);
            $swidth += $width;
        }
    }
    // Override core with skin
    if (is_dir(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/img/icons_{$color}")) {
        $_skin = glob(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/img/icons_{$color}/*.png");
        if (isset($_skin) && is_array($_skin)) {
            foreach ($_skin as $v) {
                $name = basename($v);
                if (!isset($_icons[$name])) {
                    $swidth += $width;
                }
                $_icons[$name] = $v;

            }
        }
        unset($_skin);
    }

    // Now create our Sprite image
    $sprite = imagecreatetruecolor($swidth,$width);
    imagealphablending($sprite,false);
    imagesavealpha($sprite,true);
    $left = 0;
    $css  = ".sprite_icon_{$width}{display:inline-block;width:{$width}px;height:{$width}px;}\n";
    $css .= ".sprite_icon_{$width}_img{background:url('{$_conf['jrCore_base_url']}/data/media/0/0/{$_conf['jrCore_active_skin']}_sprite_{$width}.png') no-repeat top left; height:100%;width:100%;}";
    foreach($_icons as $name => $image) {
        $img = imagecreatefrompng($image);
        imagecopyresampled($sprite,$img,$left,0,0,0,$width,$width,64,64);
        // Generate CSS
        $nam = str_replace('.png','',$name);
        if ($left > 0) {
            $css .= "\n.sprite_icon_{$width}_{$nam}{background-position:-{$left}px 0;}";
        }
        else {
            $css .= "\n.sprite_icon_{$width}_{$nam}{background-position:0 0;}";
        }
        $left += $width;
    }
    $dir = jrCore_get_media_directory(0);
    jrCore_write_to_file("{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.css",$css ."\n");
    imagepng($sprite,"{$dir}/{$_conf['jrCore_active_skin']}_sprite_{$width}.png");
    imagedestroy($sprite);
    return true;
}

/**
 * Get HTML code for a given CSS icon sprite
 * @param $name string Name of CSS Icon to get
 * @param $size int Size (in pixels) for icon
 * @return string
 */
function jrCore_get_sprite_html($name,$size = null)
{
    global $_conf;
    if (is_null($size)) {
        $_tmp = jrCore_get_registered_module_features('jrCore','icon_size');
        if (isset($_tmp["{$_conf['jrCore_active_skin']}"])) {
            $size = array_keys($_tmp["{$_conf['jrCore_active_skin']}"]);
            $size = reset($size);
            if (!is_numeric($size)) {
                $size = 32;
            }
        }
        else {
            $size = 32;
        }
    }
    $out = '';
    if (!jrCore_get_flag("jrcore_include_icon_css_{$size}")) {
        $out = '<link rel="stylesheet" href="'. $_conf['jrCore_base_url'] .'/'. jrCore_get_module_url('jrCore') .'/icon_css/'. $size .'" media="screen" />';
        jrCore_set_flag("jrcore_include_icon_css_{$size}",1);
    }
    // See if we are doing a highlighted icon
    if (strpos($name,'-hilighted')) {
        $name = str_replace('-hilighted','',$name);
        $out .= "<div class=\"sprite_icon sprite_icon_hilighted sprite_icon_{$size}\"><div class=\"sprite_icon_{$size}_img sprite_icon_{$size}_{$name}\">&nbsp;</div></div>";
    }
    else {
        $out .= "<div class=\"sprite_icon sprite_icon_{$size}\"><div class=\"sprite_icon_{$size}_img sprite_icon_{$size}_{$name}\">&nbsp;</div></div>";
    }
    return $out;
}

/**
 * jrCore_skin_meta_data - get meta data for a skin
 * @param string $skin skin string skin name
 * @return mixed returns metadata/key if found, false if not
 */
function jrCore_skin_meta_data($skin)
{
    $func = "{$skin}_skin_meta";
    if (!function_exists($func)) {
        require_once APP_DIR ."/skins/{$skin}/include.php";
    }
    if (!function_exists($func)) {
        return false;
    }
    $_tmp = $func();
    if (isset($_tmp) && is_array($_tmp)) {
        return $_tmp;
    }
    return false;
}

/**
 * jrCore_get_skins
 * Retrieves a list of skins available on the system
 */
function jrCore_get_skins()
{
    $tmp = jrCore_get_flag('jrcore_get_skins');
    if ($tmp) {
       return $tmp;
    }
    $_sk = array();
    // and now do our deletion
    if ($h = opendir(APP_DIR .'/skins')) {
        while (($file = readdir($h)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            elseif (is_file(APP_DIR ."/skins/{$file}/include.php")) {
                $_sk[$file] = $file;
            }
        }
        closedir($h);
    }
    jrCore_set_flag('jrcore_get_skins',$_sk);
    return $_sk;
}

/**
 * Delete an existing Skin Menu Item
 * @param $module string Module Name that created the Skin Menu Item
 * @param $unique string Unique name/tag for the Skin Menu Item
 * @return mixed
 */
function jrCore_delete_skin_menu_item($module,$unique)
{
    $tbl = jrCore_db_table_name('jrCore','menu');
    $req = "DELETE FROM {$tbl} WHERE menu_module = '". jrCore_db_escape($module) ."' AND menu_unique = '". jrCore_db_escape($unique) ."' LIMIT 1";
    return jrCore_db_query($req,'COUNT');
}

/**
 * Parses a template and returns the result
 *
 * <br><p>
 * This is one of the main functions used to move data from php out to the smarty templates.
 * anything you put in the $_rep array becomes a template variable.  so if you have $_rep['foo'] = 'bar'; then you can call
 *  &#123;$foo} in the template to produce 'bar' output.
 * </p><br>
 * <p>
 *  If you have a template in your module, the system will look for it in the /templates directory.  so call it with <br>
 *  <i>$out = jrCore_parse_template('embed.tpl',null,'jrDisqus');</i>  will call /modules/jrDisqus/templates/embed.tpl
 * </p><br>
 * <p>
 *  Skins can over-ride the modules template by defining their own version of it. so
 * /module/jrDisqus/templates/embed.tpl can be over-ridden by the skin by defining:
 * /skin/jrElastic/jrDisqus_embed.tpl
 * </p>
 * @param string $template Name of template
 * @param array $_rep (Optional) replacement variables for use in template.
 * @param string $directory default active skin directory, module directory for module/templates
 * @return string
 */
function jrCore_parse_template($template,$_rep = null,$directory = null)
{
    global $_conf, $_post, $_mods;
    // make sure we get smarty included
    if (!class_exists('Smarty')) {
        require_once APP_DIR .'/modules/jrCore/contrib/smarty/libs/Smarty.class.php';
    }

    // Set our compile dir
    $temp = new Smarty;
    $temp->compile_dir = APP_DIR .'/data/cache/'. $_conf['jrCore_active_skin'];

    // Get plugin directories
    $_dir = array(APP_DIR .'/modules/jrCore/contrib/smarty/libs/plugins');
    $temp->plugins_dir = $_dir;

    // If we are running in developer mode, make sure compiled template is removed on every call
    if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
        $temp->error_reporting = (E_ALL ^ E_NOTICE);
        $temp->force_compile = true;
    }

    // Our Data
    $_data = array();
    if (isset($_rep) && is_array($_rep)) {
        $_data = $_rep;
    }
    $_data['page_title']  = jrCore_get_flag('jrcore_html_page_title');
    $_data['jamroom_dir'] = APP_DIR;
    $_data['jamroom_url'] = $_conf['jrCore_base_url'];
    $_data['_conf']       = $_conf;
    $_data['_post']       = $_post;
    $_data['_mods']       = $_mods;
    $_data['_user']       = $_SESSION;
    $_data['jr_template'] = $template;

    // Remove User and MySQL info - we don't want this to ever leak into a template
    unset($_data['_user']['user_password'],$_data['_user']['user_old_password'],$_data['_user']['user_forgot_key']);
    unset($_data['_conf']['jrCore_db_host'],$_data['_conf']['jrCore_db_user'],$_data['_conf']['jrCore_db_pass'],$_data['_conf']['jrCore_db_name'],$_data['_conf']['jrCore_db_port']);

    if (strpos($template,'.tpl')) {
        $file = jrCore_get_template_file($template,$directory);
        $tkey = "{$directory}_{$template}";
    }
    else {
        $file = 'string:'. $template;
        $tkey = md5($template);
    }
    // Lastly, see if we have already shown this template in this process
    $_data['template_already_shown'] = '1';
    $shw = jrCore_get_flag("template_shown_{$tkey}");
    if (!$shw) {
        jrCore_set_flag("template_shown_{$tkey}",1);
        $_data['template_already_shown'] = '0';
    }

    // Trigger for additional replacement vars
    $_data = jrCore_trigger_event('jrCore', 'template_variables', $_data, $_rep);

    $temp->assign($_data);
    ob_start();
    $temp->display($file);
    $html = ob_get_contents();
    ob_end_clean();

    return $html;
}

/**
 * Returns the proper template to use for display.  Will also create/maintain the template cache
 * @param string $template Template file to get
 * @param string $directory Name of module or skin that the template belongs to
 * @param bool $reset Set to TRUE to reset the template cache
 * @return mixed Returns full file path on success, bool false on failure
 */
function jrCore_get_template_file($template,$directory,$reset = false)
{
    global $_conf;
    // Check for skin override
    if (is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/{$directory}_{$template}")) {
        $template  = "{$directory}_{$template}";
        $directory = $_conf['jrCore_active_skin'];
    }
    if (is_null($directory) || $directory === false || strlen($directory) === 0) {
        $directory = $_conf['jrCore_active_skin'];
    }
    // We check for our "cached" template, as that will be the proper one to display
    // depending on if the admin has customized the template or not.  If we do NOT
    // have the template in our cache, we have to go get it.
    $cdir = jrCore_get_module_cache_dir('jrCore');
    $hash = md5($_conf['jrCore_active_skin'] .'-'. $directory .'-'. $template);
    $file = "{$cdir}/{$hash}.tpl";
    if (!is_file($file) || $reset || $_conf['jrCore_default_cache_seconds'] == '0') {

        $_rt = jrCore_get_flag("jrcore_get_template_cache");
        if (!$_rt) {
            // We need to check for a customized version of this template
            $tbl = jrCore_db_table_name('jrCore','template');
            $req = "SELECT CONCAT_WS('_',template_module,template_name) AS template_name, template_body FROM {$tbl} WHERE template_active = '1'";
            $_rt = jrCore_db_query($req,'template_name');
            if (isset($_rt) && is_array($_rt)) {
                jrCore_set_flag('jrcore_get_template_cache',$_rt);
            }
            else {
                jrCore_set_flag('jrcore_get_template_cache',1);
            }
        }
        $key = "{$directory}_{$template}";
        if (isset($_rt) && is_array($_rt) && isset($_rt[$key])) {
            if (!jrCore_write_to_file($file,$_rt[$key]['template_body'])) {
                jrCore_notice('CRI',"Unable to write to template cache directory: data/cache/jrCore");
            }
        }
        // Check for skin template
        elseif (isset($directory) && is_dir(APP_DIR ."/skins/{$directory}") && is_file(APP_DIR ."/skins/{$directory}/{$template}")) {
            if (!copy(APP_DIR ."/skins/{$directory}/{$template}",$file)) {
                jrCore_notice('CRI',"Unable to copy skins/{$directory}/{$template} to template cache directory: data/cache/jrCore");
            }
        }
        // Module template
        elseif (is_dir(APP_DIR ."/modules/{$directory}/templates")) {
            if (!copy(APP_DIR ."/modules/{$directory}/templates/{$template}",$file)) {
                jrCore_notice('CRI',"Unable to copy modules/{$directory}/templates/{$template} to template cache directory: data/cache/jrCore");
            }
        }
        else {
            jrCore_notice('CRI',"Invalid template: {$template}, or template directory: {$directory}");
        }
    }
    return $file;
}

/**
 * Returns a 404 page not found
 * @return null
 */
function jrCore_page_not_found()
{
    $out = jrCore_parse_template('404.tpl',array());
    header('HTTP/1.0 404 Not Found');
    header('Connection: close');
    header('Content-Length: '. strlen($out));
    header("Content-Type: text/html; charset=utf-8");
    ob_start();
    echo $out;
    ob_end_flush();
    exit;
}

/**
 * Create a new master CSS files from module and skin CSS files
 * @param string $skin Skin to create CSS file for
 * @return string Returns MD5 checksum of CSS contents
 */
function jrCore_create_master_css($skin)
{
    global $_conf;
    // Make sure we get a good skin
    if (!is_dir(APP_DIR ."/skins/{$skin}")) {
        return false;
    }

    // Create our output
    $out = '';
    // First - round up any custom CSS from modules
    $_tm = jrCore_get_registered_module_features('jrCore','css');
    // [jrCore] => Array
    //     (
    //         [jrCore.css] => 1
    //         [fileuploader.css] => 1
    //         [jquery.lightbbox.css] => 1
    //     )
    // [jrAudio] => Array
    //     (
    //         [jrAudio.css] => 1
    //     )
    if (isset($_tm) && is_array($_tm)) {
        foreach ($_tm as $mod => $_entries) {
            if (!is_dir(APP_DIR ."/modules/{$mod}")) {
                // Skin gets added below so it can override everything it needs
                continue;
            }
            foreach ($_entries as $script => $ignore) {
                if (strpos($script,'http') === 0 || strpos($script,'//') === 0)  {
                    continue;
                }
                if (strpos($script,APP_DIR) !== 0) {
                    $script = APP_DIR ."/modules/{$mod}/css/{$script}";
                }
                if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
                    $out .= "\n\n". @file_get_contents($script);
                }
                else {
                    $o = false;
                    $_tmp = @file($script);
                    if (isset($_tmp) && is_array($_tmp)) {
                        $out .= "\n/* ". str_replace(APP_DIR .'/','',$script) ." */\n";
                        foreach ($_tmp as $line) {
                            $line = trim($line);
                            // check for start of comment
                            if (strpos($line,'/*') === 0 && !$o) {
                                if (!strpos(' '. $line,'*/')) {
                                    // start of multi-line comment
                                    $o = true;
                                }
                                continue;
                            }
                            if ($o) {
                                // We're still in a comment - see if we are closing
                                if (strpos(' '. $line,'*/')) {
                                    // Closed - continue
                                    $o = false;
                                }
                                continue;
                            }
                            elseif (strpos(' '. $line,'*/')) {
                                // Closing comment tag
                                continue;
                            }
                            if (strlen($line) > 0) {
                                $out .= "{$line}\n";
                            }
                        }
                    }
                }
            }
        }
    }

    // Skin last (so it can override modules if needed)
    if (isset($_tm[$skin]) && is_array($_tm[$skin])) {
        foreach ($_tm[$skin] as $script => $ignore) {
            if (strpos($script,'http') === 0 || strpos($script,'//') === 0)  {
                // full URLs to external sources are handled at registration time
                continue;
            }
            if (strpos($script,APP_DIR) !== 0) {
                $script = APP_DIR ."/skins/{$skin}/css/{$script}";
            }
            if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
                $out .= "\n\n". @file_get_contents($script);
            }
            else {
                $_tmp = @file($script);
                if (isset($_tmp) && is_array($_tmp)) {
                    $out .= "\n/* ". str_replace(APP_DIR .'/','',$script) ." */\n";
                    foreach ($_tmp as $line) {
                        $line = trim($line);
                        // Check for comment line
                        if (strpos($line,'/*') === 0 || strpos($line,'*') === 0) {
                            continue;
                        }
                        if (strlen($line) > 0) {
                            $out .= "{$line}\n";
                        }
                    }
                }
            }
        }
    }

    // Next, get our customized style from the database
    $tbl = jrCore_db_table_name('jrCore','skin');
    $req = "SELECT skin_custom_css FROM {$tbl} WHERE skin_directory = '". jrCore_db_escape($skin) ."'";
    $_rt = jrCore_db_query($req,'SINGLE');
    if (isset($_rt['skin_custom_css']{1})) {
        $_custom = json_decode($_rt['skin_custom_css'],true);
        if (isset($_custom) && is_array($_custom)) {
            $out .= "\n/* custom */\n";
            foreach ($_custom as $sel => $_rules) {
                $out .= $sel ." {\n";
                $_tm = array();
                foreach ($_rules as $k => $v) {
                    $_tm[] = $k .':'. $v .';';
                }
                $out .= implode("\n",$_tm) ."\n}\n";
            }
        }
    }

    $url = $_conf['jrCore_base_url'];
    $prt = jrCore_get_server_protocol();
    if (isset($prt) && $prt === 'https') {
        $url = str_replace('http:','https:',$url);
    }
    // Save file
    $sum = md5($out);
    $out = "/* {$_conf['jrCore_system_name']} css ". date('r') ." */\n". str_replace('{$jamroom_url}',$url,$out);
    $cdr = jrCore_get_module_cache_dir($skin);

    // Our SSL version of the CSS file is prefixed with an "S".
    if (isset($prt) && $prt === 'https') {
        jrCore_write_to_file("{$cdr}/S{$sum}.css",$out,true);
    }
    else {
        jrCore_write_to_file("{$cdr}/{$sum}.css",$out,true);
    }

    // We need to store the MD5 of this file in the settings table - thus
    // we don't have to look it up on each page load, and we can then set
    // a VERSION on the css so our visitors will immediately see any CSS
    // changes without having to worry about a cached old version
    $_field = array(
        'name'     => "{$skin}_css_version",
        'type'     => 'hidden',
        'validate' => 'md5',
        'value'    => $sum,
        'default'  => $sum
    );
    jrCore_update_setting('jrCore',$_field);
    return $sum;
}

/**
 * jrCore_create_master_javascript
 * @param string $skin Skin to create Javascript file for
 * @return string Returns MD5 checksum of Javascript contents
 */
function jrCore_create_master_javascript($skin)
{
    global $_conf, $_urls;
    // Make sure we get a good skin
    if (!is_dir(APP_DIR ."/skins/{$skin}")) {
        return false;
    }

    // Create our output
    require_once APP_DIR .'/modules/jrCore/contrib/jsmin/jsmin.php';

    // Create our output
    $url = $_conf['jrCore_base_url'];
    $prt = jrCore_get_server_protocol();
    if (isset($prt) && $prt === 'https') {
        $url = str_replace('http:','https:',$url);
    }

    $out = "/* {$_conf['jrCore_system_name']} js */\nvar core_system_url='{$url}';\nvar core_active_skin='{$skin}';\n";

    // We keep track of the MP5 hash of every JS script we include - this
    // keeps us from including the same JS from different modules
    $_hs = array();

    // First - round up any custom JS from modules
    $_tm = jrCore_get_registered_module_features('jrCore','javascript');
    // Add in custom module javascript
    if (isset($_tm) && is_array($_tm)) {
        $_ur = array_flip($_urls);
        $_dn = array();
        foreach ($_tm as $mod => $_entries) {
            if ($mod == $skin) {
                continue;
            }
            $url = $_ur[$mod];
            if (!isset($_dn[$url])) {
                $out .= "var {$mod}_url='{$url}';\n";
                $_dn[$url] = 1;
            }
        }
        foreach ($_tm as $mod => $_entries) {
            if ($mod == $skin) {
                continue;
            }
            foreach ($_entries as $script => $ignore) {
                if (strpos($script,'http') === 0 || strpos($script,'//') === 0) {
                    continue;
                }
                if (strpos($script,APP_DIR) !== 0) {
                    $script = APP_DIR ."/modules/{$mod}/js/{$script}";
                }
                $tmp = @file_get_contents($script);
                // This MD5 check ensures we don't include the same JS script 2 times from different modules
                $key = md5($tmp);
                if (!isset($_hs[$key])) {
                    if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
                        $out .= "{$tmp}\n\n";
                    }
                    else {
                        if (!strpos($script,'.min')) {
                            $out .= JSMin::minify($tmp) ."\n\n";
                        }
                        else {
                            $out .= "{$tmp}\n\n";
                        }
                    }
                    $_hs[$key] = 1;
                }
            }
        }
    }

    // Skin last (so it can override modules if needed)
    if (isset($_tm[$skin]) && is_array($_tm[$skin])) {
        foreach ($_tm[$skin] as $script => $ignore) {
            if (strpos($script,'http') === 0 || strpos($script,'//') === 0) {
                continue;
            }
            if (strpos($script,APP_DIR) !== 0) {
                $script = APP_DIR ."/skins/{$skin}/js/{$script}";
            }
            $tmp = @file_get_contents($script);
            $key = md5($tmp);
            if (!isset($_hs[$key])) {
                if (isset($_conf['jrDeveloper_developer_mode']) && $_conf['jrDeveloper_developer_mode'] == 'on') {
                    $out .= "{$tmp}\n\n";
                }
                else {
                    if (!strpos($script,'.min')) {
                        $out .= JSMin::minify($tmp) ."\n\n";
                    }
                    else {
                        $out .= "{$tmp}\n\n";
                    }
                }
                $_hs[$key] = 1;
            }
        }
    }

    // Save file
    $sum = md5($prt .'-'. $out);
    $cdr = jrCore_get_module_cache_dir($skin);
    if (isset($prt) && $prt === 'https') {
        jrCore_write_to_file("{$cdr}/S{$sum}.js",$out,true);
    }
    else {
        jrCore_write_to_file("{$cdr}/{$sum}.js",$out,true);
    }

    // We need to store the MD5 of this file in the settings table - thus
    // we don't have to look it up on each page load, and we can then set
    // a VERSION on the js so our visitors will immediately see any JS
    // changes without having to worry about a cached old version
    $_field = array(
        'name'     => "{$skin}_javascript_version",
        'type'     => 'hidden',
        'validate' => 'md5',
        'value'    => $sum,
        'default'  => $sum
    );
    jrCore_update_setting('jrCore',$_field);
    return $sum;
}

