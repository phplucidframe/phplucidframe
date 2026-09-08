<?php

namespace LucidFrame\Test;

use LucidFrame\Console\ConsoleTable;

/**
 * Unit Test for ConsoleTable class (lib/classes/console/ConsoleTable.php)
 * Covers headers/rows/columns/footers, borders, padding, indentation,
 * alignment and the generated table output.
 */
class ConsoleTableTest extends LucidFrameTestCase
{
    public function testForHeadersAndRows()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Name', 'Qty'))
            ->addRow(array('Apple', 3))
            ->addRow(array('Banana', 5));

        $this->assertEqual($table->getHeaders(), array('Name', 'Qty'));
        $this->assertEqual($table->getFooters(), null);

        $output = $table->getTable();

        $this->assertIsString($output);
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Qty', $output);
        $this->assertStringContainsString('Apple', $output);
        $this->assertStringContainsString('Banana', $output);
        // A bordered table starts and ends with a border line
        $this->assertStringContainsString('+', $output);
    }

    public function testForRowWidthAlignment()
    {
        // The column width is driven by the widest cell; shorter cells are padded
        $table = (new ConsoleTable())
            ->setHeaders(array('name'))
            ->addRow(array('a'))
            ->addRow(array('longer-value'));

        $output = $table->getTable();

        $this->assertStringContainsString('a ', $output);
        $this->assertStringContainsString('longer-value', $output);
    }

    public function testForAddColumn()
    {
        $table = new ConsoleTable();
        $table->addRow(array('A'));
        $table->addColumn('B');

        $output = $table->getTable();

        $this->assertStringContainsString('B', $output);
    }

    public function testForHideBorder()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Name'))
            ->addRow(array('Apple'))
            ->hideBorder();

        $output = $table->getTable();

        $this->assertStringNotContainsString('+', $output);
        $this->assertStringContainsString('Name', $output);
    }

    public function testForShowAllBorders()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Name'))
            ->addRow(array('Apple'))
            ->showAllBorders();

        $output = $table->getTable();

        // Every row is separated by a border line when all borders are shown
        $this->assertStringContainsString('+', $output);
    }

    public function testForPadding()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('x'))
            ->addRow(array('y'))
            ->setPadding(3);

        $output = $table->getTable();

        // padding=3 produces 3 spaces on each side of the cell content
        $this->assertStringContainsString('   y   ', $output);
    }

    public function testForIndent()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('x'))
            ->addRow(array('y'))
            ->setIndent(4);

        $output = $table->getTable();

        // The first cell of every border/data line is indented by 4 spaces
        $this->assertStringContainsString('    ', $output);
    }

    public function testForAddBorderLine()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('A', 'B'))
            ->addRow(array(1, 2))
            ->addBorderLine()
            ->addRow(array(3, 4));

        $output = $table->getTable();

        $this->assertStringContainsString('1', $output);
        $this->assertStringContainsString('2', $output);
        $this->assertStringContainsString('3', $output);
        $this->assertStringContainsString('4', $output);
    }

    public function testForFooter()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Item', 'Total'))
            ->addRow(array('Apple', 3))
            ->addFooter('Total', ConsoleTable::ALIGN_RIGHT)
            ->addFooter('3');

        $output = $table->getTable();

        $this->assertEqual($table->getFooters(), array('Total', '3'));
        $this->assertStringContainsString('Total', $output);
    }

    public function testForDisplayEchoesTable()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Name'))
            ->addRow(array('Apple'));

        ob_start();
        $table->display();
        $output = ob_get_clean();

        $this->assertStringContainsString('Apple', $output);
    }

    public function testForColumnAlignment()
    {
        $table = (new ConsoleTable())
            ->setHeaders(array('Left', 'Right'))
            ->addRow(array('a', 'bb'))
            ->setColumnAlign(1, ConsoleTable::ALIGN_RIGHT);

        $output = $table->getTable();

        $this->assertStringContainsString('bb', $output);
        $this->assertStringContainsString('a', $output);
    }

    public function testForEmptyTable()
    {
        $table = new ConsoleTable();

        $output = $table->getTable();

        $this->assertIsString($output);
    }
}