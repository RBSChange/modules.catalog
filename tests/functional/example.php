<?php
class catalog_FunctionnalTestExample extends PHPUnit_Extensions_SeleniumTestCase
{
	public function setUp()
	{
		// Browser to use: "*firefox", "*iexplore"...
		$this->setBrowser("*firefox");

		// URL to check.
		$this->setBrowserUrl("http://www.google.fr/");

		// $this->setTimeout(10000);

		// Think Time: simulate a user that is thinking during 1 second between each action.
		$this->setSleep(1);

		// The host IP and port on which Selenium RC server is listening for requests.
		$this->setHost("10.199.2.73");
		$this->setPort(4444);
	}

	public function tearDown()
	{
	}

	public function testSaintDizierWebsiteIsWellReferencedByGoogle()
	{
	    $this->open("/");
	    $this->type("q", "saint dizier");
	    $this->click("btnG");
	    $this->waitForPageToLoad("30000");
		$this->assertTrue($this->isTextPresent(utf8_encode("Agavi Exception")));
	}
}