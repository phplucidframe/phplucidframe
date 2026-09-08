<?php

namespace LucidFrame\Test;

use LucidFrame\Core\Component;

/**
 * Unit Test for Component class (lib/classes/Component.php)
 * Component files are looked up via _i() under APP_ROOT and ROOT "@components".
 * This test creates temporary fixture components under ROOT/@components at
 * setUp time and removes them in tearDown, so no fixture files pollute the
 * repository and the component resolution API is exercised end-to-end.
 */
class ComponentTest extends LucidFrameTestCase
{
    /** @var string Temporary directory created under ROOT */
    private $tmpDir;

    public function setUp()
    {
        parent::setUp();

        $this->tmpDir = ROOT . '@components';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }

        file_put_contents($this->tmpDir . '/test_card.php', $this->fixtureLogic());
        file_put_contents($this->tmpDir . '/test_card.view.php', $this->fixtureView());
    }

    public function tearDown()
    {
        @unlink($this->tmpDir . '/test_card.php');
        @unlink($this->tmpDir . '/test_card.view.php');
        @rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function fixtureLogic()
    {
        return '<?php
/** Test fixture component logic: nothing to do; the view renders $data. */
';
    }

    private function fixtureView()
    {
        return '<?php if (!isset($title)) { $title = ""; } ?>
<div class="lc-test-card">
    <h3><?php echo $title; ?></h3>
    <?php if (isset($items) && is_array($items)) { ?>
        <ul>
            <?php foreach ($items as $item) { ?>
                <li><?php echo $item; ?></li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>
';
    }

    public function testForComponentRenderReturnsHtml()
    {
        $component = new Component('test_card', array('title' => 'Hello'));

        $html = $component->render(true);

        $this->assertIsString($html);
        $this->assertStringContainsString('lc_component_test_card', $html);
        $this->assertStringContainsString('lc-component', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function testForComponentDataIsExtracted()
    {
        $component = new Component('test_card', array(
            'title' => 'Card',
            'items' => array('A', 'B'),
        ));

        $html = $component->render(true);

        $this->assertStringContainsString('Card', $html);
        $this->assertStringContainsString('<li>A</li>', $html);
        $this->assertStringContainsString('<li>B</li>', $html);
    }

    public function testForComponentSetProps()
    {
        $component = new Component('test_card', array('title' => 'Before'));

        $component->setProps('title', 'After');

        $this->assertEqual($component->getProps('title'), 'After');

        $html = $component->render(true);
        $this->assertStringContainsString('After', $html);
    }

    public function testForComponentGetProps()
    {
        $component = new Component('test_card', array('title' => 'NoProps'));

        // Initial props array is empty
        $this->assertIsArray($component->getProps());
        $this->assertCount(0, $component->getProps());

        $component->setProps('theme', 'dark');
        $props = $component->getProps();
        $this->assertEqual('dark', $props['theme']);
    }

    public function testForComponentSetData()
    {
        $component = new Component('test_card', array('title' => 'Hello'));

        $component->setData('title', 'Updated');
        $this->assertEqual('Updated', $component->getData('title'));

        $html = $component->render(true);
        $this->assertStringContainsString('Updated', $html);
    }

    public function testForComponentGetData()
    {
        $component = new Component('test_card', array('title' => 'Hello'));

        $this->assertEqual('Hello', $component->getData('title'));

        // Whole data array is returned when no name is given
        $data = $component->getData();
        $this->assertArrayHasKey('title', $data);
    }

    public function testForComponentUseDataWithDefault()
    {
        $component = new Component('test_card', array('title' => 'Hello'));

        $this->assertEqual('Hello', $component->useData('title', 'ignored'));
        $this->assertEqual('fallback', $component->useData('missing', 'fallback'));
    }

    public function testForComponentNameWithoutPhpExtension()
    {
        $component = new Component('test_card.php');
        $this->assertEqual('test_card', $this->readName($component));

        $html = $component->render(true);
        $this->assertStringContainsString('lc_component_test_card', $html);
    }

    public function testForComponentMissingComponentThrows()
    {
        $thrown = false;
        try {
            $component = new Component('no_such_component_abc');
            $component->render(true);
        } catch (\RuntimeException $e) {
            $thrown = true;
            $this->assertStringContainsString('no_such_component_abc', $e->getMessage());
        }
        $this->assertTrue($thrown, 'A missing component must throw a RuntimeException');
    }

    /**
     * The component name is exposed to the component logic file as $this->name.
     * Use reflection to read the private name property for the assertion.
     *
     * @param Component $component
     * @return mixed
     */
    private function readName(Component $component)
    {
        $ref = new \ReflectionProperty(Component::class, 'name');
        $ref->setAccessible(true);

        return $ref->getValue($component);
    }
}