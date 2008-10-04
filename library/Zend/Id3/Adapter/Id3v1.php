<?php
/**
 * Zend_Id3_Adapter_Id3v1
 *
 * @uses
 * @package    Zend
 * @category   Id3
 * @copyright  Copyright (C) 2008 - Present, Jon Whitcraft
 * @author     Jon Whitcraft <jon.zf@mac.com>
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @version    $Id: $
 */

require_once ('library/Zend/Id3/Adapter/Abstract.php');

class Zend_Id3_Adapter_Id3v1 extends Zend_Id3_Adapter_Abstract
{
	/**
	 * List of Geners for Id3v1
	 *
	 * @var array
	 */
	protected $_genres = array (
			0    => 'Blues',
			1    => 'Classic Rock',
			2    => 'Country',
			3    => 'Dance',
			4    => 'Disco',
			5    => 'Funk',
			6    => 'Grunge',
			7    => 'Hip-Hop',
			8    => 'Jazz',
			9    => 'Metal',
			10   => 'New Age',
			11   => 'Oldies',
			12   => 'Other',
			13   => 'Pop',
			14   => 'R&B',
			15   => 'Rap',
			16   => 'Reggae',
			17   => 'Rock',
			18   => 'Techno',
			19   => 'Industrial',
			20   => 'Alternative',
			21   => 'Ska',
			22   => 'Death Metal',
			23   => 'Pranks',
			24   => 'Soundtrack',
			25   => 'Euro-Techno',
			26   => 'Ambient',
			27   => 'Trip-Hop',
			28   => 'Vocal',
			29   => 'Jazz+Funk',
			30   => 'Fusion',
			31   => 'Trance',
			32   => 'Classical',
			33   => 'Instrumental',
			34   => 'Acid',
			35   => 'House',
			36   => 'Game',
			37   => 'Sound Clip',
			38   => 'Gospel',
			39   => 'Noise',
			40   => 'Alt. Rock',
			41   => 'Bass',
			42   => 'Soul',
			43   => 'Punk',
			44   => 'Space',
			45   => 'Meditative',
			46   => 'Instrumental Pop',
			47   => 'Instrumental Rock',
			48   => 'Ethnic',
			49   => 'Gothic',
			50   => 'Darkwave',
			51   => 'Techno-Industrial',
			52   => 'Electronic',
			53   => 'Pop-Folk',
			54   => 'Eurodance',
			55   => 'Dream',
			56   => 'Southern Rock',
			57   => 'Comedy',
			58   => 'Cult',
			59   => 'Gangsta Rap',
			60   => 'Top 40',
			61   => 'Christian Rap',
			62   => 'Pop/Funk',
			63   => 'Jungle',
			64   => 'Native American',
			65   => 'Cabaret',
			66   => 'New Wave',
			67   => 'Psychedelic',
			68   => 'Rave',
			69   => 'Showtunes',
			70   => 'Trailer',
			71   => 'Lo-Fi',
			72   => 'Tribal',
			73   => 'Acid Punk',
			74   => 'Acid Jazz',
			75   => 'Polka',
			76   => 'Retro',
			77   => 'Musical',
			78   => 'Rock & Roll',
			79   => 'Hard Rock',
			80   => 'Folk',
			81   => 'Folk/Rock',
			82   => 'National Folk',
			83   => 'Swing',
			84   => 'Fast-Fusion',
			85   => 'Bebob',
			86   => 'Latin',
			87   => 'Revival',
			88   => 'Celtic',
			89   => 'Bluegrass',
			90   => 'Avantgarde',
			91   => 'Gothic Rock',
			92   => 'Progressive Rock',
			93   => 'Psychedelic Rock',
			94   => 'Symphonic Rock',
			95   => 'Slow Rock',
			96   => 'Big Band',
			97   => 'Chorus',
			98   => 'Easy Listening',
			99   => 'Acoustic',
			100  => 'Humour',
			101  => 'Speech',
			102  => 'Chanson',
			103  => 'Opera',
			104  => 'Chamber Music',
			105  => 'Sonata',
			106  => 'Symphony',
			107  => 'Booty Bass',
			108  => 'Primus',
			109  => 'Porn Groove',
			110  => 'Satire',
			111  => 'Slow Jam',
			112  => 'Club',
			113  => 'Tango',
			114  => 'Samba',
			115  => 'Folklore',
			116  => 'Ballad',
			117  => 'Power Ballad',
			118  => 'Rhythmic Soul',
			119  => 'Freestyle',
			120  => 'Duet',
			121  => 'Punk Rock',
			122  => 'Drum Solo',
			123  => 'A Cappella',
			124  => 'Euro-House',
			125  => 'Dance Hall',
			126  => 'Goa',
			127  => 'Drum & Bass',
			128  => 'Club-House',
			129  => 'Hardcore',
			130  => 'Terror',
			131  => 'Indie',
			132  => 'BritPop',
			133  => 'Negerpunk',
			134  => 'Polsk Punk',
			135  => 'Beat',
			136  => 'Christian Gangsta Rap',
			137  => 'Heavy Metal',
			138  => 'Black Metal',
			139  => 'Crossover',
			140  => 'Contemporary Christian',
			141  => 'Christian Rock',
			142  => 'Merengue',
			143  => 'Salsa',
			144  => 'Trash Metal',
			145  => 'Anime',
			146  => 'JPop',
			147  => 'Synthpop',

			255  => 'Unknown',

			'CR' => 'Cover',
			'RX' => 'Remix'
		);

	public function analyze()
	{
		$fp = $this->_id3->getFilePointer();
		$info = $this->_id3->getFileInfo();

		fseek($fp, -256, SEEK_END);
		$_beforeId3v1 = fread($fp, 128);
		$_id3Tag = fread($fp, 128);

		if(substr($_id3Tag, 0, 3) == 'TAG') {
			$info['avdataend'] -= 128;

			$info['id3v1'] = array();

			$info['id3v1']['title'] = $this->_cutfield(substr($_id3Tag, 3, 30));
			$info['id3v1']['artist'] = $this->_cutfield(substr($_id3Tag, 33, 30));
			$info['id3v1']['album'] = $this->_cutfield(substr($_id3Tag, 63, 30));
			$info['id3v1']['year'] = $this->_cutfield(substr($_id3Tag, 93, 4));
			$info['id3v1']['comment'] = substr($_id3Tag, 97, 30);
			$info['id3v1']['genreid'] = ord(substr($_id3Tag, 127, 1));

			// check to see if this is ID3v1.1 to see if there is a track number
			if(($_id3Tag{125} === "\x00") && ($_id3Tag{126} !== "\x00")) {
				$info['id3v1']['track']   = ord(substr($info['id3v1']['comment'], 29,  1));
				$info['id3v1']['comment'] =     substr($info['id3v1']['comment'],  0, 28);
			}
			$info['id3v1']['comment'] = $this->_cutfield($info['comment']);

			$info['id3v1']['genre'] = $this->_lookupGenreName($info['id3v1']['genreid']);
			if (!empty($info['id3v1']['genre'])) {
				unset($info['id3v1']['genreid']);
			}
			if (empty($info['id3v1']['genre']) || (@$info['id3v1']['genre'] == 'Unknown')) {
				unset($info['id3v1']['genre']);
			}

			$info['id3v1']['tag_offset_end']   = $info['filesize'];
			$info['id3v1']['tag_offset_start'] = $info['id3v1']['tag_offset_end'] - 128;
		}

		if (substr($_beforeId3v1, 0, 3) == 'TAG') {
			// The way iTunes handles tags is, well, brain-damaged.
			// It completely ignores v1 if ID3v2 is present.
			// This goes as far as adding a new v1 tag *even if there already is one*

			// A suspected double-ID3v1 tag has been detected, but it could be that the "TAG" identifier is a legitimate part of an APE or Lyrics3 tag
			if (substr($_beforeId3v1, 96, 8) == 'APETAGEX') {
				// an APE tag footer was found before the last ID3v1, assume false "TAG" synch
			} elseif (substr($_beforeId3v1, 119, 6) == 'LYRICS') {
				// a Lyrics3 tag footer was found before the last ID3v1, assume false "TAG" synch
			} else {
				// APE and Lyrics3 footers not found - assume double ID3v1
				$info['id3v1']['avdataend'] -= 128;
			}
		}

		$this->_id3->setFileInfo($info);

		return true;
	}

	protected function _cutfield($str)
	{
		return trim(substr($str, 0, strcspn($str, "\x00")));
	}

	protected function _lookupGenreName($genreId)
	{
		switch ($genreId) {
			case 'RX':
				// break intentionally omitted
			case 'CR':
				break;
			default:
				$genreId = intval($genreId); // to handle 3 or '3' or '03'
				break;
		}
		return (isset($this->_genres[$genreId]) ? $this->_genres[$genreId] : false);
	}
}