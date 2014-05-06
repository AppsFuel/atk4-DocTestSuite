<?php

require_once $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . '/site-config.php';

try
{
    if (!empty($_SERVER['DOCTEST_COVERAGE'])) {
        if (!extension_loaded('xdebug')) {
            @dl('xdebug.so');
        }
        @xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    define('TARGET_BASE_PATH', $_SERVER['TARGET_BASE_PATH']);
    $_SERVER['SCRIPT_FILENAME']= TARGET_BASE_PATH . "/index.php";

    chdir(TARGET_BASE_PATH);

	include_once TARGET_BASE_PATH . "/atk4/loader.php";
    include_once $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . "/lib-test/lib/DocTestEnvironment.php";

	class _doctest extends DocTestEnvironment {
		function run() {
			try {
				$this->db->beginTransaction();
                %s

                %s
				$this->db->rollback();
			} catch (Exception $ex) {
				$this->db->rollback();
				throw($ex);
			}
		}
		
		function addSharedLocations($pathfinder,$base_directory) {
			if (function_exists('addSharedCustomizedLocations')) {
				addSharedCustomizedLocations($pathfinder, $base_directory);
			}
		}

	}

	$api=new _doctest();
	$api->run();

	if ($_SERVER['DOCTEST_COVERAGE']=="on") {
        include $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . "/lib-test/doctestEnv/coverage.php";
		$xdebug_output = @xdebug_get_code_coverage();
		@xdebug_stop_code_coverage();
		update_coverage_db($xdebug_output, $_SERVER['DOCTEST_SCRIPT'], COVERAGE_DB);
	}
}
catch (Exception $ex) {
	print "Exception in doctest:\n";
	echo $ex->getTraceAsString();
}
