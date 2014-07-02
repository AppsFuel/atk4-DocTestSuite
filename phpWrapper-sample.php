#!/usr/bin/php
<?php
/******************************************************
 * The purpose of --php-werapper option is to give
 * the opportunity to fully customize the php environment
 * where the tests are run.
 *
 * You can full control the php configuration and ENV
 * of the runned tests. (FIND my_custom BELOW for the samples)
 *
 * NOTE: This wrapper always run with the
 *       system wide config (like phpdt itself)
 ******************************************************/

//Read standard input
$in="";
$f = fopen( 'php://stdin', 'r' );
while( $line = fgets( $f ) ) {
    $in .= $line;
}

//Read params to pass to php
$params="";
for ($a=1; $a<=count($argv)-1; $a++) {
    $params.=" ".$argv["$a"];
}

//Execute php
$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    2 => array("pipe", "a")   // stderr is a file to write to
);

//Define a full php configuration and change error reporting level
//!!!KEEP DOUBLE SPACE AT THE END!!!
$cmd = 'php -c /my_custom_php_config/php.ini -d error_reporting="E_ALL & ~E_NOTICE & ~E_DEPRECATED"  '.$params;

$env = array(
    'PHP_INI_SCAN_DIR' => '/my_custom_php_config/php.d',
    'MY_CUSTOM_ENV_VARIABLE' => 'foobar'
);

//Spawn the process and pipes through our in/out/err
$process = proc_open($cmd, $descriptorspec, $pipes, NULL, $env);

if (is_resource($process)) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // 2 => append handle connected to child stderr

    fwrite($pipes[0], $in);
    fclose($pipes[0]);

    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    // It is important that you close any pipes before calling
    // proc_close in order to avoid a deadlock
    $return_value = proc_close($process);
    exit($return_value);
}
