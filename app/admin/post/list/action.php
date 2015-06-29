<?php
/**
 * DELETE post
 */
if (sizeof($_POST)) {
    $post = _post($_POST);
    extract($post);
    if (isset($action) && $action == 'delete' && isset($hidDeleteId) && $hidDeleteId) {
        # DELETE
        db_delete('post', array('postId' => $hidDeleteId));
    }
}
