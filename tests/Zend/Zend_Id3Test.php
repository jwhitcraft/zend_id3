<?php

set_include_path(
		dirname(__FILE__) . '/../../library'
		. PATH_SEPARATOR . get_include_path()
	);

require_once 'library/Zend/Id3.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Zend_Id3 test case.
 */
class Zend_Id3Test extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Zend_Id3
	 */
	private $_zendId3;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->_zendId3 = new Zend_Id3();

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->_zendId3 = null;

		parent::tearDown();
	}

	/**
	 * Tests Zend_Id3::analyze()
	 */
	public function testAnalyzeFileForId3V1Tags()
	{
		$response = $this->_zendId3->analyze(dirname(__FILE__) . '/Id3/_files/demo.mp3');

		$this->assertType('array', $response);
		$this->assertTrue(isset($response['id3v1']));
		$this->assertEquals('Juman Sucks', $response['id3v1']['album']);
		$this->assertEquals("Llama Whippin' Intro", $response['id3v1']['title']);
		$this->assertEquals('Nullsoft', $response['id3v1']['artist']);
	}
}

