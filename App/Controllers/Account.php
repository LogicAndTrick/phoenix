<?php

class AccountController extends Controller
{
    public $login_redirect_action = null;
    public $login_redirect_controller = null;
    public $login_redirect_params = array();

    public $logout_redirect_action = null;
    public $logout_redirect_controller = null;
    public $logout_redirect_params = array();

    public $register_redirect_action = null;
    public $register_redirect_controller = null;
    public $register_redirect_params = array();

    public $verify_redirect_action = null;
    public $verify_redirect_controller = null;
    public $verify_redirect_params = array();
    
    function Login()
    {
        if (Post::IsPostBack())
        {
            if (!Authentication::$autologin)
            {
                Authentication::Login();
            }
            else if (Authentication::$post_username != null && Authentication::$post_password != null)
            {
                Authentication::Login(
                    Post::Get(Authentication::$post_username),
                    Post::Get(Authentication::$post_password),
                    Authentication::$post_remember != null ? Post::Get(Authentication::$post_remember) : null
                );
            }
        }
        if (Authentication::IsLoggedIn()) {
            return $this->RedirectToAction(
                $this->login_redirect_action != null ? $this->login_redirect_action : Router::$default_action,
                $this->login_redirect_controller != null ? $this->login_redirect_controller : Router::$default_controller,
                $this->login_redirect_params
            );
        }
        return $this->View();
    }

    function Logout()
    {
        Authentication::Logout();
        return $this->RedirectToAction(
            $this->logout_redirect_action != null ? $this->logout_redirect_action : Router::$default_action,
            $this->logout_redirect_controller != null ? $this->logout_redirect_controller : Router::$default_controller,
            $this->logout_redirect_params
        );
    }

    public function Register()
    {
        if (Authentication::IsLoggedIn())
        {
            return $this->RedirectToAction(
                $this->login_redirect_action != null ? $this->login_redirect_action : Router::$default_action,
                $this->login_redirect_controller != null ? $this->login_redirect_controller : Router::$default_controller,
                $this->login_redirect_params
            );
        }
        if (Authentication::ValidateRegister())
        {
            Authentication::RegisterUser();
            return $this->RedirectToAction(
                $this->register_redirect_action != null ? $this->register_redirect_action : 'RegisterSuccess',
                $this->register_redirect_controller != null ? $this->register_redirect_controller : 'Account',
                $this->register_redirect_params
            );
        }
        return $this->View();
    }

    public function RegisterSuccess()
    {
        return $this->View();
    }

    public function Verify($id, $code)
    {
        $user = new User($id);
        if (Authentication::UnlockUserAccount($user, $code))
        {
            return $this->RedirectToAction(
                $this->verify_redirect_action != null ? $this->verify_redirect_action : 'VerifySuccess',
                $this->verify_redirect_controller != null ? $this->verify_redirect_controller : 'Account',
                $this->verify_redirect_params
            );
        }
        return $this->View();
    }

    public function VerifySuccess()
    {
        return $this->View();
    }
}

?>
