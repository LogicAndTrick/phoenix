<?php

class ErrorController extends Controller
{
    public function Index($message)
    {
        Templating::SetPageTitle('Something Bad Happened!');
        return $this->View($message);
    }
}
