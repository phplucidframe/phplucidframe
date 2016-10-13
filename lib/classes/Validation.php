<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Form validation helper
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

namespace LucidFrame\Core;

/**
 * Form validation helper
 */
class Validation
{
    /** @var array The array of the error messages upon validation */
    public static $errors = array();
    /** @var array The array of default error messages */
    private static $messages;
    /** @var array The array of the rules for group of inputs validation */
    private static $batchRules = array('mandatoryOne', 'mandatoryAll');

    /**
     * Setter
     * @param string $key   The property name
     * @param mixed  $value The value for the property
     * @return void
     */
    public static function set($key, $value = null)
    {
        self::$$key = $value;
    }

    /**
     * Getter
     * @param string $key   The property name
     * @return mixed
     */
    public static function get($key)
    {
        return isset(self::$$key) ? self::$$key : null;
    }

    /**
     * Check all inputs according to the validation rules provided
     *
     * @param array $validations The array of the validation rules
     * @param string $type The return form of the error message:
     *  "multi" to return all error messages occurred;
     *  "single" to return the first error message occurred
     *
     * @return void
     */
    public static function check($validations, $type = 'multi')
    {
        form_init();

        $type = strtolower($type);
        if (!in_array($type, array('single', 'multi'))) {
            $type = 'multi';
        }
        self::$errors = array();
        foreach ($validations as $id => $v) {
            if (isset($v['rules']) && is_array($v['rules'])) {
                foreach ($v['rules'] as $rule) {
                    $success = true;
                    $caption = (!isset($v['caption'])) ? $id : $v['caption'];

                    if (is_array($v['value']) && in_array($rule, self::$batchRules)) {
                        # Batch validation rules may be applied for array of values
                        $values = $v['value'];
                        $func = 'validate_'.$rule;
                        if (function_exists($func)) {
                            $success = call_user_func_array($func, array($values));
                            if (!$success) {
                                self::setError($id, $rule, $v);
                            }
                            continue; # go to the next rule
                        }
                        # if array of values, the validation function
                        # (apart from the batch validation functions) will be applied to each value
                    } else {
                        if (!is_array($v['value']) ||
                            (is_array($v['value']) && array_key_exists('tmp_name', $v['value']))) {
                            $values = array($v['value']);
                        } else {
                            $values = $v['value'];
                        }
                    }

                    foreach ($values as $value) {
                        # Custom validation function
                        if (strstr($rule, 'validate_')) {
                            $args = array($value);
                            if (isset($v['parameters']) && is_array($v['parameters'])) {
                                $params = (isset($v['parameters'][$rule])) ? $v['parameters'][$rule] : $v['parameters'];
                                $args = array_merge($args, $params);
                            }
                            $success = call_user_func_array($rule, $args);
                            if (!$success) {
                                self::setError($id, $rule, $v);
                            }
                        } else {
                        # Pre-defined validation functions
                            $func = 'validate_'.$rule;
                            if (function_exists($func)) {
                                switch($rule) {
                                    case 'min':
                                        # Required property: min
                                        if (!isset($v['min'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['min']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['min']);
                                        }
                                        break;

                                    case 'max':
                                        # Required property: max
                                        if (!isset($v['max'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['max']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['max']);
                                        }
                                        break;

                                    case 'minLength':
                                        # Required property: min
                                        if (!isset($v['min'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['min']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['min']);
                                        }
                                        break;

                                    case 'maxLength':
                                        # Required property: max
                                        if (!isset($v['max'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['max']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['max']);
                                        }
                                        break;

                                    case 'between':
                                        # Required property: min|max
                                        if (!isset($v['min']) || !isset($v['max'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['min'], $v['max']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['min'], $v['max']);
                                        }
                                        break;

                                    case 'ip':
                                        # Required property: protocol
                                        if (!isset($v['protocol']) || ( isset($v['protocol']) && !in_array($v['protocol'], array('ipv4','ipv6')))) {
                                            $v['protocol'] = 'ipv4';
                                        }
                                        $success = call_user_func_array($func, array($value, $v['protocol']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v);
                                        }
                                        break;

                                    case 'custom':
                                        # Required property: pattern
                                        if (!isset($v['pattern'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['pattern']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v);
                                        }
                                        break;

                                    case 'fileMaxSize':
                                        # Required property: maxSize
                                        if (!isset($v['maxSize'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxSize']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxSize']);
                                        }
                                        break;

                                    case 'fileMaxWidth':
                                        # Required property: maxWidth
                                        if (!isset($v['maxWidth'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxWidth']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxWidth']);
                                        }
                                        break;

                                    case 'fileMaxHeight':
                                        # Required property: maxHeight
                                        if (!isset($v['maxHeight'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxHeight']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxHeight']);
                                        }
                                        break;

                                    case 'fileMaxDimension':
                                        # Required property: maxWidth, maxHeight
                                        if (!isset($v['maxWidth']) || !isset($v['maxHeight'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxWidth'], $v['maxHeight']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxWidth'], $v['maxHeight']);
                                        }
                                        break;

                                    case 'fileExactDimension':
                                        # Required property: width, height
                                        if (!isset($v['width']) || !isset($v['height'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['width'], $v['height']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['width'], $v['height']);
                                        }
                                        break;

                                    case 'fileExtension':
                                        # Required property: extensions
                                        if (!isset($v['extensions'])) {
                                            continue;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['extensions']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, implode(', ', $v['extensions']));
                                        }
                                        break;

                                    case 'date':
                                        # Optional property: dateFormat
                                        if (!isset($v['dateFormat']) || (isset($v['dateFormat']) && empty($v['dateFormat']))) {
                                            $v['dateFormat'] = 'y-m-d';
                                        }
                                        $success = call_user_func_array($func, array($value, $v['dateFormat']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['dateFormat']);
                                        }
                                        break;

                                    case 'time':
                                        # Optional property: $timeFormat
                                        if (!isset($v['timeFormat']) || (isset($v['timeFormat']) && empty($v['timeFormat']))) {
                                            $v['timeFormat'] = 'both';
                                        }
                                        $success = call_user_func_array($func, array($value, $v['timeFormat']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, ($v['timeFormat'] === 'both') ? '12/24-hour' : $v['timeFormat'].'-hour');
                                        }
                                        break;

                                    case 'datetime':
                                        # Optional property: dateFormat, timeFormat
                                        if (!isset($v['dateFormat']) || (isset($v['dateFormat']) && empty($v['dateFormat']))) {
                                            $v['dateFormat'] = 'y-m-d';
                                        }
                                        if (!isset($v['timeFormat']) || (isset($v['timeFormat']) && empty($v['timeFormat']))) {
                                            $v['timeFormat'] = 'both';
                                        }
                                        $success = call_user_func_array($func, array($value, $v['dateFormat'], $v['timeFormat']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['dateFormat'], ($v['timeFormat'] === 'both') ? '12/24-hour' : $v['timeFormat'].'-hour');
                                        }
                                        break;

                                    default:
                                        $success = call_user_func_array($func, array($value));
                                        if (!$success) {
                                            self::setError($id, $rule, $v);
                                        }
                                }
                                if (!$success) {
                                    if ($type == 'single') {
                                        break 3;
                                    }
                                    continue 3;
                                }
                            } # if (function_exists($func))
                        } # if (strstr($rule, 'validate_'))
                    } # foreach ($values as $value)
                } # foreach ($v['rules'] as $rule)
            } # if (is_array($v['rules']) )
        } # foreach ($validations as $id => $v)
        return (count(self::$errors)) ? false : true;
    }
    /**
     * @internal
     */
    private static function setError($id, $rule, $element)
    {
        $caption  = $element['caption'];
        $msg      = ( isset(self::$messages[$rule]) ) ? self::$messages[$rule] : self::$messages['default'];
        $msg      = ( isset($element['messages'][$rule]) ) ? $element['messages'][$rule] : $msg;
        $args = func_get_args();
        if (count($args) > 3) {
            $args = array_slice($args, 3);
            array_unshift($args, $caption);
            self::$errors[] = array(
                "msg" => vsprintf($msg, $args),
                "htmlID" => $id
            );
        } else {
            self::$errors[] = array(
                'msg' => sprintf($msg, $caption),
                'htmlID' => $id
            );
        }
    }
    /**
     * Add an external error messsage
     *
     * @param string $id HTML ID
     * @param string $msg The error message to show
     *
     * @return void
     */
    public static function addError($id, $msg)
    {
        self::$errors[] = array(
            'msg' => sprintf($msg),
            'htmlID' => $id
        );
    }
}
