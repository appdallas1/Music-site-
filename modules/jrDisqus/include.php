<?php
/**
 * Jamroom 5 jrDisqus module
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
function jrDisqus_meta()
{
    $_tmp = array(
        'name'        => 'Disqus Comments',
        'url'         => 'disqus',
        'version'     => '1.0.0',
        'developer'   => 'The Jamroom Network, &copy;' . strftime('%Y'),
        'description' => 'Add Disqus Users Comments to item detail pages',
        'category'    => 'users'
    );
    return $_tmp;
}

/**
 * init
 */
function jrDisqus_init()
{
    return true;
}

/**
 * {jrDisqus_comments}
 * @param $params array Smarty function params
 * @param $smarty object Smarty Object
 * @return string
 */
function smarty_function_jrDisqus_comments($params, $smarty)
{
    global $_conf;
    if (!jrCore_module_is_active('jrDisqus') || !isset($_conf['jrDisqus_site_name']) || strlen($_conf['jrDisqus_site_name']) === 0) {
        return '';
    }
    // Is it allowed in this quota?
    if (!jrProfile_is_allowed_by_quota('jrDisqus', $smarty)) {
        return '';
    }

    $_rep['disqus_identifier'] = $params['disqus_identifier'];
    $out = jrCore_parse_template('embed.tpl', $_rep, 'jrDisqus');
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}

/**
 * {jrDisqus_comment_count}
 * placed once on the page in the footer allows link counts to be returned
 * @param $params array Smarty function params
 * @param $smarty object Smarty Object
 * @return string
 */
function smarty_function_jrDisqus_comment_count($params, $smarty)
{
    global $_conf;
    if (!isset($_conf['jrDisqus_site_name']) || strlen($_conf['jrDisqus_site_name']) === 0) {
        return '';
    }
    $out = jrCore_parse_template('comment_count.tpl', null, 'jrDisqus');
    if (!empty($params['assign'])) {
        $smarty->assign($params['assign'], $out);
        return '';
    }
    return $out;
}
