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
function image_filter_border($image_handle,$_params,$height,$width)
{
    // Border width in pixels is first
    if (!is_numeric($_params[1])) {
        $_params[1] = 1;
    }
    $_params[1] = intval($_params[1]);
    // Make sure we get good values, or default to 127
    foreach (array(2,3,4) as $num) {
        if (!isset($_params[$num]) || !is_numeric($_params[$num])) {
            $_params[$num] = 0;
        }
        $_params[$num] = intval($_params[$num]);
    }
    $color = imagecolorallocate($image_handle,$_params[2],$_params[3],$_params[4]); 
    $brd_x = 0;
    $brd_y = 0;
    $img_x = (imagesx($image_handle) - 1);
    $img_y = (imagesy($image_handle) - 1);
    for ($i = 0; $i < $_params[1]; $i++) { 
        imagerectangle($image_handle,$brd_x++,$brd_y++,$img_x--,$img_y--,$color); 
    } 
    return $image_handle;
}
?>
