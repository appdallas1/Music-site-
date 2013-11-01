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
 * @package Utilities
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

//-----------------------------------------
// FEATURE registration
//-----------------------------------------

/**
 * Register a feature for a module
 *
 * @param string $module The Module that provides the feature
 * @param string $feature The Feature name
 * @param string $r_module The module that wants to use the feature
 * @param string $r_feature The unique setting
 * @param mixed $_options Parameters for the feature
 * @return bool
 */
function jrCore_register_module_feature($module,$feature,$r_module,$r_feature,$_options = true)
{
    if (!isset($GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'])) {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'] = array();
    }
    if (!isset($GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module])) {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module] = array(
            $feature => array(
                $r_module => array()
            )
        );
    }
    elseif (!isset($GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature])) {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature] = array(
            $r_module => array()
        );
    }
    elseif (!isset($GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature][$r_module])) {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature][$r_module] = array();
    }
    // See if we have a registered module feature function
    if (isset($GLOBALS['__JR_FLAGS']['jrcore_register_module_feature_function'][$module][$feature])) {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature][$r_module][$r_feature] = $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature_function'][$module][$feature]($r_module,$r_feature,$_options);
    }
    else {
        $GLOBALS['__JR_FLAGS']['jrcore_register_module_feature'][$module][$feature][$r_module][$r_feature] = $_options;
    }
    return true;
}

/**
 * Returns an array of modules registered for a given feature
 *
 * @param string $module The Module that provides the feature
 * @param string $feature The unique Feature name from the providing module
 * @return array
 */
function jrCore_get_registered_module_features($module,$feature)
{
    $_tmp = jrCore_get_flag('jrcore_register_module_feature');
    return (isset($_tmp[$module]) && isset($_tmp[$module][$feature])) ? $_tmp[$module][$feature] : false;
}

/**
 * Run a function when a module calls the jrCore_register_module_feature function
 *
 * @param string $module The Module that provides the feature
 * @param string $feature The unique Feature name from the providing module
 * @param string $function Function to execute when jrCore_register_module_feature is called for this feature
 * @return bool
 */
function jrCore_register_module_feature_function($module,$feature,$function)
{
    if (!function_exists($function)) {
        return false;
    }
    $_tmp = jrCore_get_flag('jrcore_register_module_feature_function');
    if (!$_tmp) {
        $_tmp = array();
    }
    if (!isset($_tmp[$module])) {
        $_tmp[$module] = array();
    }
    $_tmp[$module][$feature] = $function;
    jrCore_set_flag('jrcore_register_module_feature_function',$_tmp);
    return true;
}

//-----------------------------------------
// COOKIE functions
//-----------------------------------------

/**
 * Set a persistent cookie
 *
 * @param string $name Cookie Name
 * @param mixed $content Content (max ~4k)
 * @param int $expires Days to expire (default 10)
 * @return bool
 */
function jrCore_set_cookie($name,$content,$expires = 10)
{
    if (!jrCore_checktype($name,'core_string')) {
        return false;
    }
    $content = json_encode($content);
    $expires = (intval($expires) * 86400);
    if (setcookie($name,$content,(time() + $expires),'/')) {
        $_COOKIE[$name] = $content;
        return true;
    }
    return false;
}

/**
 * Get value for persistent cookie if it exists
 *
 * @param string $name Name of cookie to retrieve
 * @return bool|mixed
 */
function jrCore_get_cookie($name)
{
    if (isset($_COOKIE[$name])) {
        return json_decode($_COOKIE[$name],true);
    }
    return false;
}

/**
 * Delete a persistent cookie
 *
 * @param string $name Name of cookie to delete
 * @return bool
 */
function jrCore_delete_cookie($name)
{
    setcookie($name,'',(time() - (365 * 86400)),'/');
    return true;
}

//-----------------------------------------
// QUEUE functions
//-----------------------------------------

/**
 * Get queue entry from a named Queue
 * @param string $name Queue Name
 * @return array
 */
function jrCore_queue_get($name)
{
    $tbl = jrCore_db_table_name('jrCore','queue');
    $req = "UPDATE {$tbl} SET
              queue_worker  = '". getmypid() ."',
              queue_started = '". time() ."'
             WHERE queue_name = '". jrCore_db_escape($name) ."'
               AND LENGTH(queue_worker) = 0
             LIMIT 1";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt > 0) {
        // We got an entry - grab it
        $req = "SELECT queue_id, queue_created, queue_module, queue_data, queue_count
                  FROM {$tbl}
                 WHERE queue_worker = '". getmypid() ."'
                   AND queue_name = '". jrCore_db_escape($name) ."'
                 LIMIT 1";
        $_rt = jrCore_db_query($req,'SINGLE');
        if (isset($_rt) && is_array($_rt)) {
            // See how many times this queue entry has been worked
            if (isset($_rt['queue_count']) && $_rt['queue_count'] >= 3) {
                // We've tried 3 times on this queue entry and failed - remove it
                jrCore_logger('CRI',"queue {$_rt['queue_module']}/{$_rt['queue_id']} unable to complete successfully after 3 tries");
                return jrCore_queue_delete($_rt['queue_id']);
            }
            $_rt['queue_data'] = json_decode($_rt['queue_data'],true);
            return $_rt;
        }
    }
    return false;
}

/**
 * Save a new entry into a named Queue
 *
 * @param string $module Module creating the Queue entry
 * @param string $name Queue Name
 * @param mixed $data Data to save to new Queue entry
 * @return bool
 */
function jrCore_queue_create($module,$name,$data)
{
    $tbl = jrCore_db_table_name('jrCore','queue');
    $nam = jrCore_db_escape($name);
    $crt = time();
    $mod = jrCore_db_escape($module);
    $uid = 0;
    if (isset($data['item_id']) && jrCore_checktype($data['item_id'],'number_nz')) {
        $uid = (int) $data['item_id'];
    }
    $req = "INSERT INTO {$tbl} (queue_name,queue_created,queue_module,queue_item_id,queue_data)
            VALUES ('{$nam}','{$crt}','{$mod}','{$uid}','". jrCore_db_escape(json_encode($data)) ."')";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt === 1) {
        return true;
    }
    return false;
}

/**
 * Delete an entry from a named Queue
 *
 * @param int $id Queue ID to delete
 * @return bool
 */
function jrCore_queue_delete($id)
{
    $tbl = jrCore_db_table_name('jrCore','queue');
    $req = "DELETE FROM {$tbl} WHERE queue_id = '". intval($id) ."'";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt === 1) {
        return true;
    }
    return false;
}

/**
 * Release an entry back into a named Queue
 *
 * @param int $id Queue ID to release
 * @param int $cnt Number of "tries" to increment this queue item by
 * @return bool
 */
function jrCore_queue_release($id,$cnt = 0)
{
    $cnt = (int) $cnt;
    $tbl = jrCore_db_table_name('jrCore','queue');
    $req = "UPDATE {$tbl} SET queue_worker = '', queue_started = '0', queue_count = (queue_count + {$cnt}) WHERE queue_id = '". intval($id) ."'";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt === 1) {
        return true;
    }
    return false;
}

/**
 * Get number of active workers for a given Queue
 *
 * @param string $queue Name of Queue to get active worker count for
 * @return int
 */
function jrCore_queue_worker_count($queue)
{
    // Let's look for any (apparently) stuck queue entries (no update after 20 minutes)
    $tbl = jrCore_db_table_name('jrCore','queue');
    $req = "DELETE FROM {$tbl} WHERE (queue_started > 0 && queue_started < ". (time() - 1800) .')';
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt > 0) {
        jrCore_logger('MAJ',"deleted {$cnt} queue entries that appeared to be stuck for more than 20 minutes");
    }
    // Return number of active queue workers
    $req = "SELECT COUNT(queue_id) AS wcount FROM {$tbl} WHERE queue_name = '". jrCore_db_escape($queue) ."' AND LENGTH(queue_worker) > 0";
    $_rt = jrCore_db_query($req,'SINGLE');
    if (isset($_rt) && is_array($_rt) && isset($_rt['wcount'])) {
        return intval($_rt['wcount']);
    }
    return 0;
}

/**
 * Delete a Queue Entry for a module/item_id
 * @param string $module Module that created the queue entry
 * @param integer $item_id Unique Item ID to delete queue entries for
 * @return bool
 */
function jrCore_queue_delete_by_item_id($module,$item_id)
{
    $tbl = jrCore_db_table_name('jrCore','queue');
    $req = "DELETE FROM {$tbl} WHERE queue_module = '". jrCore_db_escape($module) ."' AND queue_item_id = '". intval($item_id) ."'";
    $cnt = jrCore_db_query($req,'COUNT');
    if (isset($cnt) && $cnt > 0) {
        return true;
    }
    return false;
}

/**
 * Register a Queue worker process
 *
 * Registering a Queue worker process tells the core that
 * when a process completes, if there is queue work to be done,
 * the process should hang around and work the queue before exiting
 *
 * @param string $module Module registering the queue worker
 * @param string $queue_name Name of the Queue to read from
 * @param string $function Function to execute when a queue entry is found
 * @param int $count The number of queue entries to process before exiting
 * @return bool
 */
function jrCore_register_queue_worker($module,$queue_name,$function,$count = 1)
{
    $_tmp = jrCore_get_flag('jrcore_register_queue_worker');
    if (!$_tmp) {
        $_tmp = array();
    }
    if (!isset($_tmp[$module])) {
        $_tmp[$module] = array();
    }
    $_tmp[$module][$queue_name] = array($function,$count);
    jrCore_set_flag('jrcore_register_queue_worker',$_tmp);
    return true;
}

/**
 * Return an "option" image HTML for pass/fail
 * @param $state string pass|fail
 * @return string
 */
function jrCore_get_option_image($state)
{
    global $_conf;
    return '<img src="'. $_conf['jrCore_base_url'] .'/modules/jrCore/img/option_'. $state .'.png" width="16" height="16" alt="'. $state .'" title="'. $state .'">';
}

/**
 * Return the detected URL to the system installation
 *
 * @return string Returns full install URL
 */
function jrCore_get_base_url()
{
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
        $url .= $_SERVER['SERVER_NAME'] .':'. $_SERVER['SERVER_PORT'] . dirname($_SERVER['REQUEST_URI']);
    }
    else {
        $url .= $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
    }
    $url = rtrim($url,'/');
    if (strpos($_SERVER['REQUEST_URI'],'/~') === 0) {
        // we have a user dir
        $url = "{$url}{$_SERVER['REQUEST_URI']}";
    }
    return $url;
}

/**
 * Returns server request protocol (http or https)
 *
 * jrCore_get_server_protocol
 */
function jrCore_get_server_protocol()
{
    $proto = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
        $proto = 'https';
    }
    return $proto;
}

/**
 * Convert a string to all lowercase
 *
 * @param string $str String to lowercase
 * @return string
 */
function jrCore_str_to_lower($str)
{
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($str,'UTF-8');
    }
    return strtolower($str);
}

/**
 * Convert a string to all uppercase
 *
 * @param string $str String to uppercase
 * @return string
 */
function jrCore_str_to_upper($str)
{
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($str,'UTF-8');
    }
    return strtoupper($str);
}

/**
 * Get the current URL
 *
 * @return string Returns full install URL
 */
function jrCore_get_current_url()
{
    $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
        $url .= $_SERVER['SERVER_NAME'] .':'. $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
    }
    else {
        $url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }
    return $url;
}

/**
 * Returns HTTP_REFERER if the URL is from the local site
 *
 * @return string Returns URL to forward to on success
 */
function jrCore_get_local_referrer()
{
    global $_conf;
    if (isset($_SERVER['HTTP_REFERER']) && strpos(jrCore_str_to_lower($_SERVER['HTTP_REFERER']),jrCore_str_to_lower($_conf['jrCore_base_url'])) === 0) {
        return $_SERVER['HTTP_REFERER'];
    }
    return $_conf['jrCore_base_url'];
}

/**
 * returns a URL if the referring URL is from the local site
 *
 * @return string
 */
function jrCore_is_local_referrer()
{
    global $_conf;
    if (isset($_SESSION['jruser_save_location'])) {
        return $_SESSION['jruser_save_location'];
    }
    $url = jrCore_get_local_referrer();
    if (isset($url) && strpos($url,"{$_conf['jrCore_base_url']}/") === 0) {
        $_SESSION['jruser_save_location'] = $url;
        return $url;
    }
    return 'referrer';
}

/**
 * Strip parameters from a URL
 *
 * @param string $url URL to strip parameters from
 * @param array $_strip Array of parameter keys to strip
 * @return string
 */
function jrCore_strip_url_params($url,$_strip)
{
    foreach ($_strip as $strip) {
        $url = preg_replace("/\/{$strip}=[^\/]+/i",'',$url);
    }
    return rtrim($url,'/');
}

/**
 * Set a new temp global flag
 *
 * @param string $flag Unique flag string to set value for
 * @param mixed $value Value to store
 * @return bool
 */
function jrCore_set_flag($flag,$value)
{
    $GLOBALS['__JR_FLAGS'][$flag] = $value;
    return true;
}

/**
 * Retrieve a previously set temp global flag
 *
 * @param mixed $flag String or Array to save to flag
 * @return mixed
 */
function jrCore_get_flag($flag)
{
    return (isset($GLOBALS['__JR_FLAGS'][$flag])) ? $GLOBALS['__JR_FLAGS'][$flag] : false;
}

/**
 * delete a previously set temp global flag
 *
 * @param mixed $flag String or Array to delete
 * @return bool
 */
function jrCore_delete_flag($flag)
{
    if (isset($GLOBALS['__JR_FLAGS'][$flag])) {
        unset($GLOBALS['__JR_FLAGS'][$flag]);
        return true;
    }
    return false;
}

/**
 * Parse REQUEST_URI into it's components
 *
 * @return array
 */
function jrCore_parse_url()
{
    global $_urls;
    // Check for cache
    $tmp = jrCore_get_flag('jr_parse_url_complete');
    if ($tmp) {
        return $tmp;
    }
    $_out = array();

    // Get everything cleaned up and into $_post
    if (isset($_REQUEST['_uri']{1})) {

        $curl = urldecode(str_replace('%26','___AMP',$_SERVER['REQUEST_URI']));
        $_REQUEST['_uri'] = substr($curl,strpos($curl,'/'. $_REQUEST['_uri']));

        // Break up our URL
        $_tmp = explode('/',str_replace(array('?','&','//','///','////','/////'),'/',trim(urldecode($_REQUEST['_uri']),'/')));

        if (isset($_tmp) && is_array($_tmp)) {
            // Page
            if (isset($_tmp[0]) && (!isset($_tmp[1]) || strpos($_tmp[1],'='))) {

                // $_out['module_url'] = preg_replace('/[^-a-zA-Z0-9_.%]/','',$_tmp[0]);

                $_out['module_url'] = rawurlencode($_tmp[0]);
                $_out['module']     = (isset($_urls["{$_out['module_url']}"])) ? $_urls["{$_out['module_url']}"] : '';
                $idx = 1;
            }
            // Module/View
            elseif (isset($_tmp[1]) && !strpos($_tmp[1],'=')) {

                // $_out['module_url'] = preg_replace('/[^-a-zA-Z0-9_.%]/','',$_tmp[0]);
                // $_out['option']     = preg_replace('/[^-a-zA-Z0-9_.%]/','',$_tmp[1]);

                $_out['module_url'] = rawurlencode($_tmp[0]);
                $_out['module']     = (isset($_urls["{$_out['module_url']}"])) ? $_urls["{$_out['module_url']}"] : '';
                $_out['option']     = rawurlencode($_tmp[1]);
                $idx = 2;
            }
            // Handle any additional parameters
            if (isset($idx) && isset($_tmp[$idx]) && strlen($_tmp[$idx]) > 0) {
                $vc = 1;
                for ($i = $idx; $i < 50; $i++) {   // NOTE: hardcoded to handle up to 50 additional parameters
                    if (isset($_tmp[$i]{0})) {
                        if (strpos($_tmp[$i],'=')) {
                            list($key,$val) = explode('=',$_tmp[$i]);
                            // Check for URL encoded array []'s
                            if (strpos($key,'%5B%5D')) {
                                $key = substr($key,0,strpos($key,'%5B%5D'));
                                $_out[$key][] = str_replace('___AMP','&',trim($val));
                            }
                            else {
                                $_out[$key] = str_replace('___AMP','&',trim($val));
                            }
                        }
                        else {
                            // these are our "bare" parameters
                            $_out["_{$vc}"] = str_replace('___AMP','&',trim($_tmp[$i]));
                            $vc++;
                        }
                    }
                    else {
                        break;
                    }
                }
            }
        }
    }
    // Lastly, check for an AJAX request
    $_SERVER['jr_is_ajax_request'] = 0;
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'],'__ajax')) {
        $_SERVER['jr_is_ajax_request'] = 1;
    }
    if (isset($_out) && is_array($_out)) {
        if (!isset($_REQUEST) || !is_array($_REQUEST)) {
            $_REQUEST = array();
        }
        $_REQUEST = $_REQUEST + $_out;
    }
    // let other modules checkout $_post...
    $_REQUEST = jrCore_trigger_event('jrCore','parse_url',$_REQUEST);
    jrCore_set_flag('jr_parse_url_complete',$_REQUEST);
    return $_REQUEST;
}

/**
 * Redirect a browser to a URL
 *
 * @param string $url URL to forward to
 * @return null Function exits on completion
 */
function jrCore_location($url)
{
    if (isset($_SESSION)) {
        session_write_close();
    }
    if (isset($url) && $url == 'referrer') {
        $url = jrCore_get_local_referrer();
    }
    if (jrCore_is_ajax_request()) {
        // AJAX redirect
        $_out = array('redirect' => $url);
        echo json_encode($_out);
        exit;
    }
    header('Location: '. trim($url));
    exit;
}

/**
 * Log an entry to the Activity Log
 *
 * @param string $pri Priority - one of INF, MIN, MAJ, CRI
 * @param string $txt Text string to log
 * @param bool $include_user Include logging User Name in text
 * @return bool
 */
function jrCore_logger($pri,$txt,$include_user = true)
{
    global $_user;
    $pri = strtolower($pri);
    $usr = '';
    if ($include_user) {
        $tmp = jrCore_get_flag('jr_daily_maintenance_is_active'); // Don't log users during maintenance
        if ($tmp) {
            $usr = '[system]: ';
        }
        else {
            $usr = (isset($_user['user_name']{0})) ? "[{$_user['user_name']}]: " : ((isset($_user['user_email'])) ? "[{$_user['user_email']}]: " : '');
        }
    }
    $tbl = jrCore_db_table_name('jrCore','log');
    $req = "INSERT INTO {$tbl} (log_created,log_priority,log_ip,log_text) VALUES (UNIX_TIMESTAMP(),'{$pri}','". jrCore_get_ip() ."','". jrCore_db_escape($usr . $txt) ."')";
    jrCore_db_query($req);
    return true;
}

/**
 * Tell if a request is a Jamroom AJAX request
 *
 * @return bool
 */
function jrCore_is_ajax_request()
{
    return (isset($_SERVER['jr_is_ajax_request']) && $_SERVER['jr_is_ajax_request'] === 1) ? true : false;
}

/**
 * Convert URLs in a string into clickable URLs
 *
 * @param string $string Input string to parse
 * @param string $type Type of conversion ("email","url" or "all")
 * @return string
 */
function jrCore_string_to_url($string,$type = 'url')
{
    // Check to see if this entry already contains HTML - if it does,
    // We need to "save" the HTML tags off into an array, do our HTML
    // expansion, then add the removed tags back in.
    if (strpos(' '. $string,'<')) {
        $string = preg_replace_callback("/(<[^>]+>)/i",'jrCore_string_to_url_callback',$string);
    }

    // Now do our actual link replacements
    switch ($type) {

        // Email Only
        case 'email':
            $string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1 <a href=\"mailto:\\2@\\3\">\\2@\\3</a>",$string);
            break;

        // URL + Email
        case 'all':
            $string = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1 <a href=\"mailto:\\2@\\3\">\\2@\\3</a>",$string);
            $string = preg_replace("/(http[s]*:\/\/)([^.]+\.[a-zA-Z0-9-\.\/\_\?\%\#\&\=\;\~\!\(\)]+)/"," <a href=\"\\1\\2\">\\1\\2</a>",$string);
            $string = preg_replace("/ (www\.|[a-zA-Z0-9-]+\.)(([a-zA-Z0-9-][a-zA-Z0-9-]+\.)+[a-zA-Z0-9-\.\/\_\?\%\#\&\=\;\~\!\(\)]+)/"," <a href=\"http://\\1\\2\">\\1\\2</a>",$string);
            break;

        // URL Only
        default:
            $string = preg_replace("/(http[s]*:\/\/)([^.]+\.[a-zA-Z0-9-\.\/\_\?\%\#\&\=\;\~\!\(\)]+)/"," <a href=\"\\1\\2\">\\1\\2</a>",$string);
            $string = preg_replace("/ (www\.|[a-zA-Z0-9-]+\.)(([a-zA-Z0-9-][a-zA-Z0-9-]+\.)+[a-zA-Z0-9-\.\/\_\?\%\#\&\=\;\~\!\(\)]+)/"," <a href=\"http://\\1\\2\">\\1\\2</a>",$string);
            break;

    }
    // $GLOBALS['JR_HTML_AUTOLINKS_MATCHES'] will be set if we have
    // embed code replacement vars
    if (isset($GLOBALS['JR_HTML_AUTOLINKS_MATCHES'])) {
        foreach ($GLOBALS['JR_HTML_AUTOLINKS_MATCHES'] as $code => $rep) {
            $string = str_replace($code,$rep,$string,$cnt);
        }
    }
    return $string;
}

/**
 * @ignore
 * Used by jrCore_string_to_url()
 * @ignore
 * @param array $_matches Array of matches from preg_match()
 * @return string
 *
 */
function jrCore_string_to_url_callback($_matches)
{
    if (isset($_matches[0]) && strlen($_matches[0]) > 0) {
        $mcode = '|'. md5(microtime());
        $GLOBALS['JR_HTML_AUTOLINKS_MATCHES'][$mcode] = $_matches[0];
        return $mcode;
    }
    return '';
}

/**
 * Download a file from a remote site by URL
 *
 * @param string $remote_url Remote File URL
 * @param string $local_file Local file to save data to
 * @param int $timeout How many seconds to allow for file download before failing
 * @param int $port Remote Port to create socket connection to.
 * @param string $username HTTP Basic Authentication User Name
 * @param string $password HTTP Basic Authentication Password
 * @return bool Returns true if file is downloaded, false on error
 */
function jrCore_download_file($remote_url,$local_file,$timeout = 120,$port = 80,$username = null,$password = null)
{
    set_time_limit(0);
    $_temp = jrCore_module_meta_data('jrCore');
    $local = fopen($local_file,'wb');
    $_opts = array(
        CURLOPT_USERAGENT      => 'Jamroom v'. $_temp['version'],
        CURLOPT_URL            => $remote_url,     // File we are downloading
        CURLOPT_PORT           => (int) $port,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => intval($timeout),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FILE           => $local
    );
    // Check for HTTP Basic Authentication
    if (!is_null($username) && !is_null($password)) {
        $_opts[CURLOPT_USERPWD] = $username .':'. $password;
    }
    $ch = curl_init();
    if (curl_setopt_array($ch,$_opts)) {
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        fwrite($local,$res);
        fclose($local);
        if (!isset($err) || $err === 0) {
            curl_close($ch);
            return true;
        }
        $errmsg = curl_error($ch) ;
        curl_close($ch);
        jrCore_logger('CRI',"jrCore_download_file: {$remote_url} returned error #{$err} ({$errmsg})");
        return false;
    }
    fclose($local);
    curl_close($ch);
    return false;
}

/**
 * Get contents of a remote URL
 *
 * @param string $url Url to load
 * @param array $_vars URI variables for URL
 * @param string $method URL method (POST or GET)
 * @param int $port Remote Port to create socket connection to.
 * @param string $username HTTP Basic Authentication User Name
 * @param string $password HTTP Basic Authentication Password
 * @param bool $log_error Set to false to prevent error logging on failed URL load
 * @return string Returns value of loaded URL, or false on failure
 */
function jrCore_load_url($url,$_vars = null,$method = 'GET',$port = 80,$username = null,$password = null,$log_error = true)
{
    $_temp = jrCore_module_meta_data('jrCore');
    $_opts = array(
        CURLOPT_POST           => false,    // Send as GET data
        CURLOPT_HEADER         => false,    // Retrieve headers
        CURLOPT_USERAGENT      => 'Jamroom v'. $_temp['version'],
        CURLOPT_URL            => $url,     // URL we are loading
        CURLOPT_PORT           => (int) $port,
        CURLOPT_FRESH_CONNECT  => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FORBID_REUSE   => true,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_VERBOSE        => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FAILONERROR    => false
    );
    // Check for HTTP Basic Authentication
    if (!is_null($username) && !is_null($password)) {
        $_opts[CURLOPT_USERPWD] = $username .':'. $password;
    }
    if ($method == 'POST') {
        $_opts[CURLOPT_POST] = true;
    }
    if (isset($_vars) && is_array($_vars)) {
        $_opts[CURLOPT_POSTFIELDS] = $_vars;
    }
    elseif (strlen($_vars) > 0) {
        $_opts[CURLOPT_POSTFIELDS] = trim($_vars);
    }
    $ch = curl_init();
    if (curl_setopt_array($ch,$_opts)) {
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        if (!isset($err) || $err === 0) {
            curl_close($ch);
            return $res;
        }
        $errmsg = curl_error($ch) ;
        if ($log_error) {
            jrCore_logger('CRI',"jrCore_load_url: {$url} returned error #{$err} ({$errmsg})");
        }
    }
    else {
        if ($log_error) {
            jrCore_logger('CRI',"jrCore_load_url: unable to load cURL options via curl_setopt_array");
        }
    }
    curl_close($ch);
    return false;
}

/**
 * Strip unsafe HTML tags from a string (recursive)
 *
 * @param mixed $string String or Array to strip tags from
 * @param array $_allowed comma separated list of allowed HTML tags
 * @return mixed
 */
function jrCore_strip_html($string,$_allowed = null)
{
    if (strlen($string) === 0 || !strpos(' ' . $string, '<')) {
        return $string;
    }
    if (isset($string) && is_array($string)) {
        foreach ($string as $c_key => $c_val) {
            $string[$c_key] = jrCore_strip_html($c_val,$_allowed);
        }
    }
    else {
        $allw = '';
        if (isset($_allowed) && strlen($_allowed) > 0) {
            $_all = explode(',',$_allowed);
            $_att = array();
            if (isset($_all) && is_array($_all)) {
                foreach ($_all as $k => $tag) {
                    $tag = trim($tag);
                    if (strlen($tag) > 0) {
                        switch ($tag) {
                            // Setup some defaults for ease of use
                            case 'a':
                                $_att[] = 'a.href,a.title';
                                break;
                            case 'img':
                                $_att[] = 'img.src,img.width,img.height,img.alt';
                                break;
                            default:
                                // If the tag has a period in it - i.e. "iframe.src"
                                // we need to add tag to tags, and attribute to attributes
                                if (strpos($tag,'.')) {
                                    list($t,) = explode('.',$tag);
                                    $_att[] = $tag;
                                    $tag = $t;
                                }
                                break;
                        }
                        $_all[$k] = $tag;
                    }
                }
                $allw = implode(',',$_all);
            }
            unset($_all);

            // now strip our tags
            if (strlen($allw) > 0) {

                // cleanup with HTML purifier
                require_once APP_DIR .'/modules/jrCore/contrib/htmlpurifier/HTMLPurifier.standalone.php';
                $pc = HTMLPurifier_Config::createDefault();
                // See: http://htmlpurifier.org/live/configdoc/plain.html
                $pc->set('Core.NormalizeNewlines', false);
                $pc->set('HTML.AllowedElements', $allw);
                if (isset($_att) && is_array($_att) && count($_att) > 0) {
                    $pc->set('HTML.AllowedAttributes', implode(',',$_att));
                    unset($_att);
                }
                $pf = new HTMLPurifier($pc);

                $string = preg_replace("@<!-- pagebreak -->@", "#!-- pagebreak --#", $string); // allow pagebreak from TinyMCE editor
                $string = $pf->purify($string);
                $string = preg_replace("@#!-- pagebreak --#@", "<!-- pagebreak -->", $string);
            }
        }
        else {
            $string = strip_tags($string);
        }
    }
    return $string;
}

/**
 * Recursively run stripslashes() on a string or array
 *
 * @param mixed $data data mixed data to strip slashes from
 * @return mixed
 */
function jrCore_stripslashes($data)
{
    if (isset($data) && is_array($data)) {
        foreach ($data as $k => $v) {
            $data[$k] = jrCore_stripslashes($v);
        }
        return $data;
    }
    return stripslashes($data);
}

/**
 * Get IP Address of a viewer
 *
 * @return string Returns IP Address.
 */
function jrCore_get_ip()
{
    $tmp = jrCore_get_flag('jrcore_get_ip');
    if ($tmp) {
        return $tmp;
    }
    // See if we are running in Demo mode (all 1's)
    if ((!isset($_SERVER['REMOTE_ADDR']) || empty($_SERVER['REMOTE_ADDR'])) || $_SERVER['REMOTE_ADDR'] == $_SERVER['SERVER_ADDR']) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {   $real_ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {   $real_ip = $_SERVER['HTTP_X_FORWARDED']; }
        elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) { $real_ip = $_SERVER['HTTP_FORWARDED_FOR']; }
        elseif (isset($_SERVER['HTTP_FORWARDED'])) {     $real_ip = $_SERVER['HTTP_FORWARDED']; }
        elseif (isset($_SERVER['HTTP_VIA'])) {           $real_ip = $_SERVER['HTTP_VIA']; }
        elseif (isset($_SERVER['HTTP_X_COMING_FROM'])) { $real_ip = $_SERVER['HTTP_X_COMING_FROM']; }
        elseif (isset($_SERVER['HTTP_COMING_FROM'])) {   $real_ip = $_SERVER['HTTP_COMING_FROM']; }
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {     $real_ip = $_SERVER['HTTP_CLIENT_IP']; }
        elseif (isset($_SERVER['REMOTE_ADDR'])) {        $real_ip = $_SERVER['REMOTE_ADDR']; }
    }
    else {
        $real_ip = $_SERVER['REMOTE_ADDR'];
    }
    if (!isset($real_ip{5})) {
        $real_ip = '0.0.0.0';
    }
    jrCore_set_flag('jrcore_get_ip',$real_ip);
    return $real_ip;
}

/**
 * Get substring of a string based on position and separator
 *
 * @param string $string Input string to get field from
 * @param string $field numerical position in string to return, or "END" to return last word of string. If $field is negative, then counting begins from the end of the string backwards.
 * @param string $sep Field separator for string
 * @return mixed Returns field from string, or false on error
 */
function jrCore_string_field($string,$field,$sep = ' ')
{
    if ($sep == ' ') {
        // first - convert tabs to spaces
        $string = str_replace("\t",' ',$string);
    }
    // see if they want the LAST field
    if ($field == 'NF' || $field == 'END') {
        $out = explode($sep,$string);
        return end($out);
    }
    // numerical (positive int)
    elseif ($field > 0) {
        $i = 1;
        foreach (@explode($sep,$string) as $v) {
            if (strlen($v) >= 1) {
                $_out[$i] = trim($v);
                if (isset($_out[$field])) {
                    return $_out[$field];
                }
                $i++;
            }
        }
    }
    // negative (backwards from end of string)
    else {
        $field = str_replace('-','',$field);
        $i = 1;
        foreach (@array_reverse(explode($sep,$string)) as $v) {
            if (strlen($v) >= 1) {
                $_out[$i] = trim($v);
                if (isset($_out[$field])) {
                    return $_out[$field];
                }
                $i++;
            }
        }
    }
    return false;
}

/**
 * Display debug information on screen
 *
 * @param string $input Data to print to the screen
 * @param string $color HTML Color code to use when printing to the screen.
 * @return bool Returns True/False on Success/Fail.
 */
function debug($input,$color = '000000')
{
    // get our script name
    $script = explode('/',$_SERVER['PHP_SELF']);
    $script = end($script);
    // get our memory usage
    $used = 'na';
    if (function_exists('memory_get_usage')) {
        $used = memory_get_usage();
    }
    // Config our time
    list($usec,$sec) = explode(' ',microtime());
    $sec  = strftime('%H:%M:%S',$sec);
    ob_start();
    echo "<pre style=\"text-align:left;color:#{$color};font-size:10px;font-family:monospace;font-weight:normal;text-transform:none\">({$sec} {$usec})-({$script})-({$used})<br>";
    if (isset($input) && (is_array($input) || is_object($input))) {
        print_r($input);
    }
    else {
        echo '|'. htmlentities($input,ENT_QUOTES) .'|';
    }
    echo "</pre><br>";
    ob_end_flush();
    return true;
}

/**
 * Log debug info to the data/logs/debug_log
 *
 * @param mixed $val each param passed in will be logged
 * @return bool
 */
function fdebug($val = null)
{
    $used = memory_get_usage(true);
    list($micro) = explode(' ',microtime());
    $micro = date('c') .' '. $micro;
    $out = '';
    if (func_num_args() > 0) {
        foreach (func_get_args() as $arg) {
            // open our file handle
            $out .= "\n({$micro})-(mem: {$used})-(pid: ". getmypid() .")-(uri: {$_SERVER['REQUEST_URI']})\n";
            if (is_array($arg) || is_object($arg)) {
                $out .= print_r($arg,true);
            }
            else {
                $out .= "|{$arg}|\n";
            }
        }
        jrCore_write_to_file(APP_DIR ."/data/logs/debug_log",$out,'append');
    }
    return true;
}

/**
 * Recursively copy one directory to another
 * @param string $source Source Directory (directory to copy to)
 * @param string $destination Destination directory (directory to copy from)
 * @param array $_replace of K/V pairs for replacement within copied files
 * @return bool
 */
function jrCore_copy_dir_recursive($source,$destination,$_replace = null)
{
    global $_conf;
    if (!is_dir($source)) {
        return false;
    }
    if (!is_dir($destination)) {
        mkdir($destination,$_conf['jrCore_dir_perms']);
    }
    $f = opendir($source);
    if ($f) {
        $pats = array();
        if (isset($_replace) && is_array($_replace)) {
            foreach (array_keys($_replace) as $str) {
                $pats[] = '/(?<![\{$\w])'. $str .'(?![=\}])/';
            }
        }
        while ($file = readdir($f)) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir("{$source}/{$file}")) {
                jrCore_copy_dir_recursive("{$source}/{$file}","{$destination}/{$file}",$_replace);
            }
            else {
                $rep = false;
                switch (jrCore_file_extension($file)) {
                    case 'tpl':
                    case 'css':
                    case 'php':
                    case 'cfg':
                    case 'js':
                    case 'htm':
                    case 'html':
                    case 'xml':
                        $rep = true;
                        break;
                }
                if (is_null($_replace) || !$_replace || !$rep) {
                    // Straight Copy (possibly to new name)
                    $fnm = $file;
                    if (isset($_replace) && is_array($_replace)) {
                        $fnm = preg_replace($pats,array_values($_replace),$file);
                    }
                    if (copy("{$source}/{$file}","{$destination}/{$fnm}")) {
                        chmod("{$destination}/{$fnm}",$_conf['jrCore_file_perms']);
                    }
                }
                else {
                    // Key => Value replacements in destination
                    $tmp = file_get_contents("{$source}/{$file}");
                    $fnm = $file;
                    if (isset($_replace) && is_array($_replace)) {
                        $tmp = preg_replace($pats,array_values($_replace),$tmp);
                        $fnm = preg_replace($pats,array_values($_replace),$file);
                    }
                    jrCore_write_to_file("{$destination}/{$fnm}",$tmp);
                    unset($tmp);
                }
            }
        }
        closedir($f);
    }
    return true;
}

/**
 * Delete all content from a directory, including sub directories, optionally aged
 *
 * @param string $dir Directory to remove all files and sub directories inside.
 * @param bool $cache_check default directory must be in cache directory
 * @param int $safe_seconds Files/Directories younger than "safe_seconds" will be ignore
 * @return bool
 */
function jrCore_delete_dir_contents($dir,$cache_check = true,$safe_seconds = 0)
{
    if (!is_dir($dir)) {
        return false;
    }
    if ($cache_check && strpos($dir,APP_DIR .'/data/cache') !== 0) {
        jrCore_logger('CRI',"jrCore_delete_dir_contents: invalid directory - must be a valid directory within the data/cache directory");
        return false;
    }
    // There are some directories we don't ever want to delete this way
    $_lock = array('jrCore','jrUser','jrProfile');
    foreach ($_lock as $locked) {
        if (strpos(' '. $dir,'modules/'. $locked)) {
            jrCore_logger('CRI',"jrCore_delete_dir_contents: invalid directory - cannot be jrCore, jrUser or jrProfile!");
            return false;
        }
    }
    $secs = false;
    if (isset($safe_seconds) && jrCore_checktype($safe_seconds,'number_nz')) {
        $secs = (time() - intval($safe_seconds));
    }

    // and now do our deletion
    $cnt = 0;
    if ($h = opendir($dir)) {
        while (($file = readdir($h)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir("{$dir}/{$file}")) {
                $cnt += jrCore_delete_dir_contents("{$dir}/{$file}",$cache_check,$safe_seconds);
                if (@rmdir("{$dir}/{$file}")) {
                    // Directory was empty so could be removed
                    $cnt++;
                }
            }
            else {
                if ($secs) {
                    $_tmp = stat("{$dir}/{$file}");
                    if (isset($_tmp['mtime']) && $_tmp['mtime'] < $secs) {
                        unlink("{$dir}/{$file}");
                        $cnt++;
                    }
                }
                else {
                    unlink("{$dir}/{$file}");
                    $cnt++;
                }
            }
        }
        closedir($h);
    }
    return $cnt;
}

/**
 * Recursively get all files and directories within a directory
 *
 * @param string $dir Directory to get files for
 * @param bool $finish flag for final dir check
 * @return mixed
 */
function jrCore_get_directory_files($dir,$finish = true)
{
    $_out = false;
    $dir = rtrim(trim($dir),'/');
    if ($h = opendir($dir)) {
        $_out = array();
        while (false !== ($file = readdir($h))) {
            if ($file == '.' || $file == '..' ) {
                continue;
            }
            elseif (is_dir($dir .'/'. $file)) {
                $_tmp = jrCore_get_directory_files("{$dir}/{$file}",false);
                if (isset($_tmp) && is_array($_tmp)) {
                    $_out = array_merge($_out,$_tmp);
                }
            }
            else {
                $_out[] = "{$dir}/{$file}";
            }
        }
        closedir($h);
    }
    if ($finish && isset($_out) && is_array($_out)) {
        foreach ($_out as $k => $full_file) {
            $_out[$full_file] = str_replace("{$dir}/",'',$full_file);
            unset($_out[$k]);
        }
    }
    return $_out;
}

/**
 * Convert a string to a File System safe string
 *
 * @param string $string String to return URL encoded
 * @return string
 */
function jrCore_file_string($string)
{
    return rawurlencode($string);
}

/**
 * Convert a string to a URL Safe string
 *
 * Note: Used to generate slug's
 *
 * @param string $string String to convert URLs in
 * @return string
 */
function jrCore_url_string($string)
{
    $str = iconv('UTF-8','ASCII//TRANSLIT',substr($string,0,128));
    $str = preg_replace("/[^a-zA-Z0-9\/_| -]/",'',$str);
    $str = strtolower(trim($str,'-'));
    $str = trim(preg_replace("/[\/_| -]+/",'-',$str));
    if (strlen($str) === 0) {
        // We may have removed everything - rawurlencode
        return rawurlencode(jrCore_str_to_lower(str_replace(array(' ','&','@','/','[',']','(',')'),'-',$string)));
    }
    return $str;
}

/**
 * Write data to a file with file locking
 *
 * @param string $file File to write to
 * @param string $data Data to write to file
 * @param string $mode Mode - can be "overwrite" or "append"
 * @return bool
 */
function jrCore_write_to_file($file,$data = null,$mode = 'overwrite')
{
    global $_conf;
    // DO NOT USE FDEBUG() IN HERE!
    if (!isset($data) || is_null($data)) {
        return false;
    }
    ignore_user_abort(true);
    if (isset($mode) && $mode == 'overwrite') {
        $f = fopen($file,'wb');
    }
    else {
        $f = fopen($file,'ab');
    }
    if (!isset($f) || !is_resource($f)) {
        return false;
    }
    flock($f,LOCK_EX);
    $ret = fwrite($f,$data);
    flock($f,LOCK_UN);
    fclose($f);
    ignore_user_abort(false);
    if (!$ret) {
        return false;
    }
    if (!isset($_conf['jrCore_file_perms'])) {
        $_conf['jrCore_file_perms'] = 0644;
    }
    chmod($file,$_conf['jrCore_file_perms']);
    return true;
}

/**
 * Get file extension from a file
 *
 * Note: Returns file extension in lower case!
 *
 * @param string $file file string file name to return extension for
 * @return string
 */
function jrCore_file_extension($file)
{
    if (strpos($file,'.')) {
        $_tmp = explode('.',trim($file));
        if (isset($_tmp) && is_array($_tmp)) {
            return jrCore_str_to_lower(array_pop($_tmp));
        }
    }
    return false;
}

/**
 * Get file extension for a given Mime-Type
 *
 * Note: This function relies on the /etc/mime.types file being readable by the web user
 * Note: File extension is return lower case
 *
 * @param string $type Mime-Type
 * @return bool|string Returns extension if mime-type found, false if not found/known
 */
function jrCore_file_extension_from_mime_type($type)
{
    $ext = false;
    if (isset($type) && strpos($type,'/') && is_readable('/etc/mime.types')) {
        $_mim = jrCore_get_flag('jrcore_loaded_mime_types');
        if (!$_mim) {
            $_mim = file('/etc/mime.types');
            jrCore_set_flag('jrcore_loaded_mime_types',$_mim);
        }
        foreach ($_mim as $line) {
            if (strpos($line,$type) === 0) {
                $ext = trim(jrCore_string_field($line,'NF'));
                if (isset($ext) && $ext != $type) {
                    switch ($ext) {
                        case 'jpe':
                        case 'jpeg':
                            $ext = 'jpg';
                            break;
                    }
                    break;
                }
            }
        }
    }
    if (!$ext) {
        // Go to our built in mime list
        $_ms = array_flip(jrCore_get_mime_list());
        if (isset($_ms[$type])) {
            return $_ms[$type];
        }
    }
    return $ext;
}

/**
 * Return an array of file extensions => mime types
 *
 * @return array
 */
function jrCore_get_mime_list()
{
    $_mimes = array(
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',

        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // archives
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'exe'  => 'application/x-msdownload',
        'msi'  => 'application/x-msdownload',
        'cab'  => 'application/vnd.ms-cab-compressed',

        // audio
        'mp3'  => 'audio/mpeg',
        'm4a'  => 'audio/mp4',
        'wma'  => 'audio/x-ms-wma',
        'ogg'  => 'application/ogg',
        'flac' => 'audio/x-flac',
        'wav'  => 'audio/wav',
        'aac'  => 'application/aac',

        // video
        'flv'  => 'video/x-flv',
        'f4v'  => 'video/x-flv',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        'ogv'  => 'video/ogg',
        'm4v'  => 'video/x-m4v',
        'mpg'  => 'video/mpeg',
        'mp4'  => 'video/mp4',
        'avi'  => 'video/avi',
        '3gp'  => 'video/3gpp',
        '3g2'  => 'video/3gpp2',
        'wmv'  => 'video/x-ms-wmv',

        // adobe
        'pdf'  => 'application/pdf',
        'psd'  => 'image/vnd.adobe.photoshop',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
        'ps'   => 'application/postscript',

        // ms office
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',

        // open office
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet'
    );
    return $_mimes;
}

/**
 * Get Mime-Type for a file
 *
 * @param string $file File Name
 * @return string
 */
function jrCore_mime_type($file)
{
    // mime_content_type is deprecated
    if (is_file($file)) {
        if (function_exists('mime_content_type')) {
            return mime_content_type($file);
        }
        elseif (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME);
            $mime  = @finfo_file($finfo,$file);
            @finfo_close($finfo);
            if (isset($mime{0})) {
                if (isset($mime) && strpos($mime,'; ')) {
                    $mime = substr($mime,0,strpos($mime,'; '));
                }
                return $mime;
            }
        }
    }
    // Go on file extension - all we have left
    $_ms = jrCore_get_mime_list();
    $ext = jrCore_file_extension($file);
    if (isset($ext) && strlen($ext) > 0 && isset($_ms[$ext])) {
        return $_ms[$ext];
    }
    // Last check - see if we can do a system call to get the extension
    if (function_exists('system')) {
        ob_start();
        $mime = @system("file -bi {$file}");
        ob_end_clean();
        if (isset($mime{0})) {
            return $mime;
        }
    }
    return 'application/binary';
}

/**
 * Format an integer to "readable" size
 *
 * @param int Number to format
 * @return string Returns formatted number
 */
function jrCore_format_size($number)
{
    // make sure we get a number
    if (!is_numeric($number)) {
        return false;
    }
    $kb = 1024;
    $mb = 1024 * $kb;
    $gb = 1024 * $mb;
    $tb = 1024 * $gb;

    // if it's less than a kb we just return
    // the size, otherwise we keep going until
    // the size is in the appropriate measurement range.
    if ($number < $kb) {
        return $number .'B';
    }
    elseif ($number < $mb) {
        return round($number / $kb) .'KB';
    }
    elseif ($number < $gb) {
        return round($number / $mb,1) .'MB';
    }
    elseif ($number < $tb) {
        return round($number / $gb,2) .'GB';
    }
    return round($number / $tb,2) .'TB';
}

/**
 * Format an integer of seconds to a "readable" timestamp
 * @param int $length Time number to format
 * @return string Returns formatted time
 */
function jrCore_format_seconds($length = 0)
{
    $length = round($length);  // no decimals

    $numh = (int) ($length / 3600);
    $hour = str_pad($numh,2,'0',STR_PAD_LEFT);
    $mins = str_pad(floor(($length - ($numh * 3600)) / 60),2,'0',STR_PAD_LEFT);
    $secs = str_pad(($length % 60),2,'0',STR_PAD_LEFT);
    $time = "{$hour}:{$mins}:{$secs}";
    return $time;
}

/**
 * Formats an Epoch Time Stamp to the format specified by the system
 *
 * @param int $timestamp Epoch Time Stamp to format
 * @param bool $date_only Set to true to just return DATE portion (instead of DATE TIME)
 * @param string $format Date Format for Display
 * @return string
 */
function jrCore_format_time($timestamp,$date_only = false,$format = null)
{
    global $_conf;
    if (!jrCore_checktype($timestamp,'number_nz')) {
        return '';
    }
    $off = date_offset_get(new DateTime);
    if ($date_only) {
        if (is_null($format)) {
            $format = $_conf['jrCore_date_format'];
        }
    }
    else {
        if (is_null($format)) {
            $format = "{$_conf['jrCore_date_format']} {$_conf['jrCore_hour_format']}";
        }
    }
    if (isset($format) && $format == 'relative') {
        $_lang = jrUser_load_lang_strings();

        $time = time();
        $diff = ($time - $timestamp);
        if ($diff < 60) {
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][51]}" : $_lang['jrCore'][52], $diff);
        }
        $diff = floor($diff / 60);
        if ($diff < 60) {
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][53]}" : $_lang['jrCore'][54], $diff);
        }
        $diff = floor($diff / 60);
        if ($diff < 24) {
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][55]}" : $_lang['jrCore'][56], $diff);
        }
        $diff = floor($diff / 24);
        if ($diff < 7) {
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][57]}" : $_lang['jrCore'][58], $diff);
        }
        if ($diff < 30) {
            $diff = floor($diff / 7);
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][59]}" : $_lang['jrCore'][60], $diff);
        }
        $diff = floor($diff / 30);
        if ($diff < 12) {
            return sprintf($diff > 1 ? "%s {$_lang['jrCore'][61]}" : $_lang['jrCore'][62], $diff);
        }
        $diff = date('Y',$time) - date('Y',$timestamp);
        return sprintf($diff > 1 ? "%s {$_lang['jrCore'][63]}" : $_lang['jrCore'][64], $diff);
    }
    return gmstrftime($format,($timestamp + $off));
}

/**
 * Save a URL to a "stack" of URLs by name
 *
 * @param string $tag Text Tag for memory URL
 * @param string $url URL to remember
 * @return bool
 */
function jrCore_create_memory_url($tag,$url = 'referrer')
{
    if (!isset($url) || strlen($url) === 0 || $url === 'referrer') {
        $url = jrCore_get_local_referrer();
    }
    if (!isset($_SESSION['jrcore_memory_urls'])) {
        $_SESSION['jrcore_memory_urls'] = array();
    }
    if (!isset($_SESSION['jrcore_memory_urls'][$tag])) {
        $_SESSION['jrcore_memory_urls'][$tag] = $url;
    }
    return true;
}

/**
 * Get a URL from the memory stack by name
 *
 * @param string $tag Text Tag for memory URL
 * @param string $url URL to return if not set
 * @return string
 */
function jrCore_get_memory_url($tag,$url = 'referrer')
{
    return (isset($_SESSION['jrcore_memory_urls'][$tag])) ? $_SESSION['jrcore_memory_urls'][$tag] : $url;
}

/**
 * Delete a URL from the memory stack by name
 * @param string $tag Text Tag for memory URL
 * @return string
 */
function jrCore_delete_memory_url($tag)
{
    unset($_SESSION['jrcore_memory_urls'][$tag]);
    return true;
}

/**
 * Get max allowed upload size as defined by PHP and the user's Quota
 * @param int $quota_max Max as set in Profile Quota
 * @return int Returns Max Upload in Megabytes
 */
function jrCore_get_max_allowed_upload($quota_max = 0)
{
    // figure max upload form size
    $php_pmax = (int) ini_get('post_max_size');
    $php_umax = (int) ini_get('upload_max_filesize');
    $val = ($php_pmax > $php_umax) ? $php_umax : $php_pmax;

    // For our progress meter we must use the following logic to arrive at our
    // max allowed upload size: Use 1/4 memory_limit, and if $val is smaller use that
    $php_mmax = ceil(intval(str_replace('M','',ini_get('memory_limit'))) / 4);
    $val = ($php_mmax > $val) ? $val : $php_mmax;
    $val = ($val * 1048576);

    // Check if we are getting a quota restricted level
    if (isset($quota_max) && is_numeric($quota_max) && $quota_max > 0 && $quota_max < $val) {
        $val = $quota_max;
    }
    return $val;
}

/**
 * Array of upload sizes to be used in a Select field
 * @return array Array of upload sizes
 */
function jrCore_get_upload_sizes()
{
    $s_max = (int) jrCore_get_max_allowed_upload(false);
    $_qmem = array();
    $_memr = array(1,2,4,8,16,24,32,48,64,72,96,100,128,160,200,256,300,350,384,400,500,512,600,640,700,768,800,896,1000,1024);
    foreach ($_memr as $m) {
        $v = $m * 1048576;
        if ($v < $s_max) {
            $_qmem[$v] = jrCore_format_size($v);
        }
    }
    $_qmem[$s_max] = jrCore_format_size($s_max) ." - max allowed";
    return $_qmem;
}

/**
 * returns a URL if the referring URL is the user's own profile,
 * @param string $default URL to return if referrer is NOT from user's profile
 * @return string
 */
function jrCore_is_profile_referrer($default = 'referrer')
{
    global $_conf, $_user;
    if (isset($_SESSION['memory_profile_cancel_url'])) {
        return $_SESSION['memory_profile_cancel_url'];
    }
    $url = jrCore_get_local_referrer();
    if (isset($_user['profile_url']) && strpos($url,"{$_conf['jrCore_base_url']}/{$_user['profile_url']}") === 0) {
        return $url;
    }
    return $default;
}

/**
 * Set a custom Send Header
 *
 * @param string $header Header to set
 * @return bool
 */
function jrCore_set_custom_header($header)
{
    $_tmp = jrCore_get_flag('jrcore_set_custom_header');
    if (!$_tmp) {
        $_tmp = array();
    }
    $_tmp[] = $header;
    jrCore_set_flag('jrcore_set_custom_header',$_tmp);
    return true;
}

/**
 * Strips a string of all non UTF8 characters
 *
 * @param string $string String to strip UTF8 characters from
 * @return mixed
 */
function jrCore_strip_non_utf8($string)
{
    // strip overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
    // removed: [\x00-\x7F][\x80-\xBF]
    $string = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]+'.
    '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})'.
    '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S','',$string);

    // strip overly long 3 byte sequences and UTF-16 surrogates and replace with ?
    return preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S','',$string);
}

/**
 * Returns RAM usage on server
 *
 * @return array
 */
function jrCore_get_system_memory()
{
    if (!function_exists('system')) {
        return false;
    }
    $key = "jrcore_get_system_memory";
    $out = jrCore_is_cached('jrCore',$key);
    if ($out) {
        return json_decode($out,true);
    }

    // See what system we are on
    ob_start();
    $_out = array();
    // Mac OS X
    if (stristr(PHP_OS,'darwin')) {
        // First get TOP for total memory
        // PhysMem:  98.9M wired,  163M active,  525M inactive,  787M used,  236M free
        // PhysMem: 1517M wired, 3389M active, 1544M inactive, 6450M used, 1739M free.
        @system('/usr/bin/top -l1 -n0',$ret);
        $out = ob_get_contents();
        ob_end_clean();
        if ($ret != 0) {
            return(false);
        }
        $_tmp = explode("\n",$out);
        if (!isset($_tmp) || !is_array($_tmp)) {
            return false;
        }
        foreach ($_tmp as $line) {
            $line = trim($line);
            if (strpos($line,'PhysMem') === 0) {
                // [6] => PhysMem: 1430M wired, 3177M active, 2355M inactive, 6962M used, 1227M free.
                $used = (int) str_replace('M','',jrCore_string_field($line,8));
                $free = (int) str_replace('M','',jrCore_string_field($line,10));
                $_out['memory_used']  = $used * 1048576;
                $_out['memory_free']  = $free * 1048576;
                $_out['memory_total'] = $_out['memory_used'] + $_out['memory_free'];
                break;
            }
        }
    }
    else {
        // Linux "Free"
        //              total       used       free     shared    buffers     cached
        // Mem:       1033748     997196      36552          0     402108      83156
        // -/+ buffers/cache:     511932     521816
        // Swap:      1379872          0    1379872
        @system('free',$ret);
        $out = ob_get_contents();
        ob_end_clean();
        if ($ret != 0) {
            return false;
        }
        $_tmp = explode("\n",$out);
        if (!isset($_tmp) || !is_array($_tmp)) {
            return false;
        }
        $_one = explode(' ',preg_replace("/[ ]+/",' ',$_tmp[1]));
        $_out['memory_total'] = strval($_one[1] * 1024);
        $_out['memory_free']  = strval(($_one[3] + $_one[5] + $_one[6]) * 1024);
        $_out['memory_used']  = strval($_out['memory_total'] - $_out['memory_free']);
    }

    // Common
    if (jrCore_checktype($_out['memory_total'],'number_nz')) {
        $_out['percent_used'] = round(($_out['memory_total'] - $_out['memory_free']) / $_out['memory_total'],2) * 100;
    }
    else {
        $_out['percent_used'] = 0;
    }
    if ($_out['percent_used'] > 95) {
        $_out['class'] = 'bigsystem-cri';
    }
    elseif ($_out['percent_used'] > 90) {
        $_out['class'] = 'bigsystem-maj';
    }
    elseif ($_out['percent_used'] > 85) {
        $_out['class'] = 'bigsystem-min';
    }
    else {
        $_out['class'] = 'bigsystem-inf';
    }
    jrCore_add_to_cache('jrCore',$key,json_encode($_out),20);
    return $_out;
}

/**
 * Get information about disk usage
 *
 * @return array
 */
function jrCore_get_disk_usage()
{
    $key = "jrcore_get_disk_usage";
    $out = jrCore_is_cached('jrCore',$key);
    if ($out) {
        return json_decode($out,true);
    }
    clearstatcache();
    $ts = disk_total_space(APP_DIR);
    $fs = disk_free_space(APP_DIR);
    $_out = array();
    $_out['disk_total']   = $ts;
    $_out['disk_free']    = $fs;
    $_out['disk_used']    = ($ts - $fs);
    $_out['percent_used'] = round(($ts - $fs) / $ts,2) * 100;
    if ($_out['percent_used'] > 95) {
        $_out['class'] = 'bigsystem-cri';
    }
    elseif ($_out['percent_used'] > 90) {
        $_out['class'] = 'bigsystem-maj';
    }
    elseif ($_out['percent_used'] > 85) {
        $_out['class'] = 'bigsystem-min';
    }
    else {
        $_out['class'] = 'bigsystem-inf';
    }
    jrCore_add_to_cache('jrCore',$key,json_encode($_out),300);
    return $_out;
}

/**
 * Returns load information for server
 *
 * @param int Number of processors to determine system load
 * @return array
 */
function jrCore_get_system_load($proc_num = 1)
{
    if (!function_exists('system')) {
        return false;
    }
    $key = "jrcore_get_system_load";
    $out = jrCore_is_cached('jrCore',$key);
    if ($out) {
        return json_decode($out,true);
    }
    // go do our system() call and get our uptime
    ob_start();
    @system('uptime',$ret);
    $out = ob_get_contents();
    ob_end_clean();
    if ($ret != 0) {
        // looks we failed on getting our system load - return false
        return false;
    }
    if (!jrCore_checktype($proc_num,'number_nz')) {
        $proc_num = 1;
    }
    // parse it for our needs
    // 17:45:22  up 95 days,  8:29,  3 users,  load average: 0.04, 0.01, 0.00
    //  2:41am  an 29 Tage 16:10,  1 Benutzer,  Durchschnittslast: 0,27, 0,25, 0,25
    $_cpu = explode(" ",$out);
    $num1 = count($_cpu) - 1;
    $num2 = $num1 - 1;
    $num3 = $num2 - 1;
    $load[15]['level'] = trim($_cpu[$num1]);
    if (!is_numeric($load[15]['level'])) {
        return(false);
    }
    $load[5]['level']  = trim(str_replace(',','',$_cpu[$num2]));
    $load[1]['level']  = trim(str_replace(',','',$_cpu[$num3]));
    foreach (array(1,5,15) as $ll) {
        $level = number_format(round(($load[$ll]['level'] / $proc_num),2),2);
        if ($level > 4) {
            $load[$ll]['class'] = 'bigsystem-cri';
        }
        elseif ($level > 3) {
            $load[$ll]['class'] = 'bigsystem-maj';
        }
        elseif ($level > 2) {
            $load[$ll]['class'] = 'bigsystem-min';
        }
        else {
            $load[$ll]['class'] = 'bigsystem-inf';
        }
        $load[$ll]['level'] = $level;
    }
    jrCore_add_to_cache('jrCore',$key,json_encode($load),30);
    return $load;
}

/**
 * Return information about Server Processors
 *
 * @return array returns Array with CPU information
 */
function jrCore_get_proc_info()
{
    $key = "jrcore_get_proc_info";
    $out = jrCore_is_cached('jrCore',$key);
    if ($out) {
        return json_decode($out,true);
    }
    $_cpu = array();
    // proc file system
    if (@is_readable('/proc/cpuinfo')) {
        $_tmp = @file("/proc/cpuinfo");
        if (!is_array($_tmp)) {
            return 'unknown CPU';
        }
        $i = 0;
        foreach ($_tmp as $_v) {
            // get our processor
            if (stristr($_v,'model name') || strstr($_v,'altivec')) {
                $i++;
                $_cpu[$i]['model'] = trim(substr($_v,strpos($_v,':') + 1));
            }
            elseif (stristr($_v,'cpu MHz') || strstr($_v,'clock')) {
                $_cpu[$i]['mhz'] = round(trim(substr($_v,strpos($_v,':') + 1))) ." MHz";
            }
            elseif (stristr($_v,'cache size') || strstr($_v,'L2 cache')) {
                $_cpu[$i]['cache'] = trim(substr($_v,strpos($_v,':') + 1));
            }
        }
    }
    // no proc file system - check for sysctl
    elseif (function_exists('is_executable') && @is_executable('/usr/sbin/sysctl')) {
        ob_start();
        @system('/usr/sbin/sysctl -a hw');
        $out = ob_get_contents();
        ob_end_clean();
        $i = 1;
        $ncp = 0;
        $_cpu[$i]['mhz']   = '';
        $_cpu[$i]['cache'] = '';
        $_cpu[$i]['model'] = '';
        foreach (explode("\n",$out) as $line) {

            // Number of procs
            if (strstr($line,'ncpu') && !isset($ncp)) {
                $ncp = (int) jrCore_string_field($line,'NF');
            }
            // Mac OS X CPU Model
            elseif (strstr($line,'hw.model')) {
                $tmp = explode('=',$line);
                $_cpu[$i]['model'] = trim($tmp[1]);
            }
            elseif (strstr($line,'hw.cpufrequency:')) {
                $tmp = explode(' ',$line);
                $tmp = (int) end($tmp);
                if (is_numeric($tmp)) {
                    $_cpu[$i]['mhz'] = round(((($tmp / 1000) / 1000) / 1000),2) ." GHz";
                }
            }
            elseif (strstr($line,'hw.l2cachesize:')) {
                $tmp = explode(' ',$line);
                $tmp = (int) end($tmp);
                if (is_numeric($tmp)) {
                    $_cpu[$i]['cache'] = round($tmp / 1024) ." Kb";
                }
            }
        }
        if (jrCore_checktype($ncp,'number_nz') && $ncp < 32) {
            while ($i < $ncp) {
                $i++;
                $_cpu[$i] = $_cpu[1];
            }
        }
    }
    else {
        return 'unknown CPU';
    }
    jrCore_add_to_cache('jrCore',$key,json_encode($_cpu),10800);
    return $_cpu;
}

/**
 * Create a new ZIP file from an array of files
 * @param $file string Full path of ZIP file to create
 * @param $_files array Array of files to add to zip file
 * @return bool
 */
function jrCore_create_zip_file($file,$_files)
{
    require_once APP_DIR . "/modules/jrCore/contrib/zip/Zip.php";
    $zip = new Zip();
    $zip->setZipFile($file);
    $cnt = 0;
    foreach ($_files as $filename => $filepath) {
        if (is_file($filepath)) {
            if ($filename === $cnt) {
                $filename = str_replace(APP_DIR .'/','',$filepath);
            }
            $zip->addFile(file_get_contents($filepath), $filename, filemtime($filepath));
        }
        $cnt++;
    }
    $zip->finalize();
    return true;
}

/**
 * ZIP files in a given array and "stream" the resulting ZIP to the browser
 * @param $name string Name of ZIP file to send
 * @param $_files array Array of files to send
 * @return bool
 */
function jrCore_stream_zip($name,$_files)
{
    $tmp = jrCore_get_module_cache_dir('jrCore');
    // Send out our ZIP stream
    require_once APP_DIR . "/modules/jrCore/contrib/zip/ZipStream.php";
    $zip = new ZipStream($name);
    $cnt = 0;
    foreach ($_files as $filename => $filepath) {
        if (is_file($filepath)) {
            $f = fopen($filepath, 'rb');
            if ($filename === $cnt) {
                $filename = basename($filepath);
            }
            $zip->addLargeFile($f, $filename, $tmp);
            fclose($f);
            $cnt++;
        }
    }
    $zip->finalize();
    return true;
}

/**
 * Send a file to a browser that causes a "Save..." dialog
 * @param $file string File to send
 * @return bool
 */
function jrCore_send_download_file($file)
{
    if (!is_file($file) || strpos($file,APP_DIR) !== 0 || strpos($file,'..')) {
        return false;
    }
    // Send headers to initiate download prompt
    $size = filesize($file);
    header('Content-Length: '. $size);
    header('Connection: close');
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'. basename($file) .'"');

    $handle = fopen($file,'rb');
    if (!$handle) {
        jrCore_logger('CRI',"jrCore_send_download_file: unable to create file handle for download: {$file}");
        return false;
    }
    $bytes_sent = 0;
    while ($bytes_sent < $size) {
        fseek($handle,$bytes_sent);
        // Read 1 megabyte at a time...
        $buffer = fread($handle,1048576);
        $bytes_sent += strlen($buffer);
        echo $buffer;
        flush();
        unset($buffer);
        // Support up to 10MB per second
        usleep(100000);
        // Also - check that we have not sent out more data then the allowed size
        if ($bytes_sent >= $size) {
            fclose($handle);
            return true;
        }
    }
    fclose($handle);
    return true;
}
