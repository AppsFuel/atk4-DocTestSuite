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

    $_SERVER['SCRIPT_FILENAME']= $_SERVER['TARGET_BASE_PATH']."/index.php";

    chdir($_SERVER['TARGET_BASE_PATH']);

	include_once $_SERVER['TARGET_BASE_PATH'] . "/atk4/loader.php";
    include_once $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . "/lib-test/lib/DocTestEnvironment.php";
    include_once $_SERVER['DOCTEST_SUITE_FRAMEWORK'] . "/lib-test/lib/TestCase.php";

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
	}

	$api=new _doctest();
	$api->run();

	if ($_SERVER['DOCTEST_COVERAGE']=="on") {
		$xdebug_output = @xdebug_get_code_coverage();
		@xdebug_stop_code_coverage();
		update_coverage_db($xdebug_output, $_SERVER['DOCTEST_SCRIPT'], $api->getConfig('coverage/db'));
	}
}
catch (Exception $ex) {
	print "Exception in doctest:\n";
	$details_str = "";
	if($ex instanceof BaseException)
	{
		$details_str = $ex->getText();
		foreach ($ex->more_info as $key => $value)
		{
			$details_str .= ($key ."=>". $value .",");
		}
	}
	$error = array(
		"id"=> $ex->getCode(),
		"msg"=> $ex->getMessage(),
		"details"=> $details_str
	);
	var_dump($error);
	$logger = new Logger();
	print $logger->txtBacktrace($ex->getTrace());
}
