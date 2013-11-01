<?php
/**
 * Jamroom 5 jrCore module
 *
 * copyright 2003 - 2013 by The Jamroom Network - All Rights Reserved
 * http://www.jamroom.net
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0.  Please see the included "license.html" file.
 *
 * This module may include works that are not developed by The Jamroom
 * Network and are used under license - any licenses are included and
 * can be found in the "contrib" directory within this module.
 *
 * Jamroom may use modules and skins that are licensed by third party
 * developers, and licensed under a different license than the Jamroom
 * Core - please reference the individual module or skin license that
 * is included with your download.
 *
 * This software is provided "as is" and any express or implied
 * warranties, including, but not limited to, the implied warranties
 * of merchantability and fitness for a particular purpose are
 * disclaimed.  In no event shall the Jamroom Network be liable for
 * any direct, indirect, incidental, special, exemplary or
 * consequential damages (including but not limited to, procurement
 * of substitute goods or services; loss of use, data or profits;
 * or business interruption) however caused and on any theory of
 * liability, whether in contract, strict liability, or tort
 * (including negligence or otherwise) arising from the use of this
 * software, even if advised of the possibility of such damage.
 * Some jurisdictions may not allow disclaimers of implied warranties
 * and certain statements in the above disclaimer may not apply to
 * you as regards implied warranties; the other terms and conditions
 * remain enforceable notwithstanding. In some jurisdictions it is
 * not permitted to limit liability and therefore such limitations
 * may not apply to you.
 *
 * @package Media and File
 * @copyright 2012 Talldude Networks, LLC.
 * @author Brian Johnson <brian [at] jamroom [dot] net>
 */

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Add a session based media key
 * @param $page string HTML contents of page
 * @return bool
 */
function jrCore_media_set_play_key($page)
{
    if (strpos($page,'[jrCore_media_play_key]')) {
        $key = mt_rand();
        if (!isset($_SESSION['JRCORE_PLAY_KEYS'])) {
            $_SESSION['JRCORE_PLAY_KEYS'] = array();
        }
        $_SESSION['JRCORE_PLAY_KEYS'][$key] = time();
        // Keep play keys cleaned up
        if (count($_SESSION['JRCORE_PLAY_KEYS']) > 500) {
            arsort($_SESSION['JRCORE_PLAY_KEYS'],SORT_NUMERIC);
            $_SESSION['JRCORE_PLAY_KEYS'] = array_slice($_SESSION['JRCORE_PLAY_KEYS'],0,500,true);
        }
        $page = str_replace('[jrCore_media_play_key]',$key,$page);
    }
    return $page;
}

/**
 * Get list of registered Media players by type
 * @param $type string one of: audio|video|mixed
 * @return array|bool
 */
function jrCore_get_registered_media_players($type)
{
    $_tmp = jrCore_get_registered_module_features('jrCore','media_player');
    if (!isset($_tmp) || !is_array($_tmp)) {
        return false;
    }
    $_out = array();
    foreach ($_tmp as $module => $_players) {
        foreach ($_players as $pname => $ptype) {
            if ($ptype == $type || $type == 'all') {
                $_out[$pname] = $module;
            }
        }
    }
    if (isset($_out) && is_array($_out) && count($_out) > 0) {
        return $_out;
    }
    return false;
}

/**
 * Checks to be sure sure the FFmpeg install is working
 * @param $notice bool Set to false to prevent form notice being set if error
 * @return bool
 */
function jrCore_check_ffmpeg_install($notice = true)
{
    global $_conf;
    // Our audio module requires FFmpeg - make sure it is executable
    $ffmpeg = APP_DIR ."/modules/jrCore/tools/ffmpeg";
    if (isset($_conf['jrCore_ffmpeg_binary'])) {
        $ffmpeg = $_conf['jrCore_ffmpeg_binary'];
    }
    if (is_file($ffmpeg) && !is_executable($ffmpeg)) {
        // Try to set permissions if we can...
        @chmod($ffmpeg,0755);
    }
    if (jrUser_is_master() && (!is_file($ffmpeg) || !is_executable($ffmpeg))) {
        if ($notice) {
            $show = htmlentities(str_replace(APP_DIR .'/','',$ffmpeg));
            jrCore_set_form_notice('error','The ffmpeg binary: '. $show .' is not executable!  Set permissions on the file to 755 or 555.');
        }
        return false;
    }
    return $ffmpeg;
}

/**
 * Uses FFMpeg to retrieve information about audio and video files
 * @param string $file File to get data for
 * @param string $field_prefix Prefix for return array keys
 * @return array
 */
function jrCore_get_media_file_metadata($file,$field_prefix)
{
    $ffmpeg = jrCore_check_ffmpeg_install();
    if (!is_file($ffmpeg)) {
        jrCore_logger('CRI','required ffmpeg binary not found in modules/jrCore/tools');
        return false;
    }
    if (!is_executable($ffmpeg)) {
        jrCore_logger('CRI','ffmpeg binary in modules/jrCore/tools is not executable!');
        return false;
    }
    $dir = jrCore_get_module_cache_dir('jrCore');
    $tmp = tempnam($dir,'media_meta_');

    // Audio (WMA)
    // Duration: 00:03:15.0, start: 1.579000, bitrate: 162 kb/s
    // Stream #0.0: Audio: wmav2, 44100 Hz, stereo, 160 kb/s

    // Audio (MP3)
    // Duration: 00:02:17.3, bitrate: 191 kb/s
    // Stream #0.0: Audio: mp3, 44100 Hz, stereo, 192 kb/s

    // Audio (M4A)
    // Duration: 00:00:28.61, start: 0.023220, bitrate: 117 kb/s
    // Stream #0:0(eng): Audio: aac (mp4a / 0x6134706D), 44100 Hz, stereo, s16, 116 kb/s

    // Audio (FLAC)
    // Duration: N/A, bitrate: N/A
    // Stream #0.0: Audio: flac, 44100 Hz, stereo

    // Audio (OGG)
    // Duration: 00:00:27.9, start: 0.686440, bitrate: 91 kb/s
    // Stream #0.0: Audio: vorbis, 44100 Hz, stereo, 96 kb/s

    // Video (WMV)
    // Duration: 00:02:39.9, start: 4.000000, bitrate: 913 kb/s
    // Stream #0.0: Audio: wmav2, 48000 Hz, stereo, 128 kb/s
    // Stream #0.1: Video: wmv3, yuv420p, 640x512, 766 kb/s, 25.00 fps(r)

    // Video MOV (with unsupported M4A audio)
    // Duration: 00:01:19.6, start: 0.000000, bitrate: 1288 kb/s
    // Stream #0.0(eng): Video: h264, yuv420p, 640x368 [PAR 0:1 DAR 0:1], 25.00 tb(r)
    // Stream #0.1(eng): Audio: mp4a / 0x6134706D, 11025 Hz, mono

    // Video (FLV)
    // Duration: 08:25:32.0, start: 0.000000, bitrate: 64 kb/s
    // Stream #0.0: Video: flv, yuv420p, 320x240, 25.00 fps(r)
    // Stream #0.1: Audio: mp3, 22050 Hz, stereo, 64 kb/s

    // Stream #0:0(eng): Video: mpeg4 (Simple Profile) (mp4v / 0x7634706D), yuv420p, 176x144 [SAR 1:1 DAR 11:9], 122 kb/s, 29.97 fps, 29.97 tbr, 90k tbn, 30k tbc

    // Metadata:
    //  encoder         : Audiograbber 1.81.03, LAME dll 3.92, 160 Kbit/s, Joint Stereo, Normal quality
    //  title           : Pastichio Medley
    //  artist          : Smashing Pumpkins
    //  publisher       : Hut
    //  genre           : Rock
    //  album           : The Aeroplane Flies High - Zero
    //  track           : 7
    //  album_artist    : Smashing Pumpkins
    //  composer        : Billy Corgan
    //  date            : 1996
    // Duration: 00:23:00.44, start: 0.000000, bitrate: 160 kb/s

    ob_start();
    $file = str_replace('"','\"',$file);
    system("nice -n 9 {$ffmpeg} -i \"{$file}\" >/dev/null 2>{$tmp}",$ret);
    ob_end_clean();

    $_out = array();
    $meta = false;
    if (isset($tmp) && is_file($tmp)) {
        $_tmp = file($tmp);
        if (isset($_tmp) && is_array($_tmp)) {
            foreach ($_tmp as $line) {
                $line = trim($line);
                if (strpos($line,'Duration:') === 0) {
                    $meta = false;
                    // Duration: 00:07:21.18, start: 0.000000, bitrate: 128 kb/s
                    $length = jrCore_string_field($line,2);
                    if (strpos($length,'.')) {
                        list($sec,) = explode('.',$length,2);
                        if (strlen($sec) >= 8) {
                            $length = $sec;
                        }
                    }
                    else {
                        $length = substr($length,0,8);
                    }
                    $_out["{$field_prefix}_length"] = $length;

                    // FLAC's bitrate will only be found on the duration line
                    // Duration: 00:05:27.76, bitrate: 728 kb/s
                    $temp = jrCore_string_field($line,3);
                    if ($temp == 'bitrate:') {
                        $_out["{$field_prefix}_bitrate"] = (int) jrCore_string_field($line,4);
                    }
                }
                elseif (strpos($line,'Stream') === 0 && strpos($line,'Audio') && !isset($save)) {
                    $meta = false;
                    // Stream #0:0: Audio: mp3, 44100 Hz, stereo, s16, 128 kb/s
                    $bitrate = jrCore_string_field($line,-2);
                    if (isset($bitrate) && jrCore_checktype($bitrate,'number_nz')) {
                        $_out["{$field_prefix}_bitrate"] = (int) $bitrate;
                    }
                    $smprate = jrCore_string_field($line,5);
                    if (isset($smprate) && jrCore_checktype($smprate,'number_nz')) {
                        $_out["{$field_prefix}_smprate"] = (int) $smprate;
                    }
                }
                elseif (strpos($line,'Audio:') === 0 && !isset($save)) {
                    $meta = false;
                    // Stream #0:0: Audio: mp3, 44100 Hz, stereo, s16, 128 kb/s
                    $_out["{$field_prefix}_bitrate"] = (int) jrCore_string_field($line,-2);
                    $_out["{$field_prefix}_smprate"] = (int) jrCore_string_field($line,5);
                }
                elseif (strpos($line,'Video:')) {
                    $meta = false;
                    $save = false;
                    // This is a video file - get our details
                    foreach (explode(' ',$line) as $word) {
                        if (strtolower($word) == 'kb/s,') {
                            $_out["{$field_prefix}_bitrate"] = $save;
                        }
                        elseif (strpos($word,'x')) {
                            $_wrd = explode('x',$word);
                            if (count($_wrd) === 2 && (strlen($_wrd[0]) > 1 && strlen($_wrd[0]) < 5) && (strlen($_wrd[1]) > 1 && strlen($_wrd[1]) < 5)) {
                                $_out["{$field_prefix}_resolution"] = $word;
                            }
                        }
                        $save = $word;
                    }
                }
                elseif (strpos($line,'Metadata:') === 0) {
                    $meta = true;
                }
                elseif ($meta && strpos($line,':')) {
                    list($tag,$val) = explode(':',$line,2);
                    $tag = strip_tags(trim($tag));
                    switch ($tag) {
                        case 'title':
                        case 'composer':
                        case 'publisher':
                        case 'album':
                        case 'genre':
                        case 'date':
                            $_out["{$field_prefix}_{$tag}"] = strip_tags(trim($val));
                            break;
                        case 'track':
                            // Our "track" becomes our order field used for
                            // ordering of items in albums.  Note that some track fields
                            // can contain a '/' - i.e. 5/12 - we only want the first
                            $val = trim(strip_tags($val));
                            if (strpos($val,'/')) {
                                list($val,) = explode('/',$val,2);
                            }
                            $_out["{$field_prefix}_track"] = intval($val);
                            break;
                    }
                }
            }
        }
    }
    @unlink($tmp);
    return $_out;
}

/**
 * Get full path to a media file
 * @param string $module Module Name to save file for
 * @param string $file_name Unique File Name field
 * @param array Array of item information from jrCore_db_get_item()
 * @return string
 */
function jrCore_get_media_file_path($module,$file_name,$_item)
{
    if (!isset($_item["{$file_name}_size"])) {
        return false;
    }
    $dir = jrCore_get_media_directory($_item['_profile_id']);
    return "{$dir}/{$module}_{$_item['_item_id']}_{$file_name}.". $_item["{$file_name}_extension"];
}

/**
 * Delete a media file for a given item ID
 * @param string $module Module Name to save file for
 * @param string $file_name Name of file field in form
 * @param int $profile_id the Profile ID to save the media file for
 * @param int $unique_id Unique Item ID from DataStore
 * @return bool
 */
function jrCore_delete_item_media_file($module,$file_name,$profile_id,$unique_id)
{
    if (!isset($unique_id) || !jrCore_checktype($unique_id,'number_nz')) {
        return false;
    }
    $dir = jrCore_get_media_directory($profile_id);
    $nam = "{$module}_{$unique_id}_{$file_name}.";
    if ($h = opendir(realpath($dir))) {
        while (false !== ($file = readdir($h))) {
            if (strpos($file,$nam) === 0) {
                unlink("{$dir}/{$file}");
            }
        }
        closedir($h);
    }
    $uid = (int) $unique_id;
    $tbl = jrCore_db_table_name($module,'item_key');
    $req = "DELETE FROM {$tbl} WHERE `_item_id` = '{$uid}' AND `key` LIKE '". jrCore_db_escape($file_name) ."_%'";
    jrCore_db_query($req);
    return true;
}

/**
 * Get media files that have been uploaded from a form
 * @param string $module Module Name to check file for
 * @param string $file_name Name of file field in form
 * @return array
 */
function jrCore_get_uploaded_media_files($module,$file_name)
{
    global $_post;
    if (isset($_post['upload_token']{0})) {
        $dir = jrCore_get_module_cache_dir('jrCore');
        if (is_dir("{$dir}/{$_post['upload_token']}")) {
            $_tmp = glob("{$dir}/{$_post['upload_token']}/*_{$file_name}.tmp");
            if (isset($_tmp) && is_array($_tmp) && count($_tmp) > 0) {
                foreach ($_tmp as $k => $v) {
                    $_tmp[$k] = substr($v,0,strlen($v) - 4);
                }
                return $_tmp;
            }
        }
    }
    return false;
}

/**
 * Checks to see if a media file has been uploaded for the given $file_name
 * @param string $module Module Name to check file for
 * @param string $file_name Name of file field in form
 * @param int $profile_id the Profile ID the file(s) were uploaded under
 * @return bool
 */
function jrCore_is_uploaded_media_file($module,$file_name,$profile_id)
{
    global $_post;
    if (isset($_post['upload_token']{0})) {
        $dir = jrCore_get_module_cache_dir('jrCore');
        if (is_dir("{$dir}/{$_post['upload_token']}")) {
            $_tmp = glob("{$dir}/{$_post['upload_token']}/*_{$file_name}.tmp");
            if (isset($_tmp) && is_array($_tmp) && count($_tmp) > 0) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Saves all uploaded media files for a given Item ID
 * @param string $module Module Name to save file for
 * @param string $view View to save files for
 * @param int $profile_id the Profile ID to save the media file for
 * @param int $unique_id Unique Item ID from DataStore
 * @param array $_existing Item Array (for update checking)
 * @return bool
 */
function jrCore_save_all_media_files($module,$view,$profile_id,$unique_id,$_existing = null)
{
    global $_post;
    if (isset($_post['jr_html_form_token'])) {
        $_form = jrCore_form_get_session($_post['jr_html_form_token']);
        if (!isset($_form['form_fields']) || !is_array($_form['form_fields'])) {
            return true;
        }
        foreach ($_form['form_fields'] as $_field) {
            if (jrCore_is_uploaded_media_file($module,$_field['name'],$profile_id)) {
                jrCore_save_media_file($module,$_field['name'],$profile_id,$unique_id,null,$_existing);
            }
        }
    }
    return true;
}

/**
 * Saves an uploaded media file to the proper profile directory
 * @param string $module Module Name to save file for
 * @param string $file_name Name of file field in form
 * @param int $profile_id the Profile ID to save the media file for
 * @param int $unique_id Unique Item ID from DataStore
 * @param string $field Field to save as (defaults to field name from file)
 * @param array $_existing Item Array (for update checking)
 * @return bool
 */
function jrCore_save_media_file($module,$file_name,$profile_id,$unique_id,$field = null,$_existing = null)
{
    global $_post;
    if (!isset($unique_id) || !jrCore_checktype($unique_id,'number_nz')) {
        return false;
    }
    // make sure this module is using a DataStore
    if (jrCore_db_get_prefix($module) === false) {
        jrCore_logger('CRI',"module: {$module} is not using a DataStore, so unable to automatically handle file uploads");
        return false;
    }
    $_up = array();

    // See if we have been given a FULLY PATH FILE - if so, that's the one we use,
    // otherwise we figure it out from the current post.
    if (isset($file_name) && is_file($file_name)) {
        // 1_audio_file_2
        if (is_null($field)) {
            list(,$field) = explode('_',basename($file_name),2);
        }
        if (is_file("{$file_name}.tmp")) {
            $fname = trim(file_get_contents("{$file_name}.tmp"));
        }
        else {
            $fname = basename($file_name);
        }
        $_up[$field] = array(
            'tmp_name' => $file_name,
            'name'     => $fname,
            'size'     => filesize($file_name),
            'type'     => jrCore_mime_type($fname),
            'error'    => 0
        );
    }
    else {
        $_up = jrCore_get_uploaded_meter_files($_post['upload_token']);
    }

    // See if we are UPDATING an existing ITEM with new items
    // or replacing what is already there
    if (!is_null($_existing) && is_array($_existing)) {
        $new = 0;
        $idx = array_keys($_up);
        $idx = reset($idx);
        // we have an item - we are just adding more media to an existing item
        // we need to cycle through keys to find our new index
        foreach ($_existing as $k => $v) {
            if (strpos($k,$idx) === 0 && strpos($k,'_size')) {
                // we have a match - find it's index
                list(,,$num,) = explode('_',$k);
                if (is_numeric($num) && $num > $new) {
                    $new = $num;
                }
            }
        }
        if ($new > 0) {
            $new++;
            $_nw = array();
            foreach ($_up as $v) {
                $_nw["{$idx}_{$new}"] = $v;
                $new++;
            }
            $_up = $_nw;
            unset($_nw);
        }
    }

    // Not uploaded...
    if (!isset($_up) || !is_array($_up) || count($_up) === 0) {
        return false;
    }

    $_data = false;
    // Save off each media file that was uploaded
    foreach ($_up as $fname => $_file) {

        $ext = jrCore_file_extension($_file['name']);
        // If we do NOT have a file extension, we need to grab the mime type and add the file extension on
        if (!isset($ext) || strlen($ext) === 0 || strlen($ext) > 4) {
            $typ = jrCore_mime_type($_file['tmp_name']);
            $ext = jrCore_file_extension_from_mime_type($typ);
        }

        $nam = "{$module}_{$unique_id}_{$fname}.{$ext}";
        if (!jrCore_write_media_file($profile_id,$nam,$_file['tmp_name'],'public')) {
            jrCore_logger('CRI',"error saving media file: {$profile_id}/{$nam}");
            return false;
        }

        $pdir = jrCore_get_media_directory($profile_id);
        $file = "{$pdir}/{$nam}";

        // We need to delete any OLD files that this new
        // file has replaced (if extension changes)
        $_old = glob("{$pdir}/{$module}_{$unique_id}_{$fname}.*");
        if (isset($_old) && is_array($_old)) {
            foreach ($_old as $old_file) {
                if ($old_file != $file) {
                    unlink($old_file);
                }
            }
        }

        // Okay we've save it.  Next, we need to update the datastore
        // entry with the info from the file
        $save_name = $_file['name'];
        if (!strpos($save_name,".{$ext}")) {
            $save_name = "{$save_name}.{$ext}";
        }
        $_data = array(
            "{$fname}_time"      => time(),
            "{$fname}_name"      => $save_name,
            "{$fname}_size"      => $_file['size'],
            "{$fname}_type"      => jrCore_mime_type($file),
            "{$fname}_extension" => $ext,
            "{$fname}_access"    => '1'  // 0 = creator only, 1 = private view/stream only, 2 = private view/stream/download, 3 = public view/stream only, 4 = public view/stream/download
        );

        // We have some extra info we want to make available to our listeners,
        // but we don't want it to be part of the data
        $_args = array(
            'module'     => $module,
            'file_name'  => $fname,
            'profile_id' => $profile_id,
            'unique_id'  => $unique_id,
            'saved_file' => $file
        );

        // Trigger our save media file event
        $_data = jrCore_trigger_event('jrCore','save_media_file',$_data,$_args);
        jrCore_db_update_item($module,$unique_id,$_data);
    }
    return $_data;
}

/**
 * jrCore_get_uploaded_meter_files
 * @param string $token Form Token
 * @return array
 */
function jrCore_get_uploaded_meter_files($token)
{
    $_up = false;
    // See if we have already processed this form ID on this load
    $tmp = jrCore_get_flag('jrcore_save_media_file_processed');
    if ($tmp) {
        return $tmp;
    }
    $dir = jrCore_get_module_cache_dir('jrCore');
    if (is_dir("{$dir}/{$token}")) {
        // We've got uploaded files via the progress meter
        $_tmp = glob("{$dir}/{$token}/*");
        // [0] => /Users/brianj/Sites/Jamroom5/core/data/cache/jrCore/12046f3177d5079e5528aa7a34175c73/1_audio_file       <- contains actual file
        // [1] => /Users/brianj/Sites/Jamroom5/core/data/cache/jrCore/12046f3177d5079e5528aa7a34175c73/1_audio_file.tmp   <- contains file name
        if (isset($_tmp) && is_array($_tmp)) {
            $_up = array();
            foreach ($_tmp as $file) {
                if (is_file($file)) {
                    $ext = jrCore_file_extension($file);
                    if (isset($ext) && $ext == 'tmp') {
                        list($f_num,$field) = explode('_',basename($file),2);
                        $field = str_replace('.tmp','',$field);
                        $fname = file_get_contents($file);
                        $fdata = "{$dir}/{$token}/{$f_num}_{$field}";
                        if (is_file($fdata)) {
                            $key = $field;
                            if ($f_num > 1 && is_file("{$dir}/{$token}/multi.txt")) {
                                $key = "{$field}_{$f_num}";
                            }
                            $_up[$key] = array(
                                'tmp_name' => $fdata,
                                'name'     => $fname,
                                'size'     => filesize($fdata),
                                'type'     => jrCore_mime_type($fname),
                                'error'    => 0
                            );
                        }
                    }
                }
            }
        }
    }
    jrCore_set_flag('jrcore_save_media_file_processed',$_up);
    return $_up;
}

/**
 * jrCore_get_active_media_system
 * @return string
 */
function jrCore_get_active_media_system()
{
    global $_conf;
    if (isset($_conf['jrCore_active_media_system']{1})) {
        return $_conf['jrCore_active_media_system'];
    }
    return 'jrCore_local';
}

/**
 * jrCore_get_media_directory
 * @param int $profile_id Profile ID to get media directory for
 * @return bool
 */
function jrCore_get_media_directory($profile_id)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_get_directory";
    if (function_exists($func)) {
        return $func($profile_id);
    }
    jrCore_logger('CRI',"jrCore_get_media_directory: required function: {$func} does not exist!");
    return false;
}

/**
 * jrCore_create_media_directory
 * @param int $profile_id Profile ID to create media directory for
 * @return bool
 */
function jrCore_create_media_directory($profile_id)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_create_directory";
    if (function_exists($func)) {
        return $func($profile_id);
    }
    jrCore_logger('CRI',"jrCore_create_media_directory: required function: {$func} does not exist!");
    return false;
}

/**
 * jrCore_delete_media_directory
 * @param int $profile_id Profile ID to create media directory for
 * @return bool
 */
function jrCore_delete_media_directory($profile_id)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_delete_directory";
    if (function_exists($func)) {
        return $func($profile_id);
    }
    jrCore_logger('CRI',"jrCore_delete_media_directory: required function: {$func} does not exist!");
    return false;
}

/**
 * jrCore_get_media_directory_size
 * @param int $profile_id Profile ID to create media directory for
 * @return int
 */
function jrCore_get_media_directory_size($profile_id)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_get_directory_size";
    if (function_exists($func)) {
        return $func($profile_id);
    }
    jrCore_logger('CRI',"jrCore_get_media_directory_size: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_read_media_file function is a wrapper function to read a file from
 * the specified filesystem type
 *
 * @param int $profile_id Profile ID
 * @param string $file File name to read
 * @return bool Returns True/False
 */
function jrCore_read_media_file($profile_id,$file)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_read";
    if (function_exists($func)) {
        return $func($profile_id,$file);
    }
    jrCore_logger('CRI',"jrCore_read_media_file: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_write_media_file function is a wrapper function to write a file
 * the specified file system
 *
 * @param int $profile_id Profile ID
 * @param string $file File to write data to
 * @param string $data Data to write to file
 * @return bool
 */
function jrCore_write_media_file($profile_id,$file,$data)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_write";
    if (function_exists($func)) {
        return $func($profile_id,$file,$data);
    }
    jrCore_logger('CRI',"jrCore_write_media_file: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_delete_media_file function is a wrapper function to delete a file of
 * the specified file type.
 *
 * @param int $profile_id Profile ID
 * @param string $file File name to delete
 * @return bool
 */
function jrCore_delete_media_file($profile_id,$file)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_delete";
    if (function_exists($func)) {
        return $func($profile_id,$file);
    }
    jrCore_logger('CRI',"jrCore_delete_media_file: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_media_file_exists function is a wrapper function to check if a file
 * of the specified file type exists.
 *
 * @param int $profile_id Profile ID
 * @param string $file File name to check
 * @return bool
 */
function jrCore_media_file_exists($profile_id,$file)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_exists";
    if (function_exists($func)) {
        return $func($profile_id,basename($file));
    }
    jrCore_logger('CRI',"jrCore_media_file_exists: required function: {$func} does not exist!");
    return false;
}

/**
 * jrCore_media_file_stream
 *
 * @param int $profile_id Profile ID
 * @param string $file File name to Download
 * @param string $send_name File name to use in download dialog
 * @return bool
 */
function jrCore_media_file_stream($profile_id,$file,$send_name)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_stream";
    if (function_exists($func)) {
        return $func($profile_id,$file,$send_name);
    }
    jrCore_logger('CRI',"jrCore_media_file_stream: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_media_file_download function will download a media file
 *
 * @param string $profile_id Profile ID
 * @param string $file File name to Download
 * @param string $send_name File name to use in download dialog
 * @return bool
 */
function jrCore_media_file_download($profile_id,$file,$send_name)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_download";
    if (function_exists($func)) {
        return $func($profile_id,$file,$send_name);
    }
    jrCore_logger('CRI',"jrCore_media_file_download: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_copy_media_file function copies a media file
 *
 * @param string $profile_id Directory file is located in
 * @param string $source_file Source File
 * @param string $target_file Target File
 * @return bool
 */
function jrCore_copy_media_file($profile_id,$source_file,$target_file)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_copy";
    if (function_exists($func)) {
        return $func($profile_id,$source_file,$target_file);
    }
    jrCore_logger('CRI',"jrCore_copy_media_file: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrCore_rename_media_file function is a media wrapper
 *
 * @param string $profile_id Directory file is located in
 * @param string $file File old (existing) name
 * @param string $new_name File new name
 *
 * @return bool Returns True/False
 */
function jrCore_rename_media_file($profile_id,$file,$new_name)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_rename";
    if (function_exists($func)) {
        return $func($profile_id,$file,$new_name);
    }
    jrCore_logger('CRI',"jrCore_rename_media_file: required function: {$func} does not exist!");
    return false;
}

/**
 * The jrMediaFileInfo function is a media wrapper
 *
 * @param string $profile_id Directory file is located in
 * @param string $file File to stat
 * @return bool
 */
function jrCore_stat_media_file($profile_id,$file)
{
    $type = jrCore_get_active_media_system();
    $func = "_{$type}_media_stat";
    if (function_exists($func)) {
        return $func($profile_id,$file);
    }
    jrCore_logger('CRI',"jrCore_stat_media_file: required function: {$func} does not exist!");
    return false;
}

//--------------------------------------
// Local FileSystem media plugins
//--------------------------------------
/**
 * The _local_media_get_directory_group function will return the
 * directory "group" that a given profile_id belongs to.  This is
 * to overcome ext3 limitations on dirs in dirs.
 *
 * @param int $profile_id Profile ID
 * @return mixed Returns string on success, bool false on failure
 */
function _jrCore_local_media_get_directory_group($profile_id)
{
    if (isset($profile_id) && jrCore_checktype($profile_id,'number_nn')) {
        $sub = (int) ceil($profile_id / 1000);
        return $sub;
    }
    return false;
}

/**
 * Local FileSystem Get Media Directory function
 * @param int $profile_id Profile ID
 * @return string
 */
function _jrCore_local_media_get_directory($profile_id)
{
    global $_conf;
    $group_dir = _jrCore_local_media_get_directory_group($profile_id);
    $media_dir = APP_DIR ."/data/media/{$group_dir}/{$profile_id}";
    if (!is_dir($media_dir)) {
        mkdir($media_dir,$_conf['jrCore_dir_perms'],true);
    }
    return $media_dir;
}

/**
 * Local FileSystem Get Media Directory function
 * @param int $profile_id Profile ID
 * @return bool
 */
function _jrCore_local_media_create_directory($profile_id)
{
    global $_conf;
     // First our media directory
    $media_dir = _jrCore_local_media_get_directory($profile_id);

    if (!is_dir($media_dir)) {
        if (!mkdir($media_dir,$_conf['jrCore_dir_perms'],true)) {
            jrCore_logger('CRI','_local_media_create_directory: unable to create profile media directory: '. str_replace(APP_DIR .'/','',$media_dir));
            return false;
        }
    }
    if (!is_writable($media_dir)) {
        if (!chmod($media_dir,$_conf['jrCore_dir_perms'])) {
            jrCore_logger('CRI','_local_media_create_directory: unable to properly permission profile media directory: '. str_replace(APP_DIR .'/','',$media_dir));
            return false;
        }
    }
    return true;
}

/**
 * Local FileSystem Get Media Directory function
 * @param int $profile_id Profile ID
 * @return bool
 */
function _jrCore_local_media_delete_directory($profile_id)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_dir($media_dir)) {
        if (jrCore_delete_dir_contents($media_dir)) {
            rmdir($media_dir);
        }
        else {
            return false;
        }
    }
    return true;
}

/**
 * Local FileSystem Get Media Directory Size function
 * @param int $profile_id Profile ID
 * @return int
 */
function _jrCore_local_media_get_directory_size($profile_id)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    clearstatcache();
    $size = 0;
    if ($h = opendir(realpath($media_dir))) {
        while (false !== ($file = readdir($h))) {
            if ($file == '.' || $file == '..' || $file == 'cache') {
                continue;
            }
            else {
                $size += filesize("{$media_dir}/{$file}");
            }
        }
        closedir($h);
    }
    return $size;
}

/**
 * Local FileSystem Get Media Url function
 * @param int $profile_id Profile ID
 * @param string $file File Name
 * @param int $expire_seconds Seconds URL is valid for
 * @return string
 */
function _jrCore_local_media_get_media_url($profile_id,$file,$expire_seconds = false)
{
    global $_conf;
    // If we are doing a secure media URL, then it passes through
    // our media stream wrapper
    if (isset($expire_seconds) && jrCore_checktype($expire_seconds,'number_nz')) {

        $murl = jrCore_get_module_url('jrCore');
        $file = rawurlencode($file);
        $expr = (time() + $expire_seconds);
        $path = hash_hmac('sha1',"{$expr}/{$file}",jrCore_get_ip());
        $path = urlencode($path);

        // Create our URL
        $proto = jrCore_get_server_protocol();
        $url   = "{$_conf['jrCore_base_url']}/{$murl}/get_file/pid={$profile_id}/key={$path}/expr={$expr}/file={$file}";
        if (isset($proto) && $proto != 'http') {
            $url = str_replace('http://',"{$proto}://",$url);
        }
    }
    else {
        // Direct URL to media item
        $group_dir = _jrCore_local_media_get_directory_group($profile_id);
        $proto     = jrCore_get_server_protocol();
        $media_url = $_conf['jrCore_base_url'] ;
        if (isset($proto) && $proto != 'http') {
            $media_url = str_replace('http://',"{$proto}://",$media_url);
        }
        $url = "{$media_url}/data/media/{$group_dir}/{$profile_id}/{$file}";
    }
    return $url;
}

/**
 * TODO: This is a bad idea for large files - find a way to not use
 * Local FileSystem Read Function
 * @param int $profile_id Profile ID
 * @param string $file File Name
 * @return string
 */
function _jrCore_local_media_read($profile_id,$file)
{
    if (is_file("{$profile_id}/{$file}")) {
        return file_get_contents("{$profile_id}/{$file}");
    }
    return false;
}

/**
 * Local FileSystem Write Function
 * NOTE: This function will set permissions on the created/updated file to 0644
 * @param int $profile_id Profile ID
 * @param string $file File Name to write to
 * @param string $data Data to write to file
 * @return bool
 */
function _jrCore_local_media_write($profile_id,$file,$data)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file($data)) {
        if (copy($data,"{$media_dir}/{$file}")) {
            return true;
        }
    }
    else {
        if (jrCore_write_to_file("{$media_dir}/{$file}",$data,'overwrite')) {
            return true;
        }
    }
    return false;
}

/**
 * Local FileSystem Delete Function
 * @ignore used internally
 */
function _jrCore_local_media_delete($profile_id,$file)
{
    global $_conf;
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file("{$media_dir}/{$file}")) {
        $tmp = @unlink("{$media_dir}/{$file}");
        if (!$tmp) {
            // try to change permissions and try again
            chmod("{$media_dir}/{$file}",$_conf['jrCore_file_perms']);
            $tmp = @unlink("{$media_dir}/{$file}");
        }
        return $tmp;
    }
    elseif (is_file($file) && strpos($file,$media_dir) === 0) {
        // We've been given a full path file - handle it
        $tmp = @unlink($file);
        if (!$tmp) {
            // try to change permissions and try again
            chmod($file,$_conf['jrCore_file_perms']);
            $tmp = @unlink($file);
        }
        return $tmp;
    }
    return false;
}

/**
 * Local FileSystem Exist function
 * @ignore used internally
 */
function _jrCore_local_media_exists($profile_id,$file)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file("{$media_dir}/{$file}")) {
        return true;
    }
    return false;
}

/**
 * Local FileSystem Stream function
 * NOTE: Sends HEADERS!
 * @param int $profile_id Profile ID
 * @param string $file File to Stream
 * @param string $send_name Send-As Filename
 * @return bool
 */
function _jrCore_local_media_stream($profile_id,$file,$send_name)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (!is_file("{$media_dir}/{$file}")) {
        return false;
    }
    // Send headers to initiate download prompt
    $size = filesize("{$media_dir}/{$file}");
    $type = jrCore_mime_type("{$media_dir}/{$file}");

    if (isset($_SERVER['HTTP_RANGE']))  {
        _jrCore_local_media_stream_with_range("{$media_dir}/{$file}");
        return true;
    }
    else {
        header('Content-Length: '. $size);
        header('Content-Type: '. $type);
        header('Connection: close');
    }

    $handle = fopen("{$media_dir}/{$file}",'rb');
    if (!$handle) {
        jrCore_logger('CRI',"local_media_stream: unable to create file handle for streaming: {$media_dir}/{$file}");
        return false;
    }
    $bytes_sent = 0;
    while (true) {
        fseek($handle,$bytes_sent);
        // Read 1 megabyte at a time...
        $buffer = fread($handle,1048576);
        $bytes_sent += strlen($buffer);
        echo $buffer;
        flush();
        unset($buffer);
        // Support up to 50MB per second
        usleep(20000);
        // Also - check that we have not sent out more data then the allowed size
        if ($bytes_sent >= $size) {
            fclose($handle);
            return true;
        }
    }
    fclose($handle);
    return true;
}

/**
 * Stream a file to the iPhone with RANGE support
 * @param $file
 */
function _jrCore_local_media_stream_with_range($file)
{
    $fp     = @fopen($file, 'rb');
    $size   = filesize($file); // File size
    $length = $size;           // Content length
    $start  = 0;               // Start byte
    $end    = $size - 1;       // End byte

    // Send the accept range header
    header("Accept-Ranges: 0-$length");
    if (isset($_SERVER['HTTP_RANGE'])) {
        $c_end  = $end;
        // Extract the range string
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            // (?) Echo some info to the client?
            exit;
        }
        // If the range starts with an '-' we start from the beginning
        // If not, we forward the file pointer
        // And make sure to get the end byte if specified
        if ($range[0] == '-') {
            // The n-number of the last bytes is requested
            $c_start = $size - substr($range, 1);
        }
        else {
            $range  = explode('-', $range);
            $c_start = $range[0];
            $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        // End bytes can not be larger than $end.
        $c_end = ($c_end > $end) ? $end : $c_end;
        // Validate the requested range and return an error if it's not correct.
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes {$start}-{$end}/{$size}");
            exit;
        }
        $start  = $c_start;
        $end    = $c_end;
        $length = $end - $start + 1; // Calculate new content length
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }
    // Notify the client the byte range we'll be outputting
    header("Content-Range: bytes {$start}-{$end}/{$size}");
    header("Content-Length: {$length}");

    // Start buffered download
    $buffer = 1024 * 8;
    while(!feof($fp) && ($p = ftell($fp)) <= $end) {
        if ($p + $buffer > $end) {
            $buffer = $end - $p + 1;
        }
        set_time_limit(0); // Reset time limit for big files
        echo fread($fp, $buffer);
        flush();
    }
    fclose($fp);
}

/**
 * Local FileSystem Download function
 * NOTE: Sends HEADERS!
 * @param int $profile_id Profile ID
 * @param string $file File to Download
 * @param string $send_name Send-As Filename
 * @return bool
 */
function _jrCore_local_media_download($profile_id,$file,$send_name)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (!is_file("{$media_dir}/{$file}")) {
        return false;
    }
    // Send headers to initiate download prompt
    $size = filesize("{$media_dir}/{$file}");
    header('Content-Length: '. $size);
    header('Connection: close');
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="'. $send_name .'"');

    $handle = fopen("{$media_dir}/{$file}",'rb');
    if (!$handle) {
        jrCore_logger('CRI',"local_media_download: unable to create file handle for download: {$media_dir}/{$file}");
        return false;
    }
    $bytes_sent = 0;
    while ($bytes_sent < $size) {
        fseek($handle,$bytes_sent);
        // Read 1 megabyte at a time...
        $buffer = fread($handle,1048576);
        $bytes_sent += strlen($buffer);
        echo $buffer;
        flush();
        unset($buffer);
        // Support up to 10MB per second
        usleep(100000);
        // Also - check that we have not sent out more data then the allowed size
        if ($bytes_sent >= $size) {
            fclose($handle);
            return true;
        }
    }
    fclose($handle);
    return true;
}

/**
 * Local FileSystem Copy function
 * @ignore used internally
 */
function _jrCore_local_media_copy($profile_id,$source_file,$target_file)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file($source_file)) {
        if (copy($source_file,"{$media_dir}/{$target_file}")) {
            return true;
        }
    }
    return false;
}

/**
 * Local FileSystem Rename function
 * @ignore used internally
 */
function _jrCore_local_media_rename($profile_id,$file,$new_name)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file($file)) {
        if (rename($file,"{$media_dir}/{$new_name}")) {
            return true;
        }
        if (copy($file,"{$media_dir}/{$new_name}")) {
            unlink($file);
            return true;
        }
    }
    return false;
}

/**
 * Local FileSystem Stat function
 * @ignore used internally
 */
function _jrCore_local_media_stat($profile_id,$file)
{
    $media_dir = _jrCore_local_media_get_directory($profile_id);
    if (is_file("{$media_dir}/{$file}")) {
        return stat("{$media_dir}/{$file}");
    }
    return false;
}
