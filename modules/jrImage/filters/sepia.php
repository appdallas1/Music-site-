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
function image_filter_sepia(&$image,&$_params,$height,$width)
{
    imagefilter($image,IMG_FILTER_GRAYSCALE);
    imagefilter($image,IMG_FILTER_BRIGHTNESS,-30);
    imagefilter($image,IMG_FILTER_COLORIZE,90,55,30);
    return $image; 
}
?>
