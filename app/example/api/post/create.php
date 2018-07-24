<?php

$data = _post();

$validations['title'] = array(
    'caption'   => _t('Title'),
    'value'     => $data['title'],
    'rules'     => array('mandatory'),
);

$errors = null;
if (validation_check($validations)) {
    /**
     * //// Database operation example
     */
} else {
    $errors = validation_get('errors');
}

_json([
    'errors' => $errors,
    'data' => $data,
]);
