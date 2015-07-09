<?php
if (auth_isAnonymous()) {
    flash_set(_t('Your session is expired.'), '', 'error');
    _redirect('admin/login');
}

$timestamp = _arg(2);
if ($timestamp) {
    if ($timestamp == $_auth->timestamp) {
        # Normal logout process
        auth_clear();
        flash_set(_t('You have signed out successfully.'));
        _redirect('admin/login');
    }
}
