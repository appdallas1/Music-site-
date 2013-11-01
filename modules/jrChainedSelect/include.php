<?php
/**
 * Jamroom 5 jrChainedSelect module
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
 * @copyright 2013 Talldude Networks, LLC.
 * @author Paul Asher <paul [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

//****************************
// Module meta data
//****************************
function jrChainedSelect_meta()
{
    $_tmp = array(
        'name'        => 'Chained Select',
        'url'         => 'chained_select',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Manage chained_select field options and choices',
        'category'    => 'forms'
    );
    return $_tmp;
}

//****************************
// Module initialisation
//****************************
function jrChainedSelect_init()
{
    // Register our jrChainedSelect tools
    jrCore_register_module_feature('jrCore','tool_view','jrChainedSelect','manage',array('Manage','Create and delete chained_select form field options'));
    jrCore_register_module_feature('jrCore','tool_view','jrChainedSelect','export',array('Export','Export chained_select form field options to a csv file'));
    jrCore_register_module_feature('jrCore','tool_view','jrChainedSelect','import',array('Import','Import chained_select form field options from a csv file'));

    return true;
}

//****************************
// Get level options
//****************************
function jrChainedSelect_level_options()
{
    return array(2=>2,3=>3,4=>4,5=>5);
}

//****************************
// Get names
//****************************
function jrChainedSelect_names()
{
    $_out = array();
    $_s = array(
        'limit'    => 1000,
        'group_by' => 'cs_name',
        'order_by' => array('cs_name' => 'ASC'),
        'exclude_jrUser_keys' => true,
        'exclude_jrProfile_keys' => true
    );
    $_rt = jrCore_db_search_items('jrChainedSelect',$_s);
    if (isset($_rt['_items'][0]) && is_array($_rt['_items'][0])) {
        foreach ($_rt['_items'] as $rt) {
            $_out[$rt['cs_name']] = $rt['cs_name'];
        }
    }
    return $_out;
}
