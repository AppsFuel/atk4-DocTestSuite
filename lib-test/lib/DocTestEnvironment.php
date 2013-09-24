<?php

class DocTestEnvironment extends ApiCLI {
	protected $environment;
	protected $real_environment;
	public $db;

	public function __construct($env=null, $real_env=null) {
		$this->environment = $env;
		$this->real_environment = $real_env;
		$this->skin='default';	//FIX for template loading

		parent::__construct();
	}
	
	function init(){
		parent::init();
        
        if (file_exists($_SERVER['ATK4_CONFIG_BASE'].$this->environment."_doctest.config.php")) {
            $this->getConfig('dummy','dummy');
            // user-config for your site
            include $_SERVER['ATK4_CONFIG_BASE'].$this->environment."_doctest.config.php";
            // test-config
            $this->setConfig($config);
        }

		$this->getLogger();
		$db=$this->dbConnect();
	}
	
    public static function testPrivateMethod($instance, $name, $param_array) {
        $class = new ReflectionClass($instance);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($instance, $param_array);
    }

    public function assertArrayEquals($actual, $expected) {
        if (!($actual == $expected))
            throw $this->exception("assertArrayEquals fail")->addMoreInfo("expected", json_encode($expected, true))
                ->addMoreInfo("actual", json_encode($actual, true));
    }
    public function assertStringEquals($actual, $expected) {
        if (!($actual == $expected))
            throw $this->exception("assertStringEquals fail")->addMoreInfo("expected", $expected)
               ->addMoreInfo("actual", $actual);
    }
    public function assertIsNull($actual) {
        if (!is_null($actual))
            throw $this->exception("assertIsNull fail")->addMoreInfo("actual", $actual);
    }
    public function assertIsNotEmpty($actual) {
        if (empty($actual))
            throw $this->exception("assertIsNotEmpty fail")->addMoreInfo("actual", $actual);
    }
    public function assertBooleanEquals($actual, $expected) {
        if (!($actual == $expected))
            throw $this->exception("assertBooleanEquals fail")->addMoreInfo("expected", $expected ? 'true' : 'false')
                ->addMoreInfo("actual", $actual ? 'true' : 'false');
    }
}
