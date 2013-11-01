<?php
/**
 * Jamroom 5 jrCustomForm module
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
 * Custom Form Install
 */
function jrCustomForm_install()
{
    // Create sample "contact_us" form
    $tbl = jrCore_db_table_name('jrCustomForm', 'form');
    $req = "INSERT INTO {$tbl} (form_created,form_updated,form_name,form_title,form_message,form_unique,form_login)
            VALUES (UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),'contact_us','Contact Us','Please enter the message you would like to send and we will get back to you ASAP.','off','on')";
    $fid = jrCore_db_query($req, 'INSERT_ID');
    if (isset($fid) && jrCore_checktype($fid, 'number_nz')) {
        // Create our single default field
        $_field = array(
            'name'     => 'form_content',
            'type'     => 'textarea',
            'label'    => 'Your Message',
            'help'     => 'Let us know what is on your mind',
            'validate' => 'printable',
            'required' => true
        );
        jrCore_verify_designer_form_field('jrCustomForm', 'contact_us', $_field);

        // Activate it or it won't show
        $tbl = jrCore_db_table_name('jrCore', 'form');
        $req = "UPDATE {$tbl} SET `active` = '1' WHERE `module` = 'jrCustomForm' AND `view` = 'contact_us' AND `name` = 'form_content' LIMIT 1";
        jrCore_db_query($req);

        // Redirect to form designer
        jrCore_register_module_feature('jrCore', 'designer_form', 'jrCustomForm', 'contact_us');
    }
    return true;
}

?>