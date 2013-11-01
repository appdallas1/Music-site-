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
function image_filter_cut_ext()
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
function image_filter_cut($image_handle,&$_params,$height,$width)
{
    if (!isset($_params[2]) && is_numeric($_params[1])) {
        $_cc[1] = $_cc[2] = $_cc[3] = $_cc[4] = $_params[1];
    }
    elseif (count($_params) === 5) {
        foreach ($_params as $k => $v) {
            if ($k < 2) {
                continue;
            }
            if (!is_numeric($v)) {
                return false;
            }
            $_cc[$k] = (int) $v;
        }
    }
    else {
        return false;
    }
    if ($tp_col = imagecolorallocatealpha($image_handle,0,0,0,127)) {
        // Upper Left
        if ($_cc[1] > 0) {
            imageline($image_handle,0,$_cc[1],$_cc[1],0,$tp_col);
            imagefilltoborder($image_handle,0,0,$tp_col,$tp_col);
        }
        // Lower Left
        if ($_cc[4] > 0) {
            imageline($image_handle,0,(($height - 1) - $_cc[4]),$_cc[4],($height - 1),$tp_col);
            imagefilltoborder($image_handle,0,($height - 1),$tp_col,$tp_col);
        }
        // Upper Right
        if ($_cc[2] > 0) {
            imageline($image_handle,(($width - 1) - $_cc[2]),0,($width - 1),$_cc[2],$tp_col);
            imagefilltoborder($image_handle,($width - 1),0,$tp_col,$tp_col);
        }
        // Lower Right
        if ($_cc[3] > 0) {
            imageline($image_handle,($width - 1),(($height - 1) - $_cc[3]),(($width - 1) - $_cc[3]),($height - 1),$tp_col);
            imagefilltoborder($image_handle,($width - 1),($height - 1),$tp_col,$tp_col);
        }
        return $image_handle;
    }
    return false;
}
