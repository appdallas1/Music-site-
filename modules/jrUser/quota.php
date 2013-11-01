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
 * quota_config
 */
function jrUser_quota_config()
{
    // Allow Signups
    $_tmp = array(
        'name'     => 'allow_signups',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'label'    => 'allow signups',
        'help'     => 'If the &quot;Allow Signups&quot; option is <b>checked</b>, then new users signing up for your system will be able to signup directly to this Profile Quota.',
        'default'  => 'off',
        'order'    => 1
    );
    jrProfile_register_quota_setting('jrUser',$_tmp);

    // Signup Method
    $_als = array(
        'instant' => 'Instant Validation',
        'email'   => 'Email Validation'
    );
    $_tmp = array(
        'name'     => 'signup_method',
        'type'     => 'select',
        'options'  => $_als,
        'default'  => 'email',
        'required' => 'on',
        'label'    => 'signup method',
        'help'     => 'How should users signup for this Quota?<br><br><b>Instant Validation</b> - The new user account and profile are activated on signup.<br><b>Email Validation</b> - An activation email is sent on signup to activate the new account.',
        'order'    => 2
    );
    jrProfile_register_quota_setting('jrUser',$_tmp);

    // Power User
    $_tmp = array(
        'name'     => 'power_user',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'label'    => 'power user enabled',
        'help'     => 'If this option is checked, User Accounts belonging to profiles in this Quota will be Power Users that can create new profiles.',
        'default'  => 'off',
        'section'  => 'power user',
        'order'    => 10
    );
    jrProfile_register_quota_setting('jrUser',$_tmp);

    // Power User Profiles
    $_tmp = array(
        'name'     => 'power_user_max',
        'type'     => 'text',
        'validate' => 'number_nz',
        'label'    => 'max profiles',
        'help'     => 'How many profiles can a Power User in this quota create?',
        'default'  => 2,
        'section'  => 'power user',
        'order'    => 11
    );
    jrProfile_register_quota_setting('jrUser',$_tmp);

    // Power User Quotas
    $_tmp = array(
        'name'     => 'power_user_quotas',
        'type'     => 'select_multiple',
        'label'    => 'allowed quotas',
        'help'     => 'When a Power User in this Quota creates a new Profile, what Quotas can they select for their new profile?',
        'options'  => 'jrProfile_get_quotas',
        'default'  => 0,
        'section'  => 'power user',
        'order'    => 12
    );
    jrProfile_register_quota_setting('jrUser',$_tmp);

    return true;
}
?>
