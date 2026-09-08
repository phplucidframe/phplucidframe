<?php

namespace LucidFrame\Test;

use LucidFrame\Core\View;

/**
 * Unit Test for View class (lib/classes/View.php)
 * Covers data extraction, block loading from tests/fixtures/, layout config,
 * head style/script collection and the missing-view errors.
 */
class ViewTest extends LucidFrameTestCase
{
    /** @var string Forward-slashed absolute path to the fixtures folder */
    private $fixtures;

    public function setUp()
    {
        parent::setUp();

        $this->fixtures = str_replace(_DS_, '/', TEST_DIR) . 'fixtures';
    }

    public function testForLayoutDefaultsToConfig()
    {
        $view = new View();

        $this->assertEqual($view->layout, _cfg('layoutName'));
    }

    public function testForMagicSetAndGet()
    {
        $view = new View();

        $view->layout = 'layout_single';
        $this->assertEqual($view->layout, 'layout_single');

        $view->name = 'edit';
        $this->assertEqual($view->name, 'edit');
    }

    public function testForBlockReturnsHtml()
    {
        $view = new View();

        $html = $view->block($this->fixtures . '/view_block', array(
            'title' => 'Block Title',
            'body'  => 'Block Body',
        ), true);

        $this->assertIsString($html);
        $this->assertStringContainsString('lc-test-block', $html);
        $this->assertStringContainsString('Block Title', $html);
        $this->assertStringContainsString('Block Body', $html);
    }

    public function testForBlockEchoesHtml()
    {
        $view = new View();

        ob_start();
        $result = $view->block($this->fixtures . '/view_block', array('title' => 'Echoed'));
        $html = ob_get_clean();

        $this->assertNull($result);
        $this->assertStringContainsString('Echoed', $html);
    }

    public function testForBlockMergesViewData()
    {
        $view = new View();
        $view->addData('title', 'From AddData');

        $html = $view->block($this->fixtures . '/view_block', array('body' => 'From Block'), true);

        // addData() values are available in the block
        $this->assertStringContainsString('From AddData', $html);
        $this->assertStringContainsString('From Block', $html);

        // Block data overrides the view data with the same key
        $html2 = $view->block($this->fixtures . '/view_block', array('title' => 'Override'), true);
        $this->assertStringContainsString('Override', $html2);
    }

    public function testForBlockWithMissingFileThrows()
    {
        $view = new View();

        $thrown = false;
        try {
            $view->block('no-such-block-xyz', array(), true);
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('no-such-block-xyz', $e->getMessage());
        }
        $this->assertTrue($thrown, 'A missing block view must throw a RuntimeException');
    }

    public function testForLoadWithMissingViewThrows()
    {
        $view = new View();

        $thrown = false;
        try {
            $view->load();
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('View file is missing', $e->getMessage());
        }
        $this->assertTrue($thrown, 'A missing view file must throw a RuntimeException');
    }

    public function testForLoadWithNamedMissingViewThrows()
    {
        $view = new View();
        $view->name = 'no_such_named_view';

        $thrown = false;
        try {
            $view->load();
        } catch (\RuntimeException $e) {
            $thrown = true;
        }
        $this->assertTrue($thrown);
    }

    public function testForAddHeadStyleAndScript()
    {
        $view = new View();

        $view->addHeadStyle('style-a.css');
        $view->addHeadStyle('style-a.css'); // duplicate must be dropped
        $view->addHeadStyle('style-b.css');
        $view->addHeadScript('script-a.js');
        $view->addHeadScript('script-a.js');

        $ref = new \ReflectionClass(View::class);

        $styles = $ref->getProperty('headStyles');
        $styles->setAccessible(true);
        $this->assertEqual($styles->getValue($view), array('style-a.css', 'style-b.css'));

        $scripts = $ref->getProperty('headScripts');
        $scripts->setAccessible(true);
        $this->assertEqual($scripts->getValue($view), array('script-a.js'));
    }
}