<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for internationalization
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

/**
 * Translation helper
 * It returns a translated string if it is found; Otherwise, the given string itself
 *
 * @param string $str The string being translated
 * @param mixed $args The multiple arguments to be substituted in the string
 *
 * @return string The translated string
 */
function _t($str/*[, mixed $args [, mixed $... ]]*/)
{
    $lang = _cfg('lang');
    $translation = _cfg('translation');
    $translationEnabled = _cfg('translationEnabled');

    $args  = func_get_args();
    $str   = array_shift($args);
    $str   = trim($str);

    if ($translationEnabled == false) {
        return (count($args)) ? vsprintf($str, $args) : $str;
    }

    $po = session_get('po');
    if (!is_array($po)) {
        $po = array();
    }
    $po[$str] = '';

    if (isset($translation[$lang])) {
        # check with lowercase
        $lowerStr = strtolower($str);
        if (isset($translation[$lang][$lowerStr]) && !empty($translation[$lang][$lowerStr])) {
            $translated = $translation[$lang][$lowerStr];
            $str = (is_array($translated)) ? $translated[0] : $translated;
        }
    }

    if (isset($translated)) {
        $po[$str] = $translated;
    }
    return (count($args)) ? vsprintf($str, $args) : $str;
}
/**
 * Get translation contents from the content file located in i18n/ctn/[lang]/*.[lang]
 * Example, i18n/ctn/en/about.en
 *
 * @param string $fileName The file name
 * @param mixed $args The array of arguments to be substituted in the string
 * @return string The translation content
 */
function _tc($fileName, $args = array())
{
    $langs = array(_cfg('lang'), _cfg('defaultLang'));
    foreach ($langs as $lng) {
        $file = I18N . 'ctn/' . $lng . '/' . $fileName . '.' . $lng;
        if (is_file($file) && file_exists($file)) {
            $content = file_get_contents($file);
            if (count($args)) {
                foreach ($args as $key => $value) {
                    $regex = '/'.$key.'\b/i';
                    $content = preg_replace($regex, $value, $content);
                }
            }
            return $content;
        }
    }
    return '';
}
/**
 * @internal
 * @ignore
 *
 * Loads the text .po file and returns array of translations
 * @return mixed Array of translations on success or FALSE on failure
 */
function __i18n_load()
{
    $lang = _cfg('lang');
    $translation = _cfg('translation');
    $translationEnabled = _cfg('translationEnabled');

    if (!$translationEnabled) {
        return false;
    }

    $filename = I18N . $lang.'.po';
    if (!file_exists($filename)) {
        return false;
    }

    # Open the po file
    if (!$file = fopen($filename, 'r')) {
        session_delete("i18n.{$lang}");
        return false;
    }

    # if the respective po file is already parsed
    if ($translations = session_get("i18n.{$lang}")) {
        $translation[$lang] = $translations;
        _cfg('translation', $translation);

        return $translations;
    }

    # parse the file
    session_delete("i18n.{$lang}");

    /**
     * Thanks to CakePHP for the po file parsing logic in the do...while loop
     * @package  Cake.I18n
     * @version  1.2.0.4116
     * @license  http://www.opensource.org/licenses/mit-license.php MIT License
     */
    $type = 0;
    $translations = array();
    $translationKey = '';
    $plural = 0;
    $header = '';

    do {
        $line = trim(fgets($file));
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (preg_match("/msgid[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
            $type = 1;
            $translationKey = strtolower(stripcslashes($regs[1]));
        } elseif (preg_match("/msgid[[:space:]]+\"\"$/i", $line, $regs)) {
            $type = 2;
            $translationKey = '';
        } elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && ($type == 1 || $type == 2 || $type == 3)) {
            $type = 3;
            $translationKey .= strtolower(stripcslashes($regs[1]));
        } elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
            $translations[$translationKey] = stripcslashes($regs[1]);
            $type = 4;
        } elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
            $type = 4;
            $translations[$translationKey] = '';
        } elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 4 && $translationKey) {
            $translations[$translationKey] .= stripcslashes($regs[1]);
        } elseif (preg_match("/msgid_plural[[:space:]]+\".*\"$/i", $line, $regs)) {
            $type = 6;
        } elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
            $type = 6;
        } elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
            $plural = $regs[1];
            $translations[$translationKey][$plural] = stripcslashes($regs[2]);
            $type = 7;
        } elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
            $plural = $regs[1];
            $translations[$translationKey][$plural] = '';
            $type = 7;
        } elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 7 && $translationKey) {
            $translations[$translationKey][$plural] .= stripcslashes($regs[1]);
        } elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && $type == 2 && !$translationKey) {
            $header .= stripcslashes($regs[1]);
            $type = 5;
        } elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && !$translationKey) {
            $header = '';
            $type = 5;
        } elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 5) {
            $header .= stripcslashes($regs[1]);
        } else {
            unset($translations[$translationKey]);
            $type = 0;
            $translationKey = '';
            $plural = 0;
        }
    } while (!feof($file));
    fclose($file);

    $merge[''] = $header;
    $translation[$lang] = array_merge($merge, $translations);
    _cfg('translation', $translation);
    # Store the array of translations in Session
    session_set("i18n.{$lang}", $translation[$lang]);

    return $translation;
}
