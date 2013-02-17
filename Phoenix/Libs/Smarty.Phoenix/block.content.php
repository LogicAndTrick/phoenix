<?php

/**
 * Define a block of content for the master page to fill in. Works only
 * when master pages are being used, and only then within a view that will be
 * embedded within a master page.
 */
function smarty_block_content($params, $content, $template, &$repeat)
{
    if ($content != null) {
        Templating::$placeholder_data[$params['name']] = $content;
    }
    return null;
}
