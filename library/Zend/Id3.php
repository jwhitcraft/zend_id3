<?php

/**
 * Zend_Id3
 *
 * @uses
 * @package    Zend
 * @category   Id3
 * @copyright  Copyright (C) 2008 - Present, Jon Whitcraft
 * @author     Jon Whitcraft <jon.zf@mac.com>
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @version    $Id: $
 */
class Zend_Id3
{

	/**
	 * Size of read buffer in bytes
	 */
	const FREAD_BUFFER_SIZE = 16384;

	/**
	 * Name of the file
	 *
	 * @var string
	 */
	protected $_filename;

	/**
	 * File Pointer
	 *
	 * @var resource
	 */
	protected $_filePointer;

	/**
	 * id3 tag info
	 *
	 * @var array
	 */
	protected $_info = array();

	/**
	 *
	 */
	function __construct()
	{
		// nothing yet
	}

	/**
	 *
	 * @param $filename string
	 */
	public function analyze($filename)
	{
		// Remote files not supported
		if (preg_match('#^([a-z][a-z0-9+.-]+):(//)?(.*?@)?#i', $filename)) {
			include_once 'Zend/Id3/Exception.php';
			throw new Zend_Id3_Exception('Remote files are not supported');
		}

		$this->_filename = $filename;

		// Open local file
		if (false === ($this->_filePointer = @fopen($this->_filename, 'rb'))) {
			include_once 'Zend/Id3/Exception.php';
			throw new Zend_Id3_Exception('Could not open file "' . $filename  . "'");
		}

		// Set filesize related parameters
		$this->_info['filesize']     = filesize($filename);
		$this->_info['avdataoffset'] = 0;
		$this->_info['avdataend']    = $this->info['filesize'];


		foreach(array('id3v1') as $tagName) {
			switch($tagName) {
				case 'id3v1':
					include_once 'Zend/Id3/Adapter/Id3v1.php';
					$tag = new Zend_Id3_Adapter_Id3v1($this);
					$tag->analyze();
					break;
			}
		}

		fclose($this->_filePointer);


		return $this->_info;
	}

	/**
	 * Gets the open file pointer
	 *
	 * @return resource
	 */
	public function getFilePointer()
	{
		return $this->_filePointer;
	}

	/**
	 * Gets the file info
	 *
	 * @return array
	 */
	public function getFileInfo()
	{
		return $this->_info;
	}

	/**
	 * Sets the file info
	 *
	 * @param array $info
	 * @return Zend_Id3
	 */
	public function setFileInfo(array $info)
	{
		$this->_info = $info;

		return $this;
	}
}
