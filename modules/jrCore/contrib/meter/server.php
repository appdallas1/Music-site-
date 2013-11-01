<?php

// make sure we are not being called directly
defined('APP_DIR') or exit();

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */

    function save($path) {
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);

        if ($realSize != $this->getSize()){
            return false;
        }

        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        return true;
    }
    function getName() {
        return $_REQUEST['field_name'];
    }
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];
        } else {
            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;
	private $uploadName;

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760){
        $allowedExtensions = array_map("strtolower", $allowedExtensions);

        $this->allowedExtensions = $allowedExtensions;
        $this->sizeLimit = $sizeLimit;

        $this->checkServerSettings();

        if (isset($_REQUEST['field_name'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false;
        }
    }

	public function getUploadName(){
		if( isset( $this->uploadName ) )
			return $this->uploadName;
	}

	public function getName(){
		if ($this->file)
			return $this->file->getName();
	}

    private function checkServerSettings(){
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");
        }
    }

    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = true)
    {
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        $size = $this->file->getSize();
        if (isset($size) && $size == 0) {
            return array('error' => 'File is empty');
        }
        elseif (isset($size) && $size > $this->sizeLimit) {
            return array('error' => 'File is too large. size('.$size.') size_limit('.$this->sizeLimit.')');
        }

        $nam = $_REQUEST['field_name'];
        $ext = jrCore_file_extension($_REQUEST[$nam]);
        $filename = $_REQUEST[$nam];
        $these = implode(', ', $this->allowedExtensions);

        // Check for valid extension
        if ($this->allowedExtensions && !in_array($ext,$this->allowedExtensions)){
            return array('error' => 'File has an invalid extension ('. $ext .') - it should be one of '. $these . '.');
        }

        // Copy the file to our temp directory
        // See if are doing multiple uploads for the same "field" - if we are, we need
        // to increment the field number each time
        $count = 1;
        if (isset($_REQUEST['multiple']) && $_REQUEST['multiple'] == 'true') {
            $count  = 0;
            $c_file = $uploadDirectory .'/multi.txt';
            // see what index number we are on
            if (is_file($c_file)) {
                $count = file_get_contents($c_file);
            }
            $count++;
            jrCore_write_to_file($c_file,$count);
        }
        $fname = $uploadDirectory . $count .'_'. $_REQUEST['upload_name'];
        if ($this->file->save($fname)) {
            jrCore_write_to_file($fname .'.tmp',$filename);
            // Make sure this is a valid file type for the extension
            switch (strtolower($ext)) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'gif':
                    if (!getimagesize($fname)) {
                        unlink($fname);
                        unlink($fname .'.tmp');
                        return array('error' => 'Invalid image file - it should be one of '. $these . '.');
                    }
                    break;
            }
            return array('success'=>true);
        }
        return array('error'=> 'Could not save uploaded file - upload was cancelled or server error encountered');
    }
}
