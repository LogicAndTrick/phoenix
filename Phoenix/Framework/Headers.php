<?php

class Headers
{
    static $impl;

    private static function Init()
    {
        if (Headers::$impl == null) Headers::$impl = new Headers();
    }

    public static function Redirect($location)
    {
        Headers::Init();
        Headers::$impl->AddHeader("Location: $location");
    }

    public static function SetContentType($type)
    {
        Headers::Init();
        Headers::$impl->AddHeader("Content-Type: $type");
    }

    public function AddHeader($header)
    {
        header($header);
    }
}