<?php
/**
 * Jamroom 5 jrSitemap module
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
 * jrSitemap_meta
 */
function jrSitemap_meta()
{
    $_tmp = array(
        'name'        => 'Sitemap Generator',
        'url'         => 'sitemap',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;'. strftime('%Y'),
        'description' => 'Create and maintain an XML Sitemap used by search engines',
        'category'    => 'tools'
    );
    return $_tmp;
}

/**
 * jrSitemap_init
 */
function jrSitemap_init()
{
    // After the core has parsed the URL, we can check for a sitemap call
    jrCore_register_event_listener('jrCore','parse_url','jrSitemap_parse_url_listener');

    // Tool to manually create sitemap
    jrCore_register_module_feature('jrCore','tool_view','jrSitemap','create',array('Create Site Map','Create or Update the Sitemap'));

    // Maintain our Sitemap on a daily basis
    jrCore_register_event_listener('jrCore','daily_maintenance','jrSitemap_daily_maintenance_listener');

    // Our "map" event trigger
    jrCore_register_event_trigger('jrSitemap','sitemap_site_pages','Fired when gathering relative URLs for sitemap');

    return true;
}

//---------------------------------------------------------
// EVENT LISTENERS
//---------------------------------------------------------

/**
 * Generates an XML Sitemap
 * @param $_data array incoming data array from jrCore_save_media_file()
 * @param $_user array current user info
 * @param $_conf array Global config
 * @param $_args array additional info about the module
 * @param $event string Event Trigger name
 * @return array
 */
function jrSitemap_parse_url_listener($_data,$_user,$_conf,$_args,$event)
{
    if (isset($_data['module_url']) && $_data['module_url'] === 'sitemap.xml') {
        $out  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $out .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        // Check for created site maps in media/0/0
        $_xml = glob(APP_DIR ."/data/media/0/0/*.xml");
        if (isset($_xml) && is_array($_xml)) {
            foreach ($_xml as $sitefile) {
                $nam = basename($sitefile);
                $mod = date('c',filemtime($sitefile));
                $out .= "<sitemap><loc>{$_conf['jrCore_base_url']}/data/media/0/0/{$nam}</loc><lastmod>{$mod}</lastmod></sitemap>\n";
            }
        }
        $out .= '</sitemapindex>';
        header("Content-Type: text/xml; charset=utf-8");
        echo $out;
        exit;
    }
    return $_data;
}

/**
 * Keep sitemap.xml up to date
 * @param array $_data incoming data array from jrCore_save_media_file()
 * @param array $_user current user info
 * @param array $_conf Global config
 * @param array $_args additional info about the module
 * @param string $event Event Trigger name
 * @return array
 */
function jrSitemap_daily_maintenance_listener($_data,$_user,$_conf,$_args,$event)
{
    // Cleanup old Site map XML files
    $_xml = glob(APP_DIR ."/data/media/0/0/*.xml");
    if (isset($_xml) && is_array($_xml) && count($_xml) > 0) {
        foreach ($_xml as $old_file) {
            unlink($old_file);
        }
    }

    $_map   = array();
    $_map[] = '/';

    // Let modules know we are looking for pages
    $_map = jrCore_trigger_event('jrSitemap','sitemap_site_pages',$_map);
    jrCore_create_media_directory(0);

    // Create our first output
    $now = strftime('%Y-%m-%d');
    $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($_map as $url) {
        if (strpos($url,$_conf['jrCore_base_url']) !== 0) {
            $url = "{$_conf['jrCore_base_url']}{$url}";
        }
        $out .= "\n<url>\n<loc>{$url}</loc>\n<priority>1.0</priority>\n<changefreq>daily</changefreq>\n<lastmod>{$now}</lastmod>\n</url>";
    }
    $out .= "\n</urlset>";
    jrCore_write_to_file(APP_DIR ."/data/media/0/0/sitemap1.xml",$out);

    // Go through our profiles (1000 at a time)
    $mapid = 2;
    $start = 0;
    $found = true;
    while ($found) {
        $_src = array(
            'search' => array(
                "profile_private = 1",   // Only globally open profiles
                "_profile_id > {$start}"
            ),
            'return_keys' => array(
                '_profile_id',
                'profile_url'
            ),
            'order_by' => array(
                '_profile_id' => 'asc'
            ),
            'limit' => 1000,
            'skip_triggers'  => true,
            'ignore_pending' => true
        );
        $_rt = jrCore_db_search_items('jrProfile',$_src);
        if (isset($_rt) && isset($_rt['_items']) && is_array($_rt['_items']) && count($_rt['_items']) > 0) {

            $out = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($_rt['_items'] as $_profile) {
                $out .= "\n<url>\n<loc>{$_conf['jrCore_base_url']}/{$_profile['profile_url']}</loc>\n<priority>1.0</priority>\n<changefreq>daily</changefreq>\n<lastmod>{$now}</lastmod>\n</url>";
                $start = $_profile['_profile_id'];
            }
            $out .= "\n</urlset>";
            jrCore_write_to_file(APP_DIR ."/data/media/0/0/sitemap{$mapid}.xml",$out);
            $mapid++;
        }
        else {
            $found = false;
        }
    }
    return $_data;
}
