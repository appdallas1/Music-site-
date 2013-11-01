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
 * @package Page Elements
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * jrCore_notice
 * Shows a notice message and is used when we are NOT sure if the
 *  UI is even working.  Will show a plain text error message
 *
 * @param string $type Error level (CRI, MAJ, etc.)
 * @param string $message Error Message
 */
function jrCore_notice($type, $message)
{
    if (jrCore_is_ajax_request()) {
        $_out = array('notices' => array());
        $_out['notices'][] = array('type' => 'error', 'text' => $message);
        echo json_encode($_out);
        exit;
    }
    echo "{$type}: {$message}";
    exit;
}

/**
 * jrCore_notice_page - show a success/warning/error notice page and Exit
 *
 * @param string $notice_type Notice Type (success/warning/notice/error)
 * @param string $notice_text Notice Text
 * @param string $cancel_url URL to link to Cancel Button
 * @param string $cancel_text Text for Cancel Button
 * @param bool $clean_output If true, Notice Text is run through htmlspecialchars()
 * @param bool $include_header If true, header/footer is included in output
 * @return null
 */
function jrCore_notice_page($notice_type, $notice_text, $cancel_url = false, $cancel_text = false, $clean_output = true, $include_header = true)
{
    global $_post;
    if (isset($notice_text) && jrCore_checktype($notice_text, 'number_nz')) {
        $_lang = jrUser_load_lang_strings();
        if (isset($_lang["{$_post['module']}"][$notice_text])) {
            $notice_text = $_lang["{$_post['module']}"][$notice_text];
        }
    }
    jrCore_page_title($notice_type);
    jrCore_page_notice($notice_type, $notice_text, $clean_output);

    if (!$include_header) {
        jrCore_page_set_no_header_or_footer();
    }
    if ($cancel_url) {
        jrCore_page_cancel_button($cancel_url, $cancel_text);
    }
    jrCore_page_display();
    exit;
}

/**
 * jrCore_create_page_element - add a page element to the _JR_VIEW_ELEMENTS global
 * for processing at view time.
 *
 * @param string $section section string section to add to: "meta", "javascript", "css", "page", "footer"
 * @param array $_params Element information as an array to pass to the element's template
 *
 * @return bool
 */
function jrCore_create_page_element($section, $_params)
{
    global $_post, $_mods, $_conf;
    $_tmp = jrCore_get_flag('jrcore_page_elements');
    if (!$_tmp) {
        $_tmp = array();

        //set system meta tags:
        $_tmp['meta']['generator'] = "{$_mods['jrCore']['module_version']}/{$_conf['jrCore_active_skin']}";
    }
    switch ($section) {
        case 'meta':
            // Meta data is simple key value pairs.
            if (!isset($_tmp[$section])) {
                $_tmp[$section] = array();
            }
            foreach ($_params as $key => $value) {
                $_tmp[$section][$key] = $value;
            }
            break;
        case 'css_embed':
        case 'javascript_embed':
        case 'javascript_ready_function':
        case 'javascript_footer_function':
            if (!isset($_tmp[$section])) {
                $_tmp[$section] = '';
            }
            foreach ($_params as $entry) {
                $_tmp[$section] .= "{$entry}\n";
            }
            break;
        case 'javascript_footer_href':
        case 'javascript_href':
        case 'css_href':
            if (!isset($_tmp[$section])) {
                $_tmp[$section] = array();
            }
            foreach ($_params as $k => $prm) {
                if ($k === 'source' && !strpos($prm, '?')) {
                    $_params[$k] = "{$prm}?v={$_mods["{$_post['module']}"]['module_version']}";
                }
            }
            $_tmp[$section][] = $_params;
            break;

        // BELOW USED INTERNALLY - do not use in view controllers.
        case 'form_begin':
            // Only one form per page
            $_tmp['form_begin'] = $_params['form_html'];
            // We also add in to regular page elements so the item templates
            // will be rendered in the correct place
            $_tmp['page'][] = $_params;
            break;
        case 'form_end':
            $_tmp[$section] = $_params['form_html'];
            break;
        case 'form_hidden':
            if (!isset($_tmp[$section])) {
                $_tmp[$section] = array();
            }
            $_tmp[$section][] = $_params['form_html'];
            break;
        case 'form_modal':
            $_tmp['form_modal'] = $_params;
            break;

        // "page" is default
        default:
            if (!isset($_params['type']{0})) {
                jrCore_logger('CRI', "jrCore_create_page_element: required element type not received - verify usage");
                return false;
            }
            if (!isset($_tmp[$section])) {
                $_tmp[$section] = array();
            }
            $_tmp[$section][] = $_params;
            break;
    }
    jrCore_set_flag('jrcore_page_elements', $_tmp);
    return true;
}

/**
 * jrCore_hilight_string
 *
 * @param string $string String to be hilighted
 * @param string $search Sub-String within String to be hilighted
 * @return string
 */
function jrCore_hilight_string($string, $search)
{
    return str_ireplace($search, '<span class="page_search_highlight">' . strip_tags($search) . '</span>', $string);
}

/**
 * jrCore_page_title - set HTML page title
 *
 * @param string $title Title of page
 * @return bool
 */
function jrCore_page_title($title)
{
    jrCore_set_flag('jrcore_html_page_title', strip_tags($title));
    return true;
}

/**
 * jrCore_page_banner_item_jumper
 *
 * @param string $module Module Name
 * @param string $field Field to link to ID
 * @param array $search Search parameters for jrCore_db_search_items
 * @param string $create Create View for module
 * @param string $update Update View for module
 * @return string
 */
function jrCore_page_banner_item_jumper($module, $field, $search, $create, $update)
{
    global $_conf, $_post;
    if (!isset($search) || !is_array($search)) {
        return false;
    }
    $_sc = array(
        'search'         => $search,
        'group_by'       => "_item_id",
        'order_by'       => array(
            $field => 'ASC'
        ),
        'limit'          => 250,
        'skip_triggers'  => true,
        'privacy_check'  => false,
        'ignore_pending' => true
    );
    $c_url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$create}";
    $u_url = "{$_conf['jrCore_base_url']}/{$_post['module_url']}/{$update}/id=";
    $htm = '<select name="item_id" class="form_select form_select_item_jumper" onchange="var iid=this.options[this.selectedIndex].value;if(iid == \'create\'){self.location=\'' . $c_url . '\'} else {self.location=\'' . $u_url . '\'+ iid}">' . "\n";
    if (isset($create) && strlen($create) > 0) {
        $_lang = jrUser_load_lang_strings();
        $htm .= '<option value="create"> ' . $_lang['jrCore'][50] . '</option>' . "\n";
    }
    $_rt = jrCore_db_search_items($module, $_sc);
    if (isset($_rt) && isset($_rt['_items']) && is_array($_rt['_items']) && count($_rt['_items']) > 0) {
        $_opts = array();
        foreach ($_rt['_items'] as $_v) {
            if ($module == 'jrProfile') {
                $_v['_item_id'] = $_v['_profile_id'];
            }
            $_opts["{$_v['_item_id']}"] = $_v[$field];
        }
        foreach ($_opts as $item_id => $display) {
            if (isset($_post['id']) && $item_id == $_post['id']) {
                $htm .= '<option value="' . $item_id . '" selected="selected"> ' . $display . '</option>' . "\n";
            }
            else {
                $htm .= '<option value="' . $item_id . '"> ' . $display . '</option>' . "\n";
            }
        }
        unset($_rt, $_opts);
    }
    $htm .= '</select>';
    return $htm;
}

/**
 * jrCore_page_banner - banner for top of page
 *
 * @param string $title Title of section
 * @param string $subtitle Subtitle text for section
 * @param string $icon Icon image
 * @return bool
 */
function jrCore_page_banner($title, $subtitle = null, $icon = null)
{
    global $_conf, $_post;
    if (is_null($icon)) {
        $_tmp = jrCore_get_registered_module_features('jrCore', 'module_icons');
        if (isset($_tmp["{$_conf['jrCore_active_skin']}"])) {
            if ($_tmp["{$_conf['jrCore_active_skin']}"]['show'] == '1') {
                $icon = "{$_conf['jrCore_base_url']}/modules/{$_post['module']}/icon.png";
            }
            elseif ($_tmp["{$_conf['jrCore_active_skin']}"]['show'] == 'custom') {
                $icon = "{$_conf['jrCore_base_url']}/skins/{$_conf['jrCore_active_skin']}/img/{$_post['module']}_icon.png";
            }
            else {
                $icon = false;
            }
        }
        else {
            $icon = "{$_conf['jrCore_base_url']}/modules/{$_post['module']}/icon.png";
        }
    }
    if ((isset($title) && jrCore_checktype($title, 'number_nz')) || (isset($subtitle) && jrCore_checktype($subtitle, 'number_nz'))) {
        $_lang = jrUser_load_lang_strings();
        if (is_numeric($title) && isset($_lang["{$_post['module']}"][$title])) {
            $title = $_lang["{$_post['module']}"][$title];
        }
        if (is_numeric($subtitle) && isset($_lang["{$_post['module']}"][$subtitle])) {
            $subtitle = $_lang["{$_post['module']}"][$subtitle];
        }
    }
    // If this is a Master Admin, they can customize the form they are viewing
    // If it has been registered as a Form Designer form
    if (jrUser_is_master()) {
        $_tmp = jrCore_get_registered_module_features('jrCore', 'designer_form');
        if (isset($_tmp) && is_array($_tmp) && isset($_tmp["{$_post['module']}"]["{$_post['option']}"])) {
            $subtitle .= '&nbsp;' . jrCore_page_button('fd', 'form designer', "window.location='{$_conf['jrCore_base_url']}/{$_post['module_url']}/form_designer/m={$_post['module']}/v={$_post['option']}'");
        }
    }

    $_tmp = array(
        'type'     => 'page_banner',
        'title'    => $title,
        'subtitle' => $subtitle,
        'icon_url' => $icon,
        'module'   => 'jrCore',
        'template' => 'page_banner.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    // Set our page title too
    jrCore_page_title($title);
    return true;
}

/**
 * jrCore_page_section_header - page divider/section
 *
 * @param string $title Title of section
 * @return bool
 */
function jrCore_page_section_header($title)
{
    $_tmp = array(
        'type'     => 'page_section_header',
        'title'    => $title,
        'module'   => 'jrCore',
        'template' => 'page_section_header.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_notice - show a success/warning/error notice on a page
 *
 * @param string $notice_type Notice Type (success/warning/notice/error)
 * @param string $notice_text Notice Text
 * @param bool $clean_output If true, Notice Text is run through htmlspecialchars()
 * @return bool
 */
function jrCore_page_notice($notice_type, $notice_text, $clean_output = true)
{
    $_lang = jrUser_load_lang_strings();
    if ($clean_output) {
        $notice_text = nl2br(htmlspecialchars($notice_text));
    }
    // Get our lang string
    switch ($notice_type) {
        case 'notice':
            $string = (isset($_lang['jrCore'][22])) ? $_lang['jrCore'][22] : 'notice';
            break;
        case 'warning':
            $string = (isset($_lang['jrCore'][23])) ? $_lang['jrCore'][23] : 'warning';
            break;
        case 'error':
            $string = (isset($_lang['jrCore'][24])) ? $_lang['jrCore'][24] : 'error';
            break;
        case 'success':
            $string = (isset($_lang['jrCore'][25])) ? $_lang['jrCore'][25] : 'success';
            break;
        default:
            $string = $notice_type;
            break;
    }
    $_tmp = array(
        'type'         => 'page_notice',
        'notice_type'  => $notice_type,
        'notice_label' => $string,
        'notice_text'  => $notice_text,
        'module'       => 'jrCore',
        'template'     => 'page_notice.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_link_cell
 * Creates an entry in a form with label and URL in body
 *
 * @param string $label Text for label title
 * @param string $url URL to link to
 * @param string $sublabel Sub label for label
 * @return bool
 */
function jrCore_page_link_cell($label, $url, $sublabel = null)
{
    global $_post;
    $mod = $_post['module'];
    $_lang = jrUser_load_lang_strings();
    if (isset($label) && jrCore_checktype($label, 'number_nz') && isset($_lang[$mod][$label])) {
        $label = $_lang[$mod][$label];
    }
    if (isset($sublabel) && jrCore_checktype($sublabel, 'number_nz') && isset($_lang[$mod][$sublabel])) {
        $sublabel = $_lang[$mod][$sublabel];
    }
    $_tmp = array(
        'type'     => 'page_link_cell',
        'label'    => $label,
        'sublabel' => (is_null($sublabel)) ? false : $sublabel,
        'url'      => $url,
        'module'   => 'jrCore',
        'template' => 'page_link_cell.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_custom - embed html into a page
 *
 * @param string $html Text to embed into page
 * @param string $label Label for Custom HTML
 * @param string $sublabel Label for Custom HTML
 * @return bool
 */
function jrCore_page_custom($html, $label = null, $sublabel = null)
{
    global $_post;

    // Expand language strings
    $_lang = jrUser_load_lang_strings();

    $_tmp = array(
        'type'     => 'page_custom',
        'html'     => $html,
        'label'    => (isset($_lang["{$_post['module']}"][$label])) ? $_lang["{$_post['module']}"][$label] : $label,
        'sublabel' => (isset($_lang["{$_post['module']}"][$sublabel])) ? $_lang["{$_post['module']}"][$sublabel] : $sublabel,
        'module'   => 'jrCore',
        'template' => 'page_custom.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_html
 * embeds RAW HTML into the page (no enclosure)
 *
 * @param string $html Text to embed into page
 * @return bool
 */
function jrCore_page_html($html)
{
    $_tmp = array(
        'type'   => 'page_html',
        'html'   => $html,
        'module' => 'jrCore'
    );
    // NOTE: no template needed for this
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_divider - create a section divider on a page
 * @return bool
 */
function jrCore_page_divider()
{
    $_tmp = array(
        'type'     => 'page_divider',
        'module'   => 'jrCore',
        'template' => 'page_divider.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_note
 *
 * @param string $html HTML to show in Note
 * @param string $class CSS Class for Note background
 *
 * @return bool
 */
function jrCore_page_note($html, $class = 'notice')
{
    $_tmp = array(
        'type'     => 'page_note',
        'html'     => $html,
        'class'    => $class,
        'module'   => 'jrCore',
        'template' => 'page_note.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_template
 *
 * @param string $template Template to embed in page (located in Module/templates)
 * @return bool
 */
function jrCore_page_template($template)
{
    $_tmp = array(
        'type'     => 'page_template',
        'file'     => $template,
        'module'   => 'jrCore',
        'template' => 'page_template.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_tab_bar
 *
 * @param array $_tabs Array of tabs to create on page
 *
 * @return bool
 */
function jrCore_page_tab_bar($_tabs)
{
    if (isset($_tabs) && is_array($_tabs) && count($_tabs) > 0) {
        $tab_n = count($_tabs);
        $width = round(100 / $tab_n);
        $i = 1;
        foreach ($_tabs as $k => $_cell) {
            $_tabs[$k]['id'] = 't' . $k;
            $_tabs[$k]['width'] = $width;
            $_tabs[$k]['class'] = 'page_tab';
            // Check for positioning
            if ($i == 1) {
                $_tabs[$k]['class'] .= ' page_tab_first';
            }
            elseif ($i == $tab_n) {
                $_tabs[$k]['class'] .= ' page_tab_last';
            }
            $i++;
        }
    }
    $_tmp = array(
        'type'     => 'page_tab_bar',
        'tabs'     => $_tabs,
        'module'   => 'jrCore',
        'template' => 'page_tab_bar.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_search
 * @param string $label Element Label
 * @param string $action Action URL for search form
 * @param string $value default value for search field
 * @param bool $show_help Show Help button true/false
 * @return bool
 */
function jrCore_page_search($label, $action, $value = null, $show_help = true)
{
    $_lng = jrCore_get_flag('jr_lang');
    $sbtn = (isset($_lng['jrCore'][8])) ? $_lng['jrCore'][8] : 'search';
    $rbtn = (isset($_lng['jrCore'][29])) ? $_lng['jrCore'][29] : 'reset';
    $html = '<input type="text" name="search_string" id="sstr" class="form_text form_text_search" value="' . $value . '" onkeypress="if (event && event.keyCode == 13 && this.value.length > 0) {var s=$(\'#sstr\').val();window.location=\'' . $action . '/search_string=\'+ encodeURIComponent(s);return false}">&nbsp;<input type="button" value="' . jrCore_str_to_lower($sbtn) . '" class="form_button" onclick="var s=$(\'#sstr\').val();window.location=\'' . $action . '/search_string=\'+ encodeURIComponent(s);return false">&nbsp;<input type="button" value="' . jrCore_str_to_lower($rbtn) . '" class="form_button" onclick="window.location=\'' . $action . '\'">';
    $_tmp = array(
        'type'      => 'page_search',
        'html'      => $html,
        'label'     => $label,
        'action'    => $action,
        'show_help' => ($show_help !== false) ? 1 : 0,
        'value'     => (is_null($value) || $value === false) ? false : $value,
        'module'    => 'jrCore',
        'template'  => 'page_search.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_tool_entry
 * @param string $url Tool URL
 * @param string $label Page element Label
 * @param string $description Page element Description
 * @param string $onclick Javascript onclick content
 * @param string $target Browser anchor target
 * @return bool
 */
function jrCore_page_tool_entry($url, $label, $description, $onclick = null, $target = '_self')
{
    $_tmp = array(
        'type'        => 'page_tool_entry',
        'label'       => $label,
        'label_url'   => $url,
        'description' => $description,
        'onclick'     => (is_null($onclick) || $onclick === false) ? false : $onclick,
        'target'      => $target,
        'module'      => 'jrCore',
        'template'    => 'page_tool_entry.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_table_header
 * @param array $_cells Array containing header row cells
 * @param string $class CSS Class for row
 * @return bool
 */
function jrCore_page_table_header($_cells, $class = null)
{
    jrCore_delete_flag('jr_html_page_table_row_num');
    jrCore_delete_flag('jr_html_page_table_header_colspan');
    $cls = '';
    if (!is_null($class) && strlen($class) > 0) {
        $cls = " {$class}";
    }
    $_tmp = array(
        'type'     => 'page_table_header',
        'cells'    => $_cells,
        'class'    => $cls,
        'module'   => 'jrCore',
        'template' => 'page_table_header.tpl'
    );
    $uniq = jrCore_get_flag('jr_html_page_table_header_colspan');
    if (!$uniq) {
        $uniq = count($_cells);
        jrCore_set_flag('jr_html_page_table_header_colspan', $uniq);
    }
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_table_row
 * @param array $_cells Array containing row cells
 * @param string $class CSS Class for row
 * @return bool
 */
function jrCore_page_table_row($_cells, $class = null)
{
    $rownum = jrCore_get_flag('jr_html_page_table_row_num');
    if (!$rownum) {
        $rownum = 0;
    }
    $colspan = jrCore_get_flag('jr_html_page_table_header_colspan');
    $col_cnt = count($_cells);
    ksort($_cells, SORT_NUMERIC);
    if (isset($colspan) && $colspan > $col_cnt) {
        // Adjust our last row in our cells to span the entire width
        $_tmp = array_pop($_cells);
        $_tmp['colspan'] = ' colspan="' . $colspan . '"';
        if ($col_cnt == 1) {
            $_cells = array($_tmp);
        }
        else {
            $_cells[] = $_tmp;
        }
    }
    foreach ($_cells as $k => $v) {
        if (!isset($v['colspan'])) {
            $_cells[$k]['colspan'] = '';
        }
    }
    $cls = '';
    if (!is_null($class) && strlen($class) > 0) {
        $cls = " {$class}";
    }
    $_tmp = array(
        'type'     => 'page_table_row',
        'cells'    => $_cells,
        'cellnum'  => $col_cnt,
        'class'    => $cls,
        'rownum'   => ++$rownum,
        'module'   => 'jrCore',
        'template' => 'page_table_row.tpl'
    );
    jrCore_set_flag('jr_html_page_table_row_num', $rownum);
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_table_pager
 *
 * @param array $_page page array page elements for pager including:
 *        'prev_page_num' => previous page number
 *        'this_page_num' => current page number
 *        'next_page_num' => next page number
 *        'total_pages'   => total number of pages without LIMIT clause
 * @param array $_xtra Array containing information about current data set (as returned from dbPagedQuery())
 * @return bool
 */
function jrCore_page_table_pager($_page, $_xtra = null)
{
    global $_conf, $_post;
    // We have to strip the page number (p) as well as any
    // other _xtra args we get so we don't duplicate them
    if (isset($_xtra) && is_array($_xtra)) {
        $_strip = $_xtra;
        $_strip['p'] = 1;
    }
    else {
        $_strip = array('p' => 1);
    }
    $this_page_url = rtrim("{$_conf['jrCore_base_url']}/" . ltrim(jrCore_strip_url_params($_post['_uri'], array_keys($_strip)), '/'), '/');
    $this_page_num = $_page['info']['this_page'];

    // If we have $_xtra, it means we need to add additional url vars
    if (isset($_xtra) && is_array($_xtra)) {
        foreach ($_xtra as $k => $v) {
            $this_page_url .= "/{$k}=" . urlencode($v);
        }
    }

    // We only show the pager when we have more than 1 page
    if (jrCore_checktype($_page['info']['total_pages'], 'number_nz') && intval($_page['info']['total_pages']) > 1) {
        $prev_page_url = '';
        if (jrCore_checktype($_page['info']['prev_page'], 'number_nz')) {
            $prev_page_url = "{$this_page_url}/p={$_page['info']['prev_page']}";
        }
        $next_page_url = '';
        if (jrCore_checktype($_page['info']['next_page'], 'number_nz')) {
            $next_page_url = "{$this_page_url}/p={$_page['info']['next_page']}";
        }

        // if we have less than 4 or more than 1000 pages, show simple pager
        if ($_page['info']['total_pages'] < 4 || $_page['info']['total_pages'] > 2500) {
            $page_jumper = $this_page_num;
        }
        // Page Jumper
        else {
            $page_jumper = '<select name="p" class="page-table-jumper" onchange="var p=this.options[this.selectedIndex].value;window.location=\'' . $this_page_url . '/p=\'+ p;">' . "\n";
            $i = 1;
            while ($i <= $_page['info']['total_pages']) {
                if ($i == $_page['info']['this_page']) {
                    $page_jumper .= '<option value="' . $i . '" selected="selected"> ' . $i . '</option>' . "\n";
                }
                else {
                    $page_jumper .= '<option value="' . $i . '"> ' . $i . '</option>' . "\n";
                }
                $i++;
            }
            $page_jumper .= '</select>';
        }
        $_tmp = array(
            'type'          => 'page_table_pager',
            'prev_page_url' => $prev_page_url,
            'this_page_url' => $this_page_url,
            'next_page_url' => $next_page_url,
            'prev_page_num' => $_page['info']['prev_page'],
            'this_page_num' => $this_page_num,
            'next_page_num' => $_page['info']['next_page'],
            'total_pages'   => $_page['info']['total_pages'],
            'page_jumper'   => $page_jumper,
            'colspan'       => jrCore_get_flag('jr_html_page_table_header_colspan'),
            'module'        => 'jrCore',
            'template'      => 'page_table_pager.tpl'
        );
        jrCore_create_page_element('page', $_tmp);
    }
    jrCore_delete_flag('jr_html_page_table_header_colspan');
    return true;
}

/**
 * jrCore_page_table_footer
 */
function jrCore_page_table_footer()
{
    $_tmp = array(
        'type'     => 'page_table_footer',
        'module'   => 'jrCore',
        'template' => 'page_table_footer.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_cancel_button
 * @param string $cancel_url URL to redirect browser to when cancel button is clicked
 * @param string $cancel_text button text value for cancel button
 * @return bool
 */
function jrCore_page_cancel_button($cancel_url, $cancel_text = null)
{
    global $_post;
    switch ($cancel_url) {
        case 'referrer':
            $cancel_url = "history.back();";
            break;
        case 'modal_close':
            $cancel_url = '$.modal.close();';
            break;
        default:
            $cancel_url = "window.location='{$cancel_url}'";
            break;
    }
    if (is_null($cancel_text) || $cancel_text === false) {
        $_lang = jrCore_get_flag('jr_lang');
        $cancel_text = (isset($_lang['jrCore'][2])) ? $_lang['jrCore'][2] : 'cancel';
    }
    elseif (isset($cancel_text) && jrCore_checktype($cancel_text, 'number_nz')) {
        $_lang = jrCore_get_flag('jr_lang');
        if (isset($_lang["{$_post['module']}"][$cancel_text])) {
            $cancel_text = $_lang["{$_post['module']}"][$cancel_text];
        }
    }
    $html = '<input type="button" class="form_button" value="' . jrCore_str_to_lower($cancel_text) . '" onclick="' . $cancel_url . '">';
    $_tmp = array(
        'type'     => 'page_cancel_button',
        'html'     => $html,
        'module'   => 'jrCore',
        'template' => 'page_cancel_button.tpl'
    );
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_close_button
 */
function jrCore_page_close_button()
{
    $_lng = jrCore_get_flag('jr_lang');
    $cbtn = (isset($_lng['jrCore'][28])) ? $_lng['jrCore'][28] : 'close';
    $html = '<input type="button" class="form_button" value="' . jrCore_str_to_lower($cbtn) . '" onclick="self.close();">';
    $_tmp = array(
        'type'     => 'page_close_button',
        'html'     => $html,
        'module'   => 'jrCore',
        'template' => 'page_close_button.tpl'
    );
    // NOTE: template not needed here
    jrCore_create_page_element('page', $_tmp);
    return true;
}

/**
 * jrCore_page_set_no_header_or_footer
 * @return bool
 */
function jrCore_page_set_no_header_or_footer()
{
    return jrCore_set_flag('jrcore_page_no_header_or_footer', true);
}

/**
 * jrCore_page_set_meta_header_only
 * @return bool
 */
function jrCore_page_set_meta_header_only()
{
    return jrCore_set_flag('jrcore_page_meta_header_only', true);
}

/**
 * jrCore_page_include_admin_menu
 * @return bool
 */
function jrCore_page_include_admin_menu()
{
    return jrCore_set_flag('jrcore_page_include_admin_menu', true);
}

/**
 * jrCore_get_module_index
 *
 * @param string $module module string module name
 * @return string
 */
function jrCore_get_module_index($module)
{
    // If our module is NOT active, show info
    if (!jrCore_module_is_active($module)) {
        return 'admin/info';
    }

    // We need to go through each module and get it's default page
    $_df = jrCore_get_registered_module_features('jrCore', 'default_admin_view');

    if (isset($_df[$module]) && is_array($_df[$module])) {
        $_tmp = array_keys($_df[$module]);
        return reset($_tmp);
    }

    if (is_file(APP_DIR . "/modules/{$module}/config.php")) {
        return 'admin/global';
    }

    if (is_file(APP_DIR . "/modules/{$module}/quota.php")) {
        return 'admin/quota';
    }

    // Get registered tool views
    $_tool = jrCore_get_registered_module_features('jrCore', 'tool_view');
    if (isset($_tool[$module])) {
        return 'admin/tools';
    }

    $_lang = jrUser_load_lang_strings();
    if (isset($_lang[$module])) {
        return 'admin/language';
    }

    // all modules have an info panel
    return 'admin/info';
}

/**
 * jrCore_page_dashboard_tabs
 * @param string $active active string active tab can be one of: global,quota,tools,language,templates,info
 * @return bool
 */
function jrCore_page_dashboard_tabs($active = 'online')
{
    // Our Tabs for the top of the dashboard view
    global $_conf;
    $_tabs = array();

    $murl = jrCore_get_module_url('jrCore');
    $_tabs['bigview'] = array(
        'label' => 'dashboard',
        'url'   => "{$_conf['jrCore_base_url']}/{$murl}/dashboard/bigview"
    );
    $_tabs['online'] = array(
        'label' => 'users online',
        'url'   => "{$_conf['jrCore_base_url']}/{$murl}/dashboard/online"
    );
    $_tabs['pending'] = array(
        'label' => 'pending items',
        'url'   => "{$_conf['jrCore_base_url']}/{$murl}/dashboard/pending"
    );
    $_tabs['activity'] = array(
        'label' => 'activity log',
        'url'   => "{$_conf['jrCore_base_url']}/{$murl}/dashboard/activity"
    );
    $murl = jrCore_get_module_url('jrUser');
    $_tabs['browser'] = array(
        'label' => 'data browser',
        'url'   => "{$_conf['jrCore_base_url']}/{$murl}/dashboard/browser"
    );
    $_tabs[$active]['active'] = true;
    jrCore_set_flag('jrcore_dashboard_active', 1);
    jrCore_page_tab_bar($_tabs);
    return true;
}

/**
 * jrCore_page_admin_tabs
 *
 * @param string $module module string module name
 * @param string $active active string active tab can be one of: global,quota,tools,language,templates,info
 */
function jrCore_page_admin_tabs($module, $active = 'tools')
{
    global $_conf;
    $_lang = jrUser_load_lang_strings();

    // Get registered tool views
    $_tools = jrCore_get_registered_module_features('jrCore', 'tool_view');
    $_quota = jrCore_get_registered_module_features('jrCore', 'quota_support');

    // Our current module url
    $url = jrCore_get_module_url($module);

    // Our admin tabs for the top of the view
    $_tabs = array();
    if (is_file(APP_DIR . "/modules/{$module}/config.php")) {
        $_tabs['global'] = array(
            'label' => 'global config',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/global"
        );
    }
    if (isset($_quota[$module]) || is_file(APP_DIR . "/modules/{$module}/quota.php")) {
        $_tabs['quota'] = array(
            'label' => 'quota config',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/quota"
        );
    }
    if (isset($_tools[$module]) || jrCore_db_get_prefix($module)) {
        $_tabs['tools'] = array(
            'label' => 'tools',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/tools"
        );
    }
    else {
        // We can't set tools for out default here as there is no tools...
        if ($active == 'tools') {
            $active = 'info';
        }
    }
    if (isset($_lang[$module])) {
        $_tabs['language'] = array(
            'label' => 'language',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/language"
        );
    }
    if (is_dir(APP_DIR . "/modules/{$module}/img")) {
        $_tabs['images'] = array(
            'label' => 'images',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/images"
        );
    }
    if (is_dir(APP_DIR . "/modules/{$module}/templates")) {
        $_tabs['templates'] = array(
            'label' => 'templates',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/templates"
        );
    }
    $_tabs['info'] = array(
        'label' => 'info',
        'url'   => "{$_conf['jrCore_base_url']}/{$url}/admin/info"
    );

    // Check for additional tabs registered by the module
    $_tmp = jrCore_get_registered_module_features('jrCore', 'admin_tab');
    $_tmp = (isset($_tmp[$module])) ? $_tmp[$module] : false;
    if ($_tmp) {
        $_tab = array();
        $murl = jrCore_get_module_url($module);
        foreach ($_tmp as $view => $label) {
            // There are some views we cannot set
            switch ($view) {
                case 'global':
                case 'quota':
                case 'tools':
                case 'language':
                case 'templates':
                case 'style':
                case 'images':
                case 'info':
                    continue;
                    break;
            }
            $_tab[$view] = array(
                'label' => $label,
                'url'   => "{$_conf['jrCore_base_url']}/{$murl}/{$view}"
            );
        }
        $_tabs = $_tabs + $_tab;
    }
    $_tabs[$active]['active'] = true;
    jrCore_page_tab_bar($_tabs);
}

/**
 * jrCore_page_skin_tabs
 *
 * @param string $skin Active Skin
 * @param string $active active string active tab can be one of: global,style,images,language,templates,info
 */
function jrCore_page_skin_tabs($skin, $active = 'info')
{
    global $_conf;
    // Core Module URL
    $url = jrCore_get_module_url('jrCore');

    $_lang = jrUser_load_lang_strings();

    $_tabs = array();
    if (is_file(APP_DIR . "/skins/{$skin}/config.php")) {
        $_tabs['global'] = array(
            'label' => 'global config',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/global/skin={$skin}"
        );
    }
    $_tabs['style'] = array(
        'label' => 'style',
        'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/style/skin={$skin}"
    );
    $_tabs['images'] = array(
        'label' => 'images',
        'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/images/skin={$skin}"
    );
    if (isset($_lang[$skin])) {
        $_tabs['language'] = array(
            'label' => 'language',
            'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/language/skin={$skin}"
        );
    }
    $_tabs['templates'] = array(
        'label' => 'templates',
        'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/templates/skin={$skin}"
    );
    $_tabs['info'] = array(
        'label' => 'info',
        'url'   => "{$_conf['jrCore_base_url']}/{$url}/skin_admin/info/skin={$skin}"
    );

    $_tabs[$active]['active'] = true;
    jrCore_page_tab_bar($_tabs);
}

/**
 * jrCore_admin_menu_accordion_js
 * @return bool
 */
function jrCore_admin_menu_accordion_js()
{
    global $_post, $_mods;
    $mcat = (isset($_mods["{$_post['module']}"]['module_category'])) ? $_mods["{$_post['module']}"]['module_category'] : 'utilities';
    $hide = 'var allPanels = $(\'.accordion > dd\')';
    if (count($_mods) > 10) {
        $hide = 'var allPanels = $(\'.accordion > dd[id!="c' . $mcat . '"]\').hide();';
    }
    // We want to hide ALL categories except the category we
    // are currently working in.
    $_js = array('(function($) { ' . $hide . '
    $(\'.accordion > dt > a\').click(function() {
    allPanels.slideUp();
    $(this).parent().next().slideDown();
    return false; }); })(jQuery);');
    jrCore_create_page_element('javascript_ready_function', $_js);
    return true;
}

/**
 * Parse a set of page elements and display them
 * @param bool $return_html Set to true to return HTML instead of display
 * @return mixed
 */
function jrCore_page_display($return_html = false)
{
    global $_post, $_mods, $_conf;
    // See if have an open form on the page - if we do, close it up
    // with our submit and and bring it into the page

    // See if we are doing an ADMIN MENU VIEW for this module/view
    $admn = jrCore_get_flag('jrcore_page_include_admin_menu');
    if ($admn) {
        if (isset($_post['skin'])) {
            $_rt = jrCore_get_skins();
            $_sk = array();
            foreach ($_rt as $skin_dir) {
                $func = "{$skin_dir}_skin_meta";
                if (!function_exists($func)) {
                    require_once APP_DIR . "/skins/{$skin_dir}/include.php";
                }
                if (function_exists($func)) {
                    $_sk[$skin_dir] = $func();
                }
            }
            $_adm = array(
                'active_tab' => 'skins',
                '_skins'     => $_sk
            );
        }
        else {
            $_adm = array(
                'active_tab' => 'modules'
            );

            $_tmp = array();
            foreach ($_mods as $mod_dir => $_inf) {
                $_tmp["{$_inf['module_name']}"] = $mod_dir;
            }
            ksort($_tmp);

            $_out = array();
            foreach ($_tmp as $mod_dir) {
                if (!isset($_mods[$mod_dir]['module_category'])) {
                    $_mods[$mod_dir]['module_category'] = 'utilities';
                }
                $cat = $_mods[$mod_dir]['module_category'];
                if (!isset($_out[$cat])) {
                    $_out[$cat] = array();
                }
                $_out[$cat][$mod_dir] = $_mods[$mod_dir];
            }
            $_adm['_modules']['core'] = $_out['core'];
            unset($_out['core']);
            $_adm['_modules'] = $_adm['_modules'] + $_out;
            ksort($_adm['_modules']);
            unset($_out);

            jrCore_admin_menu_accordion_js();
        }
    }

    // See if we have an active form session
    $_form = jrCore_form_get_session();

    // Setup module
    $module = $_form['form_params']['module'];
    $design = false;

    // Make sure we have not already displayed this form (i.e. the form is embedded into another page)
    $tmp = jrCore_get_flag("jrcore_page_display_form_{$_form['form_token']}");
    if (!$tmp && isset($_form) && is_array($_form) && isset($_form['form_params'])) {

        $_form['form_fields'] = jrCore_get_flag('jrcore_form_session_fields');

        // If our form info changes from a listener, reload
        $_tfrm = jrCore_get_flag('jrcore_form_session_fields');
        if ($_tfrm !== $_form['form_fields']) {
            $_form['form_fields'] = $_tfrm;
        }
        unset($_tfrm);

        // Check and see if this form is registered with the form designer
        if (isset($_form['form_fields']) && is_array($_form['form_fields']) && count($_form['form_fields']) > 0) {
            $_tmp = jrCore_get_registered_module_features('jrCore', 'designer_form');

            // if our install flag is set, and this form has registered for the form designer, we need to make sure we are setup.
            $_fld = jrCore_get_designer_form_fields($_post['module'], $_post['option']);
            if (jrUser_is_master() && isset($_tmp["{$_post['module']}"]["{$_post['option']}"])) {
                // This is a designer form - make sure the fields are setup
                foreach ($_form['form_fields'] as $k => $_field) {
                    if (!isset($_fld) || !is_array($_fld) || !isset($_fld["{$_field['name']}"])) {
                        $_field['active'] = 1;
                        $_field['order'] = ($k + 1);
                        jrCore_verify_designer_form_field($_post['module'], $_post['option'], $_field);
                    }
                }
            }

            // Next - let's get all our designer info about this module/view so we can override
            // what is coming in from the actual module view
            if (isset($_fld) && is_array($_fld)) {
                $design = true;
                // Go through and remove the fields we have already substituted
                foreach ($_form['form_fields'] as $_field) {
                    if ($_field['type'] == 'hidden') {
                        continue;
                    }
                    $fname = $_field['name'];
                    if (isset($_fld[$fname])) {
                        unset($_fld[$fname]);
                    }
                }
                // See if we have any NEW fields left over
                if (isset($_fld) && is_array($_fld) && count($_fld) > 0) {
                    $_val = jrCore_get_flag('jrcore_form_create_values');
                    foreach ($_fld as $_field) {
                        if (isset($_field['active']) && $_field['active'] == '1') {
                            // If this is a meter based field, we need to pass in a copy of the active item id as "value".
                            if (!isset($_field['value'])) {
                                if (isset($_val["{$_field['name']}_size"])) {
                                    // We have a file based field - add in value
                                    $_field['value'] = $_val;
                                }
                            }
                            jrCore_form_field_create($_field, $module, null, false);
                        }
                    }
                }
            }

            // If this is the FIRST load of a form that is a designer form, $_fld will be
            // empty and $design will be false - we change that here if needed.
            if (isset($_tmp["{$_post['module']}"]["{$_post['option']}"])) {
                $design = true;
            }

            // Bring in any additional form fields added by modules
            $_form = jrCore_trigger_event('jrCore', 'form_display', $_form);

            // Make sure additional fields form listeners are added in
            $_form['form_fields'] = jrCore_get_flag('jrcore_form_session_fields');
        }

        // Bring in lang strings
        $_lang = jrCore_get_flag('jr_lang');

        $undo = false;
        if (isset($_form['form_params']['reset'])) {
            if (isset($_form['form_params']['reset_value']) && isset($_lang[$module]["{$_form['form_params']['reset_value']}"])) {
                $undo = $_lang[$module]["{$_form['form_params']['reset_value']}"];
            }
            else {
                $undo = (isset($_lang['jrCore'][9])) ? $_lang['jrCore'][9] : 'reset';
            }
        }
        $cancel_text = false;
        $cancel_url = false;
        if (isset($_form['form_params']['cancel']{0})) {

            // Cancel text
            if (isset($_form['form_params']['cancel_value']) && isset($_lang[$module]["{$_form['form_params']['cancel_value']}"])) {
                $cancel_text = $_lang[$module]["{$_form['form_params']['cancel_value']}"];
            }
            elseif (isset($_form['form_params']['cancel_value']{0})) {
                $cancel_text = $_form['form_params']['cancel_value'];
            }
            else {
                $cancel_text = (isset($_lang['jrCore'][2])) ? $_lang['jrCore'][2] : 'cancel';
            }

            // Cancel Url
            if ($_form['form_params']['cancel'] == 'referrer') {
                $cancel_url = jrCore_get_local_referrer();
            }
            elseif ($_form['form_params']['cancel'] == 'modal_close') {
                $cancel_url = '$.modal.close();';
            }
            else {
                $cancel_url = $_form['form_params']['cancel'];
            }
        }
        // get lang replacements in place
        if (isset($_form['form_params']['submit_value']) && isset($_lang[$module]["{$_form['form_params']['submit_value']}"])) {
            $_form['form_params']['submit_value'] = $_lang[$module]["{$_form['form_params']['submit_value']}"];
        }

        if (isset($_SESSION['quota_max_items_reached'])) {
            jrCore_page_cancel_button($cancel_url, $cancel_text);
        }
        else {
            jrCore_form_submit($_form['form_params']['submit_value'], $undo, $cancel_text, $cancel_url);
        }
        jrCore_form_end();

        // Lastly - save all fields that rolled out on this form to the form session
        if (isset($_form['form_fields']) && is_array($_form['form_fields'])) {
            $tbl = jrCore_db_table_name('jrCore', 'form_session');
            $tkn = jrCore_db_escape($_form['form_token']);
            $sav = jrCore_db_escape(json_encode($_form['form_fields']));
            $req = "UPDATE {$tbl} SET form_updated = UNIX_TIMESTAMP(), form_rand = '" . mt_rand() . "', form_fields = '{$sav}' WHERE form_token = '{$tkn}'";
            jrCore_db_query($req);
        }
        // We only ever show a form once per page display
        jrCore_set_flag("jrcore_page_display_form_{$_form['form_token']}", 1);
    }

    $html = '';
    $page = '';
    $_rep = array();
    $_tmp = jrCore_get_flag('jrcore_page_elements');

    // $_tmp['page'] contains all the page elements we are going to be showing on
    // this view - if we are a designer form, we need to adjust our field order here
    if ($design && isset($_tmp['page']) && is_array($_tmp['page'])) {
        $_or = array();
        $_nw = array();
        $num = 1;
        $elm = 0;
        $tab = -100;
        foreach ($_tmp['page'] as $k => $_field) {

            // Make sure field is active
            if (isset($_field['active']) && $_field['active'] == '0') {
                unset($_tmp['page'][$k]);
                continue;
            }

            // If this is a mobile device, and we are asking for an editor, we use a textarea instead
            if ($_field['type'] == 'editor' && jrCore_is_mobile_device()) {
                $_field['type'] = 'textarea';
            }

            // We need to check here for form fields.  Note that ALL form fields must
            // come after the opening form element - so we first must scan for our
            // opening form element and make sure it comes before the form fields.
            if (isset($_field['name'])) {
                $val = (isset($_field['order'])) ? (int) $_field['order'] : $k + 1;
                $val = ($val * 100);
                if (in_array($val, $_or)) {
                    $val += $k;
                }
                $_or[$num] = $val;
                $elm += 100;
            }
            else {
                switch ($_field['type']) {
                    case 'page_tab_bar':
                        // Tabs always appear at the top
                        $_or[$num] = $tab++;
                        break;
                    case 'page_banner':
                        $_or[$num] = -1;
                        break;
                    case 'form_submit':
                        $_or[$num] = 10000;
                        break;
                    case 'form_begin':
                    case 'page_notice':
                        $_or[$num] = 0;
                        break;
                    default:
                        $elm += 100;
                        $_or[$num] = $elm;
                        break;
                }
            }
            $_nw[$num] = $_field;
            $num++;
        }
        $_fn = array();
        if (isset($_nw) && is_array($_nw) && count($_nw) > 0) {
            asort($_or, SORT_NUMERIC);
            $ti = 1;
            foreach ($_or as $k => $num) {
                // Fixup tab index order
                if (isset($_nw[$k]['name']) && isset($_nw[$k]['html']) && strpos($_nw[$k]['html'], 'tabindex')) {
                    $_nw[$k]['html'] = preg_replace('/tabindex="[0-9]*"/', 'tabindex="' . $ti . '"', $_nw[$k]['html']);
                    $ti++;
                }
                $_fn[] = $_nw[$k];
                unset($_nw[$k]);

            }
            $_tmp['page'] = $_fn;
        }
    }

    //--------------------------------------
    // PROCESS VIEW
    //--------------------------------------

    // Begin page output
    $meta = false;
    $temp = jrCore_get_flag('jrcore_page_no_header_or_footer');
    if (!$temp) {
        // Check for META header only
        $meta = jrCore_get_flag('jrcore_page_meta_header_only');
        if ($meta) {
            $html .= jrCore_parse_template('meta.tpl', $_tmp) . "\n<body>";
        }
        else {
            // Check for backup header elements
            $_bkp = jrCore_get_flag('jrcore_page_elements_backup');
            if ($_bkp) {
                if (isset($_bkp['javascript_ready_function'])) {
                    if (isset($_tmp['javascript_ready_function'])) {
                        $_tmp['javascript_ready_function'] .= $_bkp['javascript_ready_function'];
                    }
                    else {
                        $_tmp['javascript_ready_function'] = $_bkp['javascript_ready_function'];
                    }
                }
                $_chk = array('javascript_href', 'css_href');
                foreach ($_chk as $hitem) {
                    if (isset($_tmp[$hitem]) && isset($_bkp[$hitem])) {
                        $_tmp[$hitem] = $_tmp[$hitem] + $_bkp[$hitem];
                    }
                }
            }
            $html .= jrCore_parse_template('header.tpl', $_tmp);
        }
    }
    else {
        // With no header being shown, any form elements added in that
        // need JS/CSS added to the meta will not function properly. Save
        // these off here so they can be added in later.
        jrCore_set_flag('jrcore_page_elements_backup', $_tmp);
    }

    // We have to check for our form begin/end - they need to sit outside
    // of any tables or page elements.
    if (isset($_tmp['form_begin']{0})) {
        $page .= $_tmp['form_begin'];
    }

    // Any hidden form elements need to follow the form_begin
    if (isset($_tmp['form_hidden']) && is_array($_tmp['form_hidden'])) {
        foreach ($_tmp['form_hidden'] as $v) {
            $page .= $v;
        }
    }

    // Bring in page begin
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/page_begin.tpl")) {
        $page .= jrCore_parse_template('page_begin.tpl', $_rep);
    }
    else {
        $page .= jrCore_parse_template('page_begin.tpl', $_rep, 'jrCore');
    }

    // If we are a master admin user viewing Global or Quota config, we need to
    // set a flag so the "updated" time and default button show in help
    $show_update = '0';
    if (jrUser_is_master() && $_post['option'] == 'admin' && isset($_post['_1'])) {
        switch ($_post['_1']) {
            case 'global':
            case 'quota':
                $show_update = '1';
                break;
        }
    }

    // Parse form/page elements
    $_seen = array();
    foreach ($_tmp['page'] as $k => $_element) {

        // Make sure we only ever show each "name" once - note
        // that this should never happen, but we've seen modules that
        // are not handling custom form insertions properly where
        // this could be the case
        if (isset($_element['name'])) {
            if (isset($_SESSION['quota_max_items_reached'])) {
                continue;
            }
            if (isset($_seen["{$_element['name']}"])) {
                unset($_tmp['page'][$k]);
                continue;
            }
            $_seen["{$_element['name']}"] = 1;
        }
        $_element['show_update_in_help'] = $show_update;
        if (!isset($_element['module'])) {
            jrCore_logger('CRI', "element added without module set - check debug_log");
            fdebug('element added without module being set:', $_element); // OK
        }

        // For some element types we need to set the "default_label"
        switch ($_element['type']) {
            case 'select':
                $_element['default_label'] = (isset($_element['default']) && isset($_element['options']["{$_element['default']}"])) ? $_element['options']["{$_element['default']}"] : false;
                break;
            case 'editor':
                // If this is a mobile device, and we are asking for an editor, we use a textarea instead
                $_element['type'] = 'textarea';
                break;
            case 'page_section_header':
                // We render section headers at the first element that defines it
                if (isset($_form['form_params'])) {
                    continue 2;
                }
                break;
        }

        // Setup our default value properly for display
        $_element['default_value'] = '';
        $_element['saved_value'] = '';
        if (isset($_element['default']) && is_string($_element['default'])) {
            $_element['default_value'] = str_replace(array("\r\n", "\r", "\n"), '\n', addslashes($_element['default']));
        }
        if (isset($_element['saved_value']) && is_string($_element['saved_value'])) {
            $_element['saved_value'] = str_replace(array("\r\n", "\r", "\n"), '\n', addslashes($_element['saved_value']));
        }

        // Check for section
        if (isset($_element['section']) && strlen($_element['section']) > 0 && !isset($_sec["{$_element['section']}"])) {
            $page .= jrCore_parse_template('page_section_header.tpl', array('title' => $_element['section']), 'jrCore');
            $_sec["{$_element['section']}"] = 1;
        }

        // Check for template - if we have a template, render it - else use HTML that comes from function
        if (isset($_element['template']{0})) {
            // Our skin can override any core/module template
            if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/{$_element['template']}")) {
                $page .= jrCore_parse_template($_element['template'], $_element);
            }
            elseif (is_file(APP_DIR . "/modules/{$_element['module']}/templates/{$_element['template']}")) {
                $page .= jrCore_parse_template($_element['template'], $_element, $_element['module']);
            }
            else {
                // default to core
                $page .= jrCore_parse_template($_element['template'], $_element, 'jrCore');
            }
        }
        elseif (isset($_element['html']{0})) {
            $page .= $_element['html'];
        }
    }
    if (isset($_SESSION['quota_max_items_reached'])) {
        unset($_SESSION['quota_max_items_reached']);
    }
    unset($_seen);

    // Bring in page end
    if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/page_end.tpl")) {
        $page .= jrCore_parse_template('page_end.tpl', $_rep);
    }
    else {
        $page .= jrCore_parse_template('page_end.tpl', $_rep, 'jrCore');
    }

    // We have to check for our form begin/end - they need to sit outside
    // of any tables or page elements.
    if (isset($_tmp['form_end'])) {
        $page .= $_tmp['form_end'];
    }

    // as well as modal window HTML
    if (isset($_tmp['form_modal'])) {
        $page .= jrCore_parse_template($_tmp['form_modal']['template'], array_merge($_rep, $_tmp['form_modal']), 'jrCore');
    }

    // See if we are doing an ADMIN MENU VIEW for this module/view
    $dash = jrCore_get_flag('jrcore_dashboard_active');
    if ($admn && isset($_adm)) {
        $_adm['admin_page_content'] = $page;
        // See if our skin is overriding our core admin template
        if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/admin.tpl")) {
            $html .= jrCore_parse_template('admin.tpl', $_adm);
        }
        else {
            $html .= jrCore_parse_template('admin.tpl', $_adm, 'jrCore');
        }
    }
    elseif ($dash) {
        $_rep = array(
            'dashboard_html' => $page
        );
        // See if our skin is overriding our core admin template
        if (is_file(APP_DIR . "/skins/{$_conf['jrCore_active_skin']}/dashboard.tpl")) {
            $html .= jrCore_parse_template('dashboard.tpl', $_rep);
        }
        else {
            $html .= jrCore_parse_template('dashboard.tpl', $_rep, 'jrCore');
        }
    }
    else {
        $html .= $page;
    }

    // Bring in footer
    if (!$temp) {
        // Check for META header only
        if ($meta) {
            $html .= "\n</body>";
        }
        else {
            $html .= jrCore_parse_template('footer.tpl', $_tmp);
        }
    }
    else {
        // Reset for next show
        jrCore_delete_flag('jrcore_page_no_header_or_footer');
    }

    // Reset elements
    jrCore_delete_flag('jrcore_page_elements');

    if ($return_html) {
        return $html;
    }
    echo $html;
    return true;
}

/**
 * The jrCore_page_buttonCode function is used for generating the necessary "button"
 * HTML code in the Jamroom Control Panel.  This ensures the Control Panel buttons
 * can be styled via the form.tpl file.
 * form.tpl element name: form_button
 *
 * @param string $name Value for Button
 * @param string $value Value for onclick handler
 * @param string $onclick If the button needs a name parameter, you can provide it here
 * @param array $_att Additional HTML <input> tag parameters
 *
 * @return string Returns HTML of button code
 */
function jrCore_page_button($name, $value, $onclick, $_att = null)
{
    // Check for provided class...
    $cls = 'form_button';
    if (isset($_att['class'])) {
        $cls = $_att['class'];
        unset($_att['class']);
    }
    $end_anchor = false;
    if (isset($onclick) && $onclick == 'disabled') {
        $html = '<input type="button" id="' . $name . '" class="' . $cls . ' form_button_disabled" name="' . $name . '" value="' . $value . '" disabled="disabled"';
    }
    // Surrounding a button with anchor tags is not valid HTML 5
    // elseif (strpos($onclick,'window.location=') === 0) {
    //     $onclick = substr($onclick,17,strlen($onclick) - 18);
    //     $html = '<span class="form_button_anchor"><a href="'. $onclick .'"><input type="button" id="'. $name .'" class="'. $cls .'" name="'. $name .'" value="'. $value .'"';
    //     $end_anchor = true;
    // }
    else {
        $html = '<input type="button" id="' . $name . '" class="' . $cls . '" name="' . $name . '" value="' . $value . '" onclick="' . $onclick . '"';
    }
    if (isset($_att) && is_array($_att)) {
        foreach ($_att as $key => $attr) {
            $html .= ' ' . $key . '="' . $attr . '"';
        }
    }
    $html .= '>';
    // if ($end_anchor) {
    //     $html .= '</a></span>';
    // }
    return $html;
}

/**
 * jrCore_show_pending_notice
 * @param string $module Module
 * @param array $_item Item info
 * @return bool
 */
function jrCore_show_pending_notice($module, $_item)
{
    global $_conf;
    $prefix = jrCore_db_get_prefix($module);
    if (!isset($_item["{$prefix}_pending"]) || $_item["{$prefix}_pending"] != '1') {
        return true;
    }
    $_lang = jrUser_load_lang_strings();
    // We are pending - show notice to normal users, approval options to admin users
    if (jrUser_is_admin()) {
        $out = $_lang['jrCore'][71] . '<br><br>';
        $url = jrCore_get_module_url('jrCore');
        $out .= jrCore_page_button('approve', 'approve', "window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_approve/id={$_item['_item_id']}'") . '&nbsp';
        $out .= jrCore_page_button('reject', 'reject', "window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_reject/id={$_item['_item_id']}'") . '&nbsp';
        $out .= jrCore_page_button('delete', 'delete', "if(confirm('Are you sure you want to delete this item? No notice will be sent.')){window.location='{$_conf['jrCore_base_url']}/{$url}/pending_item_delete/id={$_item['_item_id']}'}");
        jrCore_page_notice('notice', $out, false);
    }
    else {
        jrCore_page_notice('notice', $_lang['jrCore'][71]);
    }
    return true;
}
