<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for user authentication system
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <cithukyaw@gmail.com>
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

if (!function_exists('auth_create')) {
    /**
     * Create Authentication object
     * This function is overridable from the custom helpers/auth_helper.php
     *
     * @param  string $id PK value
     * @param  object $data The user data object (optional). If it is not given, auth_create will load it from db
     *
     * @return object|bool The authenticated user object or FALSE on failure
     */
    function auth_create($id, $data = null)
    {
        $lc_auth = auth_prerequisite();
        $auth    = auth_get();
        if (!$auth) {
            $table     = db_table($lc_auth['table']);
            $fieldId   = $lc_auth['fields']['id'];
            $fieldRole = $lc_auth['fields']['role'];

            if (is_object($data)) {
                $session = $data;
            } else {
                $session = db_select($table)
                    ->where()->condition($fieldId, $id)
                    ->getSingleResult();
            }
            if (isset($session)) {
                $session->sessId        = session_id();
                $session->timestamp     = time();
                $session->token         = strtoupper(_randomCode(20));
                $session->permissions   = auth_permissions($session->$fieldRole);
                $session->blocks        = auth_blocks($session->$fieldRole);

                auth_set($session);

                return $session;
            }
        } else {
            return $auth;
        }

        return false;
    }
}

/**
 * Check and get the authentication configuration settings
 */
function auth_prerequisite()
{
    db_prerequisite();

    $auth = _cfg('auth');

    if (isset($auth['table']) && $auth['table'] &&
        isset($auth['fields']['id']) && $auth['fields']['id'] &&
        isset($auth['fields']['role']) && $auth['fields']['role']) {
        return $auth;
    } else {
        _header(400);
        throw new \InvalidArgumentException('Required to configure <code class="inline">$lc_auth</code> in <code class="inline">/inc/config.php</code> or <code class="inline">/inc/site.config.php</code>.');
    }
}

/**
 * Get the namespace for the authentication object
 * Sometimes, the Auth session name should be different upon directory (namespace)
 *
 * @return string
 */
function auth_namespace()
{
    return LC_NAMESPACE ? 'AuthUser.' . LC_NAMESPACE : 'AuthUser.default';
}

/**
 * Get the authenticated user object from Session
 * @return mixed
 */
function auth_get()
{
    return session_get(auth_namespace(), true);
}

/**
 * Set the authenticated user object to Session
 * @param object $sess The authentication object
 */
function auth_set($sess)
{
    _app('auth', $sess);
    session_set(auth_namespace(), $sess, true);
}

/**
 * Clear the authenticated user object from session
 */
function auth_clear()
{
    session_delete(auth_namespace());
    _app('auth', null);
}

/**
 * Check if a user is not authenticated
 * @return bool TRUE if user is not authenticated, otherwise FALSE
 */
function auth_isAnonymous()
{
    $auth    = auth_prerequisite();
    $field   = $auth['fields']['id'];
    $session = auth_get();

    return (is_object($session) && $session->$field > 0) ? false : true;
}

/**
 * Check if a user is authenticated
 * @return boolean
 */
function auth_isLoggedIn()
{
    return ! auth_isAnonymous();
}

if (!function_exists('auth_role')) {
    /**
     * Check if the authenticated user has the specific user role
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $role The user role name or key
     * @return boolean
     */
    function auth_role($role)
    {
        if (auth_isAnonymous()) {
            return false;
        }

        $auth     = auth_prerequisite();
        $field    = $auth['fields']['role'];
        $session  = auth_get();

        return $session->$field == $role;
    }
}

if (!function_exists('auth_roles')) {
    /**
     * Check if the authenticate user has the specific user role(s)
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $roles The user role names
     * @return boolean
     */
    function auth_roles()
    {
        if (auth_isAnonymous()) {
            return false;
        }

        $auth       = auth_prerequisite();
        $field      = $auth['fields']['role'];
        $session    = auth_get();
        $roles      = func_get_args();

        return in_array($session->$field, $roles);
    }
}

if (!function_exists('auth_permissions')) {
    /**
     * Get the permissions of a particular role
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $role The user role name or key
     * @return array Array of permissions of the role
     */
    function auth_permissions($role)
    {
        $auth = _cfg('auth');
        $perms = isset($auth['perms']) ? $auth['perms'] : array();

        return isset($perms[$role]) ? $perms[$role] : array();
    }
}

if (!function_exists('auth_blocks')) {
    /**
     * Get the blocked permissions of a particular role
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $role The user role name or key
     * @return array Array of permissions of the role
     */
    function auth_blocks($role)
    {
        $auth = _cfg('auth');
        $perms = isset($auth['block']) ? $auth['block'] : array();

        return isset($perms[$role]) ? $perms[$role] : array();
    }
}

if (!function_exists('auth_access')) {
    /**
     * Check if the authenticated user has a particular permission
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $perm The permission key
     * @return boolean TRUE if the authenticated user has a particular permission, otherwise FALSE
     */
    function auth_access($perm)
    {
        if (auth_isAnonymous()) {
            return false;
        }

        $sess = auth_get();
        if (in_array($perm, $sess->permissions)) {
            return true;
        }

        return false;
    }
}

if (!function_exists('auth_block')) {
    /**
     * Check if the authenticated user is blocked for a particular permission
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $perm The permission key
     * @return boolean TRUE if the authenticated user is blocked for a particular permission, otherwise FALSE
     */
    function auth_block($perm)
    {
        if (auth_isAnonymous()) {
            return true;
        }

        $sess = auth_get();
        if (in_array($perm, $sess->blocks)) {
            return true;
        }

        return false;
    }
}
