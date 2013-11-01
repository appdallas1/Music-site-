<?php
/**
 * Jamroom 5 Image Filter
 * @copyright 2003 - 2012 by The Jamroom Network - All Rights Reserved
 * @author Brian Johnson - brian@jamroom.net
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Main filter function for processing image
 *
 * @param resource Incoming Image resource
 * @param array Filter params (0 = filter name)
 * @param int Output height
 * @param int Output width
 *
 * @return mixed returns false on failure, resource on success
 */
function image_filter_colorize($image_handle,$_params,$height,$width)
{
    // Make sure we get good values, or default to 127
    foreach (array(1,2,3,4) as $num) {
        if (!isset($_params[$num]) || !is_numeric($_params[$num])) {
            $_params[$num] = 127;
        }
    } 
    imagefilter($image_handle,IMG_FILTER_COLORIZE,$_params[1],$_params[2],$_params[3],$_params[4]);
    return $image_handle;
}
?>
