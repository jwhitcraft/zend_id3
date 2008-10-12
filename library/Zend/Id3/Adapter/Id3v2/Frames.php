<?php

class Zend_Id3_Adapter_Id3v2_Frame
{
    /**
     * Collection of proccessed frames
     *
     * @var array
     */
    protected $_frames = array();

    /**
     * Parse frame data (not includeing the frames)
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Unparsed frame data
     *
     * @var string
     */
    protected $_rawData = '';

    /**
     * Version of the Id3v2 tag
     *
     * @var integer
     */
    protected $_tagVersion = 0;

    protected $_frameDataOffset = 0;

    protected $_headerSize = 0;

    protected $_frameClassPrefix = 'Zend_Id3_Adapter_Id3v2_Frame_';

    public function __construct($frameData, $frameDataOffset, $tagVersion)
    {
        $this->_rawData = $frameData;
        $this->_frameDataOffset = $frameDataOffset;
        $this->_tagVersion = $tagVersion;

        $this->_headerSize = ($tagVersion == 2) ? 6: 10;

        $this->_parse();
    }

    protected function _parse()
    {
        $frameData = $this->_rawData;
        while (isset($frameData) && (strlen($frameData) > 0)) {
            if(strlen($frameData) <= $this->_headerSize) {
                $this->_parsePadding($frameData);
                /**
                 * Since we found padding skip the rest of the frame data
                 */
                break;
            }

            if($this->_tagVersion == 2) {
                $info = $this->_getFrameV2($frameData);
            } else {
                $info = $this->_getFrame($frameData);
            }

            /**
             * If info is false then there was padding so we skip the rest of the frame data
             */
            if(false === $info) {
                /**
                 * Since we found padding skip the rest of the frame data
                 */
                break;
            }

            if ($info['frameName'] == 'COM ') {
                // Note: this particular error has been known to happen with tags edited by iTunes (versions "X v2.0.3", "v3.0.1" are known-guilty, probably others too)
                $info['frameName'] = 'COMM';
            }

            if(($info['frameSize'] <= strlen($frameData)) && ($this->_isValidID3v2FrameName($info['frameName']))) {
                $frameData = $info['frameData'];
                $info['data']            = substr($frameData, 0, $info['frameSize']);
                $info['datalength']      = (int)($info['frameSize']);
                $info['dataoffset']      = $this->_frameDataOffset;

                $frameClass = $this->_frameClassPrefix . trim(ucfirst(strtolower($info['frameName'])));

                print $frameClass . "<br />" . PHP_EOL;

                $frameData = substr($frameData, $info['frameSize']);
            }

            $this->_frameDataOffset += ($info['frameSize'] + $this->_headerSize);
        }

    }

    /**
     * Proccess any found padding
     *
     * @param string $frameData
     * @param string $frameHeader
     */
    protected function _parsePadding($frameData, $frameHeader = '')
    {
        $this->_data['padding']['start'] = $this->_frameDataOffset;
        $this->_data['padding']['length'] = strlen($frameHeader) + strlen($frameData);
        $this->_data['padding']['valid'] = true;
        for ($i = 0; $i < $this->_data['padding']['length']; $i++) {
            if ($frameData{$i} != "\x00") {
                $this->_data['padding']['valid'] = false;
                $this->_data['padding']['errorpos'] = $this->_data['padding']['start'] + $i;
                //$getid3->warning('Invalid ID3v2 padding found at offset '.$info_id3v2['padding']['errorpos'].' (the remaining '.($info_id3v2['padding']['length'] - $i).' bytes are considered invalid)');
                break;
            }
        }

        return false;
    }

    /**
     * parse out frame information from v2.2 tag
     *
     * @param string $frameData
     * @return array|boolean
     */
    protected function _getFrameV2($frameData)
    {
        $info = array();
        $info['frameHeader'] = substr($frameData, 0, 6); // take next 6 bytes for header
        $info['frameData']   = substr($frameData, 6);    // and leave the rest in $frame_data
        $info['frameName']   = substr($info['frameHeader'], 0, 3);
        $info['fameSize']    = Zend_Id3_ByteConvert::bigEndian2Int(substr($info['frameHeader'], 3, 3));
        $info['frameFlags']  = 0; // not used for anything in ID3v2.2, just set to avoid E_NOTICEs

        // padding encountered
        if($info['frameName'] == "\x00\x00\x00" || $info['frameName'] == "\x00\x00\x00\x00") {
            return $this->_parsePadding($info['frameData'], $info['frameHeader']);
        }

        return $info;
    }

    /**
     * Parse frame out from v2.3 and higher tags
     *
     * Frame ID  $xx xx xx xx (four characters)
     * Size      $xx xx xx xx (32-bit integer in v2.3, 28-bit synchsafe in v2.4+)
     * Flags     $xx xx
     *
     * @param string $frameData
     */
    protected function _getFrame($frameData)
    {
        $info = array();
        // take next 10 bytes for header
        $info['frameHeader'] = substr($frameData, 0, 10);
        // and leave the rest in $frame_data
        $info['frameData'] = substr($frameData, 10);
        $info['frameName'] = substr($info['frameHeader'], 0, 4);

        $info['frameSize'] = ($this->_tagVersion == 3) ? Zend_Id3_ByteConvert::bigEndian2Int(substr($info['frameHeader'], 4, 4)) :
                Zend_Id3_ByteConvert::bigEndianSyncSafe2Int(substr($info['frameHeader'], 4, 4));

        if($info['frameSize'] < (strlen($frameData) + 4)) {

            $arrBrokenMP3extFrames = array(
                "\x00".'MP3',
                "\x00\x00".'MP',
                ' MP3',
                'MP3e',
            );

            $nextFrameID = substr($frameData, $info['frameSize'], 4);
            if ($this->_isValidID3v2FrameName($nextFrameID)) {
                // next frame is OK
            } else if (in_array($info['frameName'], $arrBrokenMP3extFrames)) {
                // MP3ext known broken frames - "ok" for the purposes of this test
            } else if($this->_tagVersion == 4 && $this->_isValidID3v2FrameName(substr($info['frameData'], Zend_Id3_ByteConvert::bigEndian2Int(substr($info['frameHeader'], 4, 4)), 4), 3)) {
                $this->_tagVersion = 3;
                $info['frameSize'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($info['frameHeader'], 4, 4)); // 32-bit integer
            }
        }

        $info['frameFlag'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($info['frameHeader'], 8, 2));

        // padding encountered
        if($info['frameName'] == "\x00\x00\x00\x00") {
            return $this->_parsePadding($info['frameData'], $info['frameHeader']);
        }

        return $info;

    }

    private function _isValidID3v2FrameName($frameName, $versionOverride = null) {

        $tagVer = $this->_tagVersion;

        if(!is_null($versionOverride)) {
            $tagVer = intval($versionOverride);
        }

        switch ($tagVer) {
            case 2:
                return preg_match('/[A-Z][A-Z0-9]{2}/', $frameName);
                break;
            case 3:
            case 4:
                return preg_match('/[A-Z][A-Z0-9]{3}/', $frameName);
                break;
        }

        return false;
    }
}