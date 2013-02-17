<?php


function getExceptionTraceAsString($exception) {
    $rtn = "";
    $count = 0;
    foreach ($exception->getTrace() as $frame) {
        if (!array_key_exists('file', $frame)) $frame['file'] = '(Unknown File)';
        if (!array_key_exists('line', $frame)) $frame['line'] = '??';
        if (!array_key_exists('function', $frame)) $frame['function'] = '(Anonymous Method)';
        $rtn .= sprintf( "#%s %s(%s): %s\n", $count, $frame['file'], $frame['line'], $frame['function']);
        $count++;
    }
    return $rtn;
}


/**
 *
 * @param Exception $exception
 */
function PhoenixExceptionHandler($exception) {
    echo "<h1>Something <em>really</em> bad happened!</h1>\n";
    echo "<h2>Uncaught exception</h2>\n";
    echo str_replace("\n", "<br>\n", $exception->getMessage()) . "<br><br>\n\nStacktrace:<br>\n";
    echo str_replace("\n", "<br>\n", getExceptionTraceAsString($exception));
}

set_exception_handler('PhoenixExceptionHandler');

?>
