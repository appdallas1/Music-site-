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
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

//------------------------------
// create
//------------------------------
function view_jrSitemap_create($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_page_include_admin_menu();
    jrCore_page_admin_tabs('jrSitemap');
    jrCore_page_banner("Create XML Site Map");

    // Form init
    $_tmp = array(
        'submit_value'  => 'create site map',
        'cancel'        => 'referrer',
        'submit_prompt' => 'Are you sure you want to create a new XML site map? This could take some time to run, so please be patient.',
        'submit_modal'  => 'update',
        'modal_width'   => 600,
        'modal_height'  => 400,
        'modal_note'    => 'Please be patient while the site map is created'
    );
    jrCore_form_create($_tmp);

    jrCore_page_note("Please be patient while the Sitemap is generated - on large systems this could take a few minutes.<br><br>Your SiteMap will be available at: <a href=\"{$_conf['jrCore_base_url']}/sitemap.xml\" target=\"_blank\"><u>{$_conf['jrCore_base_url']}/sitemap.xml</u></a>",false);

    // Display page with form in it
    jrCore_page_display();
}

//------------------------------
// create_save
//------------------------------
function view_jrSitemap_create_save($_post,$_user,$_conf)
{
    jrUser_master_only();
    jrCore_form_validate($_post);
    jrCore_logger('INF','create XML site map started');

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
    jrCore_form_modal_notice('update','triggering modules for site map URLs');
    $_map = jrCore_trigger_event('jrCore','sitemap_site_pages',$_map);
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
            jrCore_form_modal_notice('update',"created XML Sitemap for ". count($_rt['_items']) ." profiles");
        }
        else {
            $found = false;
        }
    }
    jrCore_form_delete_session();
    jrCore_logger('INF','XML site map has been created');
    jrCore_form_modal_notice('complete','The XML Site Map has been created');
    exit;
}
