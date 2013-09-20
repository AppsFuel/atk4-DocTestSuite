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

    protected function assertThrowException($exceptionType, $obj, $method, $args=array()) {
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

    protected function assertNotEmpty($data) {
        if (empty($data))
            $this->fails('Found data empty');
    }

	protected function assertEmpty($data) {
        if (!empty($data))
            $this->fails('Found data not empty');
    }

    public function fails($msg) {
        print $msg;
    }

    protected function assertEquals($expected, $actual) {
        if ($expected !== $actual)
            $this->fails("Expected: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }

    protected function assertSame($expected, $actual) {
        if ($expected != $actual)
            $this->fails("Expected: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }

    protected function assertTrue($bool, $msg) {
        if ($bool !== true)
            $this->fails($msg);
    }

    protected function assertFalse($bool, $msg) {
        if ($bool !== false)
            $this->fails($msg);
    }
    
    protected function assertNotEquals($expected, $actual) {
        if ($expected === $actual)
            $this->fails("assertNotEquals: '" . print_r($expected, true) . "' given: '" . print_r($actual, true) . "'");
    }
}

