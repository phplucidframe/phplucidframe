<?php
/**
 * The action.php (optional) handles form submission.
 * It should perform form validation, create, update, delete of data manipulation to database.
 * By default, a form is initiated for AJAX and action.php is automatically invoked if the action attribute is not given in the <form> tag.
 */
$success = false;

if (sizeof($_POST)) {
    $post = _post($_POST);
    extract($post);

    $validations = array(
        'txtName' => array( // The HTML id or name of the input element
            'caption'   => _t('Name'), // The caption to show in the error message
            'value'     => $txtName, // The value to be validated
            'rules'     => array('mandatory'), // The valiation rules defined in /helpers/validation_helper.php or /app/helpers/validation_helper.php
        ),
        'txtEmail' => array(
            'caption'   => _t('Email'),
            'value'     => $txtEmail,
            'rules'     => array('mandatory', 'email'),
        ),
        'txtConfirmEmail' => array(
            'caption'   => _t('Re-type Email'),
            'value'     => $txtConfirmEmail,
            'rules'     => array('mandatory', 'email', 'validate_emailRetyped'), // validate_emailRetyped is defined in /app/helpers/validation_helper.php
            'parameters'=> array( // The paramaters (starting from the second arguments) passing to the custom validation functions
                'validate_emailRetyped' => array($txtEmail) // The second argument to the function validation_emailRetyped()
            ),
            'messages'  => array( // The custom error messages by rule
                'mandatory' => _t('Please re-type Email.'),
                'validate_emailRetyped' => _t('Your re-typed email address does not match.')
            )
        ),
        'txaComment' => array(
            'caption'   => _t('Comment'),
            'value'     => $txaComment,
            'rules'     => array('mandatory'),
        ),
    );

    /* form token check && input validation check */
    if (form_validate() === true && Validation::check($validations) === true) {
        /**
        * //// Database operation example
        *
        * $data = array(
        *   // 'fieldname' => data
        *   'name'       => $txtName,
        *   'email'      => $txtEmail,
        *   'comment'    => $txaComment
        * );
        *
        * if (db_insert('comment', $data, $useSlug = false)) { // if the third parameter is omitted, your table must have a field "slug".
        *   $commentId = db_insertId();
        *   $success = true;
        * }
        */
        $success = true; # this should be set to true only when db operation is successful.
        if ($success) {
            form_set('success', true);
            flash_set(_t('Your comment has been posted.'));
            _redirect();
        }
    } else {
        form_set('error', Validation::$errors);
    }
}
