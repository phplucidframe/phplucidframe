<?php
if (auth_isAnonymous()) {
    flash_set('You are not authenticated. Please log in.', '', 'error');
    _redirect('admin/login');
} else {
    if (auth_isEditor() && _arg(1) == 'user') {
        _page401();
    }
}
