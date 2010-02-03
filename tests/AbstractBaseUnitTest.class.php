<?php
abstract class catalog_tests_AbstractBaseUnitTest extends catalog_tests_AbstractBaseTest
{
	/**
	 * @return void
	 */
	public function prepareTestCase()
	{
		$this->resetDatabase();
	}
}