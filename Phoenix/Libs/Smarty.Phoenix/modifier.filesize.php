<?php

function smarty_modifier_filesize($bytes)
{
    if ($bytes < 1024) return $bytes . 'B';
    $kbytes = $bytes / 1024;
    if ($kbytes < 1024) return round($kbytes, 2) . 'kB';
    $mbytes = $kbytes / 1024;
    if ($mbytes < 1024) return round($mbytes, 2) . 'MB';
    $gbytes = $mbytes / 1024;
    if ($gbytes < 1024) return round($gbytes, 2) . 'GB';
    $tbytes = $gbytes / 1024;
    if ($tbytes < 1024) return round($tbytes, 2) . 'TB';
    $pbytes = $tbytes / 1024;
    return round($pbytes, 2) . 'PB';
}
