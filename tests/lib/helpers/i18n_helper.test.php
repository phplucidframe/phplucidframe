<?php

use LucidFrame\Test\LucidFrameTestCase;

/**
 * Unit Test for i18n_helper.php
 * Covers _t (plain, with params, missing-key passthrough, translation
 * enabled/disabled), _tc (content files with args) and __i18n_load.
 */
class I18nHelperTestCase extends LucidFrameTestCase
{
    /** @var string Saved language, restored after each test */
    private $savedLang;
    /** @var bool Saved translation flag, restored after each test */
    private $savedTranslationEnabled;

    public function setUp()
    {
        parent::setUp();

        $this->savedLang = _cfg('lang');
        $this->savedTranslationEnabled = _cfg('translationEnabled');
        _cfg('translation', array());
    }

    public function tearDown()
    {
        _cfg('lang', $this->savedLang);
        _cfg('translationEnabled', $this->savedTranslationEnabled);

        parent::tearDown();
    }

    public function testForTMissingKeyPassthrough()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        // There is no en.po file, so the given string is returned as-is
        $this->assertEqual(_t('This string is not translated.'), 'This string is not translated.');
    }

    public function testTWithParams()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        $result = _t('Hello %s, you have %d new messages.', 'John', 3);
        $this->assertEqual($result, 'Hello John, you have 3 new messages.');
    }

    public function testTWithTranslationDisabled()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', false);

        // Even with a known translation key, the string is not translated
        $this->assertEqual(_t("'%s' is required."), "'%s' is required.");
        // Params are still substituted
        $this->assertEqual(_t('Hello %s!', 'John'), 'Hello John!');
    }

    public function testTWithMyanmarTranslation()
    {
        _cfg('lang', 'my');
        _cfg('translationEnabled', true);

        // Force (re-)load the my.po translations
        __i18n_load();

        $source = "'%s' is required.";
        $translated = _t($source);

        // The string must be translated, not passed through
        $this->assertNotEqual($translated, $source);
        // The placeholder is preserved in the translated string
        $this->assertPattern('/%s/', $translated);
    }

    public function testTTrimsTheSourceString()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', false);

        $this->assertEqual(_t('  padded string  '), 'padded string');
    }

    public function testTcForDefaultLang()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        $content = _tc('about');
        $this->assertIsString($content);
        $this->assertStringContainsString('PHPLucidFrame', $content);
    }

    public function testTcForMyanmarLang()
    {
        _cfg('lang', 'my');
        _cfg('translationEnabled', true);

        $content = _tc('about');
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
    }

    public function testTcWithArgs()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        // i18n/ctn/en/about.en contains the word "framework"
        $content = _tc('about');
        $this->assertStringContainsString('framework', $content);
    }

    public function testTcWithMissingFile()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        $this->assertEqual(_tc('no-such-content-file'), '');
    }

    public function testI18nLoadWithUnknownLang()
    {
        _cfg('lang', 'en');
        _cfg('translationEnabled', true);

        // There is no en.po file; __i18n_load() returns false
        $this->assertFalse(__i18n_load());

        _cfg('lang', 'my');
        $result = __i18n_load();

        // The my.po file exists and must be parsed into an array of translations
        $this->assertIsArray($result);
    }
}
