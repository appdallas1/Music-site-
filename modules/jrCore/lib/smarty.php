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
 * @package Smarty Functions and Modifiers
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Core Media Player
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_media_player($params,$smarty)
{
    // {jrCore_media_player type="jrAudio_blue_monday" module="jrAudio" field="audio_file" item_id=# autoplay=false}
    // {jrCore_media_player type="jrVideo_blue_monday" module="jrVideo" field="video_file" item_id=# autoplay=false}
    // {jrCore_media_player type="jrPlaylist_blue_monday" module="jrPlaylist" item_id=# autoplay=false}
    global $_conf;

    // We have several required fields
    $_rq = array('type','module');
    foreach ($_rq as $param) {
        if (!isset($params[$param]) || strlen($params[$param]) === 0) {
            $out = "jrCore_media_player: invalid {$param} parameter";
            if (!empty($params['assign'])) {
                $smarty->assign($params['assign'],$out);
                return '';
            }
            return $out;
        }
    }

    // Get registered players
    $_pl = jrCore_get_registered_media_players('all');

    // Make sure our type is valid
    if (!isset($_pl["{$params['type']}"])) {
        $out = 'jrCore_media_player: invalid type parameter';
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$out);
            return '';
        }
        return $out;
    }

    // See if our skin has registered a default skin
    if ($params['type'] != 'jrAudio_button') {
        $_tmp = jrCore_get_registered_module_features('jrCore','media_player_skin');
        if (isset($_tmp) && isset($_tmp["{$_conf['jrCore_active_skin']}"]) && isset($_tmp["{$_conf['jrCore_active_skin']}"]["{$params['module']}"])) {
            $params['type'] = $_tmp["{$_conf['jrCore_active_skin']}"]["{$params['module']}"];
        }
    }

    $mod = $_pl["{$params['type']}"];
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$params['type']}.tpl")) {
        $mod = false;
    }
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/css/{$params['type']}.css")) {
        $css = "{$_conf['jrCore_base_url']}/skins/{$_conf['jrCore_active_skin']}/css/{$params['type']}.css";
    }
    elseif (is_file(APP_DIR ."/modules/{$mod}/css/{$params['type']}.css")) {
        $css = "{$_conf['jrCore_base_url']}/modules/{$mod}/css/{$params['type']}.css";
    }

    // Setup common parameters
    $_rep = array();

    // auto play
    $_rep['autoplay'] = 'false';
    if (isset($params['autoplay']) && ($params['autoplay'] === 1 || $params['autoplay'] === true || $params['autoplay'] == 'true' || $params['autoplay'] == 'on')) {
        $_rep['autoplay'] = 'true';
    }

    // Get our playlist info
    if (isset($params['item_id']) && jrCore_checktype($params['item_id'],'number_nz')) {
        $_rt = array(
            jrCore_db_get_item($params['module'],$params['item_id'])
        );
        if (!isset($_rt[0]) || !is_array($_rt[0])) {
            $out = '';
            if (!empty($params['assign'])) {
                $smarty->assign($params['assign'],$out);
                return '';
            }
            return $out;
        }
    }
    // Single item as an array
    elseif (isset($params['item']) && is_array($params['item']) && isset($params['item']['_item_id'])) {
        $_rt = array($params['item']);
        unset($params['item']);
    }
    // Array of items
    elseif (isset($params['items']) && is_array($params['items']) && count($params['items']) > 0) {
        $_rt = $params['items'];
        unset($params['items']);
    }

    // Get items
    $_fmt = array();
    if (!isset($_rt) || !is_array($_rt)) {

        // Go get our media based on params
        $_args = array();
        foreach ($params as $k => $v) {
            // Search
            if (strpos($k,'search') === 0) {
                if (!isset($_args['search'])) {
                    $_args['search'] = array();
                }
                $_args['search'][] = $v;
            }
            // Order by
            elseif (strpos($k,'order_by') === 0) {
                if (!isset($_args['order_by'])) {
                    $_args['order_by'] = array();
                }
                list($fld,$dir) = explode(' ',$v);
                $fld = trim($fld);
                $_args['order_by'][$fld] = trim($dir);
            }
            // Group By
            elseif ($k == 'group_by') {
                $_args['group_by'] = trim($v);
            }
            // Limit
            elseif ($k == 'limit') {
                $_args['limit'] = (int) $v;
            }
        }
        if (isset($_args) && is_array($_args) && count($_args) > 0) {
            $_args['exclude_jrProfile_quota_keys'] = true;
            $_rt = jrCore_db_search_items($params['module'],$_args);
            if (isset($_rt['_items']) && is_array($_rt['_items'])) {
                $_rt = $_rt['_items'];
            }
        }

        // Make sure we got media items
        if (!isset($_rt) || !is_array($_rt)) {
            // No media
            $out = 'jrCore_media_player: no media found for player';
            if (!empty($params['assign'])) {
                $smarty->assign($params['assign'],$out);
                return '';
            }
            return $out;
        }
    }

    // Send out player playlist trigger
    $_rt = jrCore_trigger_event('jrCore','media_playlist',$_rt,$params);

    // Our allowed formats
    $_fm = array(
        'mp3' => 1,
        'flv' => 1
    );

    // Prepare our playlist setup
    $_rep['media'] = array();
    foreach ($_rt as $_item) {

        // media_playlist listeners can setup their own
        // media_playlist_url and media_playlist_ext
        if (!isset($_item['media_playlist_ext']{0}) || !isset($_item['media_playlist_ext']{0})) {
            $ext = false;
            $fld = false;
            if (isset($params['field'])) {
                // We know the field, so the module is for the item
                $ext = $_item["{$params['field']}_extension"];
                $pfx = jrCore_db_get_prefix($params['module']);
                $url = jrCore_get_module_url($params['module']);
            }
            else {
                // We need to figure out our extension
                foreach ($_item as $k => $v) {
                    if (strpos($k,'_extension') && !strpos($k,'_original') && isset($_fm[$v])) {
                        $fld = str_replace('_extension','',$k);
                        $ext = $v;
                        break;
                    }
                }
                if (!$ext) {
                    // unknown file type
                    continue;
                }
                // We have to figure out the module based on the item
                $pfx = jrCore_db_get_prefix($_item['module']);
                $url = jrCore_get_module_url($_item['module']);
            }
            $fld = ($fld) ? $fld : $params['field'];
            $str = "{$_conf['jrCore_base_url']}/{$url}/stream/{$fld}/{$_item['_item_id']}/key=[jrCore_media_play_key]/file.{$ext}";
            $img = "{$_conf['jrCore_base_url']}/{$url}/image/{$pfx}_image/{$_item['_item_id']}/large/image.png";
            $_item["{$pfx}_artist"] = $_item['profile_name'];
        }
        else {
            $url = jrCore_get_module_url($_item['module']);
            $pfx = jrCore_db_get_prefix($_item['module']);
            $ext = $_item['media_playlist_ext'];
            $str = $_item['media_playlist_url'];
            $img = (isset($_item['media_playlist_img'])) ? $_item['media_playlist_img'] : '';
        }
        if (isset($_fm[$ext])) {
            $_rep['media'][] = array(
                'title'      => $_item["{$pfx}_title"],
                'artist'     => $_item['profile_name'],
                'poster'     => $img,
                'module'     => $params['module'],
                'module_url' => $url,
                'prefix'     => $pfx,
                'item_id'    => $_item['_item_id'],
                '_item'      => $_item,
                'formats'    => array(
                    $ext     => $str
                )
            );
            $_fmt[$ext] = $ext;
        }
    }

    // Additional items
    $_rep['uniqid']   = 'm'. uniqid();
    $_rep['formats']  = implode(',',$_fmt);
    $_rep['params']   = $params;
    $_rep['solution'] = 'html,flash';

    // TEMP: If this is Firefox or IE, we fall back to flash for audio
    if (stristr($_SERVER['HTTP_USER_AGENT'],'firefox') || stristr($_SERVER['HTTP_USER_AGENT'],'MSIE')) {
        $_rep['solution'] = 'flash,html';
    }

    // Parse and return
    $out = '';
    if (isset($css) && strlen($css) > 0 && !isset($GLOBALS["JRCORE_MEDIA_PLAYER_{$params['type']}"])) {
        $out .= '<link rel="stylesheet" href="'. $css .'" media="screen" />'."\n";
    }
    $out .= jrCore_parse_template("{$params['type']}.tpl",$_rep,$mod);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Display a Skin Menu with registered entries
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_skin_menu($params,$smarty)
{
    global $_conf, $_user;

    // See if we have cached content for this user...
    $key = "skin_menu_visitor";
    if (jrUser_is_logged_in()) {
        $key = "skin_menu_{$_user['_user_id']}";
    }
    if ($tmp = jrCore_is_cached('jrCore',$key)) {
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$tmp);
        }
        return $tmp;
    }

    // Get core menu items
    $tbl = jrCore_db_table_name('jrCore','menu');
    $req = "SELECT *, CONCAT_WS('/',menu_module,menu_unique) AS menu_key FROM {$tbl} ORDER BY menu_order ASC";
    $_rt = jrCore_db_query($req,'menu_key',false,null,false);

    // See if we have anything that has registered
    $_tmp = jrCore_get_registered_module_features('jrCore','skin_menu_item');
    if ($_tmp) {
        // We have registered skin menu entries.  We need to go through each
        // one and make sure it is setup in the menu table
        foreach ($_tmp as $module => $_options) {
            if (!jrCore_module_is_active($module)) {
                // This module is not active
                continue;
            }
            // module, unique_id, user_group, label, action, function
            // $_tmp[$module][$label] = array($unique,$user_group,$url,$notify_function,$onclick);
            foreach ($_options as $unq => $_inf) {
                if (!isset($_rt["{$module}/{$unq}"])) {
                    // We are not setup - setup...
                    // We always place new entries at the bottom - find out our lowest number in
                    // our configured skin menu entries
                    $ord = 100;
                    foreach ($_tmp as $_op) {
                        foreach ($_op as $_o) {
                            if (isset($_o['order']) && $_o['order'] >= $ord) {
                                $ord = ($_o['order'] + 1);
                            }
                        }
                    }
                    $mod = jrCore_db_escape($module);
                    $lbl = jrCore_db_escape($_inf['label']);
                    $grp = jrCore_db_escape($_inf['group']);
                    $act = jrCore_db_escape($_inf['url']);
                    $fnc = (!is_null($_inf['function'])) ? jrCore_db_escape($_inf['function']) : '';
                    $onc = (!is_null($_inf['onclick'])) ? jrCore_db_escape($_inf['onclick']) : '';
                    $req = "INSERT INTO {$tbl} (menu_module,menu_unique,menu_active,menu_label,menu_action,menu_groups,menu_order,menu_function,menu_onclick)
                            VALUES ('{$mod}','{$unq}','on','{$lbl}','{$act}','{$grp}','{$ord}','{$fnc}','{$onc}')
                            ON DUPLICATE KEY UPDATE menu_action = '{$act}', menu_function = '{$fnc}', menu_onclick = '{$onc}'";
                    $cnt = jrCore_db_query($req,'COUNT',false,null,false);
                    if (!isset($cnt) || $cnt !== 1) {
                        jrCore_logger('CRI',"unable to create new menu entry for {$module}/{$act}");
                    }
                    else {
                        $_rt["{$module}/{$unq}"] = array(
                            'menu_module'   => $module,
                            'menu_unique'   => $unq,
                            'menu_active'   => 'on',
                            'menu_label'    => $_inf['label'],
                            'menu_action'   => $_inf['url'],
                            'menu_groups'   => $_inf['group'],
                            'menu_order'    => (isset($_inf['order']) && jrCore_checktype($_inf['order'],'number_nz')) ? (int) $_inf['order'] : 90,
                            'menu_function' => (!is_null($_inf['function'])) ? $_inf['function'] : '',
                            'menu_onclick'  => (!is_null($_inf['onclick'])) ? $_inf['onclick'] : ''
                        );
                    }
                }
            }
        }
    }
    if (!isset($_rt) || !is_array($_rt)) {
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],'');
        }
        return '';
    }

    // Bring in strings
    $_lang = jrUser_load_lang_strings();

    // Go through each and process via template
    $alert = 0;
    $_ct = array();
    $_ci = array();
    $tpl = 'skin_menu.tpl';
    $dir = 'jrCore';
    if (isset($params['template']) && is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/{$params['template']}")) {
        $tpl = $params['template'];
        $dir = NULL;
    }
    foreach ($_rt as $k => $_opt) {
        if (!jrCore_module_is_active($_opt['menu_module'])) {
            // This module is not active
            unset($_rt[$k]);
            continue;
        }
        elseif (isset($_user["quota_{$_opt['menu_module']}_allowed"]) && $_user["quota_{$_opt['menu_module']}_allowed"] != 'on') {
            // User is not allowed based on quota
            unset($_rt[$k]);
            continue;
        }
        // See if we have been given a specific category
        if (isset($params['category']) && strlen($params['category']) > 0 && $_opt['menu_category'] != $params['category']) {
            unset($_rt[$k]);
            continue;
        }
        if (!isset($_opt['menu_active']) || $_opt['menu_active'] != 'on') {
            unset($_rt[$k]);
            continue;
        }
        if (isset($_opt['menu_groups']) && strpos($_opt['menu_groups'],',')) {
            $_grp = explode(',',$_opt['menu_groups']);
        }
        else {
            $_grp = array(trim($_opt['menu_groups']));
        }
        if (!isset($_grp) || !is_array($_grp)) {
            unset($_rt[$k]);
            continue;
        }

        $show = false;
        foreach ($_grp as $group) {

            // See if this user as access.
            switch ($group) {
                case 'master':
                    if (jrUser_is_master()) {
                        $show = true;
                    }
                    break;
                case 'admin':
                    if (jrUser_is_admin()) {
                        $show = true;
                    }
                    break;
                case 'power':
                    if (jrUser_is_power_user()) {
                        $show = true;
                    }
                    break;
                case 'multi':
                    if (jrUser_is_multi_user()) {
                        $show = true;
                    }
                    break;
                case 'user':
                    if (jrUser_is_logged_in()) {
                        $show = true;
                    }
                    break;
                case 'visitor':
                    if (!jrUser_is_logged_in()) {
                        $show = true;
                    }
                    break;
                case 'all':
                    // Shown to everyone
                    $show = true;
                    break;
                default:
                    // See if we have been given a quota...
                    if (isset($group) && jrCore_checktype($group,'number_nz') && $_user['profile_quota_id'] == $group) {
                        $show = true;
                    }
                    break;
            }
        }
        if (!$show) {
            unset($_rt[$k]);
            continue;
        }

        // Build our categories...
        $cat = 'default';
        if (isset($_opt['menu_category']) && strlen($_opt['menu_category']) > 0) {
            if (is_numeric($_opt['menu_category']) && isset($_lang["{$_conf['jrCore_active_skin']}"]["{$_opt['menu_category']}"])) {
                $_opt['menu_category'] = $_lang["{$_conf['jrCore_active_skin']}"]["{$_opt['menu_category']}"];
            }
            $cat = $_opt['menu_category'];
        }
        if (!isset($_ct[$cat])) {
            $_ct[$cat] = 0;
            $_ci[$cat] = array();
        }
        $_ct[$cat]++;

        $lbl = $_opt['menu_label'];
        $_rt[$k]['menu_label'] = (isset($lbl) && isset($_lang["{$_opt['menu_module']}"][$lbl])) ? $_lang["{$_opt['menu_module']}"][$lbl] : $lbl;
        if (strpos($_opt['menu_action'],'http') === 0) {
            $_rt[$k]['menu_url'] = $_opt['menu_action'];
        }
        else {
            if ($_opt['menu_module'] != 'CustomEntry' && !strpos($_opt['menu_action'],'/')) {
                $murl = jrCore_get_module_url($_opt['menu_module']);
                $_rt[$k]['menu_url'] = "{$_conf['jrCore_base_url']}/{$murl}/{$_opt['menu_action']}";
            }
            else {
                if (strpos(trim(trim($_opt['menu_action']),'/'),'/')) {
                    $_rt[$k]['menu_url'] = "{$_conf['jrCore_base_url']}/{$_opt['menu_action']}";
                }
                else {
                    $murl = jrCore_get_module_url($_opt['menu_module']);
                    $_rt[$k]['menu_url'] = "{$_conf['jrCore_base_url']}/{$murl}/{$_opt['menu_action']}";
                }
            }
        }

        // See if this menu item has a FUNCTION that needs to be run
        if (isset($_opt['menu_function']) && function_exists($_opt['menu_function'])) {

            // Our menu function can return a NUMBER, an IMAGE or bool TRUE/FALSE
            $res = $_opt['menu_function']($_conf,$_user);
            if (!$res) {
                // Function returned FALSE - don't show menu item
                unset($_rt[$k]);
                continue;
            }
            elseif (isset($res) && is_numeric($res)) {
                // Number - show next to title - i.e. this is a "notification"
                $_rt[$k]['menu_function_result'] = $res;
                $alert += $res;
            }
            elseif (isset($res) && strlen($res) > 0 && is_file(APP_DIR ."/modules/{$_opt['menu_module']}/img/{$res}")) {
                // Image
                switch (jrCore_file_extension($res)) {
                    case 'gif':
                    case 'png':
                    case 'jpg':
                    case 'jpeg':
                        $_rt[$k]['menu_function_result'] = $res;
                        break;
                }
            }

        }
        // By category too
        $_ci[$cat][$k] = $_rt[$k];
    }
    $params['menu_id'] = (isset($params['menu_id'])) ? $params['menu_id'] : 'skin_menu';
    $params['label']   = (isset($params['label']) && isset($_lang["{$_conf['jrCore_active_skin']}"]["{$params['label']}"])) ? $_lang["{$_conf['jrCore_active_skin']}"]["{$params['label']}"] : $params['label'];
    $_rp = array(
        '_items'             => $_rt,
        '_categories'        => $_ct,
        '_items_by_category' => $_ci,
        'params'             => $params,
        'alert'              => $alert
    );
    unset($_rt);
    $out = jrCore_parse_template($tpl,$_rp,$dir);

    // Save to cache
    jrCore_add_to_cache('jrCore',$key,$out);

    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Count a hit for a module item
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_counter($params,$smarty)
{
    if (!isset($params['module']{0})) {
        return 'jrCore_counter: module parameter required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        // Not installed or wrong...
        return '';
    }
    if (!isset($params['name']{0})) {
        return 'jrCore_counter: name parameter required';
    }
    if (!isset($params['item_id']) || !jrCore_checktype($params['item_id'],'number_nz')) {
        return 'jrImage_counter: item_id parameter required';
    }
    $inc = 1;
    if (isset($params['increment']) && jrCore_checktype($params['increment'],'number_nz')) {
        $inc = intval($params['increment']);
    }
    // Count it
    jrCore_counter($params['module'],$params['item_id'],$params['name'],$inc);
    return '';
}

/**
 * Get hit Count for a module item
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_get_count($params,$smarty)
{
    if (!isset($params['module']{0})) {
        return 'jrCore_get_count: module parameter required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        // Not installed or wrong...
        return '';
    }
    if (!isset($params['name']{0})) {

        // No specific field - get counts for entire module
        $cnt = jrCore_db_number_rows($params['module'],'item');
    }
    else {
        // Counts for a specific counter field
        if (!isset($params['item_id'])) {
            // We're doing ALL counts for a specific type
            $cnt = jrCore_get_count($params['module'],$params['name']);
        }
        else {
            if (!jrCore_checktype($params['item_id'],'number_nz')) {
                return 'jrImage_get_count: item_id parameter required';
            }
            $cnt = jrCore_get_count($params['module'],$params['name'],$params['item_id']);
        }
    }
    // Return counter
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$cnt);
        return '';
    }
    return $cnt;
}

/**
 * Embed an image in a template
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_image($params,$smarty)
{
    global $_conf;
    if (!isset($params['image'])) {
        return 'jrCore_image: image name required';
    }
    // See if we have a custom file for this image
    $skn = $_conf['jrCore_active_skin'];
    $tag = '';
    if (isset($params['module'])) {
        $skn = $params['module'];
        $tag = 'mod_';
    }
    $_im = array();
    if (isset($_conf["jrCore_{$skn}_custom_images"]{2})) {
        $_im = json_decode($_conf["jrCore_{$skn}_custom_images"],TRUE);
    }
    if (isset($_im["{$params['image']}"]) && isset($_im["{$params['image']}"][1]) && $_im["{$params['image']}"][1] == 'on') {
        $src = "{$_conf['jrCore_base_url']}/data/media/0/0/{$tag}{$skn}_{$params['image']}?r=". $_im["{$params['image']}"][0];
    }
    else {
        if (isset($params['module'])) {
            $src = "{$_conf['jrCore_base_url']}/modules/{$skn}/img/{$params['image']}";
        }
        else {
            $src = "{$_conf['jrCore_base_url']}/skins/{$skn}/img/{$params['image']}";
        }
    }
    $out = "<img src=\"{$src}\" ";
    // Our other params are optional
    foreach ($params as $k => $v) {
        $k = jrCore_str_to_lower($k);
        switch ($k) {
            case 'width':
            case 'height':
            case 'alt':
            case 'class':
            case 'style':
                $out .= "{$k}=\"{$v}\" ";
                break;
        }
    }
    $out = rtrim($out,' ') . '>';
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Embed a Power List into a template
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_list($params,$smarty)
{
    global $_conf, $_post;
    if (!isset($params['module']{0})) {
        return 'jrCore_list: module name required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        // Not installed or wrong...
        return '';
    }
    // Check for cache
    $key = json_encode($params);
    if ($tmp = jrCore_is_cached($params['module'],$key)) {
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$tmp);
            return '';
        }
        return $tmp ."\n<!--c-->";
    }
    $tpl_dir = null;
    if (!isset($params['template']) || strlen($params['template']) === 0) {
        // Check for Skin override
        if (is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/{$params['module']}_item_list.tpl")) {
            $params['template'] = "{$params['module']}_item_list.tpl";
        }
        // See if this module provides one
        elseif (is_file(APP_DIR ."/modules/{$params['module']}/templates/item_list.tpl")) {
            $tpl_dir = $params['module'];
            $params['template'] = 'item_list.tpl';
        }
        else {
            return "ERROR: {$params['module']}/templates/item_list.tpl NOT FOUND";
        }
    }
    else {
        // Check for template
        if (!is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/{$params['template']}") && is_file(APP_DIR ."/modules/{$params['module']}/templates/{$params['template']}")) {
            $tpl_dir = $params['module'];
        }
    }
    $module = $params['module'];
    unset($params['module']);

    // Next - we need to see if this is a list that is on a profile - if it is, and the
    // quota the profile belongs to has a max_items setting enabled, we have to limit
    // the number of items that are going to be shown.
    if (isset($params['profile_id']) && isset($smarty->tpl_vars['jr_template']->value) && $smarty->tpl_vars['jr_template']->value == 'item_index.tpl') {
        // We are showing an item_index - see if this profile is limited
        if (isset($smarty->tpl_vars["quota_{$module}_max_items"]->value) && jrCore_checktype($smarty->tpl_vars["quota_{$module}_max_items"]->value,'number_nz')) {
            // We have a LIMIT - enforce
            $max = (int) $smarty->tpl_vars["quota_{$module}_max_items"]->value;
            if (!isset($params['limit']) || $params['limit'] > $max) {
                $params['limit'] = $max;
            }
        }
    }

    // $params = array(
    //     'search' => array(
    //         'user_name = brian',
    //         'user_weight > 100'
    //     ),
    //     'order_by' => array(
    //         'user_name' => 'asc',
    //         'user_weight' => 'desc'
    //     ),
    //     'limit' => 50
    // );
    // {jrCore_list module="jrProfile" search1="profile_name = brian" search2="profile_name != test" order_by="created desc" template="list_profile_row.tpl" limit=5}
    // Set params for our function
    $_args = array();
    foreach ($params as $k => $v) {
        // Search
        if (strpos($k,'search') === 0) {
            if (!isset($_args['search'])) {
                $_args['search'] = array();
            }
            $_args['search'][] = $v;
        }
        // Order by
        elseif (strpos($k,'order_by') === 0) {
            if (!isset($_args['order_by'])) {
                $_args['order_by'] = array();
            }
            list($fld,$dir) = explode(' ',$v);
            $fld = trim($fld);
            $_args['order_by'][$fld] = trim($dir);
        }
        // Group By
        elseif ($k == 'group_by') {
            $_args['group_by'] = trim($v);
        }
        // Limit
        elseif ($k == 'limit') {
            $_args['limit'] = (int) $v;
        }
        // Page break
        elseif ($k == 'pagebreak') {
            $_args['pagebreak'] = (int) $v;
        }
        // Page
        elseif ($k == 'page') {
            $_args['page'] = (int) $v;
        }
        elseif ($k == 'return_keys') {
            $_args['return_keys'] = explode(',',$v);
        }
        else {
            // Everything else
            $_args[$k] = $v;
        }
    }

    // Prep our data for display
    $_rs = jrCore_db_search_items($module,$_args);
    if (isset($_rs) && !is_array($_rs) && strpos($_rs,'error') === 0) {
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$_rs);
            return '';
        }
        return $_rs;
    }
    if (isset($_rs['_items']) && is_array($_rs['_items']) && count($_rs['_items']) > 0) {
        foreach ($_rs['_items'] as $k => $_v) {
            $_rs['_items'][$k]['list_rank'] = (isset($_rs['info']['page']) && $_rs['info']['page'] > 1) ? intval($k + ((($_rs['info']['page'] - 1) * $_rs['info']['pagebreak']) + 1)) : intval($k + 1);
        }
    }

    // If we have been given NO template, just assign vars and return
    if (isset($params['template']) && $params['template'] == 'null' && !empty($params['assign'])) {
        $tmp = $_rs['_items'];
    }
    else {
        // Parse our template and return results
        $tmp = jrCore_parse_template($params['template'],$_rs,$tpl_dir);

        // See if we are including the default pager
        if (isset($params['pager']) && $params['pager'] == true) {
            $tpl = 'list_pager.tpl';
            $dir = 'jrCore';
            if (isset($params['pager_template'])) {
                $tpl = $params['pager_template'];
                $dir = $_conf['jrCore_active_skin'];
            }
            $tmp .= jrCore_parse_template($tpl,$_rs,$dir);
        }
    }
    $pid = 0;
    if (isset($params['profile_id']) && jrCore_checktype($params['profile_id'],'number_nz')) {
        $pid = (int) $params['profile_id'];
    }
    elseif (isset($_post['_profile_id']) && jrCore_checktype($_post['_profile_id'],'number_nz')) {
        $pid = (int) $_post['_profile_id'];
    }
    jrCore_add_to_cache($module,$key,$tmp,false,$pid);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$tmp);
        return '';
    }
    return $tmp;
}

/**
 * Run a Smarty template function for a module
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_module_function($params,$smarty)
{
    if (!isset($params['function']{0})) {
        return 'jrCore_module_function: function name required';
    }
    $mod = substr($params['function'],0,strpos($params['function'],'_'));
    if (!jrCore_module_is_active($mod)) {
        return '';
    }
    $func = "smarty_function_{$params['function']}";
    if (!function_exists($func)) {
        // Not installed or wrong...
        return '';
    }
    unset($params['function']);
    return $func($params,$smarty);
}

/**
 * Jamroom CSS SRC URL generator
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_css_src($params,$smarty)
{
    global $_conf;
    if (!isset($params['skin']{0})) {
        $params['skin'] = $_conf['jrCore_active_skin'];
    }
    $skn = $params['skin'];
    $sum = (isset($_conf["jrCore_{$skn}_css_version"])) ? $_conf["jrCore_{$skn}_css_version"] : '';
    $prt = jrCore_get_server_protocol();
    $cdr = jrCore_get_module_cache_dir($skn);
    if (isset($prt) && $prt === 'https') {
        if ((strlen($sum) === 0 || !is_file("{$cdr}/S{$sum}.css")) || (isset($_conf['jrCore_default_cache_seconds']) && $_conf['jrCore_default_cache_seconds'] == '0')) {
            $sum = jrCore_create_master_css($skn);
        }
        return "{$_conf['jrCore_base_url']}/data/cache/{$skn}/S{$sum}.css";
    }
    else {
        if ((strlen($sum) === 0 || !is_file("{$cdr}/{$sum}.css")) || (isset($_conf['jrCore_default_cache_seconds']) && $_conf['jrCore_default_cache_seconds'] == '0')) {
            $sum = jrCore_create_master_css($skn);
        }
        return "{$_conf['jrCore_base_url']}/data/cache/{$skn}/{$sum}.css";
    }
}

/**
 * Jamroom Javascript SRC URL generator
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_javascript_src($params,$smarty)
{
    global $_conf;
    if (!isset($params['skin']{0})) {
        $params['skin'] = $_conf['jrCore_active_skin'];
    }
    $skn = $params['skin'];
    $sum = (isset($_conf["jrCore_{$skn}_javascript_version"])) ? $_conf["jrCore_{$skn}_javascript_version"] : '';
    $prt = jrCore_get_server_protocol();
    $cdr = jrCore_get_module_cache_dir($skn);
    if (isset($prt) && $prt === 'https') {
        if (!is_file("{$cdr}/S{$sum}.js") || (isset($_conf['jrCore_default_cache_seconds']) && $_conf['jrCore_default_cache_seconds'] == '0')) {
            $sum = jrCore_create_master_javascript($skn);
        }
        return "{$_conf['jrCore_base_url']}/data/cache/{$skn}/S{$sum}.js";
    }
    else {
        if (!is_file("{$cdr}/{$sum}.js") || (isset($_conf['jrCore_default_cache_seconds']) && $_conf['jrCore_default_cache_seconds'] == '0')) {
            $sum = jrCore_create_master_javascript($skn);
        }
        return "{$_conf['jrCore_base_url']}/data/cache/{$skn}/{$sum}.js";
    }
}

/**
 * Generate a unique Form Session for an embedded template form
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_form_create_session($params,$smarty)
{
    global $_conf;
    if (!isset($params['module']) || strlen($params['module']) === 0) {
        return 'jrCore_form_create_session: INVALID MODULE';
    }
    if (!jrCore_module_is_active($params['module'])) {
        return '';
    }
    if (!isset($params['option']) || strlen($params['option']) === 0) {
        return 'jrCore_form_create_session: INVALID OPTION';
    }
    $url = jrCore_get_module_url($params['module']);
    $_fm = array(
        'token' => jrCore_form_token_create(),
        'module' => $params['module'],
        'option' => $params['option'],
        'action' => "{$_conf['jrCore_base_url']}/{$url}/{$params['option']}_save"
    );
    if (isset($params['action']{0})) {
        $_fm['action'] = $params['action'];
    }
    jrCore_form_create_session($_fm['token'], $_fm);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $_fm['token']);
        return '';
    }
    return $_fm['token'];
}

/**
 * Generate a unique Form Token for use in a form
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_form_token($params,$smarty)
{
    $out = jrCore_form_token_create();
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Display a language string by language ID
 *
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_lang($params,$smarty)
{
    if (!isset($params['module']{0})) {
        if (!isset($params['skin']{0})) {
            $out = 'NO_LANG_MODULE_OR_SKIN';
            if (!empty($params['assign'])) {
                $smarty->assign($params['assign'],$out);
                return '';
            }
            return $out;
        }
        $params['module'] = $params['skin'];
    }
    if (!isset($params['id'])) {
        $out = 'INVALID_LANG_ID';
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$out);
            return '';
        }
        return $out;
    }

    // Bring in strings
    $_lang = jrUser_load_lang_strings();

    $out = 'NO_LANG_FOR_ID';
    if (isset($_lang["{$params['module']}"]) && isset($_lang["{$params['module']}"]["{$params['id']}"])) {
        $out = $_lang["{$params['module']}"]["{$params['id']}"];
    }
    elseif (isset($params['default']{0})) {
        if (jrUser_is_master()) {
            $out = '*'. $params['default'] .'*';
        }
    }
    if (strpos(' ' . $out,'%')) {
        $_rp = array();
        foreach ($params as $k => $v) {
            if (is_numeric($k)) {
                $_rp["%{$k}"] = $v;
            }
            $out = str_replace(array_keys($_rp),$_rp,$out);
        }
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Get the configured URL for a specific module
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_module_url($params,$smarty)
{
    global $_urls;
    if (!isset($params['module']{0})) {
        return 'INVALID_MODULE';
    }
    $url = array_search($params['module'],$_urls);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$url);
        return '';
    }
    return $url;
}

/**
 * Format an epoch time stamp to the system configured time format
 * @param int $timestamp Epoch Time Stamp to convert
 * @param string $format Format for output
 * @return string
 */
function smarty_modifier_jrCore_date_format($timestamp,$format = NULL)
{
    return jrCore_format_time($timestamp,FALSE,$format);
}

/**
 * Convert @ tags into links to profiles
 * @param string $text String to convert at tags in
 * @return string
 */
function smarty_modifier_jrCore_convert_at_tags($text)
{
    global $_conf;
    return preg_replace('/(@([_a-z0-9\-]+))/i','<a href="'. $_conf['jrCore_base_url'] .'/$2"><span class="at_link">$1</span></a>',$text);
}

/**
 * Remove HTML and convert newlines in a string
 * @param string $text String to format
 * @param int $quota_id Quota ID for Profile string belongs to
 * @return string
 */
function smarty_modifier_jrCore_format_string($text,$quota_id = 0)
{
    if (jrCore_checktype($quota_id,'number_nz')) {
        // If we have an active Quota ID we need to properly strip tags
        $_qt = jrProfile_get_quota($quota_id);
        if (isset($_qt) && isset($_qt['quota_jrCore_allowed_tags']) && strlen($_qt['quota_jrCore_allowed_tags']) > 0) {
            $text = jrCore_strip_html($text,$_qt['quota_jrCore_allowed_tags']);
        }
        else {
            // No tags allowed
            $text = strip_tags($text);
        }
    }
    else {
        // If we get a Quota ID of 0, we remove all HTML
        $text = strip_tags($text);
    }
    return nl2br($text);
}

/**
 * Return portion of string up to first <!-- pagebreak -->
 * @param $text string String to return substring of
 * @return string
 */
function smarty_modifier_jrCore_readmore($text)
{
    list($before,) = explode('<!-- pagebreak -->',$text,2);
    return jrCore_closetags($before);
}

/**
 * Close open HTML tags in a string that are left unclosed
 * http://stackoverflow.com/questions/3810230/php-how-to-close-open-html-tag-in-a-string
 * @param $html string HTML to close tags for
 * @return string
 */
function jrCore_closetags($html)
{
    preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $result);
    $openedtags = $result[1];
    preg_match_all('#</([a-z]+)>#iU', $html, $result);
    $closedtags = $result[1];
    $len_opened = count($openedtags);
    if (count($closedtags) == $len_opened) {
        return $html;
    }
    $openedtags = array_reverse($openedtags);
    for ($i=0; $i < $len_opened; $i++) {
        if (!in_array($openedtags[$i], $closedtags)) {
            $html .= '</'.$openedtags[$i].'>';
        } else {
            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
        }
    }
    return $html;
}

/**
 * Get Admin Menu index page for a module
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_get_module_index($params,$smarty)
{
    if (!isset($params['module']{0})) {
        return 'jrCore_get_module_index: module name required';
    }
    $out = jrCore_get_module_index($params['module']);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Create a Create button for a new DataStore item
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_item_create_button($params,$smarty)
{
    global $_conf, $_user;
    if (!jrUser_is_logged_in()) {
        return '';
    }
    // Check for group requirement
    if (isset($params['group'])) {
        switch ($params['group']) {
            case 'master':
                if (!jrUser_is_master()) {
                    return '';
                }
                break;
            case 'admin':
                if (!jrUser_is_admin()) {
                    return '';
                }
                break;
        }
    }
    if (!isset($params['module']{0})) {
        return 'jrCore_item_create_button: module name required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        return '';
    }
    $out = '';
    $skn = $_conf['jrCore_active_skin'];
    // See if this user has access to perform this action on this profile
    if (jrProfile_is_profile_owner($params['profile_id'])) {

        // Bring in language strings
        $_lang = jrUser_load_lang_strings();

        // See if we are using the default view
        $def = 'create';
        if (isset($params['view']{0})) {
            $def = trim($params['view']);
        }
        // Figure button ID
        $bid = "{$params['module']}_create";
        if (isset($params['id'])) {
            $bid = $params['id'];
        }
        $anc = TRUE;
        $url = jrCore_get_module_url($params['module']);
        if (isset($params['action'])) {
            $onc = "window.location='{$_conf['jrCore_base_url']}/{$params['action']}'";
            $url = "{$_conf['jrCore_base_url']}/{$params['action']}";
        }
        else {
            $onc = "window.location='{$_conf['jrCore_base_url']}/{$url}/{$def}'";
            $url = "{$_conf['jrCore_base_url']}/{$url}/{$def}";
        }
        // See if we are being given the onclick
        if (isset($params['onclick']{1})) {
            $onc = $params['onclick'];
            $anc = FALSE;
        }

        // See if we are limiting the number of items that can be created by a profile in this quota
        $q_max = isset($_user["quota_{$params['module']}_max_items"]) ? (int) $_user["quota_{$params['module']}_max_items"] : 0;
        $p_cnt = isset($_user["profile_{$params['module']}_item_count"]) ? (int) $_user["profile_{$params['module']}_item_count"] : 0;
        if ($q_max > 0 && $p_cnt >= $q_max) {
            $onc = "alert('". addslashes($_lang['jrCore'][70]) ."');return false;";
            $anc = FALSE;
        }

        if (!isset($params['alt'])) {
            $params['alt'] = $_lang['jrCore'][36];
        }
        if (jrCore_checktype($params['alt'],'number_nz') && isset($_lang["{$params['module']}"]["{$params['alt']}"])) {
            $alt = ' alt="'. htmlentities($_lang["{$params['module']}"]["{$params['alt']}"]) .'"';
        }
        else {
            $alt = ' alt="'. htmlentities($params['alt']) .'"';
        }
        $ttl = '';
        if (isset($params['title'])) {
            if (jrCore_checktype($params['title'],'number_nz') && isset($_lang["{$params['module']}"]["{$params['title']}"])) {
                $ttl = ' title="'. htmlentities($_lang["{$params['module']}"]["{$params['title']}"]) .'"';
            }
            else {
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
        }
        elseif (isset($alt) && strlen($alt) > 0) {
            $ttl = ' title='. substr($alt,5);
        }

        // Check for "icon" param
        if (isset($params['icon']{0}) || !isset($params['image'])) {

            if (!isset($params['icon']) && !isset($params['image'])) {
                $params['icon'] = 'plus';
            }
            if (isset($params['title'])) {
                if (isset($_lang["{$params['module']}"]["{$params['title']}"])) {
                    $params['title'] = $_lang["{$params['module']}"]["{$params['title']}"];
                }
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
            elseif (isset($params['alt'])) {
                if (isset($_lang["{$params['module']}"]["{$params['alt']}"])) {
                    $params['alt'] = $_lang["{$params['module']}"]["{$params['alt']}"];
                }
                $ttl = ' title="'. htmlentities($params['alt']) .'"';
            }
            else {
                $ttl = ' title="'. htmlentities($_lang['jrCore'][36]) .'"';
            }
            $id = '';
            if (isset($params['id'])) {
                $id = ' id="'. $params['id'] .'"';
            }
            $out = "<a href=\"{$url}\"". $id . $ttl .' onclick="'. $onc .'">'. jrCore_get_sprite_html($params['icon']) .'</a>';
        }

        // See if we are doing an IMAGE as the button - this will override
        // any default button images setup in the Active Skin
        elseif (isset($params['image']) && strlen($params['image']) > 0) {

            $wdt = '';
            if (isset($params['width']) && jrCore_checktype($params['width'],'number_nz')) {
                $wdt = ' width="'. intval($params['width']) .'"';
            }
            $hgt = '';
            if (isset($params['height']) && jrCore_checktype($params['height'],'number_nz')) {
                $hgt = ' height="'. intval($params['height']) .'"';
            }

            // figure our src
            if (strpos($params['image'],$_conf['jrCore_base_url']) !== 0) {
                // See if we have a custom image...
                $_im = array();
                if (isset($_conf["jrCore_{$params['module']}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$params['module']}_custom_images"],TRUE);
                }
                if (isset($_im["{$params['image']}"]) && isset($_im["{$params['image']}"][1]) && $_im["{$params['image']}"][1] == 'on') {
                    $params['image'] = "{$_conf['jrCore_base_url']}/data/media/0/0/mod_{$params['module']}_{$params['image']}?r=". $_im["{$params['image']}"][0];
                }
                else {
                    // Check for skin override
                    $bimg = basename($params['image']);
                    if (is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}")) {
                        $params['image'] = "{$_conf['jrCore_base_url']}/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}";
                    }
                    else {
                        $params['image'] = "{$_conf['jrCore_base_url']}/modules/{$params['module']}/img/{$bimg}";
                    }
                }
            }

            $cls = '';
            if (isset($params['class'])) {
                $cls = ' class="'. $params['class'] .'"';
            }

            // We're using an image for our button
            if ($anc) {
                $out = '<a href="'. $url .'"><img src="'. $params['image'] .'"'. $cls . $hgt . $wdt . $alt . $ttl .'></a>';
            }
            else {
                $out = '<img id="'. $bid .'" src="'. $params['image'] .'" onclick="'. $onc .'"'. $cls . $hgt . $wdt . $alt . $ttl .'>';
            }
        }
        else {

            // Get skin image attributes
            $_tmp = jrCore_get_registered_module_features('jrCore','skin_action_button');

            // Check for skin override
            if (isset($_tmp[$skn]['create']) && is_array($_tmp[$skn]['create']) && $_tmp[$skn]['create']['type'] == 'image') {

                $src = "{$_conf['jrCore_base_url']}/skins/{$skn}/img/{$_tmp[$skn]['create']['image']}";
                if (isset($_conf["jrCore_{$skn}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$skn}_custom_images"],TRUE);
                    if (isset($_im['create.png']) && isset($_im['create.png'][1]) && $_im['create.png'][1] == 'on') {
                        $src = "{$_conf['jrCore_base_url']}/data/media/0/0/{$skn}_create.png?r=". $_im['create.png'][0];
                    }
                }

                // Check for class
                $cls = ' class="create_img"';
                if (isset($params['class'])) {
                    $cls = ' class="create_img '. $params['class'] .'"';
                }
                if ($anc) {
                    $out = '<a href="'. $url .'"><img src="'. $src .'"'. $cls . $ttl . $alt .'></a>';
                }
                else {
                    $out = '<img src="'. $src .'" onclick="'. $onc .'"'. $cls . $ttl . $alt .'>';
                }
            }
            else {

                // Check for button value
                $txt = (isset($_lang['jrCore'][36])) ? $_lang['jrCore'][36] : 'create';
                if (isset($params['value'])) {
                    if (is_numeric($params['value']) && isset($_lang["{$params['module']}"]["{$params['value']}"])) {
                        $txt = $_lang["{$params['module']}"]["{$params['value']}"];
                    }
                    else {
                        $txt = $params['value'];
                    }
                }
                // Check for additional options to pass to button
                $_bp = array();
                if (isset($params['style'])) {
                    $_bp['style'] = $params['style'];
                }
                $out = jrCore_page_button($bid,$txt,$onc,$_bp);
            }
        }
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Create an Update button for a DataStore item
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_item_update_button($params,$smarty)
{
    global $_conf;
    if (!jrUser_is_logged_in()) {
        return '';
    }
    if (!isset($params['module']{0})) {
        return 'jrCore_item_update_button: module name required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        return '';
    }
    if ((!isset($params['item_id']) || !is_numeric($params['item_id'])) && !isset($params['action'])) {
        return 'jrCore_item_update_button: item_id required';
    }
    $out = '';
    $skn = $_conf['jrCore_active_skin'];
    // See if this user has access to perform this action on this profile
    if (jrProfile_is_profile_owner($params['profile_id'])) {

        // Bring in language strings
        $_lang = jrUser_load_lang_strings();

        // See if we are using the default view
        $def = 'update';
        if (isset($params['view']{0})) {
            $def = trim($params['view']);
        }
        // Figure button ID
        $bid = "{$params['module']}_update";
        if (isset($params['id'])) {
            $bid = $params['id'];
        }
        $anc = TRUE;
        $url = jrCore_get_module_url($params['module']);
        if (isset($params['action'])) {
            $onc = "window.location='{$_conf['jrCore_base_url']}/{$params['action']}'";
            $url = "{$_conf['jrCore_base_url']}/{$params['action']}";
        }
        else {
            $onc = "window.location='{$_conf['jrCore_base_url']}/{$url}/{$def}/id={$params['item_id']}'";
            $url = "{$_conf['jrCore_base_url']}/{$url}/{$def}/id={$params['item_id']}";
        }
        // See if we are being given the onclick
        if (isset($params['onclick']{1})) {
            $onc = $params['onclick'];
            $anc = FALSE;
        }

        // Check for "icon" param
        if (isset($params['icon']{0}) || !isset($params['image'])) {

            if (!isset($params['icon']) && !isset($params['image'])) {
                $params['icon'] = 'gear';
            }

            if (isset($params['title'])) {
                if (isset($_lang["{$params['module']}"]["{$params['title']}"])) {
                    $params['title'] = $_lang["{$params['module']}"]["{$params['title']}"];
                }
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
            elseif (isset($params['alt'])) {
                if (isset($_lang["{$params['module']}"]["{$params['alt']}"])) {
                    $params['alt'] = $_lang["{$params['module']}"]["{$params['alt']}"];
                }
                $ttl = ' title="'. htmlentities($params['alt']) .'"';
            }
            else {
                $ttl = ' title="'. htmlentities($_lang['jrCore'][37]) .'"';
            }
            $out = "<a href=\"{$url}\"". $ttl .' onclick="'. $onc .'">'. jrCore_get_sprite_html($params['icon']) .'</a>';
        }

        // See if we are doing an IMAGE as the button - this will override
        // any default button images setup in the Active Skin
        elseif (isset($params['image']) && strlen($params['image']) > 0) {

            $wdt = '';
            if (isset($params['width']) && jrCore_checktype($params['width'],'number_nz')) {
                $wdt = ' width="'. intval($params['width']) .'"';
            }
            $hgt = '';
            if (isset($params['height']) && jrCore_checktype($params['height'],'number_nz')) {
                $hgt = ' height="'. intval($params['height']) .'"';
            }
            // figure our src
            if (strpos($params['image'],$_conf['jrCore_base_url']) !== 0) {
                $_im = array();
                // See if we have a custom image...
                if (isset($_conf["jrCore_{$params['module']}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$params['module']}_custom_images"],TRUE);
                }
                if (isset($_im["{$params['image']}"]) && isset($_im["{$params['image']}"][1]) && $_im["{$params['image']}"][1] == 'on') {
                    $params['image'] = "{$_conf['jrCore_base_url']}/data/media/0/0/mod_{$params['module']}_{$params['image']}?r=". $_im["{$params['image']}"][0];
                }
                else {
                    // Check for skin override
                    $bimg = basename($params['image']);
                    if (is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}")) {
                        $params['image'] = "{$_conf['jrCore_base_url']}/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}";
                    }
                    else {
                        $params['image'] = "{$_conf['jrCore_base_url']}/modules/{$params['module']}/img/{$bimg}";
                    }
                }
            }

            // Check for class
            $cls = '';
            if (isset($params['class'])) {
                $cls = ' class="'. $params['class'] .'"';
            }
            $alt = '';
            if (isset($params['alt'])) {
                $alt = ' alt="'. htmlentities($params['alt']) .'"';
            }
            $ttl = '';
            if (isset($params['title'])) {
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
            elseif (isset($params['alt'])) {
                $ttl = ' title="'. htmlentities($params['alt']) .'"';
            }
            elseif (isset($alt) && strlen($alt) > 0) {
                $ttl = ' title='. substr($alt,5);
            }

            // We're using an image for our button
            if ($anc) {
                $out = '<a href="'. $url .'"><img src="'. $params['image'] .'"'. $cls . $hgt . $wdt . $alt . $ttl .'></a>';
            }
            else {
                $out = '<img src="'. $params['image'] .'" onclick="'. $onc .'"'. $cls . $hgt . $wdt . $alt . $ttl .'>';
            }
        }
        else {

            // Get skin image attributes
            $_tmp = jrCore_get_registered_module_features('jrCore','skin_action_button');

            // Check for skin override
            if (isset($_tmp[$skn]['update']) && is_array($_tmp[$skn]['update']) && $_tmp[$skn]['update']['type'] == 'image') {
                $src = "{$_conf['jrCore_base_url']}/skins/{$skn}/img/{$_tmp[$skn]['update']['image']}";
                if (isset($_conf["jrCore_{$skn}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$skn}_custom_images"],TRUE);
                    if (isset($_im['update.png']) && isset($_im['update.png'][1]) && $_im['update.png'][1] == 'on') {
                        $src = "{$_conf['jrCore_base_url']}/data/media/0/0/{$skn}_update.png?r=". $_im['update.png'][0];
                    }
                }

                // Check for class
                $cls = ' class="update_img"';
                if (isset($params['class'])) {
                    $cls = ' class="update_img '. $params['class'] .'"';
                }
                $alt = $_lang['jrCore'][37];
                if (isset($params['alt'])) {
                    $alt = $params['alt'];
                }
                $ttl = '';
                if (isset($params['title'])) {
                    $ttl = ' title="'. htmlentities($params['title']) .'"';
                }
                elseif (isset($params['alt'])) {
                    $ttl = ' title="'. htmlentities($params['alt']) .'"';
                }
                elseif (isset($alt) && strlen($alt) > 0) {
                    $ttl = ' title="'. $alt .'"';
                }
                // We're using an image for our button
                if ($anc) {
                    $out = '<a href="'. $url .'"><img src="'. $src .'"'. $cls . $ttl .' alt="'. $alt .'"></a>';
                }
                else {
                    $out = '<img src="'. $src .'" onclick="'. $onc .'"'. $cls . $ttl .' alt="'. $alt .'">';
                }
            }
            else {

                // Check for button value
                if (isset($_tmp[$skn]['update']) && is_array($_tmp[$skn]['update']) && isset($_tmp[$skn]['update']['image']) && jrCore_checktype($_tmp[$skn]['update']['image'],'number_nz')) {
                    $txt = (isset($_lang[$skn]["{$_tmp[$skn]['update']['image']}"])) ? $_lang[$skn]["{$_tmp[$skn]['update']['image']}"] : $_tmp[$skn]['update']['image'];
                }
                else {
                    $txt = (isset($_lang['jrCore'][37])) ? $_lang['jrCore'][37] : 'update';
                    if (isset($params['value'])) {
                        if (is_numeric($params['value']) && isset($_lang["{$params['module']}"]["{$params['value']}"])) {
                            $txt = $_lang["{$params['module']}"]["{$params['value']}"];
                        }
                        else {
                            $txt = $params['value'];
                        }
                    }
                }
                // Check for additional options to pass to button
                $_bp = array();
                if (isset($params['style'])) {
                    $_bp['style'] = $params['style'];
                }
                $out = jrCore_page_button($bid,$txt,$onc,$_bp);
            }
        }
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Create a delete button for a DataStore item
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_item_delete_button($params,$smarty)
{
    global $_conf;
    if (!jrUser_is_logged_in()) {
        return '';
    }
    if (!isset($params['module']{0})) {
        return 'jrCore_item_delete_button: module name required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        return '';
    }
    if ((!isset($params['item_id']) || !is_numeric($params['item_id'])) && !isset($params['action'])) {
        return 'jrCore_item_delete_button: item_id required';
    }
    $out = '';
    $skn = $_conf['jrCore_active_skin'];
    // See if this user has access to perform this action on this profile
    if (jrProfile_is_profile_owner($params['profile_id'])) {

        // Bring in language strings
        $_lang = jrUser_load_lang_strings();

        // See if we are using the default view
        $def = 'delete';
        if (isset($params['view']{0})) {
            $def = trim($params['view']);
        }

        // Figure button ID
        $bid = "{$params['module']}_delete";
        if (isset($params['id'])) {
            $bid = $params['id'];
        }
        $url = jrCore_get_module_url($params['module']);

        // Check for onclick prompt
        $ptx = (isset($_lang['jrCore'][40])) ? $_lang['jrCore'][40] : 'are you sure you want to delete this item?';
        if (isset($params['prompt'])) {
            if ($params['prompt'] === false) {
                // do not show a prompt
                $ptx = false;
            }
            elseif (is_numeric($params['prompt']) && isset($_lang["{$params['module']}"]["{$params['prompt']}"])) {
                $ptx = $_lang["{$params['module']}"]["{$params['prompt']}"];
            }
            else {
                $ptx = $params['prompt'];
            }
        }
        // See if we are being given the onclick
        if (isset($params['action'])) {
            $url = "{$_conf['jrCore_base_url']}/{$params['action']}";
        }
        else {
            $url = "{$_conf['jrCore_base_url']}/{$url}/{$def}/id={$params['item_id']}";
        }

        $ask = '';
        if (isset($params['onclick']{1})) {
            $ask = $params['onclick'];
        }
        if ($ptx) {
            $ask = "if (!confirm('". addslashes($ptx) ."')){ return false; }";
        }

        $onc = '';
        if ($ask) {
            $onc = ' onclick="'. $ask .'"';
        }

        // Check for "icon" param
        if (isset($params['icon']{0}) || !isset($params['image'])) {

            if (!isset($params['icon']) && !isset($params['image'])) {
                $params['icon'] = 'trash';
            }
            if (isset($params['title'])) {
                if (isset($_lang["{$params['module']}"]["{$params['title']}"])) {
                    $params['title'] = $_lang["{$params['module']}"]["{$params['title']}"];
                }
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
            elseif (isset($params['alt'])) {
                if (isset($_lang["{$params['module']}"]["{$params['alt']}"])) {
                    $params['alt'] = $_lang["{$params['module']}"]["{$params['alt']}"];
                }
                $ttl = ' title="'. htmlentities($params['alt']) .'"';
            }
            else {
                $ttl = ' title="'. htmlentities($_lang['jrCore'][38]) .'"';
            }
            $out = "<a href=\"{$url}\"". $ttl . $onc .">". jrCore_get_sprite_html($params['icon']) .'</a>';
        }

        // See if we are doing an IMAGE as the button - this will override
        // any default button images setup in the Active Skin
        elseif (isset($params['image']) && strlen($params['image']) > 0) {

            $wdt = '';
            if (isset($params['width']) && jrCore_checktype($params['width'],'number_nz')) {
                $wdt = ' width="'. intval($params['width']) .'"';
            }
            $hgt = '';
            if (isset($params['height']) && jrCore_checktype($params['height'],'number_nz')) {
                $hgt = ' height="'. intval($params['height']) .'"';
            }
            // figure our src
            if (strpos($params['image'],$_conf['jrCore_base_url']) !== 0) {
                // See if we have a custom image...
                $_im = array();
                if (isset($_conf["jrCore_{$params['module']}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$params['module']}_custom_images"],TRUE);
                }
                if (isset($_im["{$params['image']}"]) && isset($_im["{$params['image']}"][1]) && $_im["{$params['image']}"][1] == 'on') {
                    $params['image'] = "{$_conf['jrCore_base_url']}/data/media/0/0/mod_{$params['module']}_{$params['image']}?r=". $_im["{$params['image']}"][0];
                }
                else {
                    // Check for skin override
                    $bimg = basename($params['image']);
                    if (is_file(APP_DIR ."/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}")) {
                        $params['image'] = "{$_conf['jrCore_base_url']}/skins/{$_conf['jrCore_active_skin']}/img/{$bimg}";
                    }
                    else {
                        $params['image'] = "{$_conf['jrCore_base_url']}/modules/{$params['module']}/img/{$bimg}";
                    }
                }
            }

            // Check for class
            $cls = '';
            if (isset($params['class'])) {
                $cls = ' class="'. $params['class'] .'"';
            }
            $alt = '';
            if (isset($params['alt'])) {
                $alt = ' alt="'. htmlentities($params['alt']) .'"';
            }
            $ttl = '';
            if (isset($params['title'])) {
                $ttl = ' title="'. htmlentities($params['title']) .'"';
            }
            elseif (isset($params['alt'])) {
                $ttl = ' title="'. htmlentities($params['alt']) .'"';
            }
            elseif (isset($alt) && strlen($alt) > 0) {
                $ttl = ' title='. substr($alt,5);
            }
            $out = '<a href="'. $url .'"><img src="'. $params['image'] .'"'. $cls . $hgt . $wdt . $alt . $ttl . $onc .'></a>';
        }
        else {

            // Get skin image attributes
            $_tmp = jrCore_get_registered_module_features('jrCore','skin_action_button');

            // Check for skin override
            if (isset($_tmp[$skn]['delete']) && is_array($_tmp[$skn]['delete']) && $_tmp[$skn]['delete']['type'] == 'image') {
                $src = "{$_conf['jrCore_base_url']}/skins/{$skn}/img/{$_tmp[$skn]['delete']['image']}";
                if (isset($_conf["jrCore_{$skn}_custom_images"]{2})) {
                    $_im = json_decode($_conf["jrCore_{$skn}_custom_images"],TRUE);
                    if (isset($_im['delete.png']) && isset($_im['delete.png'][1]) && $_im['delete.png'][1] == 'on') {
                        $src = "{$_conf['jrCore_base_url']}/data/media/0/0/{$skn}_delete.png?r=". $_im['delete.png'][0];
                    }
                }

                // Check for class
                $cls = ' class="delete_img"';
                if (isset($params['class'])) {
                    $cls = ' class="delete_img '. $params['class'] .'"';
                }
                $alt = $_lang['jrCore'][38];
                if (isset($params['alt'])) {
                    $alt = $params['alt'];
                }
                $ttl = '';
                if (isset($params['title'])) {
                    $ttl = ' title="'. htmlentities($params['title']) .'"';
                }
                elseif (isset($params['alt'])) {
                    $ttl = ' title="'. htmlentities($params['alt']) .'"';
                }
                elseif (isset($alt) && strlen($alt) > 0) {
                    $ttl = ' title="'. $alt .'"';
                }
                $out = '<a href="'. $url .'"><img src="'. $src .'"'. $cls . $onc . $ttl .' alt="'. $alt .'"></a>';
            }
            else {

                // Check for button value
                if (isset($_tmp[$skn]['delete']) && is_array($_tmp[$skn]['delete']) && isset($_tmp[$skn]['delete']['image']) && jrCore_checktype($_tmp[$skn]['delete']['image'],'number_nz')) {
                    $txt = (isset($_lang[$skn]["{$_tmp[$skn]['delete']['image']}"])) ? $_lang[$skn]["{$_tmp[$skn]['delete']['image']}"] : $_tmp[$skn]['delete']['image'];
                }
                else {
                    $txt = (isset($_lang['jrCore'][38])) ? $_lang['jrCore'][38] : 'delete';
                    if (isset($params['value'])) {
                        if (is_numeric($params['value']) && isset($_lang["{$params['module']}"]["{$params['value']}"])) {
                            $txt = $_lang["{$params['module']}"]["{$params['value']}"];
                        }
                        else {
                            $txt = $params['value'];
                        }
                    }
                }
                // Check for additional options to pass to button
                $_bp = array();
                if (isset($params['style'])) {
                    $_bp['style'] = $params['style'];
                }
                $out = jrCore_page_button($bid,$txt,$ask,$_bp);
            }
        }
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$out);
            return '';
        }
    }
    return $out;
}

/**
 * Create an array within a template
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_item_order_button($params,$smarty)
{
    global $_conf;
    if (!jrUser_is_logged_in()) {
        return '';
    }
    if (!isset($params['module']{0})) {
        return 'jrCore_item_order_button: module name required';
    }
    if (!jrCore_module_is_active($params['module'])) {
        return '';
    }
    if (!isset($params['profile_id']) || !is_numeric($params['profile_id'])) {
        return 'jrCore_item_order_button: profile_id required';
    }
    $out = '';
    // See if this user has access to perform this action on this profile
    if (jrProfile_is_profile_owner($params['profile_id'])) {
        $_ln = jrUser_load_lang_strings();
        $out = '<a href="'. $_conf['jrCore_base_url'] .'/'. jrCore_get_module_url($params['module']) .'/item_display_order" title="'. $_ln['jrCore'][83] .'">' . jrCore_get_sprite_html($params['icon']) .'</a>';
        if (!empty($params['assign'])) {
            $smarty->assign($params['assign'],$out);
            return '';
        }
    }
    return $out;
}

/**
 * Create an array within a template
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_array($params,$smarty)
{
    if (!isset($params['name']) || strlen($params['name']) === 0) {
        return '';
    }
    // check for our separator
    if (isset($params['explode']) && (!isset($params['separator']) || strlen($params['separator']) === 0)) {
        $params['separator'] = ',';
    }
    // See if we have a comma and our explode value
    $_tmp = array();
    if (isset($params['explode'])) {
        if (isset($params['key']) && strlen($params['key']) > 0) {
            $_tmp["{$params['key']}"] = explode($params['separator'],$params['value']);
        }
        else {
            $_tmp = explode($params['separator'],$params['value']);
        }
    }
    else {
        if (isset($params['key']) && strlen($params['key']) > 0) {
            $_tmp["{$params['key']}"] = $params['value'];
        }
        else {
            $_tmp = $params['value'];
        }
    }
    // see if we already exists - if so we need to append
    if (is_array($smarty->getTemplateVars($params['name']))) {
        $smarty->append($params['name'],$_tmp,TRUE);
    }
    else {
        $smarty->assign($params['name'],$_tmp);
    }
    return '';
}

/**
 * Load a remote URL into a template variable
 * @param array $params parameters for function
 * @param object $smarty Smarty object
 * @return string
 */
function smarty_function_jrCore_load_url($params,$smarty)
{
    $out = '';
    if (jrCore_checktype($params['url'],'url')) {
        $out = jrCore_load_url($params['url']);
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Set a page title from a Template
 * @param array $params function params
 * @param object $smarty Smarty Object
 * @return string
 */
function smarty_function_jrCore_page_title($params,$smarty)
{
    if (isset($params['title'])) {
        jrCore_page_title($params['title']);
    }
    return '';
}

/**
 * Include a Jamroom Template within another template
 * @param array $params function params
 * @param object $smarty Smarty Object
 * @return string
 */
function smarty_function_jrCore_include($params,$smarty)
{
    global $_conf;
    $dir = $_conf['jrCore_active_skin'];
    if (isset($params['module'])) {
        // If we are given a module
        $dir = $params['module'];
    }
    if (!isset($params['template'])) {
        return 'jrCore_include: template parameter required';
    }
    $_rp = $smarty->getTemplateVars();
    foreach ($params as $k => $v) {
        $_rp[$k] = $v;
    }
    $out = jrCore_parse_template($params['template'],$_rp,$dir);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Get DataStore prefix for a given module and save to template variable
 * @param array $params function params
 * @param object $smarty Smarty Object
 * @return string
 */
function smarty_function_jrCore_get_datastore_prefix($params,$smarty)
{
    if (!isset($params['module']{0})) {
        return 'jrCore_get_datastore_prefix: module name required';
    }
    $out = jrCore_db_get_prefix($params['module']);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Random number generator
 * @param array $params function params
 * @param object $smarty Smarty Object
 * @return string
 */
function smarty_function_jrCore_random_number($params,$smarty)
{
    if (!isset($params['assign'])) {
        return "jrCore_random_number parameter 'assign' cannot be empty";
    }
    if (!isset($params['min'])) {
        $params['min'] = 0;
    }
    if (!isset($params['max'])) {
        $params['max'] = 10;
    }
    $tmp = mt_rand($params['min'],$params['max']);
    $smarty->assign($params['assign'],$tmp);
    return '';
}

/**
 * Create an Icon in a template
 * @param array $params function params
 * @param object $smarty Smarty Object
 * @return string
 */
function smarty_function_jrCore_icon($params,$smarty)
{
    global $_conf;
    if (empty($params['icon'])) {
        return 'jrCore_icon: icon parameter required';
    }
    $size = 32;
    if (isset($params['size'])) {
        $size = (int) $params['size'];
    }
    else {
        // See if our skin has registered an icon size
        $_tmp = jrCore_get_registered_module_features('jrCore','icon_size');
        if (isset($_tmp["{$_conf['jrCore_active_skin']}"])) {
            $size = array_keys($_tmp["{$_conf['jrCore_active_skin']}"]);
            $size = (int) reset($size);
        }
    }
    $out = jrCore_get_sprite_html($params['icon'],$size);
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * Site Statistics for modules that have registered
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrCore_stats($params,$smarty)
{
    global $_conf;
    if (!isset($params['template']{1})) {
        return 'jrCore_stats: template parameter required';
    }
    // We piggyback on Profile Stats...
    $_tmp = jrCore_get_registered_module_features('jrProfile','profile_stats');
    if (!$_tmp) {
        // No registered modules
        return '';
    }
    // Get all table counts in 1 shot
    $req = "SELECT TABLE_NAME, TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$_conf['jrCore_db_name']}'";
    $_rt = jrCore_db_query($req,'TABLE_NAME',false,'TABLE_ROWS');
    if (!$_rt || !is_array($_rt)) {
        // No Table counts - shouldn't happen
        return '';
    }
    $_st['_stats'] = array();
    $_lang = jrUser_load_lang_strings();
    foreach ($_tmp as $mod => $_stats) {
        foreach ($_stats as $key => $title) {
            if (is_numeric($title) && isset($_lang[$mod][$title])) {
                $title = $_lang[$mod][$title];
            }
            // See if we have been given a function
            $count = false;
            if (function_exists($key)) {
                $count = $key();
            }
            else {
                $key = jrCore_db_table_name($mod,'item');
                if (isset($_rt[$key]) && $_rt[$key] > 0) {
                    $count = $_rt[$key];
                }
            }
            if ($count) {
                $_st['_stats'][$title] = array(
                    'count'  => $count,
                    'module' => $mod
                );
            }
        }
    }
    $out = '';
    if (isset($_st['_stats']) && is_array($_st['_stats'])) {
        $out = jrCore_parse_template($params['template'],$_st,'jrProfile');
    }
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}
