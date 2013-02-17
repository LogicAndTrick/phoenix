<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <title>{$page_title} | Phoenix MVC</title>
        <link rel="stylesheet" type="text/css" href="{resolve path='/Content/Styles/Phoenix.css'}">
    </head>
    <body>
        <div id="account">
            {partial view='AccountInfo'}
        </div>
        <div id="content">
            {$content}
        </div>
        {$phoenix_debug}
    </body>
</html>