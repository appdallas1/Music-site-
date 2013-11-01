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
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * meta
 */
function jrTrace_meta()
{
    $_tmp = array(
        'name'        => 'Event Tracer',
        'url'         => 'tracer',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Save registered event &quot;Traces&quot; to the Trace DataStore for listing',
        'category'    => 'listing',
        'priority'    => 251, // LOW load priority (we want other listeners to run first)
        'locked'      => false,
        'activate'    => false
    );
    return $_tmp;
}

/**
 * init
 */
function jrTrace_init()
{
    // Register our listeners
    jrCore_register_event_listener('jrCore','all_events','jrTrace_all_events_listener');
    jrCore_register_event_listener('jrCore','db_search_items','jrTrace_db_search_items_listener');

    // We provide triggers for our trace saving
    jrCore_register_event_trigger('jrTrace','trace_saved','Fired when a Trace is saved to the Trace DataStore');

    // Keep trace history clean
    jrCore_register_event_listener('jrCore','daily_maintenance','jrTrace_daily_maintenance_listener');

    return true;
}

/**
 * Listen for Traced Events
 * @param $_data array incoming data array
 * @param $_user array current user info
 * @param $_conf array Global config
 * @param $_args array additional info about the module
 * @param $event string Event Trigger name
 * @return array
 */
function jrTrace_all_events_listener($_data,$_user,$_conf,$_args,$event)
{
    // See if we are active on this events
    if (isset($_conf['jrTrace_active_tracers']) && strpos(",{$_conf['jrTrace_active_tracers']},",$event)) {
        // We want to save off SOME of the data here as $_data can be HUGE
        $_save = array(
            'trace_module' => $_args['module'],
            'trace_event'  => $event,
            'trace_data'   => array()
        );
        if (isset($_data['_item_id'])) {
            $_save['trace_item_id'] = (int) $_data['_item_id'];
        }
        foreach ($_data as $k => $v) {
            switch ($k) {
                case 'profile_id':
                case 'profile_name':
                case 'profile_url':
                case 'profile_quota_id':
                case 'user_name':
                case 'user_group':
                case 'user_language':
                    $_save['trace_data'][$k] = $v;
                    break;
                default:
                    if (strpos(' '. $k,'_title')) {
                        $_save['trace_data'][$k] = $v;
                    }
                    break;
            }
        }
        $_save['trace_data'] = json_encode($_save['trace_data']);
        $tid = jrCore_db_create_item('jrTrace',$_save);

        // Trigger our traced event
        $_args = array(
            'trace_id' => $tid,
            'module'   => $_args['module'],
            'trace'    => $_save
        );
        jrCore_trigger_event('jrTrace','trace_saved',$_data,$_args);
    }
    return $_data;
}

/**
 * Expand trace_data field back to an Array
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrTrace_db_search_items_listener($_data,$_user,$_conf,$_args,$event)
{
    if (!isset($_args['module']) || $_args['module'] != 'jrTrace' || !is_array($_data['_items'])) {
        return $_data;
    }
    foreach ($_data['_items'] as $k => $_v) {
        if (isset($_v['trace_data']) && strpos($_v['trace_data'],'{') === 0) {
            $_data['_items'][$k]['trace_data'] = json_decode($_v['trace_data'],true);
        }
    }
    return $_data;
}

/**
 * Get all registered event tracers
 * @return array
 */
function jrTrace_get_event_tracers()
{
    $_out = array();
    $_tmp = jrCore_get_registered_module_features('jrTrace','trace_event');
    foreach ($_tmp as $mod => $_traces) {
        foreach ($_traces as $name => $desc) {
            $_out["{$mod}_{$name}"] = "{$mod}_{$name} - {$desc}";
        }
    }
    ksort($_out);
    return $_out;
}

/**
 * Keep the trace DataStore clean
 * @param $_data array Array of information from trigger
 * @param $_user array Current user
 * @param $_conf array Global Config
 * @param $_args array additional parameters passed in by trigger caller
 * @param $event string Triggered Event name
 * @return array
 */
function jrTrace_daily_maintenance_listener($_data,$_user,$_conf,$_args,$event)
{
    $tmp = jrCore_get_flag('jrtrace_daily_maintenance_run');
    if (!$tmp) {
        $dif = (time() - ($_conf['jrTrace_trace_history'] * 86400));
        $tb1 = jrCore_db_table_name('jrTrace','item');
        $tb2 = jrCore_db_table_name('jrTrace','item_key');
        $cnt = 0;
        while (true) {
            // Cleanup in batches of 1,000
            $req = "SELECT `_item_id` FROM {$tb2} WHERE `key` = '_created' AND `value` < {$dif} LIMIT 1000";
            $_rt = jrCore_db_query($req,'_item_id');
            if (isset($_rt) && is_array($_rt)) {
                $req = "DELETE FROM {$tb2} WHERE `_item_id` IN(". implode(',',array_keys($_rt)) .")";
                jrCore_db_query($req);
                $req = "DELETE FROM {$tb1} WHERE `_item_id` IN(". implode(',',array_keys($_rt)) .")";
                $cnt += jrCore_db_query($req,'COUNT');
            }
            else {
                if ($cnt > 0) {
                    jrCore_logger('INF',"deleted {$cnt} trace items older than {$_conf['jrTrace_trace_history']} days");
                }
                return true;
            }
        }
        jrCore_set_flag('jrtrace_daily_maintenance_run',1);
    }
    return true;
}
