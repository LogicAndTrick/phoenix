<?php
 
class HomeController extends Controller
{
    function Index($text = '')
    {
        return $this->Content($text);
    }

    function Def()
    {

    }

    function ShowView($model)
    {
        return $this->View($model);
    }

    function PostOnly_Post($text = '')
    {
        return $this->Content($text);
    }

    function LotsOfParameters($one, $two, $three)
    {

    }
}

?>