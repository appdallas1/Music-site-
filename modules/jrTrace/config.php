<?php
/**
 * Jamroom 5 jrTrace module
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
 * jrTrace_config
 */
function jrTrace_config()
{
    // Active Trace Events
    $_tmp = array(
        'name'     => 'active_tracers',
        'type'     => 'optionlist',
        'default'  => '',
        'options'  => 'jrTrace_get_event_tracers',
        'label'    => 'active trace events',
        'validate' => 'core_string',
        'help'     => 'Select the system events you would like to record to the trace datastore.<br><br><b>WARNING:</b> Activating all system event tracers will incur a <b>serious performance penalty</b> for your system - only enable tracing of the specific events you need to save.',
        'order'    => 1
    );
    jrCore_register_setting('jrTrace',$_tmp);

    // Trace History
    $_tmp = array(
        'name'     => 'trace_history',
        'default'  => 2,
        'type'     => 'text',
        'required' => 'on',
        'min'      => 1,
        'max'      => 60,
        'validate' => 'number_nn',
        'label'    => 'trace history length',
        'help'     => 'Trace information can take up a lot of database space - how many days of trace history do you want to maintain in the Trace DataStore?',
        'order'    => 2
    );
    jrCore_register_setting('jrTrace',$_tmp);
    return true;
}
?>
