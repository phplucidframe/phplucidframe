<?php
/**
 * All custom validation helper functions specific to the site should be defined here.
 */

/**
 * Hook for custom validation messages
 * @return string[]
 */
function __validation_messages()
{
    return array(
        # rule => message
        'validate_emailRetyped'     => _t('Your re-typed email address does not match.'),
        'validate_confirmPassword'  => _t('"%s" does not match.'),
    );
}

/**
* The custom validation function to check the retyped email address matching
* This is just for example
*
* @param string $emailRetyped The re-typed email address
* @param string $email The email address to check against
* @return boolean
*/
function validate_emailRetyped($emailRetyped, $email = '')
{
    if (empty($email)) {
        return true;
    }

    return strcasecmp($emailRetyped, $email) == 0;
}
/**
 * Custom validation function for password confirmation
 * This is just for the example admin panel
 *
 * @param $value    (string) confirmed password
 * @param $pwd      (string) password need to be checked
 * @return boolean  TRUE for success; FALSE for failure
 */
function validate_confirmPassword($value, $pwd)
{
    $confirmPwd = trim($value);
    if (empty($confirmPwd)) {
        return true;
    }

    return $confirmPwd == trim($pwd);
}
