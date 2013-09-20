#!/usr/bin/php
<?php


include __DIR__ . '/lib/config.php';



function getSQLite() {
    global $config;
    return new SQLite3($config['coverage']['db']);
}

function getDependencies($filename) {
    $q = "select file_name from coverage_file where file_id in (select a.parent_file_id from coverage_origin a, coverage_file b where a.file_id = b.file_id and b.file_name='$filename');";
    $db = getSQLite();
    $res = $db->query($q);
    return $res->fetchArray(SQLITE3_ASSOC);
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
    copy($config['coverage']['db'], $config['coverage']['store']);
}
function getCoverage() {
    $db = getSQLite();
    $q = "select ROUND(((ROUND(SUM(B.covered_lines)) / ROUND(SUM(A.total_lines))) * 100),0) as coverage  from coverage_file A, (select file_id,count(*) as covered_lines from coverage_data where covered=1 or covered=-2 group by file_id) B where A.file_id = B.file_id;";
    $res = $db->query($q);
    $res = $res->fetchArray(SQLITE3_ASSOC);
    return $res['coverage'];
}

include_once 'Console/CommandLine.php';

$parser = new Console_CommandLine(array(
    'description' => 'DocTest utils',
    'version'     => '0.6.0'
));

$parser->addOption('dependencies', array(
    'short_name'  => '-d',
    'long_name'   => '--dep-list',
    'description' => 'Dependencies',
    'action'      => 'StoreString'
));

$parser->addOption('flushall', array(
    'short_name'  => '-f',
    'long_name'   => '--flush-all',
    'description' => 'FlushAll',
    'action'      => 'StoreTrue'
));

$parser->addOption('flush', array(
    'short_name'  => '-r',
    'long_name'   => '--remove',
    'description' => 'Flush',
    'action'      => 'StoreString'
));

$parser->addOption('update', array(
    'short_name'  => '-u',
    'long_name'   => '--update',
    'description' => 'Update',
    'action'      => 'StoreString'
));

$parser->addOption('save', array(
    'short_name'  => '-s',
    'long_name'   => '--save',
    'description' => 'Save',
    'action'      => 'StoreTrue'
));

$parser->addOption('coverage', array(
    'short_name'  => '-c',
    'long_name'   => '--coverage',
    'description' => 'Coverage',
    'action'      => 'StoreTrue'
));

try {
    $result = $parser->parse();
    $options = $result->options;

    if ($options['dependencies']) {
        echo implode(' ' ,getDependencies($options['dependencies']));
    }
    if ($options['coverage']) {
        echo getCoverage();
    }
    if ($options['flushall']) {
        flushAll();
    }
    if ($options['flush']) {
        flushFile($options['flush']);
    }
    if ($options['update']) {
        updateFile($options['update']);
    }
    if ($options['save']) {
        saveTempDb();
    }
} catch (Exception $e) {
    $parser->displayError($e->getMessage());
    return 1;
}