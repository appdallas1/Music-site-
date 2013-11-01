<?php
/**
 * Jamroom 5 jrCore module
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
 * config
 */
function jrCore_config()
{
    // System Name
    $_tmp = array(
        'name'     => 'system_name',
        'default'  => $_SERVER['HTTP_HOST'],
        'type'     => 'text',
        'validate' => 'not_empty',
        'required' => 'on',
        'label'    => 'system name',
        'help'     => 'This is the name of your system.',
        'section'  => 'general settings',
        'order'    => 1
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Default Cache length
    $_tmp = array(
        'name'     => 'default_cache_seconds',
        'default'  => '30',
        'type'     => 'text',
        'required' => 'on',
        'min'      => 0,
        'max'      => 86400,
        'validate' => 'number_nn',
        'label'    => 'default cache seconds',
        'help'     => 'Displaying a template will cause the output to be cached for the number of seconds entered here. Set to "0" (zero) to disable template caching.<br><br><b>Note:</b> It is <b>highly recommended</b> that you use caching - disabling caching will have significant performance penalties.',
        'section'  => 'general settings',
        'order'    => 2
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Allowed Domains
    $_tmp = array(
        'name'     => 'allowed_domains',
        'default'  => '',
        'type'     => 'textarea',
        'validate' => 'false',
        'required' => 'off',
        'label'    => 'allowed domains',
        'help'     => 'All streams and downloads via media players are restricted to the local domain by default - you can enter additional domains (one per line) that you would like to allow access.<br><br><b>Note:</b> If you would like to allow playback from any domain, enter <b>ALLOW_ALL_DOMAINS</b> as the value for this setting.',
        'section'  => 'general settings',
        'order'    => 3
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Maintenance Mode
    $_tmp = array(
        'name'     => 'maintenance_mode',
        'default'  => 'off',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'required' => 'on',
        'label'    => 'maintenance mode',
        'help'     => 'Enabling this option will place your system in Maintenance Mode.',
        'section'  => 'maintenance',
        'order'    => 10
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Maintenance Notice
    $_tmp = array(
        'name'     => 'maintenance_notice',
        'default'  => 'The System is currently undergoing system maintenance. We are working to get the system back online as soon as possible. Thank you for your patience.',
        'type'     => 'textarea',
        'validate' => 'false',
        'required' => 'off',
        'label'    => 'maintenance message',
        'help'     => 'If you have enabled &quot;Maintenance Mode&quot; you can enter a message that is shown on the login page.',
        'section'  => 'maintenance',
        'order'    => 11
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Server Time Zone
    $_tzs = array(
        'Kwajalein'              => 'GMT -12.00 - Kwajalein',
        'Pacific/Samoa'          => 'GMT -11:00 - Samoa',
        'Pacific/Honolulu'       => 'GMT -10:00 - Hawaii',
        'America/Anchorage'      => 'GMT -09:00 - Alaska',
        'America/Vancouver'      => 'GMT -08:00 - (PST) USA, Canada',
        'America/Denver'         => 'GMT -07:00 - (MST) USA, Canada',
        'America/Tegucigalpa'    => 'GMT -06:00 - (CST) USA, Canada, Mexico City',
        'America/New_York'       => 'GMT -05:00 - (EST) USA, Canada',
        'America/Caracas'        => 'GMT -04:30 - Caracas',
        'America/Halifax'        => 'GMT -04:00 - (AST) Canada',
        'America/St_Johns'       => 'GMT -03:30 - (NST) Newfoundland',
        'America/Sao_Paulo'      => 'GMT -03:00 - Brazil, Buenos Aires, Sao Paulo',
        'Atlantic/South_Georgia' => 'GMT -02:00 - South Georgia',
        'Atlantic/Azores'        => 'GMT -01:00 - Azores',
        'Europe/Dublin'          => 'GMT -00:00 - (GMT) Lisbon, London, Dublin',
        'Europe/Belgrade'        => 'GMT +01:00 - Madrid, Paris, Belgrade',
        'Europe/Minsk'           => 'GMT +02:00 - Cairo, Minsk, South Africa',
        'Asia/Kuwait'            => 'GMT +03:00 - Baghdad, Moscow, St Petersburg',
        'Asia/Tehran'            => 'GMT +03:30 - Tehran',
        'Asia/Muscat'            => 'GMT +04:00 - Abu Dhabi, Tbilisi',
        'Asia/Kabul'             => 'GMT +04:30 - Kabul',
        'Asia/Yekaterinburg'     => 'GMT +05:00 - Karachi, Lahore, Tashkent',
        'Asia/Kolkata'           => 'GMT +05:30 - (IST) Bangalore, Mumbai, New Delhi',
        'Asia/Katmandu'          => 'GMT +05:45 - Katmandu',
        'Asia/Dhaka'             => 'GMT +06:00 - Dhaka',
        'Asia/Rangoon'           => 'GMT +06:30 - Yangon, Rangoon',
        'Asia/Krasnoyarsk'       => 'GMT +07:00 - Bangkok, Jakarta',
        'Asia/Brunei'            => 'GMT +08:00 - Beijing, Hong Kong, Singapore',
        'Asia/Seoul'             => 'GMT +09:00 - Osaka, Seoul, Tokyo',
        'Australia/Darwin'       => 'GMT +09:30 - Adelaide, Darwin',
        'Australia/Canberra'     => 'GMT +10:00 - Brisbane, Canberra, Vladivostok',
        'Asia/Magadan'           => 'GMT +11:00 - Sydney, Melboune',
        'Pacific/Fiji'           => 'GMT +12:00 - Auckland, Fiji',
        'Pacific/Tongatapu'      => 'GMT +13:00 - Tongatupu'
    );
    $_tmp = array(
        'name'     => 'system_timezone',
        'default'  => 'Europe/Dublin',
        'type'     => 'select',
        'options'  => $_tzs,
        'required' => 'on',
        'label'    => 'system time zone',
        'help'     => 'Setting the System Time Zone will adjust all of the output Date and Time stamps to the specified time zone, regardless of where your actual server may be physically located.',
        'section'  => 'date and time settings',
        'order'    => 5
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Hour Format
    $_hrs = array(
        '%I:%M:%S%p' => '12 hour format (with am/pm)',
        '%T'         => '24 hour format'
    );
    $_tmp = array(
        'name'     => 'hour_format',
        'default'  => '%I:%M:%S%p',
        'type'     => 'select',
        'options'  => $_hrs,
        'required' => 'on',
        'label'    => 'hour format',
        'help'     => 'This option allows you to define how Hours of the Day will be displayed.',
        'section'  => 'date and time settings',
        'order'    => 7
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Date Format
    $_edf = array(
        '%D'       => 'mm/dd/yy',
        '%d/%m/%y' => 'dd/mm/yy',
        '%d %b %Y' => 'dd mmm yyyy'
    );
    $_tmp = array(
        'name'     => 'date_format',
        'default'  => '%D',
        'type'     => 'select',
        'options'  => $_edf,
        'required' => 'on',
        'label'    => 'date format',
        'help'     => 'Select the Date Format to use for dates.',
        'section'  => 'date and time settings',
        'order'    => 6
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Internal settings

    // Active Skin
    $_tmp = array(
        'name'     => 'active_skin',
        'default'  => 'jrElastic',
        'type'     => 'hidden',
        'validate' => 'not_empty',
        'required' => 'on',
        'label'    => 'active skin',
        'help'     => 'this hidden field keeps track of the Active Skin.',
    );
    jrCore_register_setting('jrCore', $_tmp);

    // Last Daily Run (hidden)
    $_tmp = array(
        'name'     => 'last_daily_maint_run',
        'default'  => '0',
        'type'     => 'hidden',
        'required' => 'on',
        'min'      => 20120801,
        'max'      => 20991231,
        'validate' => 'number_nn',
        'label'    => 'last daily maintenance run',
        'help'     => 'this hidden field keeps track of the last time the daily maintenance was run - do not modify by hand.'
    );
    jrCore_register_setting('jrCore', $_tmp);
    return true;
}
