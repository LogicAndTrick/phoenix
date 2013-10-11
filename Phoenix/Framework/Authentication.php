<?php

Authentication::RegisterPasswordHasher(new AlgorithmHasher());
Authentication::RegisterDataExtractor(new StandardAuthenticationDataExtractor());

class AuthenticationMethod
{

    public function InterceptRequest($config) { }

    public function CanLogin($config) { }

    /**
     * @param AuthenticationPersistence $store
     * @param array $config
     * @return object
     */
    public function Login($store, $config) { }

    public function Logout() { }
}

class SessionAuthentication
{
    public function InterceptRequest($config) {
        return true;
    }

    public function CanLogin($config) {
        return Session2::Get($config['session']['id']) !== null;
    }

    /**
     * @param AuthenticationPersistence $store
     * @param array $config
     * @return object
     */
    public function Login($store, $config) {
        $id = Session2::Get($config['session']['id']);
        if ($id === null) return false;
        $user = $store->Fetch(array(
            $config['model']['id'] => $id
        ));
        return $user ? $user : false;
    }
}

class CookieAuthentication
{

    public function InterceptRequest($config) {
        return $config['cookie']['enabled'];
    }

    public function CanLogin($config) {
        return $config['cookie']['enabled']
            && Cookie::Get($config['cookie']['id']) !== null
            && Cookie::Get($config['cookie']['code']) !== null;
    }

    /**
     * @param AuthenticationPersistence $store
     * @param array $config
     * @return object
     */
    public function Login($store, $config) {
        if (!$config['cookie']['enabled']) return false;
        $id = Cookie::Get($config['cookie']['id']);
        $code = Cookie::Get($config['cookie']['code']);
        if ($id === null || $code === null) return false;
        $user = $store->Fetch(array(
            $config['model']['id'] => $id,
            $config['model']['cookie'] => $code,
        ));
        if (!$user) {
            Cookie::Clear($config['cookie']['id']);
            Cookie::Clear($config['cookie']['code']);
            return false;
        }
        return $user;
    }
}

class FormsAuthentication
{
    public function InterceptRequest($config) {
        return $config['post']['login_automatically'];
    }

    public function CanLogin($config) {
        return Post::Get($config['post']['username']) != null
            && Post::Get($config['post']['password']) != null;
    }

    /**
     * @param AuthenticationPersistence $store
     * @param array $config
     * @return object
     */
    public function Login($store, $config) {
        $username = Post::Get($config['post']['username']);
        $password = Post::Get($config['post']['password']);
        if ($username === null || $password === null) return false;

        // Username and email must be unique (password is matched after the user object is found)
        $user = $store->Fetch(array(
            $config['model']['username'] => $username
        ));
        if (!$user && $config['post']['username_match_email']) {
            $user = $store->Fetch(array(
                $config['model']['email'] => $username
            ));
        }

        // Match the password using the salt from the database
        if ($user) {
            $salt = $store->Get($user, $config['model']['salt']);
            $hashed = Authentication2::HashPassword($password, $salt);
            $password = $store->Get($user, $config['model']['password']);
            if ($hashed != $password) {
                $user = false;
            }
        }

        if (!$user) {
            Validation::AddError($config['post']['username'], $config['post']['error']);
            return false;
        }
        return $user;
    }
}

class OAuth2Authentication
{

}

class OpenIDAuthentication
{
    /**
     * @var LightOpenID
     */
    private $openid = null;
    private $valid;

    private function Init($config) {
        if ($this->openid !== null) return;
        $this->openid = new LightOpenID($config['openid']['host']);
        if ($config['openid']['realm'] != null) $this->openid->realm = $config['openid']['realm'];
        $this->valid = $this->openid->validate();
    }

    public function InterceptRequest($config) {
        $this->Init($config);
        return $this->valid || $config['openid']['login_automatically'];
    }

    public function CanLogin($config) {
        $this->Init($config);
        return $this->valid
            || Post::Get($config['post']['openid']) != null;
    }

    /**
     * @param AuthenticationPersistence $store
     * @param array $config
     * @return object
     */
    public function Login($store, $config) {
        if ($this->valid) {
            return $this->FinishLogin($store, $config);
        } else if (Post::Get($config['post']['openid']) != null) {
            return $this->StartLogin($store, $config);
        }
        return false;
    }

    private function StartLogin($store, $config) {
        $url = Post::Get($config['post']['openid']);
        $remember = Post::Get($config['post']['remember']);
        if ($url != null) {
            Session2::Set($config['openid']['session_remember'], $remember);
            $this->openid->identity = $url;
            Headers::Redirect($this->openid->authUrl());
            exit();
        }
        return false;
    }

    private function FinishLogin($store, $config) {
        $this->Init($config);
        if (!$this->openid->mode || !$this->openid->mode != 'cancel' || !$this->valid) return false;
        $identity = $this->openid->identity;
        $user = $store->Fetch(array(
            $config['model']['identity'] => $identity
        ));
        return $user ? $user : null;
    }
}

class AuthenticationPersistence
{
    /**
     * @param array $fields
     * @return object
     */
    function Fetch($fields) { }
    function Store($object) { }
    function Set($object, $key, $value) { }
    function Get($object, $key) { }
}

class DatabaseAuthenticationPersistence
{

}

class Authentication2
{
    private static $_methods = array();

    /**
     * @var AuthenticationPersistence
     */
    private static $_persistence = null;
    private static $_hash = null;

    public static $config = array(
        'model' => array(
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'identity' => 'Identity',
            'salt' => 'Salt',
            'email' => 'Email',
            'cookie' => 'Cookie',
            'num_login' => 'NumLogins',
            'last_login' => 'LastLogin',
            'last_access' => 'LastAccess',
            'last_page' => 'Page',
            'ip' => 'IP',
            'unlock' => 'Unlock'
        ),
        'session' => array(
            'id' => 'phoenix_session'
        ),
        'cookie' => array(
            'enabled' => true,
            'id' => 'phoenix_id',
            'code' => 'phoenix_code',
            'timeout' => 30000000
        ),
        'post' => array(
            'username' => 'username',
            'password' => 'password',
            'remember' => 'remember',
            'password_confirm' => 'password_confirm',
            'email' => 'email',
            'openid' => 'openid',
            'login_automatically' => true,
            'username_match_email' => true,
            'error' => 'This username and password combination did not match any registered accounts.'
        ),
        'openid' => array(
            'host' => null,
            'realm' => null,
            'session_remember' => 'temp_phoenix_authentication_remember',
            'login_automatically' => true
        ),
        'ban' => array(
            'enabled' => false,
            'model' => 'Ban',
            'action' => 'Index',
            'controller' => 'Banned',
            'user_id' => 'UserID',
            'ip' => 'IP',
            'time' => 'Time',
            'text' => 'Text'
        )
    );

    /**
     * Merge two arrays recursively. Keys in the second array overwrite keys in the first array.
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected static function MergeArrays($array1, $array2) {
        $merged = array_merge(array(), $array1); // Copy the array, don't want side effects
        foreach ($array2 as $key => $value) {
            if (array_key_exists($key, $merged) && is_array($value) && is_array($merged[$key])) {
                $merged[$key] = Authentication2::MergeArrays($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    public static function Configure($config) {
       Authentication2::$config = Authentication2::MergeArrays(Authentication2::$config, $config);
    }

    private static $_enabled = false;

    static function AddAuthenticationMethod($method)
    {
        Authentication2::$_methods[] = $method;
    }

    static function SetPersistenceMethod($method)
    {
        Authentication2::$_persistence = $method;
    }

    static function SetPasswordHasher($hasher)
    {
        Authentication2::$_hash = $hasher;
    }

    static function HashPassword($password, $salt = '')
    {
        return Authentication2::$_hash->Hash($password, $salt);
    }

    static function IsLoggedIn()
    {
        throw new Exception("Not implemented");
    }

    static function GetUser()
    {
        throw new Exception("Not implemented");
    }

    static function GetUserID()
    {
        throw new Exception("Not implemented");
    }

    static function GetUserName()
    {
        throw new Exception("Not implemented");
    }

    static function Enable()
    {
        if (Authentication2::$_hash == null) throw new Exception("You must set the Authentication hashing function.");
        if (Authentication2::$_persistence == null) throw new Exception("You must set the persistence method.");
        if (count(Authentication2::$_methods) == 0) throw new Exception("You must add at least one authentication method.");

        // Make sure the registered classes are configured correctly
        Authentication2::$_persistence->Validate();
        foreach (Authentication2::$_methods as $method) $method->Validate();

        Authentication2::$_enabled = true;
        Authentication2::Login(true);
    }

    static function Login($intercept = false)
    {
        $cfg = Authentication2::$config;
        $store = Authentication2::$_persistence;
        $user = false;
        $successful_method = null;
        foreach (Authentication2::$_methods as $method) {
            if ((!$intercept || $method->InterceptRequest($cfg)) && $method->CanLogin($cfg)) {
                $user = $method->Login($store, $cfg);
                if ($user) {
                    $successful_method = $method;
                    break;
                }
            }
        }
        if ($user) Authentication2::LoginSuccess($user, $successful_method);
    }

    static function LoginSuccess($user, $method)
    {
        $config = Authentication2::$config;
        $store = Authentication2::$_persistence;
        $session = $config['session']['id'];
        $model = $config['model'];

        Session2::Set($session, $store->Get($user, $model['id']));

        if ($method->CountsAsLogin()) {
            Session2::Set($session.'_last_login', $store->Get($user, $model['last_access']));
            $store->Set($user, $model['num_login'], $store->Get($user, $model['num_login']));
            $store->Set($user, $model['last_login'], gmdate("Y-m-d H:i:s"));
            $store->Set($user, $model['ip'], $_SERVER['REMOTE_ADDR']);

            if ($config['cookie']['enabled']) {
                $salt = $store->get($user, $model['salt']) || Authentication2::RandomString();
                $salt = substr($salt, 0, strlen($salt) / 2); // Don't reveal the entire salt in the cookie, even if we're hashing it beforehand
                $salt = Authentication2::HashPassword($store->Get($user, $model['username']), $salt . '_Phoenix_Cookie');
                $cookie = Authentication2::ConstantString($salt);
                $store->Set($user, $model['cookie'], $cookie);
                Cookie::Set($config['cookie']['id'], $store->Get($user, $model['id']), $config['cookie']['timeout']);
                Cookie::Set($config['cookie']['code'], $store->Get($user, $model['cookie']), $config['cookie']['timeout']);
            }
        }

        $store->Set($user, $model['last_access'], gmdate("Y-m-d H:i:s"));
        $store->Set($user, $model['last_page'], array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '');

        $store->Store($user);
    }

    static function Logout()
    {
        $config = Authentication2::$config;
        if ($config['cookie']['enabled']) {
            Cookie::Clear($config['cookie']['id']);
            Cookie::Clear($config['cookie']['code']);
        }
        $_SESSION = array();
        session_destroy();
    }

    static function RandomString($seed = '')
    {
        $seed .= 'Phoenix_Authentication_Random_String';
        for ($i = 0; $i < 5; $i++) {
            $seed = hash('sha256', $seed . '_random_' . $i . '_' . mt_rand(0, 500000));
        }
        return $seed;
    }

    static function ConstantString($seed)
    {
        $seed .= 'Phoenix_Authentication_Constant_String';
        for ($i = 0; $i < 5; $i++) {
            $seed = hash('sha256', $seed . '_constant_' . $i);
        }
        return $seed;
    }
}

class Authentication
{
    public static $user = null;
    
    public static $model = 'User';

    public static $field_id = 'ID';
    public static $field_username = 'Username';
    public static $field_password = 'Password';
    public static $field_email = 'Email';
    public static $field_openid = 'OpenID';
    public static $field_cookie = 'Cookie';
    public static $field_numlogins = 'NumLogins';
    public static $field_lastlogin = 'LastLogin';
    public static $field_lastaccess = 'LastAccess';
    public static $field_lastpage = 'LastPage';
    public static $field_ip = 'IP';
    public static $field_unlocker = 'Unlock';

    public static $cookie_id = 'phoenix_username';
    public static $cookie_code = 'phoenix_code';
    public static $cookie_timeout = 30000000;
    
    public static $post_username = 'username';
    public static $post_password = 'password';
    public static $post_password_confirm = 'password_confirm';
    public static $post_remember = 'remember';
    public static $post_logout = 'logout';
    public static $post_openid = 'openid';
    public static $post_email = 'email';
    public static $post_register = 'register';

    public static $email_from_name;
    public static $email_from_email;
    public static $email_subject;
    public static $email_content;
    public static $email_sendmail_envelope = true;

    public static $session_id = 'phoenix_login';
    public static $autologin = false;
    public static $useopenid = true;
    public static $openid_host = null;
    public static $emailrequired = true;
    public static $emailconfirmation = false;

    public static $ban_enabled = false;
    public static $ban_model = 'Ban';
    public static $ban_field_userid = 'UserID';
    public static $ban_field_ip = 'IP';
    public static $ban_field_time = 'Time';
    public static $ban_field_text = 'Text';
    public static $ban_action = 'Index';
    public static $ban_controller = 'Banned';

    public static $_enabled = false;
    public static $_hasher;
    public static $_extractor;
    private static $_openid;

    static function RegisterPasswordHasher($passhash)
    {
        Authentication::$_hasher = $passhash;
    }

    static function HashPassword($password)
    {
        return Authentication::$_hasher->Hash($password);
    }

    static function RegisterDataExtractor($extractor)
    {
        Authentication::$_extractor = $extractor;
    }

    static function ExtractData($username, $hashed_password, $additional_info)
    {
        return Authentication::$_extractor->GetUser($username, $hashed_password, $additional_info);
    }

    static function Enable()
    {
        if (!Database::$_enabled) {
            echo 'You cannot use authentication without the database enabled.';
            return;
        }
        if (Authentication::$openid_host === null) {
            Authentication::$useopenid = false;
        }
        if (Authentication::$useopenid)
        {
            Authentication::$_openid = new LightOpenID(Authentication::$openid_host);
        }
        Authentication::$_enabled = true;
        session_start();
        Authentication::LoginCurrentUser();
    }

    static function IsLoggedIn()
    {
        return Authentication::$user != null;
    }

    static function GetUser()
    {
        return Authentication::$user;
    }

    static function GetUserID()
    {
        return Authentication::$user != null ?
            Authentication::$user->{Authentication::$field_id} :
            null;
    }

    static function GetUserName()
    {
        return Authentication::$user != null ?
            Authentication::$user->{Authentication::$field_username} :
            null;
    }

    static function Login($username = null, $password = null, $remember = false, $additional_info = array())
    {
        $user = null;
        if (($username == null || $password == null) && Authentication::IsAutologinEnabled()) {
            $username = Post::Get(Authentication::$post_username);
            $password = Post::Get(Authentication::$post_password);
            $remember = Post::Get(Authentication::$post_remember);
        }
        if ($username != null && $password != null) {
            $password = Authentication::$_hasher->Hash($password);
            $user = Authentication::ExtractData($username, $password, $additional_info);
            if ($user != null) {
                $unlock = $user->{Authentication::$field_unlocker};
                if (Authentication::$emailrequired && Authentication::$emailconfirmation && $unlock != null && $unlock != '')
                {
                    Validation::AddError(Authentication::$post_username, 'Please check your email to activate your account!');
                    return null;
                }
                $_SESSION[Authentication::$session_id] = $user->{Authentication::$field_id};
                if (Authentication::AreCookiesEnabled() && $remember) {
                    $user->{Authentication::$field_cookie} = Authentication::GenerateCookieCodeForUsername($user->{Authentication::$field_username});
                    setcookie(Authentication::$cookie_id,   $user->{Authentication::$field_id},     time() + Authentication::$cookie_timeout, Phoenix::$base_url);
                    setcookie(Authentication::$cookie_code, $user->{Authentication::$field_cookie}, time() + Authentication::$cookie_timeout, Phoenix::$base_url);
                }
                $_SESSION[Authentication::$session_id.'_LastLogin'] = $user->{Authentication::$field_lastaccess};
                $user->{Authentication::$field_numlogins}++;
                $user->{Authentication::$field_lastlogin} = date("Y-m-d H:i:s");
                $user->{Authentication::$field_ip} = $_SERVER['REMOTE_ADDR'];
                $user->{Authentication::$field_lastaccess} = date("Y-m-d H:i:s");
                $user->{Authentication::$field_lastpage} = array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '';
                $user->Save();
            }
            else
            {
                Validation::AddError(Authentication::$post_username, 'Username/Password combination not found!');
            }
        }
        return $user;
    }

    static function LoginOpenID()
    {
        $user = null;
        $remember = $_SESSION['Phoenix_Temp_Authentication_RememberMe'];
        unset($_SESSION['Phoenix_Temp_Authentication_RememberMe']);
        $openid = Authentication::$_openid;
        if ($openid->mode && $openid->mode != 'cancel' && $openid->validate())
        {
            $id = $openid->identity;
            $obj = new Authentication::$model;
            $user_arr = Model::Search(
                Authentication::$model,
                array(
                    $obj->GetDbName(Authentication::$field_openid).' = :openid'
                ),
                array(
                    ':openid' => $id
                )
            );
            if (is_array($user_arr) && count($user_arr) == 1) {
                $user = $user_arr[0];
                $unlock = $user->{Authentication::$field_unlocker};
                if (Authentication::$emailrequired && Authentication::$emailconfirmation && $unlock != null && $unlock != '')
                {
                    return null;
                }
                $_SESSION[Authentication::$session_id] = $user->{Authentication::$field_id};
                if (Authentication::AreCookiesEnabled() && $remember) {
                    $user->{Authentication::$field_cookie} = Authentication::GenerateCookieCodeForUsername($user->{Authentication::$field_username});
                    setcookie(Authentication::$cookie_id,   $user->{Authentication::$field_id},     time() + Authentication::$cookie_timeout, Phoenix::$base_url);
                    setcookie(Authentication::$cookie_code, $user->{Authentication::$field_cookie}, time() + Authentication::$cookie_timeout, Phoenix::$base_url);
                }
                $_SESSION[Authentication::$session_id.'_LastLogin'] = $user->{Authentication::$field_lastaccess};
                $user->{Authentication::$field_numlogins}++;
                $user->{Authentication::$field_lastlogin} = date("Y-m-d H:i:s");
                $user->{Authentication::$field_ip} = $_SERVER['REMOTE_ADDR'];
                $user->{Authentication::$field_lastaccess} = date("Y-m-d H:i:s");
                $user->{Authentication::$field_lastpage} = array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '';
                $user->Save();
            }
        }
        return $user;
    }

    static function BeginLoginOpenID($url = null, $remember = false)
    {
        if ($url == null && Authentication::IsAutologinEnabled()) {
            $url = Post::Get(Authentication::$post_openid);
            $remember = Post::Get(Authentication::$post_remember);
        }
        if ($url != null) {
            $_SESSION['Phoenix_Temp_Authentication_RememberMe'] = $remember;
            $openid = Authentication::$_openid;
            $openid->identity = $url;
            header('Location: ' . $openid->authUrl());
            exit();
        }
    }

    static function Logout()
    {
        $_SESSION = array();
        session_destroy();
        $cookieid = Authentication::$cookie_id;
        $cookiecode = Authentication::$cookie_code;
        setcookie($cookieid,   '', time() - 1, Phoenix::$base_url);
        setcookie($cookiecode, '', time() - 1, Phoenix::$base_url);
        Authentication::$user = null;
    }

    static function AreCookiesEnabled()
    {
        return Authentication::$cookie_id != null
            && Authentication::$cookie_code != null
            && Authentication::$field_cookie != null
            && Authentication::$cookie_timeout > 0;
    }

    static function IsAutologinEnabled()
    {
        return Authentication::$post_username != null
            && Authentication::$post_password != null
            && Authentication::$autologin == true;
    }

    static function GetLastLoginTime() {
        if (!Authentication::IsLoggedIn() || !isset($_SESSION[Authentication::$session_id.'_LastLogin'])) return null;
        return $_SESSION[Authentication::$session_id.'_LastLogin'];
    }

    static function LoginCurrentUser()
    {
        $user = null;
        $model = Authentication::$model;
        $session = Authentication::$session_id;
        $idfield = Authentication::$field_id;
        $namefield = Authentication::$field_username;
        $passfield = Authentication::$field_password;
        $cookiesenabled = Authentication::AreCookiesEnabled();
        $autologinenabled = Authentication::IsAutologinEnabled();
        $cookieid = Authentication::$cookie_id;
        $cookiecode = Authentication::$cookie_code;
        $cookiefield = Authentication::$field_cookie;
        $loginname = Authentication::$post_username;
        $loginpass = Authentication::$post_password;
        $loginrem = Authentication::$post_remember;
        $logout = Authentication::$post_logout;
        $useopen = Authentication::$useopenid;
        $openidfield = Authentication::$post_openid;
        $openid = Authentication::$_openid;

        $register = Authentication::$post_register;

        $updateinfo = false;

        if (isset($_SESSION[$session]))
        {
            // Try to find the user from the session data first
            $user = new $model($_SESSION[$session]);
            if ($user->$idfield != $_SESSION[$session]) {
                $user = null;
            } else {
                $updateinfo = true;
            }
        }
        else if ($cookiesenabled && isset($_COOKIE[$cookieid]) && isset($_COOKIE[$cookiecode]))
        {
            // Next try the cookie data
            $user = new $model($_COOKIE[$cookieid]);
            if ($user->$cookiefield != $_COOKIE[$cookiecode]) {
                $user = null;
                setcookie($cookieid,   '', time() - 1, Phoenix::$base_url);
                setcookie($cookiecode, '', time() - 1, Phoenix::$base_url);
            } else {
                $_SESSION[$session] = $user->$idfield;
                $_SESSION[Authentication::$session_id.'_LastLogin'] = $user->{Authentication::$field_lastaccess};
                $user->{Authentication::$field_numlogins}++;
                $user->{Authentication::$field_lastlogin} = date("Y-m-d H:i:s");
                $user->{Authentication::$field_ip} = $_SERVER['REMOTE_ADDR'];
                $updateinfo = true;
            }
        }
        else if ($useopen && $openid->mode && $openid->mode != 'cancel' && !isset($_POST[$register]) && !isset($_SESSION['Phoenix_Temp_Authentication_Register']))
        {
            // OpenID login was successful
            $user = Authentication::LoginOpenID();
        }
        else if ($useopen && !$openid->mode && isset($_POST[$openidfield]) && !isset($_POST[$register]))
        {
            // Begin OpenID login
            Authentication::BeginLoginOpenID();
        }
        else if ($autologinenabled && isset($_POST[$loginname]) && isset($_POST[$loginpass]) && !isset($_POST[$register]))
        {
            // Finally try the postback
            $user = Authentication::Login();
        }

        if ($user !== null && $updateinfo) {
            $user->{Authentication::$field_lastaccess} = date("Y-m-d H:i:s");
            $user->{Authentication::$field_lastpage} = array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '';
            $user->Save();
        }

        if ($autologinenabled && $logout != null && $updateinfo && isset($_POST[$logout])) {
            // User is logging out (and is logged in via session or cookie)
            Authentication::Logout();
            $user = null;
        }

        Authentication::$user = $user;
        return ($user !== null);
    }

    static function ValidateRegister()
    {
        $namepost = Authentication::$post_username;
        $emailpost = Authentication::$post_email;
        $passpost = Authentication::$post_password;
        $passpost2 = Authentication::$post_password_confirm;
        $emailrequired = Authentication::$emailrequired;

        if (Authentication::$useopenid
            && isset($_SESSION['Phoenix_Temp_Authentication_Register'])
            && $_SESSION['Phoenix_Temp_Authentication_Register'] == 'openid'
            && isset($_SESSION['Phoenix_Temp_Authentication_Username'])
            && (isset($_SESSION['Phoenix_Temp_Authentication_Email']) || !$emailrequired))
        {
            $_POST[$namepost] = $_SESSION['Phoenix_Temp_Authentication_Username'];
            $_POST[$emailpost] = $_SESSION['Phoenix_Temp_Authentication_Email'];
            $_POST[Authentication::$post_register] = 'openid';
            $openid = Authentication::$_openid;
            $bad_id = true;
            if ($openid->mode && $openid->mode != 'cancel' && $openid->validate())
            {
                $id = $openid->identity;
                $bad_id = Query::Create(Authentication::$model)->Where(Authentication::$field_openid, '=', $id)->Count() > 0;
                if ($bad_id) Validation::AddError(Authentication::$field_openid, 'An account has already been created for this OpenID.');
            }
            if ($bad_id)
            {
                unset($_SESSION['Phoenix_Temp_Authentication_Register']);
                unset($_SESSION['Phoenix_Temp_Authentication_Username']);
                unset($_SESSION['Phoenix_Temp_Authentication_Email']);
                $_SERVER['REQUEST_METHOD'] = 'POST';
                return false;
            }
            return true;
        }

        if (!Post::IsPostBack()) return false;


        $register = $_POST[Authentication::$post_register];
        if ($register != 'openid' && $register != 'account') return false;

        $username = $_POST[$namepost];
        $email = $_POST[$emailpost];

        if ($username === null || $username == '')
        {
            Validation::AddError($namepost, 'Please enter a username.');
            $bad_user = true;
        }
        else
        {
            $bad_user = Query::Create(Authentication::$model)->Where(Authentication::$field_username, '=', $username)->Count() > 0;
            if ($bad_user) Validation::AddError($namepost, 'This username is already in-use, please choose another.');
        }

        $bad_email = false;
        if (Authentication::$emailrequired)
        {
            if ($email === null || $email == '' || preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}/i', $email) == 0)
            {
                Validation::AddError($emailpost, 'Please enter a valid email.');
                $bad_email = true;
            }
            else
            {
                $bad_email = Query::Create(Authentication::$model)->Where(Authentication::$field_email, '=', $email)->Count() > 0;
                if ($bad_email) Validation::AddError($emailpost, 'An account has already been created for this email address.');
            }
        }

        if (Authentication::$useopenid && $register == 'openid' && !$bad_email && !$bad_user) {
            $oid = $_POST[Authentication::$post_openid];
            if ($oid === null || $oid == '')
            {
                Validation::AddError(Authentication::$post_openid, 'Please enter your OpenID URL.');
                return false;
            }
            $_SESSION['Phoenix_Temp_Authentication_Register'] = $register;
            $_SESSION['Phoenix_Temp_Authentication_Username'] = $username;
            $_SESSION['Phoenix_Temp_Authentication_Email'] = $email;
            $openid = Authentication::$_openid;
            $openid->identity = $oid;
            header('Location: ' . $openid->authUrl());
            exit();
        } else if ($register == 'account') {
            $pass = $_POST[$passpost];
            $pass2 = $_POST[$passpost2];

            if ($pass === null || strlen($pass) < 6)
            {
                Validation::AddError($passpost, 'Your password must be at least 6 characters long.');
                return false;
            }
            if ($pass != $pass2)
            {
                Validation::AddError($passpost2, 'The two password fields must be identical.');
                return false;
            }
        }
        return (!$bad_email && !$bad_user);
    }

    static function RegisterUser()
    {
        $register = $_POST[Authentication::$post_register];
        if (Authentication::$useopenid && $register == 'openid') {
            $user = Authentication::RegisterOpenIDUser(
                $_SESSION['Phoenix_Temp_Authentication_Username'],
                Authentication::$_openid->identity,
                $_SESSION['Phoenix_Temp_Authentication_Email']
            );
            unset($_SESSION['Phoenix_Temp_Authentication_Register']);
            unset($_SESSION['Phoenix_Temp_Authentication_Username']);
            unset($_SESSION['Phoenix_Temp_Authentication_Email']);
            return $user;
        } else if ($register == 'account') {
            return Authentication::RegisterRegularUser(
                $_POST[Authentication::$post_username],
                $_POST[Authentication::$post_password],
                $_POST[Authentication::$post_email]
            );
        }
        return false;
    }

    static function RegisterRegularUser($username, $password, $email)
    {
        $user = new Authentication::$model;
        $user->{Authentication::$field_username} = $username;
        $user->{Authentication::$field_email} = $email;
        $user->{Authentication::$field_password} = Authentication::HashPassword($password);
        Authentication::PopulateUserFields($user);
        return $user;
    }

    static function RegisterOpenIDUser($username, $openid, $email)
    {
        $user = new Authentication::$model;
        $user->{Authentication::$field_username} = $username;
        $user->{Authentication::$field_email} = $email;
        $user->{Authentication::$field_openid} = $openid;
        Authentication::PopulateUserFields($user);
        return $user;
    }

    static function UnlockUserAccount($user, $usercode)
    {
        if ($user->ID === null) return false;
        if ($usercode === null || $usercode == '') return false;
        if ($user->{Authentication::$field_unlocker} == $usercode)
        {
            $user->{Authentication::$field_unlocker} = '';
            $user->Save();
            return true;
        }
        return false;
    }

    static function PopulateUserFields($user)
    {
        $user->{Authentication::$field_cookie} = Authentication::GenerateCookieCodeForUsername($user->{Authentication::$field_username});
        $user->{Authentication::$field_numlogins} = 1;
        $user->{Authentication::$field_lastlogin} = date("Y-m-d H:i:s");
        $user->{Authentication::$field_ip} = $_SERVER['REMOTE_ADDR'];
        $user->{Authentication::$field_lastaccess} = date("Y-m-d H:i:s");
        $user->{Authentication::$field_lastpage} = array_key_exists('phoenix_route', $_GET) ? $_GET['phoenix_route'] : '';
        $user->Save();
        if (Authentication::$emailconfirmation && Authentication::$emailrequired)
        {
            $prefix = $user->{Authentication::$field_id} . '_';
            $gen = Authentication::GenerateCookieCodeForUsername($user->{Authentication::$field_email});
            $user->{Authentication::$field_unlocker} = substr($prefix . $gen, 0, strlen($gen));
            $user->Save();
            $search = array('{id}', '{username}', '{unlock_code}', '{email}');
            $replace = array(
                $user->{Authentication::$field_id},
                $user->{Authentication::$field_username},
                $user->{Authentication::$field_unlocker},
                $user->{Authentication::$field_email},
            );
            $to = $user->{Authentication::$field_email};
            $subject = str_replace($search, $replace, Authentication::$email_subject);
            $message = str_replace($search, $replace, Authentication::$email_content);
            $headers = 'From: ' . str_replace($search, $replace, Authentication::$email_from_name) .
                       ' <'. Authentication::$email_from_email . ">\r\n" .
                       'Reply-To: ' . Authentication::$email_from_email . "\r\n" .
                       'X-Mailer: PHP/' . phpversion() . "\r\n";
            $params = Authentication::$email_sendmail_envelope ? ('-f' . Authentication::$email_from_email) : null;
            mail($to, $subject, $message, $headers, $params);
        }
    }

    static function ChangeUserPassword($user, $oldpass, $newpass, $newpass_confirm)
    {
        $oldhash = Authentication::HashPassword($oldpass);
        $currentpass = $user->{Authentication::$field_password};
        if ($oldhash == $currentpass && $newpass !== null
            && strlen($newpass) >= 6 && $newpass == $newpass_confirm)
        {
            $user->{Authentication::$field_password} = Authentication::HashPassword($newpass);
            $user->Save();
            return true;
        }
        return false;
    }

    static function GenerateCookieCodeForUsername($username)
    {
        $cookie = $username;
        for ($i = 0; $i < 5; $i++) // The cookie is hashed using only the username, so be really paranoid about creating it
        {
            $cookie = Authentication::HashPassword($cookie . '~Phoenix_Cookie$'.$i.'_=_=_=_'.mt_rand(0, 500000));
        }
        return $cookie;
    }

    static function IsCurrentUserBanned() {
        $ipadd = $_SERVER['REMOTE_ADDR'];
        if (Authentication::IsIpBanned($ipadd)) return true;
        if (Authentication::IsLoggedIn() && Authentication::IsUserBanned(Authentication::GetUserID())) return true;
        return false;
    }

    static function BanUser($id, $untiltime, $text = '', $ban_by_ip = true) {
        $ban = new Authentication::$ban_model;
        $ban->{Authentication::$ban_field_userid} = $id;
        $ban->{Authentication::$ban_field_ip} = '';
        if ($ban_by_ip) {
            $user = Query::Create(Authentication::$model)
                    ->Where(Authentication::$field_id, '=', $id)
                    ->One();
            if ($user->{Authentication::$field_id} !== null) {
                $ban->{Authentication::$ban_field_ip} = $user->{Authentication::$field_ip};
            }
        }
        $ban->{Authentication::$ban_field_text} = $text;
        $ban->{Authentication::$ban_field_time} = $untiltime;
        $ban->Save();
    }

    static function BanIp($ip, $untiltime, $text = '') {
        $ban = new Authentication::$ban_model;
        $ban->{Authentication::$ban_field_userid} = 0;
        $ban->{Authentication::$ban_field_ip} = $ip;
        $ban->{Authentication::$ban_field_text} = $text;
        $ban->{Authentication::$ban_field_time} = $untiltime;
        $ban->Save();
    }

    static function UnbanUser($id) {
        foreach (Authentication::GetActiveBansForUser($id) as $ban) {
            $ban->Delete();
        }
    }

    static function UnbanIp($ip) {
        foreach (Authentication::GetActiveBansForIp($ip) as $ban) {
            $ban->Delete();
        }
    }

    static function IsUserBanned($id) {
        if (!Authentication::$ban_enabled || Authentication::$ban_field_userid === null) return false;
        return Query::Create(Authentication::$ban_model)
            ->Where(Authentication::$ban_field_userid, '=', $id)
            ->Where(Authentication::$ban_field_time, '>', date("Y-m-d H:i:s"))
            ->Count() > 0;
    }

    static function IsIpBanned($ip) {
        if (!Authentication::$ban_enabled || Authentication::$ban_field_ip === null) return false;
        return Query::Create(Authentication::$ban_model)
            ->Where(Authentication::$ban_field_ip, '=', $ip)
            ->Where(Authentication::$ban_field_time, '>', date("Y-m-d H:i:s"))
            ->All();
    }

    static function GetActiveBansForUser($id) {
        if (!Authentication::$ban_enabled || Authentication::$ban_field_userid === null) return array();
        return Query::Create(Authentication::$ban_model)
            ->Where(Authentication::$ban_field_userid, '=', $id)
            ->Where(Authentication::$ban_field_time, '>', date("Y-m-d H:i:s"))
            ->All();
    }

    static function GetActiveBansForIp($ip) {
        if (!Authentication::$ban_enabled || Authentication::$ban_field_ip === null) return array();
        return Query::Create(Authentication::$ban_model)
            ->Where(Authentication::$ban_field_ip, '=', $ip)
            ->Where(Authentication::$ban_field_time, '>', date("Y-m-d H:i:s"))
            ->Count() > 0;
    }
}

class Session
{
    public static function Get($name, $reset = false)
    {
        if (!array_key_exists($name, $_SESSION)) {
            return null;
        }
        $var = $_SESSION[$name];
        if ($reset === null) {
            unset($_SESSION[$name]);
        } else if ($reset !== false) {
            $_SESSION[$name] = $reset;
        }
        return $var;
    }

    public static function Set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function __get($name)
    {
        return Session::Get($name);
    }

    public function __set($name, $value)
    {
        Session::Set($name, $value);
    }
}

class PasswordHasher
{
    public function Hash($password, $salt = '')
    {
        // Virtual
    }
}

/**
 * Plain text hasher that is UNSAFE and UNSECURE.
 */
class PlainTextHasher extends PasswordHasher
{
    public function Hash($password, $salt = '')
    {
        return $salt.$password;
    }
}

/**
 * Salted algorithm hash. Should be suitable for most uses.
 */
class AlgorithmHasher extends PasswordHasher
{
    private $algorithm = 'sha256';
    private $salt_before = '';
    private $salt_after = '';

    /**
     * @param string $algorithm The name of the algorithm to use. Must be contained in the results of the hash_algos() function. Examples: sha256 (default), md5, crc32
     * @param string $salt_before Salt to be prepended to the password before hashing.
     * @param string $salt_after Salt to be appended to the password before hashing.
     */
    public function __construct($algorithm = 'sha256', $salt_before = '', $salt_after = '')
    {
        $this->algorithm = $algorithm;
        $this->salt_before = $salt_before;
        $this->salt_after = $salt_after;
    }

    public function Hash($password, $salt = '')
    {
        return hash($this->algorithm, $this->salt_before . $salt . $password . $this->salt_after);
    }
}

class AuthenticationDataExtractor
{
    function GetUser($username, $hashed_password, $additional_info)
    {
        // Virtual
    }
}

class StandardAuthenticationDataExtractor extends AuthenticationDataExtractor
{
    function GetUser($username, $hashed_password, $additional_info)
    {
        $obj = new Authentication::$model;
        $user_arr = Model::Search(
            Authentication::$model,
            array(
                $obj->GetDbName(Authentication::$field_username).' = :user',
                $obj->GetDbName(Authentication::$field_password).' = :pass'
            ),
            array(
                ':user' => $username,
                ':pass' => $hashed_password
            )
        );
        return (is_array($user_arr) && count($user_arr) == 1) ? $user_arr[0] : null;
    }
}
