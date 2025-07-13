<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for AJAX form handling and form validation
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

use LucidFrame\Core\Form;

/**
 * Initialize form
 * @return void
 */
function form_init()
{
    Form::init();
}

/**
 * Setter for the class properties
 * @param string $key The property name
 * @param mixed $value The value to be set
 * @return void
 */
function form_set($key, $value = '')
{
    Form::set($key, $value);
}

/**
 * Form token generation
 * @return void
 */
function form_token()
{
    Form::token();
}

/**
 * Form token validation
 * @param array $validations The array of validation rules
 * @param array $data The optional data array (if no `value` in $validation, it will be looked up in $data)
 * @return boolean
 */
function form_validate($validations = null, $data = [])
{
    return Form::validate($validations, $data);
}

/**
 * AJAX form responder
 * @param string $formId The HTML form ID
 * @param array $errors The array of the errors (it is used only for generic form processing)
 * @param bool $forceJson Send json header
 * @return void
 */
function form_respond($formId, $errors = null, $forceJson = false)
{
    Form::respond($formId, $errors, $forceJson);
}

/**
 * Permits you to set the value of an input or textarea.
 * Allows you to safely use HTML and characters such as quotes within form elements without breaking out of the form
 *
 * @param string $name The input element field name
 * @param mixed $defaultValue The default value of the input element (optional)
 *
 * @return mixed The value of the input element
 */
function form_value($name, $defaultValue = null)
{
    return Form::value($name, $defaultValue);
}

/**
 * Permits you to set the value to a rich text editor or any input where HTML source is required to be rendered.
 * Allows you to safely use HTML and characters such as quotes within form elements without breaking out of the form
 *
 * @param string $name The input element field name
 * @param mixed $defaultValue The default value of the input element (optional)
 *
 * @return mixed The value of the input element
 */
function form_htmlValue($name, $defaultValue = null)
{
    return Form::htmlValue($name, $defaultValue);
}

/**
 * Allow you to select the option of a drop-down list.
 *
 * @param string $name The field name of the drop-down list
 * @param mixed $value The option value to check against
 * @param mixed $defaultValue The default selected value (optional)
 *
 * @return string `'selected="selected"'` if the option is found, otherwise the empty string returned
 */
function form_selected($name, $value, $defaultValue = null)
{
    return Form::inputSelection($name, $value, $defaultValue) ? 'selected="selected"' : '';
}

/**
 * Allow you to select a checkbox or a radio button
 *
 * @param string $name The field name of the checkbox or radio button
 * @param mixed $value The value to check against
 * @param mixed $defaultValue The default selected value (optional)
 *
 * @return string `'checked="checked"'` if the option is found, otherwise the empty string returned
 */
function form_checked($name, $value, $defaultValue = null)
{
    return Form::inputSelection($name, $value, $defaultValue) ? 'checked="checked"' : '';
}
