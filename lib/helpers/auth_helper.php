<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for user authentication system
 *
 * @package     PHPLucidFrame\Core
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @link        http://phplucidframe.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE
 */

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
        throw new \InvalidArgumentException('Required to configure <code class="inline">$lc_auth</code> in <code class="inline">/inc/config.php</code>.');
    }
}

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
        $auth = auth_get();

        if (!$auth) {
            $session = is_object($data) ? $data : auth_getUserInfo($id);
            if (isset($session)) {
                $fieldRole = $lc_auth['fields']['role'];

                // Regenerate session ID to prevent session fixation
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                $session->sessId        = session_id();
                $session->timestamp     = time();
                $session->token         = strtoupper(_randomCode(20));
                $session->permissions   = auth_permissions($session->$fieldRole);

                auth_set($session);

                return $session;
            }
        } else {
            return $auth;
        }

        return false;
    }
}

if (!function_exists('auth_getUserInfo')) {
    /**
     * Get user record from db to create auth session
     * This function is overridable from the custom helpers/auth_helper.php
     * @param int $id User ID
     * @return mixed
     */
    function auth_getUserInfo($id)
    {
        $auth = _cfg('auth');
        $table = db_table($auth['table']);
        $fieldId = $auth['fields']['id'];

        return db_select($table)
            ->where()->condition($fieldId, $id)
            ->getSingleResult();
    }
}

/**
 * Get the namespace for the authentication object
 * The Auth session name can be different upon directory (namespace)
 * But it can also be shared according to $lc_sharedNamespaces
 *
 * @return string
 */
function auth_namespace()
{
    $sites = _cfg('sites');
    $namespaces = _cfg('sharedNamespaces');

    if (LC_NAMESPACE && isset($sites[LC_NAMESPACE]) && isset($namespaces[LC_NAMESPACE])) {
        $namespace = $namespaces[LC_NAMESPACE];
    } else {
        $namespace = LC_NAMESPACE;
    }

    return LC_NAMESPACE ? 'AuthUser.' . $namespace : 'AuthUser.default';
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

if (!function_exists('auth_permissions')) {
    /**
     * Get the permissions of a particular role
     * This function is overridable from the custom helpers/auth_helper.php
     * @param string $role The user role name or id
     * @return array|null Array of permissions of the role
     */
    function auth_permissions($role)
    {
        $auth = _cfg('auth');
        $perms = isset($auth['permissions']) ? $auth['permissions'] : array();

        return isset($perms[$role]) ? $perms[$role] : null;
    }
}

if (!function_exists('auth_role')) {
    /**
     * Check if the authenticated user has the specific user role
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $role The user role name or id
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
     * Check if the authenticated user has the specific user role(s)
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  array|string $roles or [$role, ...] Array of role name or The list of user role names
     * @return boolean
     */
    function auth_roles($roles)
    {
        if (auth_isAnonymous()) {
            return false;
        }

        $auth       = auth_prerequisite();
        $field      = $auth['fields']['role'];
        $session    = auth_get();
        $roles      = is_array($roles) ? $roles : func_get_args();

        return in_array($session->$field, $roles);
    }
}

if (!function_exists('auth_can')) {
    /**
     * Check if the authenticated user has a particular permission
     * This function is overridable from the custom helpers/auth_helper.php
     * @param  string $perm The permission name
     * @return boolean TRUE if the authenticated user has a particular permission, otherwise FALSE
     */
    function auth_can($perm)
    {
        if (auth_isAnonymous()) {
            return false;
        }

        $sess = auth_get();

        if (!is_array($sess->permissions)) {
            return false;
        }

        if (count($sess->permissions) == 0 || in_array($perm, $sess->permissions)) {
            return true;
        }

        return false;
    }
}
