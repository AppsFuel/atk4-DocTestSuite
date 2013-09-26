#!/usr/bin/php
<?php

$basePath = realpath(__DIR__);
define('DOCTEST_SUITE_FRAMEWORK', $basePath);
putenv('DOCTEST_SUITE_FRAMEWORK=' . DOCTEST_SUITE_FRAMEWORK);

$commandPattern = 'phpdt %(preparams)s %(files)s %(postparams)s';
$_SERVER['DOCTEST_SUITE_FRAMEWORK'] = DOCTEST_SUITE_FRAMEWORK;

$wrapperPath = $basePath . '/lib-test/doctestEnv/atk.doctest.tmpl.php';

include $basePath . '/site-config.php';
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
$parser->addOption('singleTest', array(
    'short_name' => '-t',
    'long_name'   => '--single-test',
    'description' => 'Run single test',
    'action'      => 'StoreString'
));
$parser->addArgument('path', array('multiple'=>true));


function getSQLite() {
    global $config;
    $db = new SQLite3(COVERAGE_DB);
    $db->exec('CREATE TABLE IF NOT EXISTS coverage_file (file_id INTEGER, file_name STRING UNIQUE, total_lines INTEGER, update_date DATE DEFAULT CURRENT_DATE, doctest INTEGER DEFAULT 0, PRIMARY KEY (file_id), UNIQUE(file_name))');
    $db->exec('CREATE TABLE IF NOT EXISTS coverage_data (file_id INTEGER not NULL, line_number INTEGER, covered BOOLEAN, line_type INTEGER, PRIMARY KEY (file_id, line_number))');
    $db->exec('CREATE TABLE IF NOT EXISTS coverage_origin (coverage_origin_id INTEGER not NULL, file_id INTEGER not NULL, parent_file_id INTEGER not NULL, PRIMARY KEY(coverage_origin_id) );');
    return $db;
}

function getDependencies($filename) {
    $q = "select file_name from coverage_file where file_id in (select a.parent_file_id from coverage_origin a, coverage_file b where a.file_id = b.file_id and b.file_name='$filename');";
    $db = getSQLite();
    $res = $db->query($q);
    return $res->fetchArray(SQLITE3_ASSOC) ? : array();
}
function flushAll() {
    $db = getSQLite();
    $q = "delete from coverage_data;";
    $db->exec($q);
    $q = "delete from coverage_origin;";
    $db->exec($q);
}

function flushFile($filename) {
    $db = getSQLite();
    $q = "delete from coverage_data WHERE coverage_data.file_id IN (SELECT file_id FROM coverage_file WHERE coverage_file.file_name like '$filename%');";
    $db->exec($q);
    $q = "delete from coverage_origin WHERE coverage_origin.parent_file_id IN (SELECT file_id FROM coverage_file WHERE coverage_file.file_name like '$filename%');";
    $db->exec($q);
}
function updateFile($filename) {
    $db = getSQLite();
    $q = "update coverage_file set doctest=1 where file_name like '$filename%';";
    $db->exec($q);
}
function saveTempDb() {
    global $config;
    copy(COVERAGE_DB, COVERAGE_STORE);
}
function getCoverage() {
    $db = getSQLite();
    $q = "select ROUND(((ROUND(SUM(B.covered_lines)) / ROUND(SUM(A.total_lines))) * 100),0) as coverage  from coverage_file A, (select file_id,count(*) as covered_lines from coverage_data where covered=1 or covered=-2 group by file_id) B where A.file_id = B.file_id;";
    $res = $db->query($q);
    $res = $res->fetchArray(SQLITE3_ASSOC);
    return $res['coverage'];
}
function getCoverageFile($file) {
    if (preg_match('/\/\/\s\[\[([^\]]*)]]/', file_get_contents($file), $matches)) {
        $testCaseFile = dirname($file) . '/' . str_replace('_', '/', $matches[1]) . '.php';
    } else {
        $testCaseFile = $file;
    }
    $q = "select ROUND(((ROUND(SUM(B.covered_lines)) / ROUND(SUM(A.total_lines))) * 100),0) as coverage, file_name as filename  from coverage_file A, (select file_id,count(*) as covered_lines from coverage_data where covered=1 or covered=-2  group by file_id) B where A.file_id = B.file_id and A.file_name='$testCaseFile';";
    $db = getSQLite();
    $res = $db->query($q);
    $res = $res->fetchArray(SQLITE3_ASSOC);
    return $res;
}


function sprintf_array($string, $array) {
    $keys    = array_keys($array);
    $keysmap = array_flip($keys);
    $values  = array_values($array);
    
    while (preg_match('/%\(([a-zA-Z0-9_ -]+)\)/', $string, $m)) {    
        if (!isset($keysmap[$m[1]])) {
            continue;
        }
        $string = str_replace($m[0], '%' . ($keysmap[$m[1]] + 1) . '$', $string);
    }
    array_unshift($values, $string);
    return call_user_func_array('sprintf', $values);
}

function getFiles ($path) {    
    $path = implode(' ', $path);

    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("file", "/tmp/error-output.txt", "a")
    );
    $cmd = 'find -H ' . $path . ' -type f -name "*.php" -exec grep -H -l -m 1 "<code>" {} \;';
    $process = proc_open($cmd, $descriptorspec, $pipes);
    $files = explode("\n", stream_get_contents($pipes[1]));

    $ret = array();
    foreach ($files as $file) {
        $f = realpath($file);
        $ret[] = $f;
        if (file_exists($f)) {
            $ret = array_merge(getDependencies($f), $ret);
        }
    }
    return array_unique($ret);
}

try {
    $result = $parser->parse();
    $options = $result->options;
    $files = getFiles($result->args['path']);

    if ($options['coverage']) {
        if (empty($files)) {
            flushAll();
        } else {
            foreach ($files as $file) {
                flushFile($file);
            }
        }
    }

    $preparams = '';
    if ($options['singleTest']) {
        $preparams = ' -t \'' . $options['singleTest'] . '\' ';
    }
    $preparams .= ' --template-code=' . $wrapperPath . ' --php-wrapper=/usr/bin/php' ;
    if ($options['coverage']) {
        putenv('DOCTEST_COVERAGE=on');
    }
    $postparams = '';
    $command = sprintf_array(
        $commandPattern,
        array(
            'files' => implode(' ', $files),
            'preparams' => $preparams,
            'postparams' => $postparams
    ));

    $process = system($command, $status);
    if($status !== 0) {
        exit($status);
    }

    if ($options['coverage']) {
        foreach ($files as $file) {
            updateFile($file);
        }
    }
    
    echo "\033[0;34mCode Coverage\033[0m\n";
    echo "=============\n";
    foreach ($files as $file) {
        $coverage = getCoverageFile($file);
        if (!empty($coverage['filename'])) {
            echo $coverage['filename'] . ': ' . ($coverage['coverage'] > 70 ? "\033[1;32m" : "\033[0;31m") . $coverage['coverage'] . "%\033[0m\n";
        }
    }
    echo "=============\n";
    $coverage = getCoverage();
    echo 'Total: ' . ($coverage > 70 ? "\033[1;32m" : "\033[0;31m") . $coverage . "%\n\n";
    echo "\033[0m";
} catch (Exception $e) {
    $parser->displayError($e->getMessage());
    return 1;
}
