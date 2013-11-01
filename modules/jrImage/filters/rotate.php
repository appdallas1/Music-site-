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
function image_filter_rotate($image_handle,$_params,$height,$width)
{
    if (!is_numeric($_params[1]) || $_params[1] <= 0 || $_params[1] >= 360) {
        return $image_handle;
    }
    if (!isset($_params[2])) {
        $_params[2] = 0;
    }
    $image_handle = imagerotate($image_handle,$_params[1],intval($_params[2]));
    imagealphablending($image_handle,true); 
    imagesavealpha($image_handle,true);

    // See if our rotation affected the size of the image - if so, we need to resize
    $new_h = imagesy($image_handle);
    $new_w = imagesx($image_handle);
    if ($new_h != $height || $new_w != $width) {
        $new_resource = imagecreatetruecolor($width,$height); 
        if (!imagecopyresampled($new_resource,$image_handle,0,0,0,0,$width,$height,$new_w,$new_h)) {
            if (!imagecopyresized($new_resource,$image_handle,0,0,0,0,$width,$height,$new_w,$new_h)) {
                return 'unable to create new source image from rotate resulting in new image size';
            }
        }
        $image_handle = $new_resource;
    }
    return $image_handle;
}
?>
