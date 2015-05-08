<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package     LC\Helpers\File
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * @ignore Flag for image resize to the fitted dimension to the given dimension
 */
define('FILE_RESIZE_BOTH', 'both');
/**
 * @ignore Flag for image resize to the given height, but width is aspect ratio of the height
 */
define('FILE_RESIZE_HEIGHT', 'height');
/**
 * @ignore Flag for image resize to the given width, but height is aspect ratio of the width
 */
define('FILE_RESIZE_WIDTH', 'width');
/**
 * @ignore File upload error flag for the failure of `move_uploaded_file()`
 */
define('FILE_UPLOAD_ERR_MOVE', 100);
/**
 * @ignore File upload error flag for the failure of image creation of GD functions
 */
define('FILE_UPLOAD_ERR_IMAGE_CREATE', 101);

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for file processing system
 */
class File {
	/** @var string The uniqued name string for this instance */
	private $name;
	/** @var string The uniqued string ID to append to the file name */
	private $uniqueId;
	/** @var array The dimension to be created for image upload */
	private $dimensions;
	/** @var string The upload directory path */
	private $uploadPath;
	/** @var const Type of file resize */
	private $resize = FILE_RESIZE_BOTH;
	/** @var string The original uploaded file name */
	private $originalFileName;
	/** @var string The file name generated */
	private $fileNameBased;
	/** @var array The uploaded file information */
	private $uploads;
	/** @var array The file upload error information */
	private $error;

	/**
	 * Constructor
	 * @param string $name The unique name
	 */
	public function File($name='') {
		$this->uniqueId = $this->getUniqueId();
		$this->name = ($name) ? $name : $this->uniqueId;
	}
	/**
	 * Setter for the class properties
	 * @param string $key The property name
	 * @param mixed $value The value to be set
	 * @return void
	 */
	public function set($key, $value) {
		# if $uniqueId is explicitly given and $name was not explicity given
		# make $name and $uniqueId same
		if ($key === 'uniqueId' && $value & $this->name === $this->uniqueId) {
			$this->name = $value;
		}
		$this->{$key} = $value;
	}
	/**
	 * Getter for the class properties
	 * @param string $key The property name
	 * @return mixed $value The value of the property or null if $name does not exist.
	 */
	public function get($key) {
		if (isset($this->{$key})) {
			return $this->{$key};
		}
		return null;
	}
	/**
	 * Getter for the orignal uploaded file name
	 */
	public function getOriginalFileName() {
		return $this->originalFileName;
	}
	/**
	 * Getter for the file name generated
	 */
	public function getFileNameBased() {
		return $this->fileNameBased;
	}
	/**
	 * Getter for the property `error`
	 * @return array The array of error information
	 *
	 *     array(
	 *       'code' => 'Error code',
	 *       'message' => 'Error message'
	 *     )
	 *
	 */
	public function getError() {
		return $this->error;
	}
	/**
	 * Get file upload error message for the given error code
	 * @param  int $code The error code
	 * @return string The error message
	 */
	public function getErrorMessage($code) {
		switch ($code) {
			case UPLOAD_ERR_INI_SIZE:
				$message = _t('The uploaded file exceeds the upload_max_filesize directive in php.ini.');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = _t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.');
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = _t('The uploaded file was only partially uploaded.');
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = _t('No file was uploaded.');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = _t('Missing a temporary folder.');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = _t('Failed to write file to disk.');
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = _t('File upload stopped by extension.');
				break;
			case FILE_UPLOAD_ERR_MOVE:
				$message = _t('The uploaded file is not valid.');
				break;
			case FILE_UPLOAD_ERR_IMAGE_CREATE:
				$message = _t('Failed to create image from the uploaded file.');
				break;
			default:
				$message = _t('Unknown upload error.');
				break;
		}
		return $message;
	}
	/**
	 * Move the uploaded file into the given directory.
	 * If the uploaded file is image, this will create the various images according to the given $dimension
	 *
	 * @param array $file The uploaded file information from $_FILES['xxx']
	 *
	 * @return  array The array of the uploaded file information:
	 *
	 *     array(
	 *       'name'     => 'Name of the input element',
	 *       'fileName' => 'The original file name',
	 *       'extension'=> 'The selected and uploaded file extension',
	 *       'dir'      => 'The uploaded directory',
	 *       'uploads'  => array(
	 *         'dimension (WxH) or index' => 'The uploaded file name like return from basename()'
	 *       )
	 *     )
	 *
	 */
	public function upload($file) {
		$fileName     = stripslashes($file['name']);
		$uploadedFile = $file['tmp_name'];
		$info         = pathinfo($fileName);
		$extension    = strtolower($info['extension']);
		$uploaded     = null;
		$path         = $this->uploadPath;

		if ($fileName && $file['error'] === UPLOAD_ERR_OK) {
			$this->originalFileName = $fileName;

			if ( !(is_array($this->dimensions) && count($this->dimensions)) ) {
				$newFileName = $this->getNewFileName($fileName);
				if (move_uploaded_file($uploadedFile, $path . $newFileName)) {
					$uploaded = array($newFileName);
				} else {
					$this->error = array(
						'code' => FILE_UPLOAD_ERR_MOVE,
						'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_MOVE)
					);
				}
			} else {
				if ($extension == "jpg" || $extension == "jpeg" ) {
					$img = imagecreatefromjpeg($uploadedFile);
				} elseif ($extension == "png") {
					$img = imagecreatefrompng($uploadedFile);
				} elseif ($extension == "gif") {
					$img = imagecreatefromgif ($uploadedFile);
				}

				if ( isset($img) && $img ) {
					$uploaded = array();
					foreach ($this->dimensions as $dimension) {
						$resize = explode('x', $dimension);
						$resizeWidth 	= $resize[0];
						$resizeHeight 	= $resize[1];

						if ($this->resize == FILE_RESIZE_WIDTH) {
							$tmp = File::resizeImageWidth($img, $uploadedFile, $resizeWidth);
						} elseif ($this->resize == FILE_RESIZE_HEIGHT) {
							$tmp = File::resizeImageHeight($img, $uploadedFile, $resizeHeight);
						} else {
							$tmp = File::resizeImage($img, $uploadedFile, $resizeWidth, $resizeHeight);
						}

						$newFileName = $this->getNewFileName($fileName, $resizeWidth);

						if ($extension == "gif") {
							imagegif ($tmp, $path . $newFileName);
						} elseif ($extension == "png") {
							imagepng($tmp, $path . $newFileName);
						} else {
							imagejpeg($tmp, $path . $newFileName, 100);
						}

						imagedestroy($tmp);
						$uploaded[$dimension] = $newFileName;
					}
					if ($img) imagedestroy($img);
				} else {
					$this->error = array(
						'code' => FILE_UPLOAD_ERR_IMAGE_CREATE,
						'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_IMAGE_CREATE)
					);
				}
			}
		} else {
			$this->error = array(
				'code' => $file['error'],
				'message' => $this->getErrorMessage($file['error'])
			);
		}

		if ($uploaded) {
			$this->uploads = array(
				'name'     => $this->name,
				'fileName' => $this->originalFileName,
				'extension'=> $extension,
				'dir'      => $this->uploadPath,
				'uploads'  => $uploaded
			);
		}

		return $this->uploads;
	}
	/**
	 * Get a new file name, e.g., original-file-name-[imageWidth]-[uniqueId].ext
	 * Spaces and periods in the original file names are replaced with dashes.
	 *
	 * @param string $file The uploaded file name
	 * @param int $width The image width if the uploaded file is image, otherwise 0
	 *
	 * @return string The file name
	 */
	private function getNewFileName($file, $width=0) {
		$info = pathinfo($file);
		# replace spaces, periods and underscores with dashes
		$justName = str_replace(array(' ', '.', '_'), '-', $info['filename']);
		# clean special characters
		$sChars   = array('"', "'", ',', '~', '`', '!', '@', '#', '$', '%', '&', '*', '(', ')', '[', ']', '{', '}', '|', '\\', '/');
		$justName = str_replace($sChars, '', $justName);
		# remove continuous dashes
		$justName = preg_replace('/[\-]+/', '-', $justName);
		# add uffix to the file name
		$suffix = '';
		if ($width) $suffix .= '-' . $width;
		$suffix .= '-' . $this->uniqueId;
		$fileName = $justName . $suffix . '.' . $info['extension'];
		$this->fileNameBased = $justName . '-' . $this->uniqueId . $info['extension'];
		return $fileName;
	}
	/**
	 * Get a unique id string from the property $uniqueId or generate a random 5-letters string
	 * @return string
	 */
	private function getUniqueId() {
		if ($this->uniqueId) return $this->uniqueId;
		else return substr(md5(time()), 0, 5);
	}
	/**
	 * Resize an image to a desired width and height by given width
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newWidth The new width to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImageWidth(&$img, $file, $newWidth) {
		list($width, $height) = getimagesize($file);
		$newHeight = ($height/$width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given height
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newHeight The new height to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImageHeight(&$img, $file, $newHeight) {
		list($width, $height) = getimagesize($file);
		$newWidth = ($width/$height) * $newHeight;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given width and height
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newWidth The new width to resize
	 * @param int $newHeight The new height to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImage(&$img, $file, $newWidth, $newHeight) {
		list($width, $height) = getimagesize($file);

		$scale = min($newWidth/$width, $newHeight/$height);
		# If the image is larger than the max shrink it
		if ($scale < 1) {
			# new width for the image
			$newWidth = floor($scale * $width);
			# new heigth for the image
			$newHeight = floor($scale * $height);
		} else {
		# if the image is small than than the resized width and height
			$newWidth = $width;
			$newHeight = $height;
		}

		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Display an image fitting into the desired dimension
	 *
	 * @param string $fileName The file name with an absolute web path
	 * @param string $caption The image caption
	 * @param int $dimension The actual image dimension in "widthxheight"
	 * @param string $desiredDimension The desired dimension in "widthxheight"
	 * @param array $attributes The HTML attributes in array like key => value
	 *
	 * @return string The <img> tag
	 */
	public static function img($fileName, $caption, $dimension, $desiredDimension='0x0', $attributes=array()) {
		$regex = '/^[0-9]+x[0-9]+$/i'; # check the format of "99x99" for the dimensions
		if (!preg_match($regex, $dimension)) {
			echo '';
			return NULL;
		}
		if (!preg_match($regex, $desiredDimension)) {
			$desiredDimension = '0x0';
		}
		list($imgWidth, $imgHeight) = explode('x', strtolower($dimension));
		list($desiredWidth, $desiredHeight) = explode('x', strtolower($desiredDimension));

		if ($imgWidth > $desiredWidth || $imgHeight > $desiredHeight) { # scale down
			if ($desiredWidth == 0 && $desiredHeight > 0) { # resized to height
				$desiredWidth 	= floor(($imgWidth/$imgHeight) * $desiredHeight);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;
			} elseif ($desiredWidth > 0 && $desiredHeight == 0) { # resized to width
				$desiredHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;
			} elseif ($desiredWidth > 0 && $desiredHeight > 0) { # resized both
				$scale = min($desiredWidth/$imgWidth, $desiredHeight/$imgHeight);
				# new width for the image
				$imgWidth  = floor($scale * $imgWidth);
				# new heigth for the image
				$imgHeight = floor($scale * $imgHeight);
				if ($imgWidth < $desiredWidth || $imgHeight < $desiredHeight) {
					$wDiff = $desiredWidth - $imgWidth;
					$hDiff = $desiredHeight - $desiredWidth;
					if ($wDiff > $hDiff) { # resize to width
						$imgHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
						$imgWidth  = $desiredWidth;
					} else { # resize to height
						$imgWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
						$imgHeight = $desiredHeight;
					}
				}
			} else { # if the desired dimension is not given
				$desiredWidth = $imgWidth;
				$desiredHeight = $imgHeight;
			}
		}

		$style = '';
		if ($imgWidth > $desiredWidth) {
			$marginH = floor(($imgWidth - $desiredWidth)/2);
			$style = 'margin-left:-'.$marginH.'px';
		}
		if ($imgHeight > $desiredHeight) {
			$marginV = floor(($imgHeight - $desiredHeight)/2);
			$style = 'margin-top:-'.$marginV.'px';
		}
		if (isset($attributes['style']) && $attributes['style']) {
			$style .= $attributes['style'];
		}
		$attributes['src']		= $fileName;
		$attributes['alt'] 		= _h($caption);
		$attributes['title'] 	= _h($caption);
		$attributes['width'] 	= $imgWidth;
		$attributes['height'] 	= $imgHeight;
		$attributes['style'] 	= $style;

		$attrHTML = '';
		foreach ($attributes as $key => $value) {
			$attrHTML .= ' ' . $key . '="' . $value .'"';
		}
		return '<img '.$attrHTML.' />';
	}
}

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for ajax-like file upload with instant preview if the preview placeholder is provided
 * @since PHPLucidFrame v 1.3.0
 */
class AsynFileUploader {
	/** @var string The input name or the POST name */
	private $name;
	/** @var string The HTML id of the file browsing button */
	private $id;
	/** @var string The input label name that shown to the user */
	private $label;
	/** @var string The button caption */
	private $caption;
	/** @var string The uploaded file name */
	private $value;
	/** @var string The directory path where the file to be uploaded permenantly */
	private $uploadDir;
	/** @var array The allowed file extensions; defaults to jpg, jpeg, png, gif */
	private $extensions;
	/** @var int The maximum file size allowed to upload in MB */
	private $maxSize;
	/** @var int The maximum file dimension */
	private $dimension;
	/** @var string URL that handles the file uploading process */
	private $uploadHandler;
	/** @var array Array of HTML ID of the buttons to be disabled while uploading */
	private $buttons;
	/** @var boolean Enable ajax file delete or not */
	private $isDeletable;
	/** @var boolean The uploaded file name is displayed or not */
	private $fileNameIsDisplayed;
	/** @var string The hook name that handles file upload process interacting the database layer */
	private $onUpload;
	/** @var string The hook name that handles file deletion process interacting the database layer */
	private $onDelete;

	/**
	 * Constructor
	 *
	 * @param string/array anonymous The input file name or The array of property/value pairs
	 */
	public function AsynFileUploader() {
		$this->name                 = 'file';
		$this->id                   = '';
		$this->label                = _t('File');
		$this->caption              = _t('Choose File');
		$this->value                = array();
		$this->maxSize              = 10;
		$this->extensions           = array();
		$this->uploadDir            = FILE . 'tmp' . _DS_;
		$this->buttons              = array();
		$this->dimensions           = '';
		$this->uploadHandler        = WEB_ROOT . 'inc/asyn-file-uploader.php';
		$this->isDeletable          = true;
		$this->fileNameIsDisplayed  = true;
		$this->onUpload             = '';
		$this->onDelete             = '';

		if (func_num_args()) {
			$arg = func_get_arg(0);
			if (is_string($arg)) {
				$this->name = $arg;
			} elseif (is_array($arg)) {
				foreach ($arg as $key => $value) {
					if (isset($this->{$key})) {
						$this->{$key} = $value;
					}
				}
			}
		}
	}
	/**
	 * Setter for the property `name`
	 * @param string $name The unique name for the file input element
	 */
	public function setName($name) {
		$this->name = $name;
	}
	/**
	 * Setter for the property `id`
	 * @param string $id The unique HTML id for the file browsing button
	 */
	public function setId($id) {
		$this->id = $id;
	}
	/**
	 * Setter for the property `label`
	 * @param string $label The caption name for the file input to use in validation error
	 */
	public function setLabel($label) {
		$this->label = $label;
	}
	/**
	 * Setter for the property `caption`
	 */
	public function setCaption($caption) {
		$this->caption = $caption;
	}
	/**
	 * Setter for the property `value`
	 * @param array $value The array of file name(s)
	 *
	 *      array(
	 *        'id-saved-in-db' => 'file name like return from basename()'
	 *      )
	 *
	 *   or
	 *
	 *      array(
	 *        'id-saved-in-db' => array(
	 *          'file name like return from basename()',
	 *          'file name like return from basename()'
	 *        )
	 *      )
	 *
	 *   or
	 *
	 *      array(
	 *        'id1-saved-in-db' => 'file name like return from basename()'
	 *        'id2-saved-in-db' => 'file name like return from basename()'
	 *      )
	 *
	 */
	public function setValue($value) {
		if (is_array($value)) {
			$this->value = $value;
		}
	}
	/**
	 * Setter for the property `value` for each value
	 * @param mixed  $id    ID saved in db
	 * @param string $value The file name like return from `basename()`
	 */
	public function addValue($id, $value) {
		$this->value[$id] = $value;
	}
	/**
	 * Setter for the property `uploadDir`
	 * @param string $dir The directory where the file will be uploaded. Default to /files/tmp/
	 */
	public function setUploadDir($dir) {
		$this->uploadDir = $dir;
	}
	/**
	 * Setter for the property `maxSize`
	 * @param int $size The maximum file size allowed in MB
	 */
	public function setMaxSize($size) {
		$this->maxSize = $size;
	}
	/**
	 * Setter for the property `extensions`
	 */
	public function setExtensions($extensions) {
		$this->extensions = $extensions;
	}
	/**
	 * Setter for the property `dimensions`
	 */
	public function setDimensions($dimensions) {
		$this->dimensions = $dimensions;
	}
	/**
	 * Setter for the property `buttons`
	 * @param string $arg1[,$arg2,$arg3,...] The HTML element ID for each button
	 */
	public function setButtons() {
		$this->buttons = func_get_args();
	}
	/**
	 * Setter for the property `isDeletable`
	 * @param boolean $value If the delete button is provided or not
	 */
	public function isDeletable($value) {
		$this->isDeletable = $value;
	}
	/**
	 * Setter for the property `fileNameIsDisplayed`
	 * @param boolean $value If the uploaded file name is displayed next to the button or not
	 */
	public function isFileNameDisplayed($value) {
		$this->fileNameIsDisplayed = $value;
	}
	/**
	 * Setter for the `onUpload` hook
	 * @param string $callable The callback PHP function name
	 */
	public function setOnUpload($callable) {
		$this->onUpload = $callable;
	}
	/**
	 * Setter for the `onDelete` hook
	 * @param string $callable The callback PHP function name
	 */
	public function setOnDelete($callable) {
		$this->onDelete = $callable;
	}
	/**
	 * Setter for the proprty `uploadHandler`
	 * @param string $url The URL where file upload will be handled
	 */
	private function setUploadHandler($url) {
		$this->uploadHandler = $url;
	}
	/**
	 * Get a uploaded file name of the largest dimension if image
	 * otherwise just return the file
	 * @param  array  $values The optional array of file names
	 * @return string The file name
	 */
	private function getAFile($values=NULL) {
		if (is_null($values)) {
			$values = array_values($this->value);
		}

		if (count($values) === 0) {
			return '';
		}

		if (is_array($this->dimensions) && count($this->dimensions)) { # image file
			$maxWidth = 0;
			$fileName = '';
			if (is_array($values[0])) {
				$values = $values[0];
			}
			foreach ($values as $value) {
				if (!file_exists($this->uploadDir . $value)) {
					continue;
				}
				$parts    = pathinfo($this->uploadDir . $value);
				$justName = explode('-', $parts['filename']);
				$uniqueId = array_pop($justName); # pop the last element from the array
				$width    = array_pop($justName); # pop the second last element from the array
				if ($width > $maxWidth) {
					$maxWidth = $width;
					$fileName = $value;
				}
			}
			return $fileName;
		} else { # non-image file
			return array_pop($values);
		}
	}
	/**
	 * Display file input HTML
	 * @param array $attributes The HTML attribute option for the button
	 *
	 *     array(
	 *       'class' => '',
	 *       'id' => '',
	 *       'title' => ''
	 *     )
	 *
	 */
	public function html($attributes=array()) {
		$name = $this->name;
		$maxSize = $this->maxSize * 1024 * 1024; # convert to bytes

		# HTML attribute preparation for the file browser button
		$attrHTML = array();
		$htmlIdForButton = false;
		$htmlClassForButton = false;
		foreach ($attributes as $attrName => $attrVal) {
			$attrName = strtolower($attrName);
			if ($attrName === 'class' && $attrVal) {
				$htmlClassForButton = true;
				$attrVal = 'asynfileuploader-button '.$attrVal;
			}
			if ($attrName === 'id' && $attrVal) {
				$this->id = $attrVal;
				$htmlIdForButton = true;
			}
			$attrHTML[] =  $attrName.'="'.$attrVal.'"';
		}
		if ($htmlIdForButton === false) {
			$this->id = 'asynfileuploader-button-'.$name;
			$attrHTML[] = 'id="'.$this->id.'"';
		}
		if ($htmlClassForButton === false) {
			$attrHTML[] = 'class="asynfileuploader-button button"';
		}
		$buttonAttrHTML = implode(' ', $attrHTML);

		$args   = array();
		$args[] = 'name=' . $name;
		$args[] = 'id=' . $this->id;
		$args[] = 'label=' . $this->label;
		$args[] = 'dir=' . base64_encode($this->uploadDir);
		$args[] = 'buttons=' . implode(',', $this->buttons);
		$args[] = 'phpCallback=' . $this->onUpload;
		$args[] = 'exts=' . implode(',', $this->extensions);
		$args[] = 'maxSize=' . $maxSize;
		if ($this->dimensions) {
			$args[] = 'dimensions=' . implode(',', $this->dimensions);
		}
		$handlerURL = $this->uploadHandler.'?'.implode('&', $args);

		# If setValue(), the file information is pre-loaded
		$currentFile    = '';
		$currentFileURL = '';
		$extension      = '';
		$uniqueId       = '';
		$ids            = array();
		$values         = array();
		$dimensions     = array();
		$webUploadDir   = str_replace('\\', '/', str_replace(ROOT, WEB_ROOT, $this->uploadDir));

		if (count($this->value)) {
			$value = $this->getAFile(); # Get a file which could be largest or the first or the only one
			if ($value) {
				$ids    = array_keys($this->value);
				$values = array_values($this->value);
				$parts           = pathinfo($this->uploadDir . $value);
				$justName        = explode('-', $parts['filename']);
				$currentFileURL  = $webUploadDir . $value;
				$extension       = $parts['extension'];
				if (is_array($this->dimensions) && count($this->dimensions)) { # image file
					$uniqueId = array_pop($justName);
					$dWidth   = array_pop($justName);
					$justName = implode('-', $justName);
					# Get dimension from the file name(s)
					$tmpDimensions = $this->dimensions;
					foreach ($values as $v) {
						$p = pathinfo($this->uploadDir . $v);
						$f = explode('-', $p['filename']);
						array_pop($f); # remove the last element, uniqueId
						$w = array_pop($f); # get the second last element, the image width
						for ($i=0; $i<count($tmpDimensions); $i++) {
							if (stristr($tmpDimensions[$i], $w.'x') !== false) {
								$dimensions[] = $tmpDimensions[$i];
								unset($tmpDimensions[$i]);
								$tmpDimensions = array_values($tmpDimensions);
								break;
							}
						}
					}
				} else { # non-image file
					$uniqueId = array_pop($justName);
					$justName = implode('-', $justName);
				}
				$currentFile = $justName . '.' . $parts['extension'];
			}
		}
		# If the generic form POST, the file information from POST is pre-loaded
		# by overwriting `$this->value`
		if (count($_POST) && isset($_POST[$name]) && is_array($_POST[$name]) &&
		   isset($_POST[$name.'-fileName']) && $_POST[$name.'-fileName'] &&
		   isset($_POST[$name.'-uniqueId']) && $_POST[$name.'-uniqueId']
		  ) {
			$post            = _post($_POST);
			$values          = $post[$name];
			$ids             = (isset($post[$name.'-id']) && count($post[$name.'-id'])) ? $post[$name.'-id'] : array();
			$fileName        = $this->getAFile($post[$name]);
			$parts           = pathinfo($this->uploadDir . $fileName);
			$extension       = $parts['extension'];
			$currentFileURL  = $webUploadDir . $fileName;
			if (file_exists($currentFileURL)) {
				$currentFile = $post[$name.'-fileName'];
				$uniqueId    = $post[$name.'-uniqueId'];
			}
			if (isset($post[$name.'-dimensions']) && is_array($post[$name.'-dimensions']) && count($post[$name.'-dimensions'])) {
				$dimensions = $post[$name.'-dimensions'];
			}
		}

		$preview = ($currentFile) ? true : false;
		?>
		<div class="asynfileuploader" id="asynfileuploader-<?php echo $name; ?>">
			<div id="asynfileuploader-value-<?php echo $name; ?>">
			<?php if (count($values)) { ?>
				<?php foreach ($values as $val) { ?>
					<?php if (is_array($val)) { ?>
						<?php foreach ($val as $v) { ?>
							<input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $v; ?>" />
						<?php } ?>
					<?php } else { ?>
						<input type="hidden" name="<?php echo $name; ?>[]" value="<?php echo $val; ?>" />
					<?php } ?>
				<?php } ?>
				<?php foreach ($dimensions as $ext) { ?>
					<input type="hidden" name="<?php echo $name; ?>-extensions[]" value="<?php echo $ext; ?>" />
				<?php } ?>
				<?php foreach ($ids as $id) { ?>
					<input type="hidden" name="<?php echo $name; ?>-id[]" value="<?php echo $id; ?>" />
				<?php } ?>
			<?php } else { ?>
				<input type="hidden" name="<?php echo $name; ?>" value="" />
			<?php } ?>
			</div>
			<input type="hidden" name="<?php echo $name; ?>-dir" value="<?php echo base64_encode($this->uploadDir); ?>" />
			<input type="hidden" name="<?php echo $name; ?>-fileName" id="asynfileuploader-fileName-<?php echo $name; ?>" value="<?php echo $currentFile; ?>" />
			<input type="hidden" name="<?php echo $name; ?>-uniqueId" id="asynfileuploader-uniqueId-<?php echo $name; ?>" value="<?php echo $uniqueId; ?>" />
			<div id="asynfileuploader-progress-<?php echo $name; ?>" class="asynfileuploader-progress">
				<div></div>
			</div>
			<?php

			?>
			<div <?php echo $buttonAttrHTML; ?>>
				<span><?php echo $this->caption; ?></span>
				<iframe id="asynfileuploader-frame-<?php echo $name; ?>" src="<?php echo $handlerURL; ?>" frameborder="0" scrolling="no" style="overflow:hidden;"></iframe>
			</div>
			<div class="asynfileuploader-file-info">
				<?php if ($this->fileNameIsDisplayed) { ?>
					<span id="asynfileuploader-name-<?php echo $name; ?>">
					<?php if ($currentFile) { ?>
						<a href="<?php echo $currentFileURL; ?>" target="_blank" rel="nofollow"><?php echo $currentFile ?></a>
					<?php } ?>
					</span>
				<?php } ?>
				<span id="asynfileuploader-delete-<?php echo $name; ?>" class="asynfileuploader-delete" <?php if (!$currentFile) echo 'style="display:none"'; ?>>
				<?php if ($this->isDeletable) { ?>
					<a href="javascript:" rel="<?php echo $this->onDelete; ?>" title="Delete">
						<span>Delete</span>
					</a>
				<?php } ?>
				</span>
			</div>
			<span class="asynfileuploader-error" id="asynfileuploader-error-<?php echo $name; ?>"></span>
			<script type="text/javascript">
				LC.AsynFileUploader.init('<?php echo $name; ?>');
				<?php
				if ($preview) {
					$json = array(
						'name'      => $name,
						'fileName'  => $currentFile,
						'extension' => $extension,
						'url'       => $currentFileURL,
						'caption'   => $this->label,
						'uploads'   => $values
					);
					echo 'LC.AsynFileUploader.preview(' . json_encode($json) . ');';
				}
				?>
			</script>
		</div>
		<?php
	}
}
