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
            //'rules'     => array('mandatory')
        )
    );

    if (form_validate($validations)) {
        $args = array();

        $user = db_select('user', 'u')
            ->where()
            ->condition('LOWER(username)', strtolower($txtUsername))
            ->getSingleResult();
        if ($user) {
            if (($user->username === 'admin' && $user->isMaster) || /* this condition is just for demo */
                ($user->password && $user->password == _encrypt($txtPwd))) {
                $success = true;
                unset($user->password);
                # Create the Authentication object
                auth_create($user->uid, $user);
            } else {
                # Other follow-up errors checkup (if any)
                validation_addError('Password', _t('Password does not match.'));
                $error = true;
            }
        } else {
            # Other follow-up errors checkup (if any)
            validation_addError('Username', 'Username does not exists.');
            $error = true;
        }

        if ($error) {
            form_set('error', validation_get('errors'));
        }

        if ($success) {
            form_set('success', true);
            form_set('redirect', _url('admin/post'));
        }

    } else {
        form_set('error', validation_get('errors'));
    }
}
form_respond('frmLogin'); # Ajax response
