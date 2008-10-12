<?php

abstract class Zend_Id3_Adapter_Id3v2_Frames_Abstract
{
    /**
     * Holds all the frame information
     *
     * @var array
     */
    protected $_data = array();

    /**
     * What version of the id3V2 tag are we proccessing
     *
     * @var integer
     */
    protected $_tagVersion = 0;

    /**
     * Constructor function for the class
     *
     * @param string $frameInfo
     * @param integer $id3v2TagVersion
     */
    public function __construct($frameInfo, $id3v2TagVersion)
    {
        $this->_data = $frameInfo;
        $this->_tagVersion = $id3v2TagVersion;

        $this->_data['frameLongName'] = $this->_longNameLookup($frameInfo['frameName']);
        $this->_data['frameShortName'] = $this->_shortNameLookup($frameInfo['frameName']);

        /**
         * Parse frame flags for v2.3 and higher as v2.2 doesnt support them.
         */
        if($this->_tagVersion >= 3) {
            $this->_data['flags'] = $this->_parseFrameFlags();
        }

        $this->parseFrame();
    }

    /**
     * Abstract function to process the framedata
     */
    abstract protected function parseFrame();

    /**
     * Return the array containg the frame information
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * Parse the Frame Flags
     *
     * @return array
     * @throws Zend_Id3_Adapter_Id3v2_Frame_Exception
     */
    private function _parseFrameFlags()
    {
        $flags = array();

        switch($this->_tagVersion) {
            /**
             * v2.3 Frame Header Flags
             * %abc00000 %ijk00000
             */
            case 3:
                $flags['TagAlterPreservation']  = (bool)($this->_data['frame_flags_raw'] & 0x8000); // a - Tag alter preservation
                $flags['FileAlterPreservation'] = (bool)($this->_data['frame_flags_raw'] & 0x4000); // b - File alter preservation
                $flags['ReadOnly']              = (bool)($this->_data['frame_flags_raw'] & 0x2000); // c - Read only
                $flags['compression']           = (bool)($this->_data['frame_flags_raw'] & 0x0080); // i - Compression
                $flags['Encryption']            = (bool)($this->_data['frame_flags_raw'] & 0x0040); // j - Encryption
                $flags['GroupingIdentity']      = (bool)($this->_data['frame_flags_raw'] & 0x0020); // k - Grouping identity
                break;

            /**
             * v2.4 Frame Header Flags
             * %0abc0000 %0h00kmnp
             */
            case 4:
                $flags['TagAlterPreservation']  = (bool)($this->_data['frame_flags_raw'] & 0x4000); // a - Tag alter preservation
                $flags['FileAlterPreservation'] = (bool)($this->_data['frame_flags_raw'] & 0x2000); // b - File alter preservation
                $flags['ReadOnly']              = (bool)($this->_data['frame_flags_raw'] & 0x1000); // c - Read only
                $flags['GroupingIdentity']      = (bool)($this->_data['frame_flags_raw'] & 0x0040); // h - Grouping identity
                $flags['compression']           = (bool)($this->_data['frame_flags_raw'] & 0x0008); // k - Compression
                $flags['Encryption']            = (bool)($this->_data['frame_flags_raw'] & 0x0004); // m - Encryption
                $flags['Unsynchronisation']     = (bool)($this->_data['frame_flags_raw'] & 0x0002); // n - Unsynchronisation
                $flags['DataLengthIndicator']   = (bool)($this->_data['frame_flags_raw'] & 0x0001); // p - Data length indicator

                // Frame-level de-unsynchronisation - ID3v2.4
                if ($flags['Unsynchronisation']) {
                    $this->_data['data'] = str_replace("\xFF\x00", "\xFF", $this->_data['data']);
                }
                break;
        }

        /**
         * Frame-Level de-compression
         */
        if ($flags['compression']) {
            $this->_data['decompressed_size'] = Zend_Id3_ByteConvert::bigEndian2Int(substr($this->_data['data'], 0, 4));

            if (!function_exists('gzuncompress')) {
                include_once 'Zend/Id3/Adapter/Id3v2/Frame/Exception.php';
                throw new Zend_Id3_Adapter_Id3v2_Frame_Exception('gzuncompress() support required to decompress ID3v2 frame "'.$this->_data['frame_name'].'"');
            } elseif ($decompressed_data = @gzuncompress(substr($this->_data['data'], 4))) {
                $this->_data['data'] = $decompressed_data;
            } else {
                include_once 'Zend/Id3/Adapter/Id3v2/Frame/Exception.php';
                throw new Zend_Id3_Adapter_Id3v2_Frame_Exception('gzuncompress() failed on compressed contents of ID3v2 frame "'.$this->_data['frame_name'].'"');
            }
        }

        return $flags;
    }

    private function _shortNameLookup($frameName) {
        static $lookup = array (
            'COM'  => 'comment',
            'COMM' => 'comment',
            'TAL'  => 'album',
            'TALB' => 'album',
            'TBP'  => 'bpm',
            'TBPM' => 'bpm',
            'TCM'  => 'composer',
            'TCO'  => 'genre',
            'TCOM' => 'composer',
            'TCON' => 'genre',
            'TCOP' => 'copyright',
            'TCR'  => 'copyright',
            'TEN'  => 'encoded_by',
            'TENC' => 'encoded_by',
            'TEXT' => 'lyricist',
            'TIT1' => 'description',
            'TIT2' => 'title',
            'TIT3' => 'subtitle',
            'TLA'  => 'language',
            'TLAN' => 'language',
            'TLE'  => 'length',
            'TLEN' => 'length',
            'TMOO' => 'mood',
            'TOA'  => 'original_artist',
            'TOAL' => 'original_album',
            'TOF'  => 'original_filename',
            'TOFN' => 'original_filename',
            'TOL'  => 'original_lyricist',
            'TOLY' => 'original_lyricist',
            'TOPE' => 'original_artist',
            'TOT'  => 'original_album',
            'TP1'  => 'artist',
            'TP2'  => 'band',
            'TP3'  => 'conductor',
            'TP4'  => 'remixer',
            'TPB'  => 'publisher',
            'TPE1' => 'artist',
            'TPE2' => 'band',
            'TPE3' => 'conductor',
            'TPE4' => 'remixer',
            'TPUB' => 'publisher',
            'TRC'  => 'isrc',
            'TRCK' => 'track',
            'TRK'  => 'track',
            'TSI'  => 'size',
            'TSIZ' => 'size',
            'TSRC' => 'isrc',
            'TSS'  => 'encoder_settings',
            'TSSE' => 'encoder_settings',
            'TSST' => 'subtitle',
            'TT1'  => 'description',
            'TT2'  => 'title',
            'TT3'  => 'subtitle',
            'TXT'  => 'lyricist',
            'TXX'  => 'text',
            'TXXX' => 'text',
            'TYE'  => 'year',
            'TYER' => 'year',
            'UFI'  => 'unique_file_identifier',
            'UFID' => 'unique_file_identifier',
            'ULT'  => 'unsychronised_lyric',
            'USER' => 'terms_of_use',
            'USLT' => 'unsynchronised lyric',
            'WAF'  => 'url_file',
            'WAR'  => 'url_artist',
            'WAS'  => 'url_source',
            'WCOP' => 'copyright',
            'WCP'  => 'copyright',
            'WOAF' => 'url_file',
            'WOAR' => 'url_artist',
            'WOAS' => 'url_source',
            'WORS' => 'url_station',
            'WPB'  => 'url_publisher',
            'WPUB' => 'url_publisher',
            'WXX'  => 'url_user',
            'WXXX' => 'url_user',
            'TFEA' => 'featured_artist',
            'TSTU' => 'studio'
        );

         return (isset($lookup[$frameName])) ? $lookup[$frameName] : '';
    }

    private function _longNameLookup($frameName) {

        static $lookup = array (
            'AENC' => 'Audio encryption',
            'APIC' => 'Attached picture',
            'ASPI' => 'Audio seek point index',
            'BUF'  => 'Recommended buffer size',
            'CNT'  => 'Play counter',
            'COM'  => 'Comments',
            'COMM' => 'Comments',
            'COMR' => 'Commercial frame',
            'CRA'  => 'Audio encryption',
            'CRM'  => 'Encrypted meta frame',
            'ENCR' => 'Encryption method registration',
            'EQU'  => 'Equalisation',
            'EQU2' => 'Equalisation (2)',
            'EQUA' => 'Equalisation',
            'ETC'  => 'Event timing codes',
            'ETCO' => 'Event timing codes',
            'GEO'  => 'General encapsulated object',
            'GEOB' => 'General encapsulated object',
            'GRID' => 'Group identification registration',
            'IPL'  => 'Involved people list',
            'IPLS' => 'Involved people list',
            'LINK' => 'Linked information',
            'LNK'  => 'Linked information',
            'MCDI' => 'Music CD identifier',
            'MCI'  => 'Music CD Identifier',
            'MLL'  => 'MPEG location lookup table',
            'MLLT' => 'MPEG location lookup table',
            'OWNE' => 'Ownership frame',
            'PCNT' => 'Play counter',
            'PIC'  => 'Attached picture',
            'POP'  => 'Popularimeter',
            'POPM' => 'Popularimeter',
            'POSS' => 'Position synchronisation frame',
            'PRIV' => 'Private frame',
            'RBUF' => 'Recommended buffer size',
            'REV'  => 'Reverb',
            'RVA'  => 'Relative volume adjustment',
            'RVA2' => 'Relative volume adjustment (2)',
            'RVAD' => 'Relative volume adjustment',
            'RVRB' => 'Reverb',
            'SEEK' => 'Seek frame',
            'SIGN' => 'Signature frame',
            'SLT'  => 'Synchronised lyric/text',
            'STC'  => 'Synced tempo codes',
            'SYLT' => 'Synchronised lyric/text',
            'SYTC' => 'Synchronised tempo codes',
            'TAL'  => 'Album/Movie/Show title',
            'TALB' => 'Album/Movie/Show title',
            'TBP'  => 'BPM (Beats Per Minute)',
            'TBPM' => 'BPM (beats per minute)',
            'TCM'  => 'Composer',
            'TCO'  => 'Content type',
            'TCOM' => 'Composer',
            'TCON' => 'Content type',
            'TCOP' => 'Copyright message',
            'TCR'  => 'Copyright message',
            'TDA'  => 'Date',
            'TDAT' => 'Date',
            'TDEN' => 'Encoding time',
            'TDLY' => 'Playlist delay',
            'TDOR' => 'Original release time',
            'TDRC' => 'Recording time',
            'TDRL' => 'Release time',
            'TDTG' => 'Tagging time',
            'TDY'  => 'Playlist delay',
            'TEN'  => 'Encoded by',
            'TENC' => 'Encoded by',
            'TEXT' => 'Lyricist/Text writer',
            'TFLT' => 'File type',
            'TFT'  => 'File type',
            'TIM'  => 'Time',
            'TIME' => 'Time',
            'TIPL' => 'Involved people list',
            'TIT1' => 'Content group description',
            'TIT2' => 'Title/songname/content description',
            'TIT3' => 'Subtitle/Description refinement',
            'TKE'  => 'Initial key',
            'TKEY' => 'Initial key',
            'TLA'  => 'Language(s)',
            'TLAN' => 'Language(s)',
            'TLE'  => 'Length',
            'TLEN' => 'Length',
            'TMCL' => 'Musician credits list',
            'TMED' => 'Media type',
            'TMOO' => 'Mood',
            'TMT'  => 'Media type',
            'TOA'  => 'Original artist(s)/performer(s)',
            'TOAL' => 'Original album/movie/show title',
            'TOF'  => 'Original filename',
            'TOFN' => 'Original filename',
            'TOL'  => 'Original Lyricist(s)/text writer(s)',
            'TOLY' => 'Original lyricist(s)/text writer(s)',
            'TOPE' => 'Original artist(s)/performer(s)',
            'TOR'  => 'Original release year',
            'TORY' => 'Original release year',
            'TOT'  => 'Original album/Movie/Show title',
            'TOWN' => 'File owner/licensee',
            'TP1'  => 'Lead artist(s)/Lead performer(s)/Soloist(s)/Performing group',
            'TP2'  => 'Band/Orchestra/Accompaniment',
            'TP3'  => 'Conductor/Performer refinement',
            'TP4'  => 'Interpreted, remixed, or otherwise modified by',
            'TPA'  => 'Part of a set',
            'TPB'  => 'Publisher',
            'TPE1' => 'Lead performer(s)/Soloist(s)',
            'TPE2' => 'Band/orchestra/accompaniment',
            'TPE3' => 'Conductor/performer refinement',
            'TPE4' => 'Interpreted, remixed, or otherwise modified by',
            'TPOS' => 'Part of a set',
            'TPRO' => 'Produced notice',
            'TPUB' => 'Publisher',
            'TRC'  => 'ISRC (International Standard Recording Code)',
            'TRCK' => 'Track number/Position in set',
            'TRD'  => 'Recording dates',
            'TRDA' => 'Recording dates',
            'TRK'  => 'Track number/Position in set',
            'TRSN' => 'Internet radio station name',
            'TRSO' => 'Internet radio station owner',
            'TSI'  => 'Size',
            'TSIZ' => 'Size',
            'TSOA' => 'Album sort order',
            'TSOP' => 'Performer sort order',
            'TSOT' => 'Title sort order',
            'TSRC' => 'ISRC (international standard recording code)',
            'TSS'  => 'Software/hardware and settings used for encoding',
            'TSSE' => 'Software/Hardware and settings used for encoding',
            'TSST' => 'Set subtitle',
            'TT1'  => 'Content group description',
            'TT2'  => 'Title/Songname/Content description',
            'TT3'  => 'Subtitle/Description refinement',
            'TXT'  => 'Lyricist/text writer',
            'TXX'  => 'User defined text information frame',
            'TXXX' => 'User defined text information frame',
            'TYE'  => 'Year',
            'TYER' => 'Year',
            'UFI'  => 'Unique file identifier',
            'UFID' => 'Unique file identifier',
            'ULT'  => 'Unsychronised lyric/text transcription',
            'USER' => 'Terms of use',
            'USLT' => 'Unsynchronised lyric/text transcription',
            'WAF'  => 'Official audio file webpage',
            'WAR'  => 'Official artist/performer webpage',
            'WAS'  => 'Official audio source webpage',
            'WCM'  => 'Commercial information',
            'WCOM' => 'Commercial information',
            'WCOP' => 'Copyright/Legal information',
            'WCP'  => 'Copyright/Legal information',
            'WOAF' => 'Official audio file webpage',
            'WOAR' => 'Official artist/performer webpage',
            'WOAS' => 'Official audio source webpage',
            'WORS' => 'Official Internet radio station homepage',
            'WPAY' => 'Payment',
            'WPB'  => 'Publishers official webpage',
            'WPUB' => 'Publishers official webpage',
            'WXX'  => 'User defined URL link frame',
            'WXXX' => 'User defined URL link frame',
            'TFEA' => 'Featured Artist',
            'TSTU' => 'Recording Studio',
            'rgad' => 'Replay Gain Adjustment'
        );

        return (isset($lookup[$frameName])) ? $lookup[$frameName] : '';

    }
}