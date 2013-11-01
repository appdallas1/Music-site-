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
function image_filter_emboss($image_handle,$_params,$height,$width)
{
    imagefilter($image_handle,IMG_FILTER_EMBOSS);
    return $image_handle;
}
?>
