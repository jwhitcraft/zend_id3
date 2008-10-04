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
	private $Zend_Id3;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();

		// TODO Auto-generated Zend_Id3Test::setUp()


		$this->Zend_Id3 = new Zend_Id3(/* parameters */);

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		// TODO Auto-generated Zend_Id3Test::tearDown()


		$this->Zend_Id3 = null;

		parent::tearDown ();
	}

	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
		// TODO Auto-generated constructor
	}

	/**
	 * Tests Zend_Id3::analyze()
	 */
	public function testAnalyzeFile()
	{
		$info = $this->Zend_Id3->analyze(dirname(__FILE__) . '/Id3/_files/demo.mp3');

		$this->assertType('array', $info);
		$this->assertEquals('Juman Sucks', $info['id3v1']['album']);
		$this->assertEquals("Llama Whippin' Intro", $info['id3v1']['title']);
		$this->assertEquals('Nullsoft', $info['id3v1']['artist']);
	}
}

