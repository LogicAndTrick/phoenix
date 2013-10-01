<?php

function smarty_modifier_pluralise($string, $count = null)
{
    if ($count !== null && stristr($string, ':n') !== false && stristr($string, ':s') !== false) {
        return str_ireplace(':n', $count, str_ireplace(':s', ($count == 1 ? '' : 's'), $string));
    }
    if ($count === null) $count = 0;
    return $string . ($count == 1 ? '' : 's');
}
