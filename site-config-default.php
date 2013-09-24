<?php

/*
 * This path points to htdocs folder where index.php is
 */
$_SERVER['TARGET_BASE_PATH'] = '/absolutepath/to/my/htdocs/project';

/*
 * The following lines are set for performance reason. Don't change them
 */
if (file_exists('/dev/shm/')) {
    $config['coverage']['db'] = '/dev/shm/doctest_coverage.db';
} else {
    $config['coverage']['db'] = '/tmp/doctest_coverage.db';
}

/*
 * This is the path where the coverage will be saved.
 */
$config['coverage']['store'] = $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . '/coverage.db';

