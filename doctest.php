#!/usr/bin/php
<?php

$basePath = realpath(__DIR__);


$commandPattern = 'phpdt %(params)s %(files)s';
putenv('DOCTEST_SUITE_FRAMEWORK='.$basePath);
$wrapperPath = $basePath . '/lib-test/doctestEnv/atk.doctest.tmpl.php';

include_once 'Console/CommandLine.php';

$parser = new Console_CommandLine(array(
    'description' => 'DocTestRunner',
    'version'     => '0.0.1'
));

$parser->addOption('coverage', array(
    'long_name'   => '--coverage',
    'description' => 'Activate coverage',
    'action'      => 'StoreTrue'
));
$parser->addArgument('path', array('multiple'=>true));


function sprintf_array($string, $array) {
    $keys    = array_keys($array);
    $keysmap = array_flip($keys);
    $values  = array_values($array);
    
    while (preg_match('/%\(([a-zA-Z0-9_ -]+)\)/', $string, $m))
    {    
        if (!isset($keysmap[$m[1]]))
        {
            echo "No key $m[1]\n";
            return false;
        }
        
        $string = str_replace($m[0], '%' . ($keysmap[$m[1]] + 1) . '$', $string);
    }
    
    array_unshift($values, $string);
    return call_user_func_array('sprintf', $values);
}

try {
    $result = $parser->parse();
    $options = $result->options;

    $path = implode(' ', $result->args['path']);

    $params = '--template-code=' . $wrapperPath;
    if ($options['coverage']) {
        putenv('DOCTEST_COVERAGE=1');
    }
    $command = sprintf_array($commandPattern,  array('files' => $path, 'params' => $params));

    $process = system($command, $status);
    if($status !== 0) {
        exit($status);
    }
} catch (Exception $e) {
    $parser->displayError($e->getMessage());
    return 1;
}
