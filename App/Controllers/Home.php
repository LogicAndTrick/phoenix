<?php

class HomeController extends Controller
{
    function Index()
    {
        return $this->View('Hello, World!');
    }
}

?>
