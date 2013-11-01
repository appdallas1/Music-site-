<?php
/**
 * Jamroom 5 Image Filter
 * @copyright 2003 - 2012 by The Jamroom Network - All Rights Reserved
 * @author Brian Johnson - brian@jamroom.net
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Filter output file type function
 *
 * @return string Returns PNG
 */
function image_filter_reflection_ext()
{
    return 'png';
}

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
function image_filter_reflection($image_handle,$_params,$height,$width)
{
    // Function requires the imagelayereffect function to work
    if (!function_exists('imagelayereffect')) {
        return false;
    }
    $rf_h = round($height * ($_params[1] / 100));
    // Buffer to hold our reflection
    if ($buff = imagecreatetruecolor($width,$rf_h)) {

        imagesavealpha($image_handle,true);
        imagealphablending($image_handle,false);
        imagesavealpha($buff,true);
        imagealphablending($buff,false);

        // We need to "squish" the reflection into a bit smaller space to give it the
        // appearance that it is coming "forward" of the image
        $rf_h = round($rf_h * .50);

        for ($y = 0;$y < $rf_h;$y++) {
            imagecopy($buff,$image_handle,0,$y,0,($height - $y - 1),$width,1);
        }

        $alpha_s = 80;
        $alpha_e = 0;
        $alpha_l = abs($alpha_s - $alpha_e);

        $new = imagecreatetruecolor($width,($height + $rf_h));
        imagesavealpha($new,true);
        imagealphablending($new,false);

        imagecopy($new,$image_handle,0,0,0,0,$width,$height);
        imagecopy($new,$buff,0,$height,0,0,$width,$rf_h);

        imagelayereffect($new,IMG_EFFECT_OVERLAY);

        for ($y = 0;$y <= $rf_h;$y++) {
            //  Get % of reflection height
            $pct = ($y / $rf_h);
            $alpha = (int) ($alpha_s - ($pct * $alpha_l));
            $alpha = (127 - $alpha);
            imagefilledrectangle($new,0,($height + $y),$width,($height + $y),imagecolorallocatealpha($new,127,127,127,$alpha));
        }
        imagedestroy($buff);
        return $new;
    }
    return false;
}
?>
