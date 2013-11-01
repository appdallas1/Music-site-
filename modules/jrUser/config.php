<?php
/**
 * Jamroom 5 jrUser module
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
 * jrUser_config
 */
function jrUser_config()
{
    // Enable Signups
    $_tmp = array(
        'name'     => 'signup_on',
        'type'     => 'checkbox',
        'default'  => 'on',
        'validate' => 'onoff',
        'label'    => 'User Signups',
        'help'     => 'Check this option to allow users to signup for your site.',
        'section'  => 'signup settings'
    );
    jrCore_register_setting('jrUser',$_tmp);

    // Max Login Time
    $_tmp = array(
        'name'     => 'session_expire_min',
        'default'  => '360',
        'type'     => 'text',
        'validate' => 'number_nz',
        'required' => 'on',
        'min'      => 10,
        'max'      => 20160,
        'label'    => 'session expiration',
        'help'     => 'How many minutes of inactivity will cause a User session to be marked as expired?',
        'section'  => 'user account settings'
    );
    jrCore_register_setting('jrUser',$_tmp);

    // Auto Login
    $_als = array(
        '1' => 'Every Login (auto login disabled)',
        '2' => 'Every 14 days',
        '3' => 'Permanent (until user resets cookies)'
    );
    $_tmp = array(
        'name'     => 'autologin',
        'default'  => '2',
        'type'     => 'select',
        'options'  => $_als,
        'required' => 'on',
        'label'    => 'user auto login reset',
        'help'     => 'How often should a user have to re-enter their login credentials?',
        'section'  => 'user account settings'
    );
    jrCore_register_setting('jrUser',$_tmp);

    // Default Language
    $_tmp = array(
        'name'     => 'default_language',
        'default'  => 'en-US',
        'type'     => 'select',
        'options'  => 'jrUser_get_languages',
        'required' => 'on',
        'label'    => 'default language',
        'help'     => 'The Default language is the language that is setup for new user accounts by default.',
        'section'  => 'user account settings'
    );
    jrCore_register_setting('jrUser',$_tmp);
    return true;
}
?>
