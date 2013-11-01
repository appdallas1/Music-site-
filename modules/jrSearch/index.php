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

//------------------------------
// Search results
// In: $_post['search_string']
// In: $_post['_1'] = module
// In: $_post['_2'] = page
// In: $_post['_3'] = pagebreak
//------------------------------
function view_jrSearch_results($_post,$_user,$_conf)
{
    if (empty($_post['search_string'])) {
        if (isset($_SESSION['jrsearch_last_search_string'])) {
            $_post['search_string'] = $_SESSION['jrsearch_last_search_string'];
        }
        else {
            jrCore_page_not_found();
        }
    }
    $_SESSION['jrsearch_last_search_string'] = strip_tags($_post['search_string']);

    // Do search and get results
    $out = jrCore_parse_template('header.tpl');

    // First - find modules we are going to be searching
    $_rm = jrCore_get_registered_module_features('jrSearch','search_fields');
    if (!empty($_post['_1']) && $_post['_1'] != 'all') {
        $_tm = explode(',',$_post['_1']);
        if (isset($_tm) && is_array($_tm)) {
            $_at = array();
            foreach ($_tm as $mod) {
                if (isset($_rm[$mod])) {
                    $_at[$mod] = $_rm[$mod];
                }
            }
            $_rm = $_at;
        }
    }

    if (isset($_rm) && is_array($_rm)) {

        // figure pagebreak
        $page = 1;
        if (!empty($_post['_2'])) {
            $page = (int) $_post['_2'];
        }
        $pbrk = 4;
        if (!empty($_post['_3'])) {
            $pbrk = (int) $_post['_3'];
        }
        $_fn = array(
            'titles'  => array(),
            'results' => array()
        );
        $_ln = jrUser_load_lang_strings();
        $ltl = '';
        $ttl = 0;
        foreach ($_rm as $mod => $_mod) {
            $pfx = jrCore_db_get_prefix($mod);
            if ($pfx) {
                $_sc = array(
                    'search'    => array(),
                    'pagebreak' => $pbrk,
                    'page'      => $page
                );
                foreach ($_mod as $fields => $title) {
                    if (strpos($fields,',')) {
                        foreach (explode(',',$fields) as $field) {
                            $_sc['search'][] = "{$field} LIKE %{$_post['search_string']}%";
                        }
                    }
                    else {
                        $_sc['search'][] = "{$fields} LIKE %{$_post['search_string']}%";
                    }
                    $_fn['titles'][$mod] = (!empty($_ln[$mod][$title])) ? $_ln[$mod][$title] : $mod;
                }
                $_rt = jrCore_db_search_items($mod,$_sc);
                if (isset($_rt) && is_array($_rt) && is_array($_rt['_items'])) {
                    if (is_file(APP_DIR ."/modules/{$mod}/templates/item_search.tpl")) {
                        $_fn['results'][$mod] = jrCore_parse_template('item_search.tpl',$_rt,$mod);
                    }
                    else {
                        $_fn['results'][$mod] = jrCore_parse_template('item_list.tpl',$_rt,$mod);
                    }
                    $_fn['info'][$mod] = $_rt['info'];
                    $ttl += count($_rt['_items']);
                    $ltl = $_fn['titles'][$mod];
                }
            }
        }
        $_fn['search_string'] = strip_tags($_post['search_string']);
        $_fn['pagebreak']     = $pbrk;
        $_fn['page']          = $page;
        $_fn['modules']       = (isset($_post['_1'])) ? strip_tags($_post['_1']) : '';
        $_fn['module_count']  = count($_fn['results']);
        if ($_fn['module_count'] === 1) {
            $_fn['titles']['all'] = $ltl;
        }
        $out .= jrCore_parse_template('search_results.tpl',$_fn,'jrSearch');

        // Save search details
        if (jrUser_is_logged_in()) {
            $_data = array(
                'search_string'  => $_post['search_string'],
                'search_module'  => (isset($_post['_1'])) ? $_post['_1'] : 'all',
                'search_results' => $ttl
            );
            jrCore_db_create_item('jrSearch',$_data);
        }
    }
    $out .= jrCore_parse_template('footer.tpl');

    return $out;
}
