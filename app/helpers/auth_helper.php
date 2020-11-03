<?php
/**
 * All custom authentication helper functions specific to the site should be defined here.
 */

/**
 * Check if the current logged-in user is master administrator or not
 */
function auth_isMaster()
{
    if (auth_isAnonymous()) {
        return false;
    }

    $auth = _app('auth');

    return is_object($auth) & $auth->is_master;
}

/**
 * Check if the current logged-in user is admin or not
 */
function auth_isAdmin()
{
    return auth_role('admin');
}

/**
 * Check if the current logged-in user is editor or not
 */
function auth_isEditor()
{
    return auth_role('editor');
}
