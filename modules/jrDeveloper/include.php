<?php
/**
 * Jamroom 5 jrDeveloper module
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
function jrDeveloper_meta()
{
    $_tmp = array(
        'name'        => 'Developer Tools',
        'url'         => 'developer',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Tools for developers working with Jamroom modules and skins',
        'category'    => 'tools'
    );
    return $_tmp;
}

/**
 * init
 */
function jrDeveloper_init()
{
    global $_conf;
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrDeveloper', "{$_conf['jrCore_base_url']}/modules/jrDeveloper/adminer.php", array('Database Admin', 'Browse your Database Tables - <b>carefully!</b>'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrDeveloper', 'clone_skin', array('Clone Skin', 'Save a copy of an existing skin to a new name'));
    jrCore_register_module_feature('jrCore', 'tool_view', 'jrDeveloper', 'package_module', array('Package Module', 'Create a Module ZIP Package from an existing module'));

    // Our default view for admins
    jrCore_register_module_feature('jrCore', 'default_admin_view', 'jrDeveloper', 'admin/tools');

    // Loader listeners
    jrCore_register_event_listener('jrCore', 'form_display', 'jrDeveloper_loader_insert_field');
    jrCore_register_event_listener('jrCore', 'db_create_item', 'jrDeveloper_loader_create_items');

    return true;
}

/**
 * jrDeveloper_loader_valid_modules
 * Returns an array of module data that the loader is able to create items for
 */
function jrDeveloper_loader_valid_modules()
{
    $_out = array(
        'jrProfile' => array(
            'name' => 'profile_name',
            'url'  => 'profile_url'
        )
    );
    return $_out;
}

//----------------------
// EVENT LISTENERS
//----------------------

/**
 * jrDeveloper_loader_insert_field
 */
function jrDeveloper_loader_insert_field($_data, $_user, $_conf, $_args, $event)
{
    // Are we using loader?
    if (jrUser_is_master() && $_conf['jrDeveloper_loader_mode'] == 'on') {
        $_tmp = explode('/', $_data['form_view']);
        $module = $_tmp[0];
        $mode = $_tmp[1];
        // Valid module?
        $_valid = jrDeveloper_loader_valid_modules();
        if (isset($_valid[$module]) && is_array($_valid[$module])) {
            // Is this a create form?
            if ($mode == 'create') {
                // Is this a DS item?
                if ($prefix = jrCore_db_get_prefix($module)) {
                    // All good - insert the count field
                    $_tmp = array(
                        'name'     => "{$prefix}_loader_cnt",
                        'label'    => "Loader Count",
                        'help'     => "How many addition items of this type are to be created by the Loader?",
                        'type'     => 'text',
                        'validate' => 'number_nn',
                        'value'    => 0,
                        'required' => true
                    );
                    jrCore_form_field_create($_tmp);
                }
            }
        }
    }
    return $_data;
}

/**
 * jrDeveloper_loader_create_items
 * $_data has all the created DS fields
 * $_post includes the 'module'_loader_cnt value
 * $_args['_item_id'] is the id of the triggering created item
 * $_args['module'] is the triggering module name
 */
function jrDeveloper_loader_create_items($_data, $_user, $_conf, $_args, $event)
{
    global $_post;

    // Are we using loader?
    if (jrUser_is_master() && $_conf['jrDeveloper_loader_mode'] == 'on') {
        // Valid module?
        $_valid = jrDeveloper_loader_valid_modules();
        if (isset($_valid[$_args['module']]) && is_array($_valid[$_args['module']])) {
            // Get prefix
            $prefix = jrCore_db_get_prefix($_args['module']);
            // Are we loading extra items?
            if (jrCore_checktype($_post["{$prefix}_loader_cnt"], 'number_nz')) {
                // Make sure this is not from self
                if (!isset($_data["{$prefix}_loader"])) {
                    // All good - let's do it
                    for ($i = 1; $i <= $_post["{$prefix}_loader_cnt"]; $i++) {
                        $j = $i + $_args['_item_id'];
                        $_tmp = array();
                        foreach ($_data as $k => $v) {
                            if (substr($k, 0, 1) != '_') {
                                $_tmp[$k] = $v;
                            }
                        }
                        $_tmp["{$prefix}_loader"] = 1;
                        $_core = array();
                        $_core['_user_id'] = $_user['_user_id'];
                        if ($_args['module'] == 'jrProfile') {
                            $_core['_profile_id'] = $j;
                            jrCore_create_media_directory($_core['_profile_id']);
                        }
                        else {
                            $_core['_profile_id'] = $_user['_profile_id'];
                        }
                        if ($_args['module'] == 'jrProfile') {
                            // This is profile - add a random image
                            $rnd = rand(1, 18);
                            $img_file = "{$_conf['jrCore_base_dir']}/modules/jrDeveloper/img/image_{$rnd}.jpg";
                            $_img = getimagesize($img_file);
                            $_tmp['profile_image_time'] = time();
                            $_tmp['profile_image_name'] = "image_{$rnd}.jpg";
                            $_tmp['profile_image_size'] = filesize($img_file);
                            $_tmp['profile_image_type'] = 'image/jpeg';
                            $_tmp['profile_image_extension'] = 'jpg';
                            $_tmp['profile_image_access'] = '1';
                            $_tmp['profile_image_width'] = $_img[0];
                            $_tmp['profile_image_height'] = $_img[1];
                            $media_dir = jrCore_get_media_directory($_core['_profile_id']);
                            $tgt = "{$media_dir}/jrProfile_{$_core['_profile_id']}_profile_image.jpg";
                            copy($img_file, $tgt);
                            $_tmp[$_valid[$_args['module']]['name']] = $_data[$_valid[$_args['module']]['name']] . ' ' . $i;
                            $_tmp[$_valid[$_args['module']]['url']] = jrCore_url_string($_tmp[$_valid[$_args['module']]['name']]);
                        }
                        jrCore_db_create_item($_args['module'], $_tmp, $_core);
                    }
                }
            }
            $tbl = jrCore_db_table_name($_args['module'], 'item_key');
            $req = "DELETE FROM {$tbl} WHERE `key` = '{$prefix}_loader' OR `key` = '{$prefix}_loader_cnt'";
            jrCore_db_query($req);
        }
    }
    return $_data;
}
