<?php

if (file_exists('/dev/shm/'))
	$config['coverage']['db'] = '/dev/shm/doctest_coverage.db';
else
	$config['coverage']['db'] = '/tmp/doctest_coverage.db';

$config['coverage']['store'] = __DIR__ . '/../coverage.db';
