<?php

class Session2
{
    static $impl;

    private static function Init()
    {
        if (Session2::$impl == null) Session2::$impl = new Session();
    }

    public static function Set($key, $value)
    {
        Session2::Init();
        Session2::$impl->SetSessionValue($key, $value);
    }

    public static function Get($key)
    {
        Session2::Init();
        return Session2::$impl->GetSessionValue($key);
    }

    function __construct()
    {
        session_start();
    }

    public function SetSessionValue($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function GetSessionValue($key)
    {
        if (!array_key_exists($key, $_SESSION)) {
            return null;
        }
        return $_SESSION[$key];
    }
}

class Cookie
{
    static $impl;

    private static function Init()
    {
        if (Cookie::$impl == null) Cookie::$impl = new Cookie();
    }

    public static function Set($key, $value, $timeout = 604800)
    {
        Cookie::Init();
        Cookie::$impl->SetCookieValue($key, $value, $timeout);
    }

    public static function Get($key)
    {
        Cookie::Init();
        return Cookie::$impl->GetCookieValue($key);
    }

    public static function Clear($key)
    {
        Cookie::Init();
        return Cookie::$impl->ClearCookieValue($key);
    }

    function __construct()
    {

    }

    public function SetCookieValue($key, $value, $timeout = 604800) // One week default
    {
        setcookie($key, $value, time() + $timeout, Phoenix::$base_url);
    }

    public function ClearCookieValue($key)
    {
        setcookie($key, '', time() - 1, Phoenix::$base_url);
    }

    public function GetCookieValue($key)
    {
        if (!array_key_exists($key, $_COOKIE)) {
            return null;
        }
        return $_COOKIE[$key];
    }
}