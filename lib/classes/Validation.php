<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Form validation helper
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

namespace LucidFrame\Core;

/**
 * Form validation helper
 */
class Validation
{
    const TYPE_MULTI = 'multi';
    const TYPE_SINGLE = 'single';

    /** @var array The array of the error messages upon validation */
    public static $errors = array();
    /** @var array The array of default error messages */
    private static $messages;
    /** @var array The array of the rules for group of inputs validation */
    private static $batchRules = array('mandatory', 'mandatoryOne', 'mandatoryAll');

    /**
     * Setter
     * @param string $key The property name
     * @param mixed $value The value for the property
     * @return void
     */
    public static function set($key, $value = null)
    {
        self::$$key = $value;
    }

    /**
     * Getter
     * @param string $key The property name
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
     * @param array $data The optional data array (if no `value` in $validation, it will be looked up in $data)
     * @param string $type The return form of the error message:
     *  "multi" to return all error messages occurred;
     *  "single" to return the first error message occurred
     *
     * @return bool
     */
    public static function check($validations, $data, $type = self::TYPE_MULTI)
    {
        form_init();

        $type = strtolower($type);
        if (!in_array($type, array(self::TYPE_SINGLE, self::TYPE_MULTI))) {
            $type = self::TYPE_MULTI;
        }

        self::$errors = array();
        foreach ($validations as $id => $v) {
            if (isset($v['rules']) && is_array($v['rules'])) {
                if (!isset($v['value'])) {
                    $v['value'] = isset($data[$id]) ? $data[$id] : '';
                }

                foreach ($v['rules'] as $rule) {
                    $success = true;

                    if (is_array($v['value']) && in_array($rule, self::$batchRules)) {
                        # Batch validation rules may be applied for array of values
                        $values = $v['value'];
                        $func = 'validate_' . $rule;
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
                        if (!is_array($v['value']) || array_key_exists('tmp_name', $v['value'])) {
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
                            $func = 'validate_' . $rule;
                            if (function_exists($func)) {
                                switch ($rule) {
                                    case 'exactLength':
                                        # Required property: length
                                        if (!isset($v['length'])) {
                                            break;
                                        }

                                        $success = call_user_func_array($func, array($value, $v['length']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['length']);
                                        }
                                        break;

                                    case 'min':
                                        # Required property: min
                                        if (!isset($v['min'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['min']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['min']);
                                        }
                                        break;

                                    case 'max':
                                        # Required property: max
                                        if (!isset($v['max'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['max']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['max']);
                                        }
                                        break;

                                    case 'minLength':
                                    case 'maxLength':
                                        $requiredProperty = $rule == 'minLength' ? 'min' : 'max';
                                        if (!isset($v[$requiredProperty])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v[$requiredProperty]));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v[$requiredProperty]);
                                        }
                                        break;

                                    case 'between':
                                        # Required property: min|max
                                        if (!isset($v['min']) || !isset($v['max'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['min'], $v['max']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['min'], $v['max']);
                                        }
                                        break;

                                    case 'ip':
                                        # Required property: protocol
                                        if (!isset($v['protocol']) || (isset($v['protocol']) && !in_array($v['protocol'], array('ipv4', 'ipv6')))) {
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
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['pattern']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v);
                                        }
                                        break;

                                    case 'fileMaxSize':
                                        # Required property: maxSize
                                        if (!isset($v['maxSize'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxSize']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxSize']);
                                        }
                                        break;

                                    case 'fileMaxWidth':
                                        # Required property: maxWidth
                                        if (!isset($v['maxWidth'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxWidth']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxWidth']);
                                        }
                                        break;

                                    case 'fileMaxHeight':
                                        # Required property: maxHeight
                                        if (!isset($v['maxHeight'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxHeight']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxHeight']);
                                        }
                                        break;

                                    case 'fileMaxDimension':
                                        # Required property: maxWidth, maxHeight
                                        if (!isset($v['maxWidth']) || !isset($v['maxHeight'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['maxWidth'], $v['maxHeight']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['maxWidth'], $v['maxHeight']);
                                        }
                                        break;

                                    case 'fileExactDimension':
                                        # Required property: width, height
                                        if (!isset($v['width']) || !isset($v['height'])) {
                                            break;
                                        }
                                        $success = call_user_func_array($func, array($value, $v['width'], $v['height']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['width'], $v['height']);
                                        }
                                        break;

                                    case 'fileExtension':
                                        # Required property: extensions
                                        if (!isset($v['extensions'])) {
                                            break;
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
                                            self::setError($id, $rule, $v, ($v['timeFormat'] === 'both') ? '12/24-hour' : $v['timeFormat'] . '-hour');
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
                                            self::setError($id, $rule, $v, $v['dateFormat'], ($v['timeFormat'] === 'both') ? '12/24-hour' : $v['timeFormat'] . '-hour');
                                        }
                                        break;

                                    case 'unique':
                                        # Required property: table, field
                                        if (!isset($v['table']) || !isset($v['field'])) {
                                            break;
                                        }

                                        $v['id'] = isset($v['id']) ? $v['id'] : 0;

                                        $success = call_user_func_array($func, array($value, $v['table'], $v['field'], $v['id']));
                                        if (!$success) {
                                            self::setError($id, $rule, $v, $v['table'], $v['field'], $v['id']);
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

        return !count(self::$errors);
    }

    /**
     * @internal
     * @param string $id
     * @param string $rule
     * @param array $element
     */
    private static function setError($id, $rule, $element)
    {
        $caption    = $element['caption'];
        $msg        = isset(self::$messages[$rule]) ? self::$messages[$rule] : self::$messages['default'];
        $msg        = isset($element['messages'][$rule]) ? $element['messages'][$rule] : $msg;
        $args       = func_get_args();

        if (count($args) > 3) {
            $args = array_slice($args, 3);
            array_unshift($args, $caption);
            self::addError($id, vsprintf($msg, $args));
        } else {
            self::addError($id, sprintf($msg, $caption));
        }
    }

    /**
     * Add an external error message
     *
     * @param string $id HTML ID
     * @param string $msg The error message to show
     *
     * @return void
     */
    public static function addError($id, $msg)
    {
        self::$errors[] = array(
            'field' => $id,
            'message' => $msg,
        );
    }
}
