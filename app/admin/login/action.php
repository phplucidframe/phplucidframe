<?php
$success = false;
$error = false;
if (sizeof($_POST)) {
    $post = _post($_POST);
    extract($post);

    # NEW/EDIT
    $validations = array(
        'txtUsername' => array(
            'caption'   => _t('Username'),
            'value'     => $txtUsername,
            'rules'     => array('mandatory')
        ),
        'txtPwd' => array(
            'caption'   => _t('Password'),
            'value'     => $txtPwd,
            'rules'     => array('mandatory')
        )
    );

    if (Form::validate() == true && Validation::check($validations) == true) {
        $args = array();
        $sql = 'SELECT u.* FROM ' . db_prefix() . 'user u
                WHERE LOWER(username) = ":value"
                LIMIT 1';
        $args[':value'] = strtolower($txtUsername);

        if ($result = db_query($sql, $args)) {
            if (! db_numRows($result)) {
                # Other follow-up errors checkup (if any)
                Validation::addError('Username', 'Username does not exists.');
                $error = true;
            } else {
                $user = db_fetchObject($result);
                if ($user->password == _encrypt($txtPwd)) {
                    $success = true;
                    unset($user->password);
                    # Create the Authentication object
                    auth_create($user->uid, $user);
                } else {
                    # Other follow-up errors checkup (if any)
                    Validation::addError('Password', _t('Password does not match.'));
                    $error = true;
                }
            }
        }

        if ($error) {
            Form::set('error', Validation::$errors);
        }

        if ($success) {
            Form::set('success', true);
            Form::set('redirect', _url('admin/post'));
        }

    } else {
        Form::set('error', Validation::$errors);
    }
}
Form::respond('frmLogin'); # Ajax response
