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

    /**
    Form validation prerequites here, for example
    ***
    $validations = array(
        'txtTitle' => array(
            'caption'   => _t('Title'),
            'value'     => $txtTitle,
            'rules'     => array('mandatory'),
        ),
        'txtBody' => array(
            'caption'   => _t('Body'),
            'value'     => $txtBody,
            'rules'     => array('mandatory'),
        ),
    );
    */

    $validations = array(
    );

    if (form_validate($validations)) {
        /**
        Database operations here
        */

        if ($success) {
            form_set('success', true);
            form_set('redirect', _url('home'));
        }
    } else {
        form_set('error', validation_get('errors'));
    }
}
form_respond('formID'); # Ajax response
