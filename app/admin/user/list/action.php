<?php
/**
 * DELETE user
 */
if (sizeof($_POST)) {
    $post = _post($_POST);
    extract($post);
    if (isset($action) && $action == 'delete' && isset($hidDeleteId) && $hidDeleteId) {
        # DELETE
        db_delete('user', array('uid' => $hidDeleteId));
    }
}
