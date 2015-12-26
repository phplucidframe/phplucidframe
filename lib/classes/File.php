<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package     LC\Helpers\File
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

namespace LC\Core;

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for file processing system
 */
class File extends \SplFileInfo
{
    /** @var string The uniqued name string for this instance */
    private $name;
    /** @var string The uniqued string ID to append to the file name */
    private $uniqueId;
    /** @var array The dimension to be created for image upload */
    private $dimensions;
    /** @var string The upload directory path */
    private $uploadPath;
    /** @var const Type of file resize */
    private $resize;
    /** @var string The original uploaded file name */
    private $originalFileName;
    /** @var string The file name generated */
    private $fileName;
    /** @var array The uploaded file information */
    private $uploads;
    /** @var array The file upload error information */
    private $error;
    /** @var array The image filter setting */
    private $imageFilterSet;

    /**
     * Constructor
     * @param string $fileName Path to the file
     */
    public function __construct($fileName = '')
    {
        $this->name = $fileName;
        $this->uploadPath = FILE . 'tmp' . _DS_;
        $this->defaultImageFilterSet();
        if ($fileName) {
            parent::__construct($fileName);
        }
    }
    /**
     * Set default image filter set and merge with user's options
     * @return object File
     */
    private function defaultImageFilterSet()
    {
        $this->imageFilterSet = array(
            'maxDimension' => '800x600',
            'resizeMode'   => FILE_RESIZE_BOTH,
            'jpgQuality'   => 75
        );
        $this->imageFilterSet = array_merge($this->imageFilterSet, _cfg('imageFilterSet'));
        $this->setImageResizeMode($this->imageFilterSet['resizeMode']);
        return $this;
    }
    /**
     * Set image resize mode
     * @param  const  FILE_RESIZE_BOTH, FILE_RESIZE_WIDTH or FILE_RESIZE_HEIGHT
     * @return object File
     */
    private function setImageResizeMode($value)
    {
        if (in_array($value, array(FILE_RESIZE_BOTH, FILE_RESIZE_WIDTH, FILE_RESIZE_HEIGHT))) {
            $this->imageFilterSet['resizeMode'] = $value;
        } else {
            $this->imageFilterSet['resizeMode'] = FILE_RESIZE_BOTH;
        }
        return $this;
    }
    /**
     * Setter for the class properties
     * @param string $key The property name
     * @param mixed $value The value to be set
     * @return void
     */
    public function set($key, $value)
    {
        if ($key === 'resize' || $key === 'resizeMode') {
            $this->setImageResizeMode($value);
            return $this;
        }

        if ($key === 'maxDimension') {
            $this->imageFilterSet['maxDimension'] = $value;
            return $this;
        }

        if ($key === 'jpgQuality') {
            $this->imageFilterSet['jpgQuality'] = $value;
            return $this;
        }

        # if $uniqueId is explicitly given and $name was not explicity given
        # make $name and $uniqueId same
        if ($key === 'uniqueId' && $value & $this->name === $this->uniqueId) {
            $this->name = $value;
        }

        if ($key === 'uploadDir' || $key === 'uploadPath') {
            $value = rtrim(rtrim($value, '/'), _DS_) . _DS_;
            $this->uploadPath = $value;
        }

        $this->{$key} = $value;
        return $this;
    }
    /**
     * Getter for the class properties
     * @param string $key The property name
     * @return mixed $value The value of the property or null if $name does not exist.
     */
    public function get($key)
    {
        if ($key === 'uploadDir') {
            return $this->uploadPath;
        }
        if (isset($this->{$key})) {
            return $this->{$key};
        }
        return null;
    }
    /**
     * Getter for the orignal uploaded file name
     */
    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }
    /**
     * Getter for the file name generated
     */
    public function getFileName()
    {
        return $this->fileName;
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
    public function getError()
    {
        return $this->error;
    }
    /**
     * Get file upload error message for the given error code
     * @param  int $code The error code
     * @return string The error message
     */
    public function getErrorMessage($code)
    {
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
     * @param string|array $file The name 'xxx' from $_FILES['xxx']
     *    or The array of uploaded file information from $_FILES['xxx']
     *
     * @return  array The array of the uploaded file information:
     *
     *     array(
     *       'name'     => 'Name of the input element',
     *       'fileName' => 'The uploaded file name',
     *       'originalFileName' => 'The original file name user selected',
     *       'extension'=> 'The selected and uploaded file extension',
     *       'dir'      => 'The uploaded directory',
     *     )
     *
     */
    public function upload($file)
    {
        if (is_string($file)) {
            if (!isset($_FILES[$file])) {
                $this->error = array(
                    'code' => UPLOAD_ERR_NO_FILE,
                    'message' => $this->getErrorMessage(UPLOAD_ERR_NO_FILE)
                );
                return null;
            }
            $this->name = $file;
            $file = $_FILES[$file];
        }

        if (!isset($file['name']) || !isset($file['tmp_name'])) {
            $this->error = array(
                'code' => UPLOAD_ERR_NO_FILE,
                'message' => $this->getErrorMessage(UPLOAD_ERR_NO_FILE)
            );
            return null;
        }

        $fileName     = stripslashes($file['name']);
        $uploadedFile = $file['tmp_name'];
        $info         = pathinfo($fileName);
        $extension    = strtolower($info['extension']);
        $uploaded     = null;
        $path         = $this->uploadPath;
        $dimensions   = $this->dimensions;

        if ($fileName && $file['error'] === UPLOAD_ERR_OK) {
            $this->originalFileName = $fileName;
            $newFileName = $this->getNewFileName();

            if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif'))) {
                # non-image file
                $uploaded = $this->move($uploadedFile, $newFileName);
            } else {
                # image file
                if (isset($this->imageFilterSet['maxDimension']) && $this->imageFilterSet['maxDimension']) {
                    # Upload the primary image by the configured dimension in config
                    $uploaded = $this->resizeImageByDimension($this->imageFilterSet['maxDimension'], $uploadedFile, $newFileName, $extension);
                } else {
                    $uploaded = $this->move($uploadedFile, $newFileName);
                }
                # if the thumbnail dimensions are defined, create them
                if (is_array($this->dimensions) && count($this->dimensions)) {
                    $this->resizeImageByDimension($this->dimensions, $uploadedFile, $newFileName, $extension);
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
                'name'              => $this->name,
                'fileName'          => $uploaded,
                'originalFileName'  => $this->originalFileName,
                'extension'         => $extension,
                'dir'               => $this->get('uploadDir')
            );
        }

        return $this->uploads;
    }
    /**
     * Get a new unique file name
     *
     * @return string The file name
     */
    private function getNewFileName()
    {
        $this->fileName = $this->getUniqueId() . '.' . $this->guessExtension();
        return $this->fileName;
    }
    /**
     * Get a unique id string
     * @return string
     */
    private function getUniqueId()
    {
        return ($this->uniqueId) ? $this->uniqueId : uniqid();
    }
    /**
     * Return the extension of the original file name
     * @param  string $file The optional file name; if it is not given, the original file name will be used
     * @return string The extension or an empty string if there is no file
     */
    public function guessExtension($file = '')
    {
        $file = ($file) ? $file : $this->originalFileName;
        if ($this->originalFileName) {
            $info = pathinfo($this->originalFileName);
            return $info['extension'];
        } else {
            return '';
        }
    }
    /**
     * Move the uploaded file to the new location with new file name
     * @param  string $file         The source file
     * @param  string $newFileName  The new file name
     * @return string The new file name or null if any error occurs
     */
    protected function move($file, $newFileName)
    {
        $targetDir = $this->uploadPath;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        if (@move_uploaded_file($file, $targetDir . $newFileName)) {
            return $newFileName;
        } else {
            $this->error = array(
                'code' => FILE_UPLOAD_ERR_MOVE,
                'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_MOVE)
            );
            return null;
        }
    }
    /**
     * Resize the image file into the given width and height
     * @param  string|array $dimensions   The dimension or array of dimensions,
     *    e.g., '400x250' or array('400x250', '200x150')
     * @param  string       $file         The source file
     * @param  string       $newFileName  The new file name to be created
     * @param  string       $extension    The file extension
     * @return string       The new file name or null if any error occurs
     */
    protected function resizeImageByDimension($dimensions, $file, $newFileName, $extension = null)
    {
        $dimensions = is_string($dimensions) ? array($dimensions) : $dimensions;
        $extension = ($extension) ? $extension : strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension == "jpg" || $extension == "jpeg") {
            $img = imagecreatefromjpeg($file);
        } elseif ($extension == "png") {
            $img = imagecreatefrompng($file);
        } elseif ($extension == "gif") {
            $img = imagecreatefromgif($file);
        }

        if (isset($img) && $img) {
            if (isset($this->imageFilterSet['jpgQuality']) && is_numeric($this->imageFilterSet['jpgQuality'])) {
                $jpgQuality = $this->imageFilterSet['jpgQuality'];
            } else {
                $jpgQuality = 75;
            }

            foreach ($dimensions as $dimension) {
                $resize = explode('x', $dimension);
                $resizeWidth  = $resize[0];
                $resizeHeight = $resize[1];

                if ($this->imageFilterSet['resizeMode'] == FILE_RESIZE_WIDTH) {
                    $tmp = File::resizeImageWidth($img, $file, $resizeWidth);
                } elseif ($this->imageFilterSet['resizeMode'] == FILE_RESIZE_HEIGHT) {
                    $tmp = File::resizeImageHeight($img, $file, $resizeHeight);
                } else {
                    $tmp = File::resizeImageBoth($img, $file, $resizeWidth, $resizeHeight);
                }

                $targetDir = (is_string(func_get_arg(0))) ? $this->uploadPath : $this->uploadPath . $dimension . _DS_;
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }
                $targetFileName = $targetDir . $newFileName;

                if ($extension == "gif") {
                    imagegif($tmp, $targetFileName);
                } elseif ($extension == "png") {
                    imagesavealpha($tmp, true);
                    imagepng($tmp, $targetFileName);
                } else {
                    imagejpeg($tmp, $targetFileName, $jpgQuality);
                }

                imagedestroy($tmp);
            }
            if ($img) {
                imagedestroy($img);
                return $newFileName;
            }
        } else {
            $this->error = array(
                'code' => FILE_UPLOAD_ERR_IMAGE_CREATE,
                'message' => $this->getErrorMessage(FILE_UPLOAD_ERR_IMAGE_CREATE)
            );
        }
        return null;
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
    public static function resizeImageWidth(&$img, $file, $newWidth)
    {
        list($width, $height) = getimagesize($file);
        $newHeight = ($height/$width) * $newWidth;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($tmp, false);
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
    public static function resizeImageHeight(&$img, $file, $newHeight)
    {
        list($width, $height) = getimagesize($file);
        $newWidth = ($width/$height) * $newHeight;
        $tmp = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($tmp, false);
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
    public static function resizeImageBoth(&$img, $file, $newWidth, $newHeight)
    {
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
        imagealphablending($tmp, false);
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
    public static function img($fileName, $caption, $dimension, $desiredDimension = '0x0', $attributes = array())
    {
        $regex = '/^[0-9]+x[0-9]+$/i'; # check the format of "99x99" for the dimensions
        if (!preg_match($regex, $dimension)) {
            echo '';
            return null;
        }
        if (!preg_match($regex, $desiredDimension)) {
            $desiredDimension = '0x0';
        }
        list($imgWidth, $imgHeight) = explode('x', strtolower($dimension));
        list($desiredWidth, $desiredHeight) = explode('x', strtolower($desiredDimension));

        if ($imgWidth > $desiredWidth || $imgHeight > $desiredHeight) {
            # scale down
            if ($desiredWidth == 0 && $desiredHeight > 0) {
                # resized to height
                $desiredWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
                $imgWidth     = $desiredWidth;
                $imgHeight    = $desiredHeight;
            } elseif ($desiredWidth > 0 && $desiredHeight == 0) {
                # resized to width
                $desiredHeight  = floor(($imgHeight/$imgWidth) * $desiredWidth);
                $imgWidth       = $desiredWidth;
                $imgHeight      = $desiredHeight;
            } elseif ($desiredWidth > 0 && $desiredHeight > 0) {
                # resized both
                $scale = min($desiredWidth/$imgWidth, $desiredHeight/$imgHeight);
                # new width for the image
                $imgWidth  = floor($scale * $imgWidth);
                # new heigth for the image
                $imgHeight = floor($scale * $imgHeight);
                if ($imgWidth < $desiredWidth || $imgHeight < $desiredHeight) {
                    $wDiff = $desiredWidth - $imgWidth;
                    $hDiff = $desiredHeight - $desiredWidth;
                    if ($wDiff > $hDiff) {
                        # resize to width
                        $imgHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
                        $imgWidth  = $desiredWidth;
                    } else {
                        # resize to height
                        $imgWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
                        $imgHeight = $desiredHeight;
                    }
                }
            } else {
                # if the desired dimension is not given
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
        $attributes['src']    = $fileName;
        $attributes['alt']    = _h($caption);
        $attributes['title']  = _h($caption);
        $attributes['width']  = $imgWidth;
        $attributes['height'] = $imgHeight;
        $attributes['style']  = $style;

        $attrHTML = '';
        foreach ($attributes as $key => $value) {
            $attrHTML .= ' ' . $key . '="' . $value .'"';
        }
        return '<img '.$attrHTML.' />';
    }
}
