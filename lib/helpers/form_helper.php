<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for AJAX form handling and form validation
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

use LucidFrame\Core\Form;
use LucidFrame\Core\Validation;

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
    $token = _encrypt(time());
    session_set(_cfg('formTokenName'), $token);
    echo '<input type="hidden" name="lc_formToken_'._cfg('formTokenName').'" value="'.$token.'" />';
}

/**
 * Form token validation
 * @param  array $validations The array of validation rules
 * @return boolean
 */
function form_validate($validations = null)
{
    if (!isset($_POST['lc_formToken_'._cfg('formTokenName')])) {
        Validation::addError('', _t('Invalid form token.'));
        return false;
    }

    $token        = _decrypt(session_get(_cfg('formTokenName')));
    $postedToken  = _decrypt(_post($_POST['lc_formToken_'._cfg('formTokenName')]));
    $result       = false;
    # check token first
    if ($token == $postedToken) {
        # check referer if it is requesting in the same site
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && _cfg('siteDomain')) {
            $siteDomain = _cfg('siteDomain');
            $siteDomain = preg_replace('/^www\./', '', $siteDomain);
            $parsedURL  = parse_url($_SERVER['HTTP_REFERER']);
            $parsedURL['host'] = preg_replace('/^www\./', '', $parsedURL['host']);
            if (strcasecmp($siteDomain, $parsedURL['host']) == 0) {
                $result = true;
            }
        }
    }

    if ($result == false) {
        Validation::addError('', _t('Error occured during form submission. Please refresh the page to try again.'));
        return false;
    }

    if ($validations && Validation::check($validations) === false) {
        return false;
    }

    return true;
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
    Form::set('id', $formId);
    $ajaxResponse = $errors === null;

    form_set('error', validation_get('errors'));
    if (is_array($errors) && count($errors)) {
        Form::set('error', $errors);
        $ajaxResponse = false;
        # if no error message and no other message, no need to respond
        $message = Form::get('message');
        if (count(Form::get('error')) == 0 && empty($message)) {
            return;
        }
    }

    $response = array(
        'formId'   => Form::get('id'),
        'success'  => Form::get('success') ? true : false,
        'error'    => Form::get('error'),
        'msg'      => Form::get('message'),
        'redirect' => Form::get('redirect'),
        'callback' => Form::get('callback')
    );

    if ($ajaxResponse) {
        if ($forceJson) {
            _json($response);
        } else {
            echo json_encode($response);
        }
    } else {
        echo '<script type="text/javascript">';
        echo 'LC.Form.submitHandler(' . json_encode($response) . ')';
        echo '</script>';
    }
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
    if (count($_POST)) {
        if (!isset($_POST[$name])) {
            return '';
        }
        $value = _post($_POST[$name]);
        return _h($value);
    } else {
        return _h($defaultValue);
    }
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
    if (count($_POST)) {
        if (!isset($_POST[$name])) {
            return '';
        }
        $value = _xss($_POST[$name]);
        return _h($value);
    } else {
        return _h($defaultValue);
    }
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
