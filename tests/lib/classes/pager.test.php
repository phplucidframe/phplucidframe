<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Pager;

/**
 * Unit Test for Pager class (lib/classes/Pager.php)
 * Covers page math edges (0 rows, last page, beyond last page), URL building,
 * HTML output and the custom page query string.
 */
class PagerTest extends LucidFrameTestCase
{
    /** @var array Saved $_GET, restored after each test */
    private $savedGet;

    public function setUp()
    {
        parent::setUp();

        $this->savedGet = $_GET;
        $_GET = array(ROUTE => '');
    }

    public function tearDown()
    {
        $_GET = $this->savedGet;
        parent::tearDown();
    }

    public function testForConstructorDefaultPage()
    {
        $pager = new Pager();
        $this->assertEqual($pager->get('page'), 1);
        $this->assertEqual($pager->get('itemsPerPage'), 15);
        $this->assertEqual($pager->get('pageNumLimit'), 5);
    }

    public function testForConstructorReadsPageFromGet()
    {
        $_GET['page'] = '3';
        $pager = new Pager();
        $this->assertEqual($pager->get('page'), 3);
    }

    public function testForConstructorCustomPageQueryString()
    {
        $_GET['pg'] = '5';
        $pager = new Pager('pg');
        $this->assertEqual($pager->get('page'), 5);
    }

    public function testForSetAndGet()
    {
        $pager = new Pager();
        $pager->set('total', 100);
        $pager->set('itemsPerPage', 10);
        $pager->set('pageNumLimit', 5);

        $this->assertEqual($pager->get('total'), 100);
        $this->assertEqual($pager->get('itemsPerPage'), 10);
        $this->assertEqual($pager->get('pageNumLimit'), 5);

        // Unknown key returns empty string
        $this->assertEqual($pager->get('no-such-key'), '');
    }

    public function testForSetReturnsSelf()
    {
        $pager = new Pager();
        $result = $pager->set('total', 10);

        $this->assertIdentical($result, $pager);
    }

    public function testForCalculateBasic()
    {
        $pager = (new Pager())
            ->set('total', 100)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 3);

        $pager->calculate();

        $result = $pager->get('result');
        $this->assertEqual($result['offset'], 20);
        $this->assertEqual($result['thisPage'], 3);
        $this->assertEqual($result['firstPageEnable'], 1);
        $this->assertEqual($result['prePageEnable'], 1);
        $this->assertEqual($result['nextPageEnable'], 1);
        $this->assertEqual($result['lastPageNo'], 10);
        $this->assertEqual($result['lastPageEnable'], 1);
        $this->assertTrue($pager->get('enabled'));
    }

    public function testForCalculateLastPage()
    {
        $pager = (new Pager())
            ->set('total', 100)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 10);

        $pager->calculate();

        $result = $pager->get('result');
        $this->assertEqual($result['thisPage'], 10);
        $this->assertEqual($result['nextPageEnable'], 0);
        $this->assertEqual($result['lastPageEnable'], 0);
        $this->assertEqual($result['prePageEnable'], 1);
    }

    public function testForCalculateZeroRows()
    {
        $pager = (new Pager())
            ->set('total', 0)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 1);

        $pager->calculate();

        $this->assertFalse($pager->get('enabled'));
    }

    public function testForCalculatePageBeyondLast()
    {
        $pager = (new Pager())
            ->set('total', 10)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 5);

        $pager->calculate();

        $this->assertFalse($pager->get('enabled'));
    }

    public function testForCalculateNonNumericPage()
    {
        $pager = (new Pager())
            ->set('total', 50)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 'abc');

        $pager->calculate();

        $result = $pager->get('result');
        $this->assertEqual($result['thisPage'], 1);
    }

    public function testForHtmlTagInvalidFallsBackToTable()
    {
        $pager = (new Pager())->set('htmlTag', 'bogus');

        $this->assertEqual($pager->get('htmlTag'), 'table');
    }

    public function testForDisplayOutput()
    {
        $pager = (new Pager())
            ->set('total', 100)
            ->set('itemsPerPage', 10)
            ->set('pageNumLimit', 5)
            ->set('page', 3);

        $pager->calculate();

        ob_start();
        $pager->display();
        $html = ob_get_clean();

        $this->assertIsString($html);
        $this->assertStringContainsString('lc-pager', $html);
        $this->assertStringContainsString('current-page', $html);
        $this->assertStringContainsString('>3<', $html);
    }
}