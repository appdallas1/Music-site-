<?php
/**
 * Jamroom 5 jrMailer module
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
function jrMailer_config()
{
    // Active Email System
    $_tmp = array(
        'name'     => 'active_email_system',
        'default'  => 'jrMailer_smtp',
        'type'     => 'select',
        'options'  => 'jrCore_get_email_system_plugins',
        'validate' => 'core_string',
        'required' => 'on',
        'label'    => 'active email system',
        'help'     => 'What Email system should be the active email system?',
        'section'  => 'general email settings',
        'order'    => 1
    );
    jrCore_register_setting('jrMailer',$_tmp);

    // From Address
    $_tmp = array(
        'name'     => 'from_email',
        'label'    => 'from email address',
        'default'  => (isset($_SERVER['SERVER_ADMIN']) && strpos($_SERVER['SERVER_ADMIN'],'@')) ? $_SERVER['SERVER_ADMIN'] : 'changeme@example.com',
        'type'     => 'text',
        'validate' => 'email',
        'help'     => 'When the system sends an automated / system message, what email address should the email be sent from? Note that this should be a real email address that will be checked with an email client.', 
        'section'  => 'general email settings'
    );
    jrCore_register_setting('jrMailer',$_tmp);

    $_trs = array(
        'mail' => 'PHP mail function (default)',
        'smtp' => 'External SMTP Server (configured below)'
    );
    // Delivery Method
    $_tmp = array(
        'name'     => 'transport',
        'label'    => 'delivery method',
        'type'     => 'select',
        'options'  => $_trs,
        'default'  => 'mail',
        'help'     => 'Select the delivery method you would like to use for outbound email.',
        'section'  => 'mail delivery settings',
        'order'    => 1
    );
    jrCore_register_setting('jrMailer',$_tmp);

    // SMTP server
    $_tmp = array(
        'name'     => 'smtp_host',
        'label'    => 'SMTP host',
        'type'     => 'text',
        'validate' => 'false',
        'default'  => 'localhost.com',
        'help'     => 'If you would like to use an external SMTP Server for sending email, enter the hostname or IP address here.',
        'section'  => 'mail delivery settings',
        'order'    => 5
    );
    jrCore_register_setting('jrMailer',$_tmp);

    // SMTP port
    $_tmp = array(
        'name'     => 'smtp_port',
        'label'    => 'SMTP port number',
        'type'     => 'text',
        'validate' => 'number_nz',
        'default'  => '25',
        'help'     => 'If you have specified an SMTP host, enter the port that the SMTP server is running on.',
        'section'  => 'mail delivery settings',
        'order'    => 6
    );
    jrCore_register_setting('jrMailer',$_tmp);

    // SMTP user
    $_tmp = array(
        'name'     => 'smtp_user',
        'label'    => 'SMTP user name',
        'type'     => 'text',
        'default'  => '',
        'validate' => 'false',
        'help'     => 'If you have specified an SMTP host, enter the user name that is used to connect to the SMTP server.',
        'section'  => 'mail delivery settings',
        'order'    => 7
    );
    jrCore_register_setting('jrMailer',$_tmp);

    // SMTP pass
    $_tmp = array(
        'name'     => 'smtp_pass',
        'label'    => 'SMTP password',
        'type'     => 'text',
        'default'  => '',
        'validate' => 'false',
        'help'     => 'If you have specified an SMTP user name, enter the password that is used to connect to the SMTP server.',
        'section'  => 'mail delivery settings',
        'order'    => 8
    );
    jrCore_register_setting('jrMailer',$_tmp);

    return true;
}
?>
