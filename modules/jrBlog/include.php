<?php
/**
 * Jamroom 5 jrBlog module
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
function jrBlog_meta() {
    $_tmp = array(
        'name'        => 'Profile Blog',
        'url'         => 'blog',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Add blogging capabilities to User profiles',
        'category'    => 'profiles'
    );
    return $_tmp;
}

/**
 * init
 */
function jrBlog_init()
{
    // Allow admin to customize our forms
    jrCore_register_module_feature('jrCore','designer_form','jrBlog','create');
    jrCore_register_module_feature('jrCore','designer_form','jrBlog','update');

    // Core support
    jrCore_register_module_feature('jrCore','quota_support','jrBlog','on');
    jrCore_register_module_feature('jrCore','pending_support','jrBlog','on');
    jrCore_register_module_feature('jrCore','max_item_support','jrBlog','on');
    jrCore_register_module_feature('jrCore','item_order_support','jrBlog','on');
    jrCore_register_module_feature('jrCore','action_support','jrBlog','create','item_action.tpl');
    jrCore_register_module_feature('jrCore','action_support','jrBlog','update','item_action.tpl');

    // remove any blog posts that are set for a future date.
    jrCore_register_event_listener('jrCore','db_get_item','jrBlog_db_get_item_listener');
    jrCore_register_event_listener('jrCore','db_search_params','jrBlog_db_search_params_listener');

    // We have fields that can be searched
    jrCore_register_module_feature('jrSearch','search_fields','jrBlog','blog_title',29);

    // Profile Stats
    jrCore_register_module_feature('jrProfile','profile_stats','jrBlog','profile_jrBlog_item_count',29);

    // When an action is shared via jrOneAll, we can provide the text of the shared item
    jrCore_register_event_listener('jrOneAll','network_share_text','jrBlog_network_share_text_listener');

    return true;
}

//---------------------------------------------------------
// BLOG EVENT LISTENERS
//---------------------------------------------------------

/**
 * Add share data to a jrOneAll network share
 * @param $_data array incoming data array from jrCore_save_media_file()
 * @param $_user array current user info
 * @param $_conf array Global config
 * @param $_args array additional info about the module
 * @param $event string Event Trigger name
 * @return array
 */
function jrBlog_network_share_text_listener($_data,$_user,$_conf,$_args,$event)
{
    // $_data:
    // [providers] => twitter
    // [user_token] => c6418e9a-b66e-4c6c-xxxx-cdea7e915d03
    // [user_id] => 1
    // [action_module] => jrBlog
    // [action_data] => (JSON array of data for item initiating action)
    $_data = json_decode($_data['action_data'],true);
    if (!isset($_data) || !is_array($_data)) {
        return false;
    }
    $_ln = jrUser_load_lang_strings($_data['user_language']);

    // We return an array:
    // 'text' => text to post (i.e. "tweet")
    // 'url'  => URL to media item,
    // 'name' => name if media item
    $url = jrCore_get_module_url('jrBlog');
    $txt = $_ln['jrBlog'][19];
    if ($_data['action_mode'] == 'update') {
        $txt = $_ln['jrBlog'][30];
    }
    $_out = array(
        'text' => "{$_conf['jrCore_base_url']}/{$_data['profile_url']} {$_data['profile_name']} {$txt}: \"{$_data['blog_title']}\" {$_conf['jrCore_base_url']}/{$_data['profile_url']}/{$url}/{$_data['_item_id']}/{$_data['blog_title_url']}",
        'link' => array(
            'url'  => "{$_conf['jrCore_base_url']}/{$_data['profile_url']}/{$url}/{$_data['_item_id']}/{$_data['blog_title_url']}",
            'name' => $_data['blog_title']
        )
    );
    // See if they included a picture with the song
    if (isset($_data['blog_image_size']) && jrCore_checktype($_data['blog_image_size'],'number_nz')) {
        $_out['picture'] = array(
            'url' => "{$_conf['jrCore_base_url']}/{$url}/image/blog_image/{$_data['_item_id']}/large"
        );
    }
    return $_out;
}

/**
 * Check publish time for a blog entry
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrBlog_db_get_item_listener($_data,$_user,$_conf,$_args,$event) {

    if (isset($_args['module']) && $_args['module'] == 'jrBlog') {
        if (isset($_data['blog_publish_date']) && $_data['blog_publish_date'] > time()) {
            // leave it in if the user is the admin or the owner of the item.
            if ($_user['_profile_id'] != $_data['_profile_id'] && !jrUser_is_admin()) {
                // unset all the blog stuff.
                $_data = array();
            }
        }
    }
    return $_data;
}

/**
 * Add blog_publish_date search criteria to blog searches
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrBlog_db_search_params_listener($_data,$_user,$_conf,$_args,$event)
{
    global $_post;
    if (isset($_args['module']) && $_args['module'] == 'jrBlog') {
        // If this is NOT an admin user or the profile owner we
        // hide blog posts that have not been published yet
        if (!jrUser_is_admin() || !isset($_post['_profile_id']) || $_post['_profile_id'] != $_user['user_active_profile_id']) {
            $_data['search'][] = 'blog_publish_date < '. time();
        }
    }
    return $_data;
}

//---------------------------------------------------------
// SMARTY FUNCTIONS
//---------------------------------------------------------

/**
 * {jrBlog_categories}
 * @param $params array Smarty function params
 * @param $smarty object Smarty Object
 * @return string
 */
function smarty_function_jrBlog_categories($params,$smarty)
{
    global $_conf;
    // Enabled?
    if (!jrCore_module_is_active('jrBlog')) {
        return '';
    }
    // Is it allowed in this quota?
    if (!jrProfile_is_allowed_by_quota('jrBlog', $smarty)) {
        return '';
    }
    // get all the categories for this users blog
    if (isset($params['profile_id']) && jrCore_checktype($params['profile_id'],'number_nz')) {
        $_sp = array(
            'search'   => array(
                "_profile_id = {$params['profile_id']}"
            ),
            'order_by' => array(
                'blog_category' => 'asc'
            ),
            'return_keys' => array('_profile_id','blog_category','blog_category_url','profile_url'),
            'exclude_jrUser_keys' => true,
            'exclude_jrProfile_quota_keys' => true,
            'limit' => 500
        );
        $_rt = jrCore_db_search_items('jrBlog',$_sp);
        if (isset($_rt['_items']) && is_array($_rt['_items'])) {
            $_ct = array();
            $url = jrCore_get_module_url('jrBlog');
            foreach ($_rt['_items'] as $_it) {
                if (!isset($_it['blog_category']) || strlen($_it['blog_category']) === 0) {
                    $_it['blog_category']     = 'default';
                    $_it['blog_category_url'] = 'default';
                }
                if (!isset($_ct["{$_it['blog_category_url']}"])) {
                    $_ct["{$_it['blog_category_url']}"] = array(
                        'url'        => "{$_conf['jrCore_base_url']}/{$_it['profile_url']}/{$url}/category/{$_it['blog_category_url']}",
                        'title'      => $_it['blog_category'],
                        'item_count' => 1
                    );
                }
                else {
                    $_ct["{$_it['blog_category_url']}"]['item_count']++;
                }
            }
            if (!empty($params['assign'])) {
                $smarty->assign($params['assign'],$_ct);
            }
        }
    }
    return '';
}
