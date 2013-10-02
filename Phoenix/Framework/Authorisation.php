<?php

class Authorisation
{
    public static $redirect_action = 'Index';
    public static $redirect_controller = 'Error';
    public static $redirect_params = array('You do not have permission to access this page.');

    /**
     * The current authorisation method that is being used
     * @var AuthorisationMethod
     */
    public static $_method;

    static function SetAuthorisationMethod($method)
    {
        Authorisation::$_method = $method;
    }

    /**
     * Quickly check if the logged-in user has the specified credentials. Implementation
     * of this function depends on the authorisation method used. For example, the role
     * auth method will check to see if the user's role is equal to the one provided.
     * @param RouteParameters $request
     */
    static function CheckCredentials($cred)
    {
        $user = Authentication::GetUser();
        if ($user == null) return false;
        return Authorisation::$_method->HasCredentials(
            $user,
            $cred
        );
    }

    /**
     * Check a resolved route to see if the current user has permission to access it
     * @param RouteParameters $request
     * @return boolean
     */
    static function Check($request)
    {
        return Authorisation::$_method->HasPermission(
            Authentication::GetUser(),
            $request == null ? null : $request->controller_instance,
            $request == null ? null : $request->action
        );
    }

    /**
     * Returns true if the current user can access the specified controller and action
     * @param  $controller string The controller name
     * @param  $action string The action name
     * @return boolean
     */
    static function CanAccess($controller, $action = null)
    {
        if ($action === null)
        {
            $split = explode('/', $controller);
            if (count($split) != 2)
            {
                return false;
            }
            $controller = $split[0];
            $action = $split[1];
        }
        $cname = $controller.'Controller';
        if (!class_exists($cname))
        {
            return false;
        }
        $con = new $cname;
        return Authorisation::$_method->HasPermission(
            Authentication::GetUser(),
            $con,
            $action
        );
    }
}

Authorisation::SetAuthorisationMethod(new DefaultAuthorisation());

class AuthorisationMethod
{
    function GetAuthValue($controller, $action, $default) {
        $auth = null;
        if ($controller != null) {
            $auth = $controller->authorise;
        }
        if ($auth == null) {
            $auth = array();
        }
        if (array_key_exists($action, $auth)) {
            return $auth[$action];
        } elseif (array_key_exists('*', $auth)) {
            return $auth['*'];
        } else {
            return $default;
        }
    }

    /**
     * @param $user
     * @param $controller
     * @param $action
     * @return boolean
     */
    function HasPermission($user, $controller, $action) {
        // Virtual
    }

    /**
     * @param $user
     * @param $cred
     * @return boolean
     */
    function HasCredentials($user, $cred) {
        // Virtual; Implementation varies depending on auth method
    }
}

/**
 * Default authorisation implementation. Everyone can access everything.
 */
class DefaultAuthorisation extends AuthorisationMethod
{
    function HasPermission($user, $controller, $action) {
        return true;
    }

    function HasCredentials($user, $cred) {
        return true;
    }
}

/**
 * Login based method of authorisation.<br>
 * The controller setup is:
 * <pre>
 * public $authorise = array(
 * 'ActOne' => true, // user must be logged in to access
 * 'ActTwo' => false // anyone can access
 * // other omitted actions are considered to be false
 * );
 * </pre>
 */
class LoggedInAuthorisation extends AuthorisationMethod
{
    function HasPermission($user, $controller, $action) {
        $needslogin = $this->GetAuthValue($controller, $action, false);
        if ($needslogin) {
            return Authentication::IsLoggedIn();
        }
        return true;
    }

    function HasCredentials($user, $cred) {
        return true;
    }
}

/**
 * A level based method of authorisation. Each user has a single level
 * associated with them, contained within the user object. The level must be
 * a numeric value.<br>
 * The controller setup is:
 * <pre>
 * public $authorise = array(
 * 'ActOne' => 10, // user must be level 10 or higher to access
 * 'ActTwo' => 0, // user must be authenticated to access
 * 'ActThree' => -1 // anyone can access
 * // other omitted actions are considered to be -1
 * );
 * </pre>
 */
class LevelAuthorisation extends AuthorisationMethod
{
    public static $field_level = 'Level';

    function HasPermission($user, $controller, $action) {
        $level = $this->GetAuthValue($controller, $action, -1);
        if (!is_numeric($level)) {
            return true;
        }
        if ($level < 0) {
            return true;
        }
        if ($level == 0) {
            return $user != null;
        }
        if ($user != null) {
            return $user->{LevelAuthorisation::$field_level} >= $level;
        }
        return false;
    }

    function HasCredentials($user, $cred) {
        if (!is_numeric($cred)) return false;
        return $user->{LevelAuthorisation::$field_level} >= $cred;
    }
}

/**
 * A permission based method of authorisation. Each user has many permissions
 * associated with them. The permissions must be joined to the user model as
 * as many-to-many relationship.
 * The controller setup is:
 * <pre>
 * public $authorise = array(
 * 'ActOne' => 'ExecActOne', // user must have permission 'ExecActOne' to access
 * 'ActTwo' => '', // user must be authenticated to access
 * 'ActThree' => null // anyone can access
 * // other omitted actions are considered to be null
 * );
 * </pre>
 */
class PermissionAuthorisation extends AuthorisationMethod
{
    private $model_join;
    private $model_permission;
    private $field_name;

    private $_permission_cache;

    function __construct($model_join, $model_permission, $field_name)
    {
        $this->model_join = $model_join;
        $this->model_permission = $model_permission;
        $this->field_name = $field_name;
        $this->_permission_cache = array();
    }

    private function _UpdateCache($user)
    {
        $user_id = $user->{$user->primaryKey};

        $permission_instance = new $this->model_permission;
        $join_instance = new $this->model_join;

        $join_to_permission = $join_instance->one[$this->model_permission];
        $join_permission_this_key = array_keys($join_to_permission); $join_permission_this_key = $join_permission_this_key[0];
        $join_permission_other_key = $join_to_permission[$join_permission_this_key];

        $join_to_user = $join_instance->one[get_class($user)];
        $join_user_this_key = array_keys($join_to_user); $join_user_this_key = $join_user_this_key[0];
        $join_user_other_key = $join_to_user[$join_user_this_key];

        $field_name = $this->field_name;

        $perms = array();
        $sql = "SELECT P.`{$field_name}` AS Name FROM `{$user->table}` U
                INNER JOIN `{$join_instance->table}` UP ON U.`{$join_user_other_key}` = UP.`{$join_user_this_key}`
                LEFT JOIN `{$permission_instance->table}` P ON UP.`{$join_permission_this_key}` = P.`{$join_permission_other_key}`
                WHERE U.`{$user->primaryKey}` = :userid";
        $params = array('userid' => $user_id);
        foreach (CustomQuery::Query($sql, $params) as $row) $perms[] = $row->Name;
        $this->_permission_cache[$user_id] = $perms;
    }

    function HasPermission($user, $controller, $action) {
        $perm = $this->GetAuthValue($controller, $action, null);
        return $this->HasCredentials($user, $perm);
    }

    function HasCredentials($user, $cred)
    {
        if ($cred === null) return true;
        if (!is_string($cred) && !is_array($cred)) return true;

        if ($user == null) return false;
        if ((is_string($cred) && strlen($cred) == 0) || (is_array($cred) && count($cred) == 0)) return true;

        if (is_string($cred)) $cred = array($cred);

        $uid = $user->{$user->primaryKey};
        if (!array_key_exists($uid, $this->_permission_cache) || $this->_permission_cache[$uid] === null) $this->_UpdateCache($user);

        $perms = $this->_permission_cache[$uid];
        foreach ($cred as $c) {
            if (array_search($c, $perms) !== false) {
                return true;
            }
        }
        return false;
    }
}

/**
 * A role based method of authorisation. Each user has a single role
 * associated with them. The roles must be joined to the user model as
 * as many-to-one relationship.
 * The controller setup is:
 * <pre>
 * public $authorise = array(
 * 'ActOne' => array('Admin', 'SuperUser'), // user must be an Admin or a SuperUser to access
 * 'ActTwo' => array(), // user must be authenticated to access
 * 'ActThree' => null // anyone can access
 * // other omitted actions are considered to be null
 * );
 * </pre>
 */
class RoleAuthorisation extends AuthorisationMethod
{
    private $model_role;
    private $field_name;

    function __construct($model_role, $field_name)
    {
        $this->model_role = $model_role;
        $this->field_name = $field_name;
    }

    function HasPermission($user, $controller, $action) {
        $roles = $this->GetAuthValue($controller, $action, null);
        if ($roles === null) {
            return true;
        }
        if (is_string($roles)) {
            $roles = array($roles);
        }
        if (!is_array($roles)) {
            return true;
        }
        if (count($roles) == 0) {
            return $user != null;
        }
        if ($user != null) {
            $rolequery = $user->Get(
                $this->model_role
            );
            $role = $rolequery->{$this->field_name};
            return $role != null && array_search($role, $roles) !== false;
        }
        return false;
    }

    function HasCredentials($user, $cred) {
        $rolequery = $user->Get(
            $this->model_role
        );
        $role = $rolequery->{$this->field_name};
        return $role == $cred;
    }
}

?>
