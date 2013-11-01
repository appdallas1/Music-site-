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
function image_filter_rounded_ext()
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
function image_filter_rounded($image_handle,&$_params,$height,$width)
{
    if (!isset($_params[2]) && is_numeric($_params[1]) && $_params[1] > 1) {
        $_arg[1] = $_arg[2] = $_arg[3] = $_arg[4] = $_params[1];
    }
    elseif (count($_params) === 5) {
        foreach ($_params as $k => $v) {
            if ($k < 2) {
                continue;
            }
            if (!is_numeric($v)) {
                return false;
            }
            $_arg[$k] = (int) $v;
        }
    }
    else {
        return false;
    }
    if ($tp_col = imagecolorallocatealpha($image_handle,0,0,0,127)) {
        $_r = $_c = array();
        // radius (ellipse width and height)
        foreach($_arg as $k => $v) {
            switch ($v) {
                case 2:
                case 4:
                case 6:
                case 8:
                case 10:
                    $_r[$k] = round($v * 3.3);
                    break;
                default:
                    $_r[$k] = round($v * 3);
                    break;
            }
            // Center of ellipse
            $_c[$k] = round($v * .45);
        }
        // Upper Left
        if ($_arg[1] > 0) {
            $x = ($_arg[1] + $_c[1]);        // x offset
            $y = ($_arg[1] + $_c[1]);        // y offset
            imagearc($image_handle,$x,$y,$_r[1],$_r[1],180,270,$tp_col);
            imagefilltoborder($image_handle,0,0,$tp_col,$tp_col);
        }
        // Lower Left
        if ($_arg[4] > 0) {
            $x = ($_arg[1] + $_c[1]);        // x offset
            $x = ($_arg[4] + $_c[4]);                    // x offset
            $y = (($height - 1) - ($_arg[4] + $_c[4])); // y offset
            imagearc($image_handle,$x,$y,$_r[4],$_r[4],90,180,$tp_col);
            imagefilltoborder($image_handle,1,($height - 1),$tp_col,$tp_col);
        }
        // Upper Right
        if ($_arg[2] > 0) {
            $x = (($width - 1) - ($_arg[2] + $_c[2]));       // x offset
            $y = ($_arg[2] + $_c[2]);                // y offset
            imagearc($image_handle,$x,$y,$_r[2],$_r[2],270,0,$tp_col);
            imagefilltoborder($image_handle,($width - 1),1,$tp_col,$tp_col);
        }
        // Lower Right
        if ($_arg[3] > 0) {
            $x = (($width - 1) - ($_arg[3] + $_c[3]));       // x offset
            $y = (($height - 1) - ($_arg[3] + $_c[3]));      // x offset
            imagearc($image_handle,$x,$y,$_r[3],$_r[3],0,90,$tp_col);
            imagefilltoborder($image_handle,($width - 1),($height - 1),$tp_col,$tp_col);
        }
    }
    return $image_handle;
}
