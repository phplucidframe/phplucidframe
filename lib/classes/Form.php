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

namespace LucidFrame\Core;

/**
 * Core utility for AJAX form handling and form validation
 */
class Form
{
    /** @var string The HTML form ID */
    private static $id;
    /** @var array The error messages and their associated HTML ID */
    private static $error = array();
    /** @var boolean TRUE/FALSE for form validation success */
    private static $success = false;
    /** @var string The form message */
    private static $message = '';
    /** @var string URL to be redirect upon form submission completed */
    private static $redirect = '';
    /** @var string The Javascript callback function to be invoked upon form submission completed */
    private static $callback = '';
    /** @var array Array of data that need to send to the client */
    private static $data = array();
    /** @var string The hashed token */
    public static $formToken = '';

    /**
     * Constructor
     */
    public static function init()
    {
        self::$id = '';
        self::$error = array();
        self::$success = false;
        self::$message = '';
        self::$redirect = '';
        self::$callback = '';
        self::$data = array();
    }

    /**
     * Setter for the class properties
     * @param string $key The property name
     * @param mixed $value The value to be set
     * @return void
     */
    public static function set($key, $value = '')
    {
        self::$$key = $value;
    }

    /**
     * Getter for the class properties
     * @param string $key The property name
     * @param mixed $value The default value for the property
     * @return mixed
     */
    public static function get($key, $value = null)
    {
        if (isset(self::$$key)) {
            return self::$$key;
        }

        return $value;
    }

    /**
     * Get the posted CSRF token value
     * @return string|null
     */
    protected static function getPostedCsrfToken(): ?string
    {
        if ($tokenValue = _post(self::getCsrfTokenName())) {
            return $tokenValue;
        }

        $tokenValue = _requestHeader(_cfg('csrfHeaderTokenName'));

        return $tokenValue ?: null;
    }

    /**
     * Get the form token name
     * @return string
     */
    public static function getCsrfTokenName(): string
    {
        return 'lc_formToken_' . _cfg('formTokenName');
    }

    /**
     * Generates (Regenerates) the CSRF token Hash.
     * @return string
     */
    public static function generateToken(): string
    {
        self::$formToken = bin2hex(random_bytes(16));
        session_set(self::getCsrfTokenName(), self::$formToken);

        return self::$formToken;
    }

    /**
     * Restore the CSRF token Hash from Session
     * @return void
     */
    public static function restoreToken()
    {
        self::$formToken = session_get(self::getCsrfTokenName());
    }

    /**
     * Form token generation
     * @return void
     */
    public static function token()
    {
        echo '<input type="hidden" name="' . self::getCsrfTokenName() . '" value="' . _encrypt(self::$formToken) . '" />';
    }

    /**
     * Form token validation
     * @param array $validations The array of validation rules
     * @param array $data The optional data array (if no `value` in $validation, it will be looked up in $data)
     * @return boolean
     */
    public static function validate($validations = null, $data = [])
    {
        $postedToken = self::getPostedCsrfToken();
        if (!$postedToken) {
            Validation::addError('', _t('Invalid form token.'));
            return false;
        }
        $postedToken = _decrypt($postedToken);

        $result = false;
        # check token first
        if (self::$formToken == $postedToken) {
            # check the referer if it is requesting in the same site
            if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] && _cfg('siteDomain')) {
                $siteDomain = _cfg('siteDomain');
                $siteDomain = preg_replace('/^www\./', '', $siteDomain);
                $parsedURL = parse_url($_SERVER['HTTP_REFERER']);
                $parsedURL['host'] = preg_replace('/^www\./', '', $parsedURL['host']);
                if (strcasecmp($siteDomain, $parsedURL['host']) == 0) {
                    $result = true;
                }
            }
        }

        if (!$result) {
            Validation::addError('', _t('Error occurred during form submission. Please refresh the page to try again.'));
            return false;
        }

        if ($validations && Validation::check($validations, $data) === false) {
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
    public static function respond($formId, $errors = null, $forceJson = false)
    {
        self::$id = $formId;
        self::$error = validation_get('errors');
        $ajaxResponse = $errors === null;

        if (is_array($errors) && count($errors)) {
            self::$error = $errors;
            $ajaxResponse = false;
            # if no error message and no other message, no need to respond
            if (count(self::$error) == 0 && empty(self::$message)) {
                return;
            }
        }

        $response = array(
            'formId'    => self::$id,
            'success'   => self::$success ? true : false,
            'error'     => self::$error,
            'msg'       => self::$message,
            'redirect'  => self::$redirect,
            'callback'  => self::$callback,
            'data'      => self::$data,
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
    public static function value($name, $defaultValue = null)
    {
        $value = _post($name);

        return $value ? _h($value) : _h($defaultValue);
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
    public static function htmlValue($name, $defaultValue = null)
    {
        if (count($_POST)) {
            if (!isset($_POST[$name])) {
                return '';
            }
            $value = _xss($_POST[$name]);

            return _h($value);
        }

        return _h($defaultValue);
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
    public static function selected($name, $value, $defaultValue = null)
    {
        return self::inputSelection($name, $value, $defaultValue) ? 'selected="selected"' : '';
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
    public static function checked($name, $value, $defaultValue = null)
    {
        return self::inputSelection($name, $value, $defaultValue) ? 'checked="checked"' : '';
    }

    /**
     * @param string $name The field name of the checkbox or radio button or drop-down list
     * @param mixed $value The value to check against
     * @param mixed $defaultValue The default selected value (optional)
     *
     * @return bool TRUE if the option is found, otherwise FALSE
     * @internal
     * @ignore
     *
     * Allow you to select a checkbox or a radio button or an option of a drop-down list
     *
     */
    public static function inputSelection($name, $value, $defaultValue = null)
    {
        if (count($_POST)) {
            $name = preg_replace('/(\[\])$/', '', $name); // group[] will be replaced as group
            if (!isset($_POST[$name])) {
                return '';
            }
            $postedValue = _post($name);
            if (is_array($postedValue) && in_array($value, $postedValue)) {
                return true;
            } elseif ($value == $postedValue) {
                return true;
            } else {
                return false;
            }
        } else {
            if (is_array($defaultValue) && in_array($value, $defaultValue)) {
                return true;
            } elseif ($value == $defaultValue) {
                return true;
            } else {
                return false;
            }
        }
    }
}
