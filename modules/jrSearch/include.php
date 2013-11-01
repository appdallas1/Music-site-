<?php
/**
 * Jamroom 5 jrSearch module
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

/**
 * meta
 */
function jrSearch_meta() {
    $_tmp = array(
        'name'        => 'Search',
        'url'         => 'search',
        'version'     => '1.0.1',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Search for items in modules that have registered with the system',
        'category'    => 'listing'
    );
    return $_tmp;
}

/**
 * init
 */
function jrSearch_init()
{
    // Add in Search javascript
    jrCore_register_module_feature('jrCore','javascript','jrSearch','jrSearch.js');

    return true;
}

/**
 * jrSearch_form
 * Build a search form
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrSearch_form($params,$smarty)
{
    global $_mods,$_conf;

    //****************************
    // In: module="ModuleName" or module="all" for a global search (default: all)
    // In: page (default:1)
    // In: pagebreak (default:10)
    // In: template (default: html_search_form.tpl)
    // In: class (optional)
    // In: style (optional)
    // In: assign (optional)
    //****************************

    // Get language strings
    $_lang = jrUser_load_lang_strings();

    // Check search module
    if (!isset($_mods['jrSearch']) || $_mods['jrSearch']['module_active != 1']) {
        return '';
    }

    // Check the incoming parameters
    if (empty($params['module'])) {
        $params['module'] = 'all';
    }
    if (!isset($params['page']) || !jrCore_checktype($params['page'],'number_nz')) {
        $params['page'] = 1;
    }

    if (!isset($params['pagebreak']) || !jrCore_checktype($params['pagebreak'],'number_nz')) {
        $params['pagebreak'] = 4;
    }

    if (empty($params['value'])) {
        $params['value'] = $_lang['jrSearch'][1];
    }

    if (empty($params['style'])) {
        $params['style'] = '';
    }

    if (empty($params['class'])) {
        $params['class'] = '';
    }

    if (!empty($params['template'])) {
        $params['tpl_dir'] = $_conf['jrCore_active_skin'];
    }
    else {
        $params['template'] = 'html_search_form.tpl';
        $params['tpl_dir'] = 'jrSearch';
    }
    $_tmp = array();
    foreach ($params as $k=>$v) {
        $_tmp['jrSearch'][$k] = $v;
    }

    // Call the appropriate template and return
    $out = jrCore_parse_template($params['template'],$_tmp,$params['tpl_dir']);
    if (isset($params['assign']) && $params['assign'] != '') {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * jrSearch_recent
 * Show most recent searches
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrSearch_recent($params,$smarty)
{
    global $_mods,$_user,$_conf;

    // Check search module
    if (!isset($_mods['jrSearch']) || $_mods['jrSearch']['module_active != 1']) {
        return '';
    }

    // Check the incoming parameters
    $_s = array();
    if (isset($params['user_id']) && jrCore_checktype($params['user_id'],'number_nz')) {
        $_s[] = "_user_id = {$params['user_id']}";
    }
    if (isset($params['module']) && $params['module'] != '') {
        $_s[] = "search_module = {$params['module']}";
    }
    if (isset($params['limit']) && jrCore_checktype($params['limit'],'number_nz')) {
    }
    else {
        $params['limit'] = 5;
    }
    if (isset($params['style']) && $params['style'] != '') {
    }
    else {
        $params['style'] = '';
    }
    if (isset($params['class']) && $params['class'] != '') {
    }
    else {
        $params['class'] = '';
    }
    if (isset($params['template']) && $params['template'] != '') {
        $params['tpl_dir'] = $_conf['jrCore_active_skin'];
    }
    else {
        $params['template'] = "search_recent.tpl";
        $params['tpl_dir'] = 'jrSearch';
    }

    $_tmp = array();
    foreach ($params as $k=>$v) {
        $_tmp['jrSearch'][$k] = $v;
    }

    // Get most recents
    $_s = array("limit"=>$params['limit'],"order_by"=>array("_created"=>"desc"),"search"=>$_s);
    $_rt = jrCore_db_search_items('jrSearch',$_s);
    if (isset($_rt['_items'][0]) && is_array($_rt['_items'][0])) {
        $i = 0;
        foreach ($_rt['_items'] as $rt) {
            $_tmp['jrSearchRecent'][$i]['module'] = $rt['search_module'];
            $_tmp['jrSearchRecent'][$i]['string'] = $rt['search_string'];
            $i++;
        }
    }

    // Call the appropriate template and return
    $out = jrCore_parse_template($params['template'],$_tmp,$params['tpl_dir']);
    if (isset($params['assign']) && $params['assign'] != '') {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}

/**
 * jrSearch_popular
 * Show most popular searches
 * @param $params array parameters for function
 * @param $smarty object Smarty object
 * @return string
 */
function smarty_function_jrSearch_popular($params,$smarty)
{
    global $_mods,$_user,$_conf;

    // Check search module
    if (!isset($_mods['jrSearch']) || $_mods['jrSearch']['module_active != 1']) {
        return '';
    }

    // Check the incoming parameters
    if (isset($params['limit']) && jrCore_checktype($params['limit'],'number_nz')) {
    }
    else {
        $params['limit'] = 5;
    }
    if (isset($params['style']) && $params['style'] != '') {
    }
    else {
        $params['style'] = '';
    }
    if (isset($params['class']) && $params['class'] != '') {
    }
    else {
        $params['class'] = '';
    }
    if (isset($params['template']) && $params['template'] != '') {
        $params['tpl_dir'] = $_conf['jrCore_active_skin'];
    }
    else {
        $params['template'] = "search_popular.tpl";
        $params['tpl_dir'] = 'jrSearch';
    }

    $_tmp = array();
    foreach ($params as $k=>$v) {
        $_tmp['jrSearch'][$k] = $v;
    }

    // Get most popular
    $tbl = jrCore_db_table_name('jrSearch','item_key');
    $req = "SELECT `_item_id`,COUNT( * ) AS count FROM {$tbl} WHERE `key` = 'search_string' GROUP BY `value` ORDER BY  `count` DESC LIMIT {$params['limit']}";
    $_rt = jrCore_db_query($req,'NUMERIC');
    if (isset($_rt[0]) && is_array($_rt[0])) {
        $i = 0;
        foreach ($_rt as $rt) {
            $_item = jrCore_db_get_item('jrSearch',$rt['_item_id']);
            $_tmp['jrSearchPopular'][$i]['module'] = $_item['search_module'];
            $_tmp['jrSearchPopular'][$i]['string'] = $_item['search_string'];
            $_tmp['jrSearchPopular'][$i]['count'] = $rt['count'];
            $i++;
        }
    }

    // Call the appropriate template and return
    $out = jrCore_parse_template($params['template'],$_tmp,$params['tpl_dir']);
    if (isset($params['assign']) && $params['assign'] != '') {
        $smarty->assign($params['assign'],$out);
        return '';
    }
    return $out;
}
