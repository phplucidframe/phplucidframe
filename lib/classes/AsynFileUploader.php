<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Helper for ajax-like file upload with instant preview if the preview placeholder is provided
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.3.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.github.io
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\File;

class AsynFileUploader
{
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
    /** @var array The array of hidden values to be posted to the callbacks */
    private $hidden;
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
    public function __construct()
    {
        $this->name                 = 'file';
        $this->id                   = '';
        $this->label                = _t('File');
        $this->caption              = _t('Choose File');
        $this->value                = array();
        $this->hidden               = array();
        $this->maxSize              = 10;
        $this->extensions           = array();
        $this->uploadDir            = FILE . 'tmp' . _DS_;
        $this->buttons              = array();
        $this->dimensions           = '';
        $this->uploadHandler        = WEB_ROOT . 'lib/asyn-file-uploader.php';
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
    public function setName($name)
    {
        $this->name = $name;
    }
    /**
     * Setter for the property `id`
     * @param string $id The unique HTML id for the file browsing button
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * Setter for the property `label`
     * @param string $label The caption name for the file input to use in validation error
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
    /**
     * Setter for the property `caption`
     * @param string $caption The caption for image uploaded
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
    }
    /**
     * Setter for the property `value`
     * @param array $value The file name saved in the database
     * @param int   $value The ID related to the file name saved in the database
     */
    public function setValue($value, $id = 0)
    {
        $this->value = array(
            $id => $value
        );
    }
    /**
     * Getter for the property `value`
     */
    public function getValue()
    {
        return is_array($this->value) ? current($this->value) : $this->value;
    }
    /**
     * Getter for the id saved in db related to the value
     */
    public function getValueId()
    {
        if (is_array($this->value)) {
            return current(array_keys($this->value));
        }
        return 0;
    }
    /**
     * Setter for the property `hidden`
     */
    public function setHidden($key, $value = '')
    {
        if (!in_array($key, array('id', 'dimensions', 'fileName', 'uniqueId'))) {
            # skip for reserved keys
            $this->hidden[$key] = $value;
        }
    }
    /**
     * Setter for the property `uploadDir`
     * @param string $dir The directory where the file will be uploaded. Default to /files/tmp/
     */
    public function setUploadDir($dir)
    {
        $this->uploadDir = $dir;
    }
    /**
     * Setter for the property `maxSize`
     * @param int $size The maximum file size allowed in MB
     */
    public function setMaxSize($size)
    {
        $this->maxSize = $size;
    }
    /**
     * Setter for the property `extensions`
     * @param array $extensions The array of extensions such as `array('jpg', 'png', 'gif')`
     */
    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;
    }
    /**
     * Setter for the property `dimensions`
     * @param array $dimensions The array of extensions such as `array('600x400', '300x200')`
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;
    }
    /**
     * Setter for the property `buttons`
     * @param string $arg1[,$arg2,$arg3,...] The HTML element ID for each button
     */
    public function setButtons()
    {
        $this->buttons = func_get_args();
    }
    /**
     * Setter for the property `isDeletable`
     * @param boolean $value If the delete button is provided or not
     */
    public function isDeletable($value)
    {
        $this->isDeletable = $value;
    }
    /**
     * Setter for the property `fileNameIsDisplayed`
     * @param boolean $value If the uploaded file name is displayed next to the button or not
     */
    public function isFileNameDisplayed($value)
    {
        $this->fileNameIsDisplayed = $value;
    }
    /**
     * Setter for the `onUpload` hook
     * @param string $callable The callback PHP function name
     */
    public function setOnUpload($callable)
    {
        $this->onUpload = $callable;
    }
    /**
     * Setter for the `onDelete` hook
     * @param string $callable The callback PHP function name
     */
    public function setOnDelete($callable)
    {
        $this->onDelete = $callable;
    }
    /**
     * Setter for the proprty `uploadHandler`
     * @param string $url The URL where file upload will be handled
     */
    private function setUploadHandler($url)
    {
        $this->uploadHandler = $url;
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
    public function html($attributes = array())
    {
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
        $id             = '';
        $value          = '';
        $currentFile    = '';
        $currentFileURL = '';
        $extension      = '';
        $uniqueId       = '';
        $dimensions     = array();
        $webUploadDir   = str_replace('\\', '/', str_replace(ROOT, WEB_ROOT, $this->uploadDir));

        if ($this->value && file_exists($this->uploadDir . $value)) {
            $value = $this->getValue();
            $id = $this->getValueId();
            $currentFile = basename($this->uploadDir . $value);
            $currentFileURL  = $webUploadDir . $value;
            $extension = pathinfo($this->uploadDir . $value, PATHINFO_EXTENSION);
            if (is_array($this->dimensions) && count($this->dimensions)) {
                $dimensions = $this->dimensions;
            }
        }

        # If the generic form POST, the file information from POST is pre-loaded
        # by overwriting `$this->value`
        if (count($_POST) && isset($_POST[$name]) && $_POST[$name] &&
            isset($_POST[$name.'-fileName']) && $_POST[$name.'-fileName']) {
            $post    = _post($_POST);
            $value   = $post[$name];
            $id      = isset($post[$name.'-id']) ? $post[$name.'-id'] : '';

            if (file_exists($this->uploadDir . $value)) {
                $currentFile = $value;
                $currentFileURL  = $webUploadDir . $value;
                $extension = pathinfo($this->uploadDir . $value, PATHINFO_EXTENSION);
                $uniqueId  = $post[$name.'-uniqueId'];
            }

            if (isset($post[$name.'-dimensions']) && is_array($post[$name.'-dimensions']) && count($post[$name.'-dimensions'])) {
                $dimensions = $post[$name.'-dimensions'];
            }
        }

        $preview = ($currentFile) ? true : false;
        ?>
        <div class="asynfileuploader" id="asynfileuploader-<?php echo $name; ?>">
            <div id="asynfileuploader-value-<?php echo $name; ?>">
                <input type="hidden" name="<?php echo $name; ?>" value="<?php if ($value) echo $value; ?>" />
                <input type="hidden" name="<?php echo $name; ?>-id" value="<?php if ($id) echo $id; ?>" />
                <?php foreach ($dimensions as $d) { ?>
                    <input type="hidden" name="<?php echo $name; ?>-dimensions[]" value="<?php echo $d; ?>" />
                <?php } ?>
            </div>
            <div id="asynfileuploader-hiddens-<?php echo $name; ?>">
                <?php foreach ($this->hidden as $hiddenName => $hiddenValue) { ?>
                    <input type="hidden" name="<?php echo $name; ?>-<?php echo $hiddenName; ?>" value="<?php echo $hiddenValue; ?>" />
                <?php } ?>
            </div>
            <input type="hidden" name="<?php echo $name; ?>-dir" value="<?php echo base64_encode($this->uploadDir); ?>" />
            <input type="hidden" name="<?php echo $name; ?>-fileName" id="asynfileuploader-fileName-<?php echo $name; ?>" value="<?php echo $currentFile; ?>" />
            <input type="hidden" name="<?php echo $name; ?>-uniqueId" id="asynfileuploader-uniqueId-<?php echo $name; ?>" value="<?php echo $uniqueId; ?>" />
            <div id="asynfileuploader-progress-<?php echo $name; ?>" class="asynfileuploader-progress">
                <div></div>
            </div>
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
                        'value'     => $value,
                        'fileName'  => $currentFile,
                        'url'       => $currentFileURL,
                        'extension' => $extension,
                        'caption'   => $this->label
                    );
                    echo 'LC.AsynFileUploader.preview(' . json_encode($json) . ');';
                }
                ?>
            </script>
        </div>
        <?php
    }
    /**
     * Get the upload directory name from REQUEST
     * @param
     */
    public static function getDirFromRequest($name)
    {
        return isset($_REQUEST[$name.'-dir']) ? _sanitize(base64_decode($_REQUEST[$name.'-dir'])) : '';
    }
}
