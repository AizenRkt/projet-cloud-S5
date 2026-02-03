<?php
require 'vendor/autoload.php';

use OpenApi\Generator;

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $msg = "Error [$errno]: $errstr in $errfile:$errline\n";
    $msg .= "Trace:\n";
    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
    $msg .= ob_get_clean();
    file_put_contents('debug_output.txt', $msg);
    return true; 
});

try {
    $generator = new Generator();
    $openapi = $generator->generate(['app/Http/Controllers']);
    echo "Scan successful\n";
    // echo $openapi->toJson();
} catch (\Throwable $e) {
    echo "Scan failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
