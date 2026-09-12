<?php

namespace LucidFrame\Test;

use LucidFrame\Core\File;

/**
 * Unit Test for File class (lib/classes/File.php)
 * Covers set/get, extension guessing, upload error codes, a real PNG upload
 * through the GD image pipeline (max dimension + thumbnails), the static
 * resize helpers and the img() tag builder.
 *
 * All temporary files are created under files/tmp/lc-file-test/ and removed
 * in tearDown. Requires ext-gd, which the framework already requires.
 */
class FileTest extends LucidFrameTestCase
{
    /** @var string Temporary upload directory used by this test */
    private $tmpDir;

    /** @var string Path to the generated source PNG */
    private $sourcePng;

    /** @var string Path to the generated source text file */
    private $sourceTxt;

    public function setUp()
    {
        parent::setUp();

        $this->tmpDir = FILE . 'tmp' . _DS_ . 'lc-file-test' . _DS_;
        $this->ensureDir(rtrim($this->tmpDir, _DS_));

        // Create a 40x30 PNG source image
        $this->sourcePng = $this->tmpDir . 'source.png';
        $img = imagecreatetruecolor(40, 30);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $white);
        imagepng($img, $this->sourcePng);
        imagedestroy($img);

        $this->sourceTxt = $this->tmpDir . 'source.txt';
        file_put_contents($this->sourceTxt, 'lc file upload test');
    }

    public function tearDown()
    {
        $this->deleteDir($this->tmpDir);

        parent::tearDown();
    }

    /** @return void */
    private function deleteDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        // Remove the files but KEEP the directories: on Windows a
        // just-removed directory stays delete-pending for a while, so a
        // recreate in the next setUp can transiently fail (mkdir: File
        // exists followed by a vanished name). The empty directory tree
        // lives under the git-ignored files/ and leaves no repo trace.
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . _DS_ . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
    }

    /**
     * Ensure the given directory exists
     *
     * A directory removed by a previous tearDown can still be delete-pending
     * on Windows, making is_dir() and mkdir() disagree; pause and retry once.
     *
     * @param string $dir The directory path
     * @return void
     */
    private function ensureDir($dir)
    {
        clearstatcache(true, $dir);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (!is_dir($dir)) {
            usleep(200000);
            clearstatcache(true, $dir);
            @mkdir($dir, 0777, true);
        }
    }

    /**
     * Build a File instance bound to the temp directory with a fixed unique id
     *
     * @param string $uniqueId
     * @return File
     */
    private function newFile($uniqueId = 'lc-file-unique')
    {
        $file = new File();
        $file->set('uploadDir', $this->tmpDir);
        $file->set('uniqueId', $uniqueId);

        return $file;
    }

    public function testForSetAndGet()
    {
        $file = new File();

        $file->set('maxDimension', '100x100');
        $this->assertEqual($file->get('imageFilterSet')['maxDimension'], '100x100');

        $file->set('jpgQuality', 90);
        $this->assertEqual($file->get('imageFilterSet')['jpgQuality'], 90);

        $file->set('resizeMode', FILE_RESIZE_WIDTH);
        $this->assertEqual($file->get('imageFilterSet')['resizeMode'], FILE_RESIZE_WIDTH);

        $file->set('uniqueId', 'lc-file-unique');
        $this->assertEqual($file->get('uniqueId'), 'lc-file-unique');

        $file->set('uploadDir', $this->tmpDir);
        $this->assertEqual($file->get('uploadDir'), $this->tmpDir);

        // Unknown key returns null
        $this->assertNull($file->get('no-such-key'));
    }

    public function testForUploadDirGetsTrailingSeparator()
    {
        $file = new File();
        $file->set('uploadDir', rtrim($this->tmpDir, _DS_));

        $this->assertEqual($file->get('uploadDir'), $this->tmpDir);
    }

    public function testForGuessExtension()
    {
        $file = new File();

        $this->assertEqual($file->guessExtension('photo.jpg'), 'jpg');
        $this->assertEqual($file->guessExtension(), '');
    }

    public function testForGetErrorMessage()
    {
        $file = new File();

        $this->assertIsString($file->getErrorMessage(UPLOAD_ERR_NO_FILE));
        $this->assertIsString($file->getErrorMessage(UPLOAD_ERR_INI_SIZE));
        $this->assertIsString($file->getErrorMessage(FILE_UPLOAD_ERR_MOVE));
        $this->assertIsString($file->getErrorMessage(FILE_UPLOAD_ERR_IMAGE_CREATE));
        $this->assertIsString($file->getErrorMessage(999999));
    }

    public function testForUploadWithNoFileError()
    {
        $file = $this->newFile();

        $uploaded = $file->upload(array(
            'name'     => 'photo.png',
            'type'     => 'image/png',
            'tmp_name' => $this->sourcePng,
            'error'    => UPLOAD_ERR_NO_FILE,
            'size'     => 0,
        ));

        $this->assertNull($uploaded);

        $error = $file->getError();
        $this->assertEqual($error['code'], UPLOAD_ERR_NO_FILE);
        $this->assertIsString($error['message']);
    }

    public function testForUploadWithMissingEntryData()
    {
        $file = $this->newFile();

        // Neither name nor tmp_name: treated as no file
        $this->assertNull($file->upload(array()));

        $this->assertEqual($file->getError()['code'], UPLOAD_ERR_NO_FILE);
    }

    public function testForUploadByUnknownInputName()
    {
        $file = $this->newFile();

        $this->assertNull($file->upload('no_such_input_name'));

        $this->assertEqual($file->getError()['code'], UPLOAD_ERR_NO_FILE);
    }

    public function testForUploadNonImageFailsWithMoveError()
    {
        // move_uploaded_file() cannot move a regular (non-uploaded) file,
        // so the upload must fail with the FILE_UPLOAD_ERR_MOVE code
        $file = $this->newFile();

        $uploaded = $file->upload(array(
            'name'     => 'document.txt',
            'type'     => 'text/plain',
            'tmp_name' => $this->sourceTxt,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($this->sourceTxt),
        ));

        $this->assertNull($uploaded);
        $this->assertEqual($file->getError()['code'], FILE_UPLOAD_ERR_MOVE);
    }

    public function testForUploadImagePng()
    {
        $file = $this->newFile('lc-file-unique');
        $file->set('name', 'photo');
        $file->set('maxDimension', '20x20'); // 40x30 source -> 20x15

        $uploaded = $file->upload(array(
            'name'     => 'photo.png',
            'type'     => 'image/png',
            'tmp_name' => $this->sourcePng,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($this->sourcePng),
        ));

        $this->assertIsArray($uploaded);
        $this->assertEqual($uploaded['name'], 'photo');
        $this->assertEqual($uploaded['fileName'], 'lc-file-unique.png');
        $this->assertEqual($uploaded['originalFileName'], 'photo.png');
        $this->assertEqual($uploaded['extension'], 'png');
        $this->assertEqual($uploaded['dir'], $this->tmpDir);

        // The resized file must exist with the fitted dimensions
        $target = $this->tmpDir . 'lc-file-unique.png';
        $this->assertTrue(is_file($target));

        list($width, $height) = getimagesize($target);
        $this->assertEqual($width, 20);
        $this->assertEqual($height, 15);

        $this->assertEqual($file->getFileName(), 'lc-file-unique.png');
        $this->assertEqual($file->getOriginalFileName(), 'photo.png');
    }

    public function testForUploadImageWithThumbnailDimensions()
    {
        $file = $this->newFile();
        $file->set('maxDimension', '20x20');
        $file->set('dimensions', array('50x50'));

        $uploaded = $file->upload(array(
            'name'     => 'photo.png',
            'type'     => 'image/png',
            'tmp_name' => $this->sourcePng,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($this->sourcePng),
        ));

        $this->assertIsArray($uploaded);

        // The thumbnail must be created in its own dimension folder
        $thumb = $this->tmpDir . '50x50' . _DS_ . $uploaded['fileName'];
        $this->assertTrue(is_file($thumb));
    }

    public function testForResizeImageWidth()
    {
        $img = imagecreatefrompng($this->sourcePng);

        $tmp = File::resizeImageWidth($img, $this->sourcePng, 20, 'png');
        $this->assertTrue($tmp !== false);

        $target = $this->tmpDir . 'resized-width.png';
        imagepng($tmp, $target);

        list($width, $height) = getimagesize($target);
        $this->assertEqual($width, 20);
        $this->assertEqual($height, 15);

        imagedestroy($tmp);
        imagedestroy($img);
    }

    public function testForResizeImageHeight()
    {
        $img = imagecreatefrompng($this->sourcePng);

        $tmp = File::resizeImageHeight($img, $this->sourcePng, 15, 'png');
        $this->assertTrue($tmp !== false);

        $target = $this->tmpDir . 'resized-height.png';
        imagepng($tmp, $target);

        list($width, $height) = getimagesize($target);
        $this->assertEqual($width, 20);
        $this->assertEqual($height, 15);

        imagedestroy($tmp);
        imagedestroy($img);
    }

    public function testForResizeImageBoth()
    {
        $img = imagecreatefrompng($this->sourcePng);

        $tmp = File::resizeImageBoth($img, $this->sourcePng, 20, 20, 'png');
        $this->assertTrue($tmp !== false);

        $target = $this->tmpDir . 'resized-both.png';
        imagepng($tmp, $target);

        list($width, $height) = getimagesize($target);
        $this->assertEqual($width, 20);
        $this->assertEqual($height, 15);

        imagedestroy($tmp);
        imagedestroy($img);
    }

    public function testForImgHelper()
    {
        // 30x20 image fitted into a 10x10 box: the algorithm scales down and
        // then fills the box, giving width 16 x height 10
        $tag = File::img('/photo.png', 'A Photo', '30x20', '10x10');

        $this->assertIsString($tag);
        $this->assertStringContainsString('<img', $tag);
        $this->assertStringContainsString('src="/photo.png"', $tag);
        $this->assertStringContainsString('alt="A Photo"', $tag);
        $this->assertStringContainsString('width="16"', $tag);
        $this->assertStringContainsString('height="10"', $tag);
    }

    public function testForImgHelperWithInvalidDimension()
    {
        ob_start();
        $result = File::img('/photo.png', 'A Photo', 'bogus', '10x10');
        $output = ob_get_clean();

        $this->assertNull($result);
        $this->assertEqual($output, '');
    }
}