<?php
/**
 * @copyright 2012 Talldude Networks, LLC.
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * jrElastic_skin_config
 */
function jrElastic_skin_config()
{
    // Profile ID's
    $_tmp = array(
        'name'     => 'profile_ids',
        'type'     => 'text',
        'default'  => '',
        'validate' => 'not_empty',
        'label'    => 'Image Slider IDs',
        'help'     => 'Enter the profile ID\'s you want to show in the image slider. Note: Separate multiple ID\'s with a comma, ie. 1,2,3',
        'order'    => 1
    );
    jrCore_register_setting('jrElastic',$_tmp);

    // Player Auto Play
    $_tmp = array(
        'name'     => 'auto_play',
        'default'  => 'off',
        'type'     => 'checkbox',
        'validate' => 'onoff',
        'required' => 'on',
        'label'    => 'Auto Play',
        'help'     => 'Enabling this option will turn on your players auto play feature.<br><span class="form_help_small">Note: This is for the following profile players only. Audio, Playlist and Video.</span>',
        'order'    => 2
    );
    jrCore_register_setting('jrElastic',$_tmp);

    return true;
}
?>
