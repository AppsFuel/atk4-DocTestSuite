<?php


class TestCase extends AbstractObject {
    public $fixtures = array();
    public function addFixture($className) {
        $fixtureObject = $this->add('UnitTest_Fixture_' . $className);
        foreach($fixtureObject->dependencies as $dependence)
            $this->addFixture($dependence);

        $fixtureObject->fixtures = array_merge($fixtureObject->fixtures, $this->fixtures);
        $fixtureObject->load();

        $this->fixtures = array_merge($this->fixtures, $fixtureObject->fixtures);
    }

    protected function installFromFactory($factory_name, $factory_method,  array $attributes){
    	return $this->add("Factory_$factory_name")->$factory_method($attributes);
    }

    function assertThrowException($exceptionType, $obj, $method, $args=array()) {
        try {
			//echo "pre call_user_func_array(array($obj, $method), $args) ";
            call_user_func_array(array($obj, $method), $args);
            $this->fails($exceptionType . ' expected. Not thrown');
        } catch (Exception $e) {
            if (!($e instanceof $exceptionType))
                $this->fails($exceptionType . ' expected. Found: ' . get_class($e) . ': ' . $e->getMessage());

            return $e;
        }
    }

    function assertNotEmpty($data) {
        if (empty($data))
            $this->fails('Found data empty');
    }

	function assertEmpty($data) {
        if (!empty($data))
            $this->fails('Found data not empty');
    }

    public function fails($msg) {
	throw new Exception($msg);
    }

    function assertEquals($expected, $actual) {
        if ($expected !== $actual)
            $this->fails("Expected: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }

    function assertSame($expected, $actual) {
        if ($expected != $actual)
            $this->fails("Expected: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }

    function assertTrue($bool, $msg) {
        if ($bool !== true)
            $this->fails($msg);
    }

    function assertFalse($bool, $msg) {
        if ($bool !== false)
            $this->fails($msg);
    }
    
    function assertNotEquals($expected, $actual) {
        if ($expected === $actual)
            $this->fails("assertNotEquals: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }
}

