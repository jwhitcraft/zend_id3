<?php
/**
 * Zend_Id3_Adapter_Abstract
 *
 * @uses
 * @package    Zend
 * @category   Id3
 * @copyright  Copyright (C) 2008 - Present, Jon Whitcraft
 * @author     Jon Whitcraft <jon.zf@mac.com>
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @version    $Id: $
 */
abstract class Zend_Id3_Adapter_Abstract
{
	/**
	 * @var Zend_Id3
	 */
	protected $_id3;

	/**
	 * analyzing filepointer or string
	 *
	 * @var boolean
	 */
	protected $_dataStringFlag = false;

	/**
	 * string to analyze
	 *
	 * @var string
	 */
	protected $_dataString;

	/**
	 * seek position in string
	 *
	 * @var integer
	 */
	protected $_dataStringPosition = 0;


	/**
	 * Constructor
	 *
	 * @param $id3 Zend_Id3
	 */
	public function __construct(Zend_Id3 $id3)
	{
		$this->_id3 = $id3;
	}

	/**
	 * Analyze from the file pointer
	 * This is implemented from the extending class
	 */
	abstract public function analyze();

	/**
	 * Returns the current position of the file read/write pointer
	 *
	 * @return integer
	 */
	protected function ftell() {

		if ($this->_dataStringFlag) {
			return $this->_dataStringPosition;
		}
		return ftell($this->_id3->getFilePointer());
	}


	/**
	 * Read up to the length of passed in bytes
	 *
	 * @param integer $bytes
	 * @return string
	 */
	protected function fread($bytes) {

		if ($this->_dataStringFlag) {
			$this->_dataStringPosition += $bytes;
			return substr($this->_dataString, $this->_dataStringPosition - $bytes, $bytes);
		}
		return fread($this->_id3->getFilePointer(), $bytes);
	}


	/**
	 * Sets the file position indicator for the file
	 *
	 * @param integer $bytes
	 * @param integer $whence
	 * @return integer|null
	 */
	protected function fseek($bytes, $whence = SEEK_SET) {

		if ($this->_dataStringFlag) {
			switch ($whence) {
				case SEEK_SET:
					$this->_dataStringPosition = $bytes;
					return;

				case SEEK_CUR:
					$this->_dataStringPosition += $bytes;
					return;

				case SEEK_END:
					$this->_dataStringPosition = strlen($this->_dataString) + $bytes;
					return;
			}
		}
		return fseek($this->_id3->getFilePointer(), $bytes, $whence);
	}
}
