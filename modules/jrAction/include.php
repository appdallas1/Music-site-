<?php
/**
 * Jamroom 5 jrAction module
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
 * meta
 */
function jrAction_meta()
{
    $_tmp = array(
        'name'        => 'Activity Stream',
        'url'         => 'action',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'User activity is logged to the profile Activity Stream',
        'category'    => 'users',
        'priority'    => 250, // LOW load priority (we want other listeners to run first)
        'activate'    => true
    );
    return $_tmp;
}

/**
 * init
 */
function jrAction_init()
{
    // register our custom JS/CSS
    jrCore_register_module_feature('jrCore', 'javascript', 'jrAction', 'char_count.js');
    jrCore_register_module_feature('jrCore', 'css', 'jrAction', 'jrAction.css');

    // Core options
    jrCore_register_module_feature('jrCore', 'quota_support', 'jrAction', 'on');
    jrCore_register_module_feature('jrCore', 'pending_support', 'jrAction', true);

    // Add additional search params
    jrCore_register_event_listener('jrCore', 'db_search_params', 'jrAction_db_search_params_listener');
    jrCore_register_event_listener('jrCore', 'db_search_items', 'jrAction_db_search_items_listener');

    // "add to timeline" option
    jrCore_register_event_listener('jrCore', 'form_display', 'jrAction_form_display_listener');

    // RSS Feed
    jrCore_register_event_listener('jrFeed', 'create_rss_feed', 'jrAction_create_rss_feed_listener');

    // notifications
    $_tmp = array(
        'label' => 12, // 'mentioned in an activity stream'
        'help'  => 16 // 'If your profile name is mentioned in an Activity Stream do you want to be notified?'
    );
    jrCore_register_module_feature('jrUser', 'notification', 'jrAction', 'mention', $_tmp);

    // We don't show an "actions" menu option in a profile
    jrCore_register_module_feature('jrProfile', 'profile_menu', 'jrAction', 'exclude', true);

    return true;
}

//----------------------
// EVENT LISTENERS
//----------------------

/**
 * Save an Action to the Timeline
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrAction_form_display_listener($_data, $_user, $_conf, $_args, $event)
{
    // See if this module supports actions
    list($mod, $view) = explode('/', $_data['form_view']);
    $_as = jrCore_get_registered_module_features('jrCore', 'action_support');
    if ($_as && isset($_as[$mod][$view])) {
        $_lng = jrUser_load_lang_strings();
        $_tmp = array(
            'name'          => "jraction_add_to_timeline",
            'label'         => $_lng['jrAction'][13],
            'help'          => $_lng['jrAction'][14],
            'type'          => 'checkbox',
            'default'       => 'on',
            'required'      => false,
            'form_designer' => false,
            'order'         => 49
        );
        jrCore_form_field_create($_tmp);
    }
    return $_data;
}

/**
 * jrAction_create_rss_feed_listener
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrAction_create_rss_feed_listener($_data, $_user, $_conf, $_args, $event)
{
    // Format latest actions
    if (isset($_args['module']) && $_args['module'] == 'jrAction') {
        foreach ($_data as $k => $_itm) {
            // We set "title", "url" and "description"
            $_ln = jrUser_load_lang_strings();
            $url = jrCore_get_module_url($_itm['action_module']);
            $pfx = jrCore_db_get_prefix($_itm['action_module']);
            if (isset($_itm['action_text'])) {
                $ttl = $_ln['jrAction'][2];
                $_data[$k]['description'] = strip_tags($_itm['action_text']);
            }
            else {
                $ttl = (isset($_itm['action_item']["{$pfx}_title"])) ? $_itm['action_item']["{$pfx}_title"] : strip_tags($_itm['action_data']);
                $_data[$k]['description'] = strip_tags($_itm['action_data']);
            }
            $_data[$k]['title'] = "@{$_itm['profile_name']} - {$ttl}";
            $_data[$k]['url'] = "{$_conf['jrCore_base_url']}/{$_itm['profile_url']}/{$url}/{$_itm['_item_id']}/" . $_itm['action_item']["{$pfx}_title_url"];
        }
    }
    return $_data;
}

/**
 * jrAction_db_search_params_listener
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrAction_db_search_params_listener($_data, $_user, $_conf, $_args, $event)
{
    if (!isset($_args['module']) || $_args['module'] != 'jrAction') {
        return $_data;
    }
    $_ram = jrCore_get_registered_module_features('jrCore', 'action_support');
    if ($_ram) {
        $_data['search'][] = "action_module in jrAction," . implode(',', array_keys($_ram));
    }
    unset($_ram);
    return $_data;
}

/**
 * jrAction_db_search_items_listener
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrAction_db_search_items_listener($_data, $_user, $_conf, $_args, $event)
{
    if (!isset($_args['module']) || $_args['module'] != 'jrAction' || !is_array($_data['_items'])) {
        return $_data;
    }
    // If we do not need the parsed template, we can skip it
    if (isset($_args['exclude_jrAction_parse_template']) && $_args['exclude_jrAction_parse_template'] === true) {
        return $_data;
    }
    $_ram = jrCore_get_registered_module_features('jrCore', 'action_support');
    foreach ($_data['_items'] as $k => $_v) {
        if (isset($_ram["{$_v['action_module']}"]) && isset($_v['action_data']{1})) {
            $tpl = $_ram["{$_v['action_module']}"]["{$_v['action_mode']}"];
            if (empty($tpl)) {
                unset($_data['_items'][$k]);
                continue;
            }
            $_rp = array('item' => $_v);
            $_rp['item']['action_data'] = json_decode($_v['action_data'], true);
            $_data['_items'][$k]['action_item'] = $_rp['item']['action_data'];
            $_data['_items'][$k]['action_data'] = jrCore_parse_template($tpl, $_rp, $_v['action_module']);
        }
    }
    return $_data;
}

/**
 * Check an Action text for '@' mentions
 * @param $text string Action Text
 * @return bool
 */
function jrAction_check_for_mentions($text)
{
    global $_user;
    // We need to grab any "mentions" our of the text and notify users that
    // they have been mentioned in an activity stream post
    if (!strpos(' ' . $text, '@')) {
        // No mentions of any kind in this text
        return true;
    }
    // We have mentions
    $_words = explode(' ', $text);
    if (isset($_words) && is_array($_words)) {
        $tbl = jrCore_db_table_name('jrProfile', 'item_key');
        $_pr = array();
        foreach ($_words as $word) {
            if (strlen($word) > 0 && strpos($word, '@') === 0) {
                // We have a mention - get profile_id for this profile name
                $_pr[] = substr($word, 1);
            }
        }
        if (isset($_pr) && is_array($_pr)) {
            $req = "SELECT `_item_id` FROM {$tbl} WHERE `key` = 'profile_name' AND `value` IN('" . implode("','", $_pr) . "')";
            $_rt = jrCore_db_query($req, 'NUMERIC');
            if (isset($_rt) && is_array($_rt)) {
                foreach ($_rt as $_prf) {
                    $_owners = jrProfile_get_owner_info($_prf['_item_id']);
                    if (isset($_owners) && is_array($_owners)) {
                        $_rp = array(
                            'action_user' => $_user,
                            'action_url'  => jrCore_get_local_referrer()
                        );
                        list($sub, $msg) = jrCore_parse_email_templates('jrAction', 'mention', $_rp);
                        foreach ($_owners as $_o) {
                            // NOTE: "0" is from_user_id - 0 is the "system user"
                            if ($_o['_user_id'] != $_user['_user_id']) {
                                jrUser_notify($_o['_user_id'], 0, 'jrAction', 'mention', $sub, $msg);
                            }
                        }
                    }
                }
            }
        }
    }
    return true;
}

/**
 * jrAction_save
 * @param $mode string Mode (create/update/delete/etc)
 * @param $module string Module creating action
 * @param $item_id integer Unique Item ID in module DataStore
 * @param $_data array Array of item-specific key pairs
 * @param $profile_check bool whether to create actions if admin is creating item on another users profile
 * @return bool
 */
function jrAction_save($mode, $module, $item_id, $_data = null, $profile_check = true)
{
    global $_post, $_user;
    // See if we are turned on for this module
    if (!isset($_user['quota_jrAction_allowed']) || $_user['quota_jrAction_allowed'] != 'on') {
        return true;
    }
    if (isset($_post['jraction_add_to_timeline']) && $_post['jraction_add_to_timeline'] != 'on') {
        return true;
    }
    // Make sure module is active
    if (!jrCore_module_is_active($module)) {
        return true;
    }
    // Make sure we get a valid $item_id...
    if (!jrCore_checktype($item_id, 'number_nz')) {
        return false;
    }

    // If we are an ADMIN USER that is creating something for a profile
    // that is NOT our home profile, we do not record the action.
    $key = jrUser_get_profile_home_key('_profile_id');
    if ($profile_check && jrUser_is_admin() && $_user['user_active_profile_id'] != $key) {
        return true;
    }

    // See if we have been given data straight away or need to grab it
    if (is_null($_data) || !is_array($_data)) {
        $_data = jrCore_db_get_item($module, $item_id, 'exclude_jrProfile_quota_keys');
    }

    // There are some fields we don't store
    unset($_data['user_password']);
    // Try to get rid of some fields that are not needed
    foreach ($_data as $k => $v) {
        if (strpos($k, 'quota_') === 0 ||
            strpos($k, '_item_count') ||
            $k == 'playlist_items' ||
            strpos(' ' . $k, 'pending') ||
            strpos($k, 'user_valid') === 0 ||
            strpos($k, '_image') ||
            strpos($k, 'notification') ||
            strpos($k, '_settings') ||
            strpos($k, '_payout') ||
            strpos($k, '_file_t') ||
            strpos($k, '_file_ext') ||
            strpos($k, '_bio')
        ) {
            unset($_data[$k]);
        }
    }

    // Store our action...
    $_save = array(
        'action_mode'     => $mode,
        'action_quota_id' => $_user['profile_quota_id'],
        'action_module'   => $module,
        'action_item_id'  => (int) $item_id,
        'action_data'     => json_encode($_data)
    );

    // See if items being created in this module are pending
    // If so, set pending on action as well.
    $_pnd = jrCore_get_registered_module_features('jrCore', 'pending_support');
    if ($_pnd && isset($_pnd[$module]) && isset($_user["quota_{$module}_pending"]) && intval($_user["quota_{$module}_pending"]) > 0) {
        $_save['action_pending'] = '1';
        $_save['action_pending_linked_item_module'] = $module;
        $_save['action_pending_linked_item_id'] = (int) $item_id;
    }
    $_core = array(
        '_profile_id' => $_user['user_active_profile_id']
    );
    $aid = jrCore_db_create_item('jrAction', $_save, $_core);

    // Send out our Action Created trigger
    $_args = array(
        '_user_id' => $_user['_user_id'],
        '_item_id' => $aid,
    );
    jrCore_trigger_event('jrAction', 'create', $_save, $_args);

    jrProfile_reset_cache();
    return true;
}

/**
 * {jrAction_form}
 * @param $params array Smarty function params
 * @param $smarty object Smarty Object
 * @return string
 */
function smarty_function_jrAction_form($params, $smarty)
{
    global $_conf;
    // Enabled?
    if (!jrCore_module_is_active('jrAction')) {
        return '';
    }
    // Is it allowed in this quota?
    if (!jrProfile_is_allowed_by_quota('jrAction', $smarty)) {
        return '';
    }

    $_lang = jrUser_load_lang_strings();

    // Setup options
    $_tmp = array('$("#action_text").charCount({allowed: 140, warning: 20});');
    jrCore_create_page_element('javascript_ready_function', $_tmp);

    $url = $_conf['jrCore_base_url'] . '/' . jrCore_get_module_url('jrAction') . '/create_save';
    $tkn = jrCore_form_token_create();
    $out = '<form id="action_form" method="post" action="' . $url . '">
        <input type="hidden" name="jr_html_form_token" value="' . $tkn . '">
        <textarea id="action_text" name="action_text" onkeypress="if (event && event.keyCode == 13 && this.value.length > 0){$(\'#action_submit\').attr(\'disabled\',\'disabled\').addClass(\'form_button_disabled\');$(\'#action_form\').submit()}"></textarea><br>
        <input id="action_submit" type="button" class="form_button" style="margin-top:8px;" value="' . str_replace('"', '\"', $_lang['jrAction'][5]) . '" onclick="var t=$(\'#action_text\').val();if (t.length < 1){return false;} else {$(this).attr(\'disabled\',\'disabled\').addClass(\'form_button_disabled\');$(\'#action_form\').submit()}">
        </form><span id="action_text_counter" class="action_warning">' . $_lang['jrAction'][6] . ': <span id="action_text_num">140</span></span>';
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}

/**
 * jrCore_convert_at_tags
 * Convert @ tags into links to profiles
 * @param string $text String to convert at tags in
 * @return string
 */
function smarty_modifier_jrAction_convert_hash_tags($text)
{
    global $_conf, $_user;
    $url = jrCore_get_module_url('jrAction');
    return preg_replace('/(#([_a-z0-9\-]+))/i', '<a href="' . $_conf['jrCore_base_url'] . '/' . $_user['profile_url'] . '/' . $url . '/search/%23$2"><span class="hash_link">$1</span></a>', $text);
}
