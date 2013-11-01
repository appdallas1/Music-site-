<?php
/**
 * Jamroom 5 Elastic skin
 * @copyright 2003 - 2012 by The Jamroom Network - All Rights Reserved
 * @author Brian Johnson - brian@jamroom.net
 */

// We are never called directly
if (!defined('APP_DIR')) { exit; }

/**
 * jrElastic_meta
 */
function jrElastic_skin_meta()
{
    $_tmp = array(
        'name'        => 'jrElastic',
        'version'     => '1.0.1',
        'developer'   => 'The Jamroom Network, &copy;'. strftime('%Y'),
        'description' => 'The Default Jamroom 5 Skin - clean and easy to expand',
        'support'     => 'http://www.jamroom.net/phpBB2'
    );
    return $_tmp;
}

/**
 * jrElastic_init
 * NOTE: unlike with a module, init() is NOT called on each page load, but is
 * called when the core needs to rebuild CSS or Javascript for the skin
 */
function jrElastic_skin_init()
{
    // Bring in all our CSS files
    jrCore_register_module_feature('jrCore','css','jrElastic','core_html.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_grid.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_site.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_page.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_banner.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_header.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_footer.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_element.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_input.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_select.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_layout.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_button.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_form_notice.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_list.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_menu.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_table.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_tabs.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_image.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_profile.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_skin.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_slider.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_text.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_base.css');

    jrCore_register_module_feature('jrCore','css','jrElastic','core_admin_menu.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_admin_log.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','core_admin_modal.css');

    jrCore_register_module_feature('jrCore','css','jrElastic','table_core.css');
    jrCore_register_module_feature('jrCore','css','jrElastic','mobile_core.css');

    // Register our Javascript files with the core
    jrCore_register_module_feature('jrCore','javascript','jrElastic','responsiveslides.min.js');
    jrCore_register_module_feature('jrCore','javascript','jrElastic','jrElastic.js');
    jrCore_register_module_feature('jrCore','javascript','jrElastic','css3_mediaqueries.js');

    // Tell the core the default icon set to use (black or white)
    jrCore_register_module_feature('jrCore','icon_color','jrElastic','black');
    // Tell the core the size of our action buttons (width in pixels, up to 64)
    jrCore_register_module_feature('jrCore','icon_size','jrElastic',30);

    // Our default media player skins
    jrCore_register_module_feature('jrCore','media_player_skin','jrElastic','jrAudio','jrAudio_player_dark');
    jrCore_register_module_feature('jrCore','media_player_skin','jrElastic','jrVideo','jrVideo_player_dark');
    jrCore_register_module_feature('jrCore','media_player_skin','jrElastic','jrPlaylist','jrPlaylist_player_dark');

    return true;
}
