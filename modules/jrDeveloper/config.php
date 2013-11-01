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
 * config
 */
function jrDeveloper_config()
{
    // Developer Mode
    $_tmp = array(
        'name'     => 'developer_mode',
        'default'  => 'off',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'required' => 'on',
        'label'    => 'developer mode',
        'help'     => 'Enabling the &quot;Developer Mode&quot; will change the information displayed in the &quot;Info&quot; tab for a module to include information about Module Triggers and Listeners.',
        'section'  => 'general settings'
    );
    jrCore_register_setting('jrDeveloper', $_tmp);

    // Loader Mode
    $_tmp = array(
        'name'     => 'loader_mode',
        'default'  => 'off',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'required' => 'on',
        'label'    => 'loader mode',
        'help'     => 'Enabling the &quot;Loader Mode&quot; will insert an extra field in the profile create form (for admin only) requesting the number of profiles to be created. If greater than zero, an incrementing number up to that value is appended to the profile name when it is created and a random image allocated. This is useful for populating a site with dummy data for test and development purposes.',
        'section'  => 'loader settings'
    );
    jrCore_register_setting('jrDeveloper', $_tmp);

    return true;
}