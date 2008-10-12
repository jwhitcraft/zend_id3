<?php

/**
 * Zend_Id3_Adapter_Id3v2
 *
 * @uses
 * @package    Zend
 * @category   Id3
 * @copyright  Copyright (C) 2008 - Present, Jon Whitcraft
 * @author     Jon Whitcraft <jon.zf@mac.com>
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @version    $Id: $
 */

require_once 'Zend/Id3/Adapter/Abstract.php';

require_once 'Zend/Id3/ByteConvert.php';



class Zend_Id3_Adapter_Id3v2 extends Zend_Id3_Adapter_Abstract
{
    /**
     * Starting offset
     *
     * @var integer
     */
    protected $_startingOffset = 0;

    protected $_frameDataOffset = 0;

    /**
     * Major Version of the tag for easy access
     *
     * @var integer
     */
    protected $_tagMajorVersion;

    /**
     * An Array that Stores the tag info
     *
     * @var array
     */
    protected $_tagInfo = array();

    public function analyze()
    {
        /**
         * Overall tag structure:
         *        +-----------------------------+
         *        |      Header (10 bytes)      |
         *        +-----------------------------+
         *        |       Extended Header       |
         *        | (variable length, OPTIONAL) |
         *        +-----------------------------+
         *        |   Frames (variable length)  |
         *        +-----------------------------+
         *        |           Padding           |
         *        | (variable length, OPTIONAL) |
         *        +-----------------------------+
         *        | Footer (10 bytes, OPTIONAL) |
         *        +-----------------------------+
         *
         *    Header
         *        ID3v2/file identifier      "ID3"
         *        ID3v2 version              $04 00
         *        ID3v2 flags                (%ab000000 in v2.2, %abc00000 in v2.3, %abcd0000 in v2.4.x)
         *        ID3v2 size                 4 * %0xxxxxxx
         */

        //$fp = $this->_id3->getFilePointer();
        $this->_tagInfo = $this->_id3->getFileInfo();


        $this->_tagInfo['id3v2']['flags'] = array ();

        $this->fseek($this->_startingOffset, SEEK_SET);
        $header = $this->fread(10);
        if (substr($header, 0, 3) == 'ID3'  &&  strlen($header) == 10) {

            $this->_tagInfo['id3v2']['majorversion'] = ord($header{3});
            $this->_tagInfo['id3v2']['minorversion'] = ord($header{4});

            $this->_tagMajorVersion = $this->_tagInfo['id3v2']['majorversion'];

        } else {
            return false;
        }

        /**
         * The current max major version is 4 so that's what we support up to.
         */
        if ($this->_tagMajorVersion > 4) {
            include_once 'Zend/Id3/Adapter/Exception.php';
            throw new Zend_Id3_Adapter_Exception('Zend_Id3_Adapter_Id3v2 can only parse upt to Id3v2.4');
        }

        $this->_tagInfo['id3v2']['flags'] = $this->_processTagFlags(ord($header{5}));

        // length of ID3v2 tag in 10-byte header doesn't include 10-byte header length
        $this->_tagInfo['id3v2']['headerlength'] = Zend_Id3_ByteConvert::bigEndianSyncSafe2Int(substr($header, 6, 4)) + 10;

        $this->_tagInfo['id3v2']['tag_offset_start'] = $this->_startingOffset;
        $this->_tagInfo['id3v2']['tag_offset_end']   = $this->_tagInfo['id3v2']['tag_offset_start'] + $this->_tagInfo['id3v2']['headerlength'];


        $this->_parseFrameData();

        $this->_id3->setFileInfo($this->_tagInfo);

        return true;

    }

    /**
     * Handle the proccesing of the Id3v2 Tag Flags
     *
     * v2.2 = %ab000000
     * v2.3 = %abc00000
     * v2.4 = %abcd0000
     *
     * @param integer $flags
     * @return array
     */
    protected function _processTagFlags($flags)
    {
        $arrFlags = array();
        switch ($this->_tagMajorVersion) {
            case 2:
                $arrFlags['unsynch']     = (bool)($flags & 0x80); // a - Unsynchronisation
                $arrFlags['compression'] = (bool)($flags & 0x40); // b - Compression
                break;

            case 3:
                $arrFlags['unsynch']     = (bool)($flags & 0x80); // a - Unsynchronisation
                $arrFlags['exthead']     = (bool)($flags & 0x40); // b - Extended header
                $arrFlags['experim']     = (bool)($flags & 0x20); // c - Experimental indicator
                break;

            case 4:
                $arrFlags['unsynch']     = (bool)($flags & 0x80); // a - Unsynchronisation
                $arrFlags['exthead']     = (bool)($flags & 0x40); // b - Extended header
                $arrFlags['experim']     = (bool)($flags & 0x20); // c - Experimental indicator
                $arrFlags['isfooter']    = (bool)($flags & 0x10); // d - Footer present
                break;
        }

        return $arrFlags;
    }

    /**
     * Proccess Frame Data
     * All ID3v2 frames consists of one frame header followed by one or more
     * fields containing the actual information. The header is always 10
     * bytes and laid out as follows:
     *
     * Frame ID      $xx xx xx xx  (four characters)
     * Size      4 * %0xxxxxxx
     * Flags         $xx xx
     */
    protected function _parseFrameData()
    {

        // get the frame size with out the 10-byte initial header
        $frameSize = $this->_tagInfo['id3v2']['headerlength'] - 10;

        // the footer takes the last 10 bytes of the header, after the frame data and before the audio
        if(isset($this->_tagInfo['id3v2']['flags']['isfooter'])) {
            $frameSize -= 10;
        }

        if($frameSize > 0) {

            $frameData = $this->fread($frameSize);

            // If the entire frame data is unsynched, de-unsynch it now (ID3v2.3.x)
            if(isset($this->_tagInfo['id3v2']['flags']['unsync']) && $this->_tagMajorVersion <= 3) {
                $frameData = str_replace("\xFF\x00", "\xFF", $frameData);
            }

            /**
             * [in ID3v2.4.0] Unsynchronisation [S:6.1] is done on frame level, instead
             * of on tag level, making it easier to skip frames, increasing the streamability
             * of the tag. The unsynchronisation flag in the header [S:3.1] indicates that
             * there exists an unsynchronised frame, while the new unsynchronisation flag in
             * the frame header [S:4.1.2] indicates unsynchronisation.
             */
            // how many bytes into the stream - start from after the 10-byte header
            $this->_frameDataOffset = 10;

            if(isset($this->_tagInfo['id3v2']['flags']['exthead']) && true == $this->_tagInfo['id3v2']['flags']['exthead']) {
                $frameData = $this->_proccessExtendedFrameHeader($frameData);
            }

            include_once 'Zend/Id3/Adapter/Id3v2/Frames.php';
            $frames = new Zend_Id3_Adapter_Id3v2_Frames($frameData, $this->_frameDataOffset, $this->_tagMajorVersion);

        }
    }

    /**
     *
     */
    protected function _proccessExtendedFrameHeader($frameData)
    {
        $extendedHeaderOffset = 0;
        switch($this->_tagMajorVersion) {
            /**
             * v2.3 definition:
             *
             * Extended header size  $xx xx xx xx   // 32-bit integer
             * Extended Flags        $xx xx
             * %x0000000 %00000000 // v2.3
             *     x - CRC data present
             * Size of padding       $xx xx xx xx
             */
            case 3:
                $this->_tagInfo['id3v2']['exthead']['length'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 4), 0);
                $extendedHeaderOffset += 4;

                $this->_tagInfo['id3v2']['exthead']['flag_bytes'] = 2;
                $this->_tagInfo['id3v2']['exthead']['flag_raw'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, $this->_tagInfo['id3v2']['exthead']['flag_bytes']));
                $extendedHeaderOffset += $this->_tagInfo['id3v2']['exthead']['flag_bytes'];

                $this->_tagInfo['id3v2']['exthead']['flags']['crc'] = (bool) ($this->_tagInfo['id3v2']['exthead']['flag_raw'] & 0x8000);

                $this->_tagInfo['id3v2']['exthead']['padding_size'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 4));
                $extendedHeaderOffset += 4;

                if ($this->_tagInfo['id3v2']['exthead']['flags']['crc']) {
                $this->_tagInfo['id3v2']['exthead']['flag_data']['crc'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 4));
                $extendedHeaderOffset += 4;
                }
                $extendedHeaderOffset += $this->_tagInfo['id3v2']['exthead']['padding_size'];
                break;

            /**
              * v2.4 definition:
              *
              * Extended header size   4 * %0xxxxxxx  *  28-bit synchsafe integer
              * Number of flag bytes       $01
              * Extended Flags             $xx
              *      %0bcd0000  *  v2.4
              *      b - Tag is an update
              *          Flag data length       $00
              *      c - CRC data present
              *          Flag data length       $05
              *          Total frame CRC    5 * %0xxxxxxx
              *      d - Tag restrictions
              *          Flag data length       $01
              */
            case 4:

                $this->_tagInfo['id3v2']['exthead']['length']     = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 4), 1);
                $extendedHeaderOffset += 4;

                $this->_tagInfo['id3v2']['exthead']['flag_bytes'] = 1;
                $this->_tagInfo['id3v2']['exthead']['flag_raw'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, $this->_tagInfo['id3v2']['exthead']['flag_bytes']));
                $extendedHeaderOffset += $this->_tagInfo['id3v2']['exthead']['flag_bytes'];

                $this->_tagInfo['id3v2']['exthead']['flags']['update']       = (bool) ($this->_tagInfo['id3v2']['exthead']['flag_raw'] & 0x4000);
                $this->_tagInfo['id3v2']['exthead']['flags']['crc']          = (bool) ($this->_tagInfo['id3v2']['exthead']['flag_raw'] & 0x2000);
                $this->_tagInfo['id3v2']['exthead']['flags']['restrictions'] = (bool) ($this->_tagInfo['id3v2']['exthead']['flag_raw'] & 0x1000);

                if ($this->_tagInfo['id3v2']['exthead']['flags']['crc']) {
                    $this->_tagInfo['id3v2']['exthead']['flag_data']['crc'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 5), 1);
                    $extendedHeaderOffset += 5;
                }
                if ($this->_tagInfo['id3v2']['exthead']['flags']['restrictions']) {
                    // %ppqrrstt
                    $restrictionsByte = Zend_Id3_ByteConvert::bigEndian2Int(substr($frameData, $extendedHeaderOffset, 1));
                    $extendedHeaderOffset += 1;
                    $this->_tagInfo['id3v2']['exthead']['flags']['restrictions']['tagsize']  = ($restrictionsByte && 0xC0) >> 6; // p - Tag size restrictions
                    $this->_tagInfo['id3v2']['exthead']['flags']['restrictions']['textenc']  = ($restrictionsByte && 0x20) >> 5; // q - Text encoding restrictions
                    $this->_tagInfo['id3v2']['exthead']['flags']['restrictions']['textsize'] = ($restrictionsByte && 0x18) >> 3; // r - Text fields size restrictions
                    $this->_tagInfo['id3v2']['exthead']['flags']['restrictions']['imgenc']   = ($restrictionsByte && 0x04) >> 2; // s - Image encoding restrictions
                    $this->_tagInfo['id3v2']['exthead']['flags']['restrictions']['imgsize']  = ($restrictionsByte && 0x03) >> 0; // t - Image size restrictions
                }

                break;

        }

        $this->_frameDataOffset += $extendedHeaderOffset;
        $frameData = substr($frameData, $extendedHeaderOffset);

        return $frameData;
    }
}
