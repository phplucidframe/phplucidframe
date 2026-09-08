<?php

namespace LucidFrame\Test;

use LucidFrame\Core\AsynFileUploader;

/**
 * Unit Test for AsynFileUploader (lib/classes/AsynFileUploader.php) and a
 * smoke test for the lib/asyn-file-uploader.php endpoint script.
 * Covers the fluent setters/getters, reserved hidden keys, static
 * getDirFromRequest() and the generated HTML.
 */
class AsynFileUploaderTest extends LucidFrameTestCase
{
    /** @var array Saved $_REQUEST, restored after each test */
    private $savedRequest;

    public function setUp()
    {
        parent::setUp();

        $this->savedRequest = $_REQUEST;
        $_REQUEST = array();
    }

    public function tearDown()
    {
        $_REQUEST = $this->savedRequest;

        parent::tearDown();
    }

    public function testForConstructorDefaults()
    {
        $uploader = new AsynFileUploader('photo');

        // The name is stored for the input element; value defaults to empty
        $this->assertEqual($uploader->getValue(), array());
        $this->assertEqual($uploader->getValueId(), 0);
    }

    public function testForConstructorWithPropertyArray()
    {
        $uploader = new AsynFileUploader(array(
            'name'   => 'gallery',
            'maxSize' => 5,
        ));

        $html = $this->renderHtml($uploader);

        // The generated markup uses the given name
        $this->assertStringContainsString('asynfileuploader-value-gallery', $html);
    }

    public function testForSetValueAndId()
    {
        $uploader = new AsynFileUploader();
        $uploader->setValue('photo-abc.jpg', 42);

        $this->assertEqual($uploader->getValue(), 'photo-abc.jpg');
        $this->assertEqual($uploader->getValueId(), 42);
    }

    public function testForSetHiddenSkipsReservedKeys()
    {
        $uploader = new AsynFileUploader();

        // Reserved keys must be ignored
        $uploader->setHidden('id', 5);
        $uploader->setHidden('dimensions', '50x50');
        $uploader->setHidden('fileName', 'x.jpg');
        $uploader->setHidden('uniqueId', 'u1');

        // A custom key must be stored
        $uploader->setHidden('postId', 99);

        $ref = new \ReflectionClass(AsynFileUploader::class);
        $hidden = $ref->getProperty('hidden');
        $hidden->setAccessible(true);

        $values = $hidden->getValue($uploader);
        $this->assertCount(1, $values);
        $this->assertEqual($values['postId'], 99);
    }

    public function testForSetUploadDir()
    {
        $uploader = new AsynFileUploader();
        $uploader->setUploadDir(FILE . 'post');

        $ref = new \ReflectionClass(AsynFileUploader::class);
        $dir = $ref->getProperty('uploadDir');
        $dir->setAccessible(true);

        $this->assertEqual($dir->getValue($uploader), FILE . 'post');
    }

    public function testForSetMaxSizeAndExtensions()
    {
        $uploader = new AsynFileUploader();
        $uploader->setMaxSize(20);
        $uploader->setExtensions(array('jpg', 'png'));

        $ref = new \ReflectionClass(AsynFileUploader::class);

        $maxSize = $ref->getProperty('maxSize');
        $maxSize->setAccessible(true);
        $this->assertEqual($maxSize->getValue($uploader), 20);

        $extensions = $ref->getProperty('extensions');
        $extensions->setAccessible(true);
        $this->assertEqual($extensions->getValue($uploader), array('jpg', 'png'));
    }

    public function testForSetDimensionsAndButtons()
    {
        $uploader = new AsynFileUploader();
        $uploader->setDimensions(array('600x400', '300x200'));
        $uploader->setButtons('btn-save', 'btn-cancel');

        $ref = new \ReflectionClass(AsynFileUploader::class);

        $dimensions = $ref->getProperty('dimensions');
        $dimensions->setAccessible(true);
        $this->assertEqual($dimensions->getValue($uploader), array('600x400', '300x200'));

        $buttons = $ref->getProperty('buttons');
        $buttons->setAccessible(true);
        $this->assertEqual($buttons->getValue($uploader), array('btn-save', 'btn-cancel'));
    }

    public function testForSetHooksAndHandler()
    {
        $uploader = new AsynFileUploader();
        $uploader->setOnUpload('my_upload_hook');
        $uploader->setOnDelete('my_delete_hook');
        $uploader->setUploadHandler('/my-upload-handler.php');

        $ref = new \ReflectionClass(AsynFileUploader::class);

        $onUpload = $ref->getProperty('onUpload');
        $onUpload->setAccessible(true);
        $this->assertEqual($onUpload->getValue($uploader), 'my_upload_hook');

        $onDelete = $ref->getProperty('onDelete');
        $onDelete->setAccessible(true);
        $this->assertEqual($onDelete->getValue($uploader), 'my_delete_hook');

        $handler = $ref->getProperty('uploadHandler');
        $handler->setAccessible(true);
        $this->assertEqual($handler->getValue($uploader), '/my-upload-handler.php');
    }

    public function testForGetDirFromRequest()
    {
        $_REQUEST['photo-dir'] = base64_encode(FILE . 'tmp');

        $dir = AsynFileUploader::getDirFromRequest('photo');

        $this->assertEqual($dir, FILE . 'tmp');
    }

    public function testForGetDirFromRequestWhenMissing()
    {
        $this->assertEqual(AsynFileUploader::getDirFromRequest('missing'), '');
    }

    public function testForHtmlOutput()
    {
        $uploader = new AsynFileUploader('photo');
        $uploader->setUploadDir(FILE . 'tmp');
        $uploader->setUploadHandler('/lib/asyn-file-uploader.php');

        $html = $this->renderHtml($uploader);

        // The widget markup carries the unique name
        $this->assertStringContainsString('asynfileuploader-value-photo', $html);
        $this->assertStringContainsString('asynfileuploader-hiddens-photo', $html);
        // The upload dir is transported base64-encoded
        $this->assertStringContainsString(base64_encode(FILE . 'tmp'), $html);
        // The JS init hook is invoked with the name
        $this->assertStringContainsString('LC.AsynFileUploader.init', $html);
        $this->assertStringContainsString('/lib/asyn-file-uploader.php', $html);
    }

    public function testForEndpointScriptExists()
    {
        // Smoke test: the endpoint script must exist and reference the
        // AsynFileUploader JS API
        $endpoint = LIB . 'asyn-file-uploader.php';

        $this->assertTrue(is_file($endpoint));

        $content = file_get_contents($endpoint);
        $this->assertStringContainsString('asynfileuploader', strtolower($content));
    }

    /**
     * Capture the echoed HTML of the given uploader
     *
     * @param AsynFileUploader $uploader
     * @return string
     */
    private function renderHtml(AsynFileUploader $uploader)
    {
        ob_start();
        $uploader->html();

        return ob_get_clean();
    }
}