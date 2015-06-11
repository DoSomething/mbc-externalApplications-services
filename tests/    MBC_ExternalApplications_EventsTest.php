<?php
 
use DoSomething\MBC_ExternalApplications\MBC_ExternalApplications_Events;

  // Including that file will also return the autoloader instance, so you can store
  // the return value of the include call in a variable and add more namespaces.
  // This can be useful for autoloading classes in a test suite, for example.
  // https://getcomposer.org/doc/01-basic-usage.md
  $loader = require_once __DIR__ . '/../vendor/autoload.php';
 
class MBC_ExternalApplications_EventsTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testConsumeQueue()
  {
    $this->assertTrue(TRUE);
  }
 
}
