<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility for session handling and flash messaging
 *
 * @package     LC\Helpers\Session
 * @since       PHPLucidFrame v 1.0.0
 * @copyright   Copyright (c), PHPLucidFrame.
 * @author      Sithu K. <hello@sithukyaw.com>
 * @link        http://phplucidframe.sithukyaw.com
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * @internal
 *
 * Initialize session.
 * @see http://php.net/manual/en/session.configuration.php
 *
 * @return void
 */
function __session_init() {
    $defaultTypes = array('default', 'database');
    $options = array(
        'name'            => 'LCSESSID', # The name of the session which is used as cookie name.
        'table'           => 'lc_sessions', # The table name without prefix that stores the session data. It is only applicable to database session
        'gc_maxlifetime'  => 240, # The number of minutes after which data will be seen as 'garbage' or the time an unused PHP session will be kept alive.
        'cookie_lifetime' => 180 # The number of minutes you want session cookies live for. The value 0 means "until the browser is closed."
    );

    $userSettings = _cfg('session');
    $type = (isset($userSettings['type']) && in_array($userSettings['type'], $defaultTypes)) ? $userSettings['type'] : 'default';

    if ($userSettings && isset($userSettings['options']) && is_array($userSettings['options'])) {
        $options = array_merge($options, $userSettings['options']);
    }

    # The table option must be given for database session
    if ($type === 'database' && !$options['table']) {
        $type = 'default';
    }

    if ($type === 'database') {
        define('LC_SESSION_TABLE', db_prefix() . $options['table']);
    }

    if (isset($options['table'])) { # no need this anymore later
        unset($options['table']);
    }

    # Force to cookie based session management
    $options['use_cookies']      = true;
    $options['use_only_cookies'] = true;
    $options['use_trans_sid']    = false;
    $options['cookie_httponly']  = true;

    foreach ($options as $key => $value) {
        if ($key == 'gc_maxlifetime' || $key == 'cookie_lifetime') $value = $value * 60;
        ini_set('session.'.$key, $value);
    }

    _cfg('session.options', $options);

    if ($type === 'database') {
        session_set_save_handler(
            '__session_open',
            '__session_close',
            '__session_read',
            '__session_write',
            '__session_destroy',
            '__session_clean'
        );
        register_shutdown_function('session_write_close');
    }

    if (function_exists('session_beforeStart')) {
        call_user_func('session_beforeStart');
    }

    session_start();
}
/**
 * @internal
 *
 * A callback for Database Session save handler
 * The open callback executed when the session is being opened.
 *
 * @return boolean Success
 */
function __session_open() {
    return true;
}
/**
 * @internal
 *
 * A callback for database Session save handler
 * The close callback executed when the session is being opened.
 *
 * @return boolean Success
 */
function __session_close() {
    global $lc_session;
    $probability = mt_rand(1, 100);
    if ($probability <= 10) {
        $maxlifetime = $lc_session['options']['gc_maxlifetime'];
        __session_clean($maxlifetime);
    }
    return true;
}
/**
 * @internal
 *
 * A callback for database Session save handler
 * The read callback is executed when the session starts or when `session_start()` is called
 * Used to read from a database session
 *
 * @param  mixed $sessionId The ID that uniquely identifies session in database
 * @return mixed The value of the key or false if it does not exist
 */
function __session_read($sessionId) {
    if (!$sessionId) {
        return false;
    }
    $sql = 'SELECT session FROM '.LC_SESSION_TABLE.' WHERE sid = ":id"';
    $data = db_fetch($sql, array('id' => $sessionId));
    return ($data) ? $data : false;
}
/**
 * @internal
 *
 * A callback for database Session save handler
 * The write callback is called when the session needs to be saved and closed.
 * Helper function called on write for database sessions.
 *
 * @param  integer $sessionId The ID that uniquely identifies session in database
 * @param  mixed   $data      The value of the data to be saved.
 * @return boolean True for successful write, false otherwise.
 */
function __session_write($sessionId, $data) {
    if (!$sessionId) {
        return false;
    }
    global $_conn;
    $record = array(
        'id' => $sessionId,
        'host' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        'timestamp' => time(),
        'session' => $data,
        'useragent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
    );
    $sql = 'REPLACE INTO '.LC_SESSION_TABLE.' (sid, host, timestamp, session, useragent)
            VALUES (":id", ":host", ":timestamp", ":session", ":useragent")';
    return db_query($sql, $record) ? true : false;
}
/**
 * @internal
 *
 * A callback for database Session save handler
 * This destroy callback is executed when a session is destroyed with `session_destroy()`
 * It is called on the destruction of a database session.
 *
 * @param  integer $sessionId The ID that uniquely identifies session in database
 * @return boolean True for successful delete, false otherwise.
 */
function __session_destroy($sessionId) {
    global $_conn;
    return db_delete(LC_SESSION_TABLE, array('sid' => $sessionId)) ? true : false;
}
/**
 * @internal
 *
 * A callback for database Session save handler
 * The garbage collector callback is invoked internally by PHP periodically in order to purge old database session data
 *
 * @param  integer $maxlifetime The value of lifetime which is passed to this callback
 *   that can be set in `$lc_session['options']['gc_maxlifetime']` reflected to `session.gc_maxlifetime`
 * @return boolean Success
 */
function __session_clean($maxlifetime) {
    global $_conn;
    $backTime = time() - $maxlifetime;
    return db_query('DELETE FROM '.LC_SESSION_TABLE.' WHERE timestamp < :backTime', array('backTime' => $backTime)) ? true : false;
}
/**
 * Set a message or value in Session using a name
 *
 * @param $name string The session variable name to store the value
 *  It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param mixed $value The value to be stored.
 * @param boolean $serialize The value is to be serialized or not
 *
 * @return void
 */
function session_set($name, $value='', $serialize=false) {
    __dotNotationToArray($name, 'session', $value, $serialize);
}
/**
 * Get a message or value of the given name from Session
 *
 * @param string $name 	The session variable name to retrieve its value
 *   It can be a value separated by period, eg., user.name will be ['user']['name']
 * @param boolean $unserialize The value is to be unserialized or not
 *
 * @return mixed The value from SESSION
 */
function session_get($name, $unserialize=false) {
    $value = __dotNotationToArray($name, 'session');
    return ($unserialize && is_string($value)) ? unserialize($value) : $value;
}
/**
 * Delete a message or value of the given name from Session
 *
 * @param string $name The session variable name to delete its value
 * @return void
 */
function session_delete($name) {
    $name = S_PREFIX . $name;
    if (isset($_SESSION[$name])) {
        unset($_SESSION[$name]);
        return true;
    }
    $keys = explode('.', $name);
    $firstKey = array_shift($keys);
    if (count($keys)) {
        if (!isset($_SESSION[$firstKey])) return false;
        $array = &$_SESSION[$firstKey];
        $parent = &$_SESSION[$firstKey];
        $found = true;
        foreach ($keys as $k) {
            if (isset($array[$k])) {
                $parent = &$array;
                $array = &$array[$k];
            } else {
                return false;
            }
        }
        $array = NULL;
        unset($array);
        unset($parent[$k]);
    }
    return true;
}

if (!function_exists('flash_set')) {
/**
 * Set the flash message in session
 * This function is overwritable from the custom helpers/session_helper.php
 *
 * @param mixed  $msg   The message or array of messages to be shown
 * @param string $name  The optional session name to store the message
 * @param string $class The HTML class name; default is success
 *
 * @return void
 */
    function flash_set($msg, $name='', $class='success') {
        $msgHTML  = '<div class="message '.$class.'" style="display:block;">';
        $msgHTML .= '<ul>';
        if (is_array($msg)) {
            foreach ($msg as $m) {
                $msgHTML .= '<li><span class="'.$class.'">'.$m.'</span></li>';
            }
        } else {
            $msgHTML .= '<li><span class="'.$class.'">'.$msg.'</span></li>';
        }
        $msgHTML .= '</ul>';
        $msgHTML .= '</div>';

        if ($name) {
            $_SESSION[S_PREFIX.'flashMessage'][$name] = $msgHTML;
        } else {
            $_SESSION[S_PREFIX.'flashMessage'] = $msgHTML;
        }
    }
}

if (!function_exists('flash_get')) {
/**
 * Get the flash message from session and then delete it
 * This function is overwritable from the custom helpers/session_helper.php
 *
 * @param string $name The optional session name to retrieve the message from
 * @param string $class The HTML class name; default is success
 *
 * @return string The HTML message
 */
    function flash_get($name='', $class='success') {
        $message = '';
        if ($name) {
            if (isset($_SESSION[S_PREFIX.'flashMessage'][$name])) {
                $message = $_SESSION[S_PREFIX.'flashMessage'][$name];
                unset($_SESSION[S_PREFIX.'flashMessage'][$name]);
            }
        } else {
            if (isset($_SESSION[S_PREFIX.'flashMessage'])) {
                $message = $_SESSION[S_PREFIX.'flashMessage'];
                unset($_SESSION[S_PREFIX.'flashMessage']);
            }
        }
        return $message;
    }
}
/**
 * Send a cookie
 * Convenience method for setcookie()
 *
 * @param string $name     The name of the cookie. 'cookiename' is called as cookie_get('cookiename') or $_COOKIE['cookiename']
 * @param mixed  $value    The value of the cookie. This value is stored on the clients computer
 * @param int    $expiry   The time the cookie expires. This is a Unix timestamp so is in number of seconds since the epoch.
 *  In other words, you'll most likely set this with the time() function plus the number of seconds before you want it to expire.
 *  If f set to 0, or omitted, the cookie will expire at the end of the session
 * @param string $path     The path on the server in which the cookie will be available on. The default path '/' will make it available to the entire domain.
 * @param string $domain   The domain that the cookie is available to. If it is not set, it depends on the configuration variable $lc_siteDomain.
 * @param bool   $secure   Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client
 * @param bool   $httpOnly When TRUE the cookie will be made accessible only through the HTTP protocol.
 *  This means that the cookie won't be accessible by scripting languages, such as JavaScript
 *
 * @see http://php.net/manual/en/function.setcookie.php
 *
 * @return void
 */
function cookie_set($name, $value, $expiry=0, $path='/', $domain='', $secure=false, $httpOnly=false) {
    if (!$domain) {
        $domain = _cfg('siteDomain');
    }
    $name = preg_replace('/^('.S_PREFIX.')/', '', $name);
    $name = S_PREFIX . $name;
    if ($expiry > 0) $expiry = time() + $expiry;
    setcookie($name, $value, $expiry, $path, $domain, $secure, $httpOnly);
}
/**
 * Get a cookie
 * Convenience method to access $_COOKIE[cookiename]
 * @param string $name The name of the cookie to retrieve
 *
 * @return mixed
 *  The value of the cookie if found.
 *  NULL if not found.
 *  The entire $_COOKIE array if $name is not provided.
 */
function cookie_get($name='') {
    if (empty($name)) {
        return $_COOKIE;
    }
    $name = preg_replace('/^('.S_PREFIX.')/', '', $name);
    $name = S_PREFIX . $name;
    return (isset($_COOKIE[$name])) ? $_COOKIE[$name] : NULL;
}
/**
 * Delete a cookie
 * Convenience method to delete $_COOKIE['cookiename']
 * @param string $name The name of the cookie to delete
 * @param string $path The path on the server in which the cookie will be available on.
 *  This would be the same value used for cookie_set().
 *
 * @return bool TRUE for the successful delete; FALSE for no delete.
 */
function cookie_delete($name, $path='/') {
    if (empty($name)) {
        return $_COOKIE;
    }
    $name = preg_replace('/^('.S_PREFIX.')/', '', $name);
    $name = S_PREFIX . $name;
    if (isset($_COOKIE[$name])) {
        unset($_COOKIE[$name]);
        setcookie($name, NULL, -1, $path);
        return true;
    }
    return (!isset($_COOKIE[$name])) ? true : false;
}

__session_init();
