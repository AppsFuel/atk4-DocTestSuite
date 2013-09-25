atk4-DocTestSuite
=================

PHPDT integration for doctest/unittest on atk4 framework

First release! Coverage works ONLY if xdebug could be loaded with dl() or already loaded from php.ini


Installation
============

* Install PHP (5.3+) CLI with all modules needed for your test target.
	* XDebug module is required for code coverage.
	* NOTE For code coverage XDebug can also be activated on demand (with dl() )
* Install PEAR package Testing_DocTest
	`pear install -f Testing_DocTest`
* Checkout this repository


Configuration
=============

* Copy site-config-default.php as site-config.php in the same folder
* Edit site-config.php to configure the target you are testing (the atk directory where index.php is located)

* Wrote tests in your code and/or create separate directory with complex unit tests
  * PHPDT inline test syntax can be found here: http://pear.php.net/package/Testing_DocTest/docs
  * ATK4 UnitTest class docs can be found here: __TODO:Wrote documentation__

Reccomended use
===============

* Light testing
  __TODO:Wrote this section!__

* Serious testing
 __TODO:Wrote this section!__

Roadmap
=======
* Complete the coverage implementation in PHP runner
* DSN for coverage DB
* PHPUnit integration for assertions and fixtures
* PHP internal webserver integration (5.4+) for dummy httprequest testing
* Better coverage site
* Profiling site integration

