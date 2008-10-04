<?php

/**
 * Zend_Id3_ByteConvert
 *
 * @uses
 * @package    Zend
 * @category   Id3
 * @copyright  Copyright (C) 2008 - Present, Jon Whitcraft
 * @author     Jon Whitcraft <jon.zf@mac.com>
 * @license    New BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @version    $Id: $
 */


class Zend_Id3_ByteConvert
{
// Convert Little Endian byte string to int - max 32 bits
	public static function littleEndian2Int($byte_word, $signed = false) {

		return Zend_Id3_ByteConvert::BigEndian2Int(strrev($byte_word), $signed);
	}



	// Convert number to Little Endian byte string
	public static function littleEndian2String($number, $minbytes=1, $synchsafe=false) {
		$intstring = '';
		while ($number > 0) {
			if ($synchsafe) {
				$intstring = $intstring.chr($number & 127);
				$number >>= 7;
			} else {
				$intstring = $intstring.chr($number & 255);
				$number >>= 8;
			}
		}
		return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
	}



	// Convert Big Endian byte string to int - max 32 bits
	public static function bigEndian2Int($byte_word, $signed = false) {

		$int_value = 0;
		$byte_wordlen = strlen($byte_word);

		for ($i = 0; $i < $byte_wordlen; $i++) {
			$int_value += ord($byte_word{$i}) * pow(256, ($byte_wordlen - 1 - $i));
		}

		if ($signed) {
			$sign_mask_bit = 0x80 << (8 * ($byte_wordlen - 1));
			if ($int_value & $sign_mask_bit) {
				$int_value = 0 - ($int_value & ($sign_mask_bit - 1));
			}
		}

		return $int_value;
	}



	// Convert Big Endian byte sybc safe string to int - max 32 bits
	public static function bigEndianSyncSafe2Int($byte_word) {

		$int_value = 0;
		$byte_wordlen = strlen($byte_word);

		// disregard MSB, effectively 7-bit bytes
		for ($i = 0; $i < $byte_wordlen; $i++) {
			$int_value = $int_value | (ord($byte_word{$i}) & 0x7F) << (($byte_wordlen - 1 - $i) * 7);
		}
		return $int_value;
	}



	// Convert Big Endian byte string to bit string
	public static function bigEndian2Bin($byte_word) {

		$bin_value = '';
		$byte_wordlen = strlen($byte_word);
		for ($i = 0; $i < $byte_wordlen; $i++) {
			$bin_value .= str_pad(decbin(ord($byte_word{$i})), 8, '0', STR_PAD_LEFT);
		}
		return $bin_value;
	}



	public static function bigEndian2Float($byte_word) {

		// ANSI/IEEE Standard 754-1985, Standard for Binary Floating Point Arithmetic
		// http://www.psc.edu/general/software/packages/ieee/ieee.html
		// http://www.scri.fsu.edu/~jac/MAD3401/Backgrnd/ieee.html

		$bit_word = Zend_Id3_ByteConvert::bigEndian2Bin($byte_word);
		if (!$bit_word) {
			return 0;
		}
		$sign_bit = $bit_word{0};

		switch (strlen($byte_word) * 8) {
			case 32:
				$exponent_bits = 8;
				$fraction_bits = 23;
				break;

			case 64:
				$exponent_bits = 11;
				$fraction_bits = 52;
				break;

			case 80:
				// 80-bit Apple SANE format
				// http://www.mactech.com/articles/mactech/Vol.06/06.01/SANENormalized/
				$exponent_string = substr($bit_word, 1, 15);
				$is_normalized = intval($bit_word{16});
				$fraction_string = substr($bit_word, 17, 63);
				$exponent = pow(2, getid3_lib::Bin2Dec($exponent_string) - 16383);
				$fraction = $is_normalized + getid3_lib::DecimalBinary2Float($fraction_string);
				$float_value = $exponent * $fraction;
				if ($sign_bit == '1') {
					$float_value *= -1;
				}
				return $float_value;
				break;

			default:
				return false;
				break;
		}
		$exponent_string = substr($bit_word, 1, $exponent_bits);
		$fraction_string = substr($bit_word, $exponent_bits + 1, $fraction_bits);
		$exponent = bindec($exponent_string);
		$fraction = bindec($fraction_string);

		if (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction != 0)) {
			// Not a Number
			$float_value = false;
		} elseif (($exponent == (pow(2, $exponent_bits) - 1)) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = '-infinity';
			} else {
				$float_value = '+infinity';
			}
		} elseif (($exponent == 0) && ($fraction == 0)) {
			if ($sign_bit == '1') {
				$float_value = -0;
			} else {
				$float_value = 0;
			}
			$float_value = ($sign_bit ? 0 : -0);
		} elseif (($exponent == 0) && ($fraction != 0)) {
			// These are 'unnormalized' values
			$float_value = pow(2, (-1 * (pow(2, $exponent_bits - 1) - 2))) * Zend_Id3_ByteConvert::decimalBinary2Float($fraction_string);
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		} elseif ($exponent != 0) {
			$float_value = pow(2, ($exponent - (pow(2, $exponent_bits - 1) - 1))) * (1 + Zend_Id3_ByteConvert::decimalBinary2Float($fraction_string));
			if ($sign_bit == '1') {
				$float_value *= -1;
			}
		}
		return (float) $float_value;
	}



	public static function littleEndian2Float($byte_word) {

		return Zend_Id3_ByteConvert::bigEndian2Float(strrev($byte_word));
	}



	public static function decimalBinary2Float($binary_numerator) {
		$numerator   = bindec($binary_numerator);
		$denominator = bindec('1'.str_repeat('0', strlen($binary_numerator)));
		return ($numerator / $denominator);
	}


	public static function printHexBytes($string, $hex=true, $spaces=true, $html_safe=true) {

		$return_string = '';
		for ($i = 0; $i < strlen($string); $i++) {
			if ($hex) {
				$return_string .= str_pad(dechex(ord($string{$i})), 2, '0', STR_PAD_LEFT);
			} else {
				$return_string .= ' '.(ereg("[\x20-\x7E]", $string{$i}) ? $string{$i} : '�');
			}
			if ($spaces) {
				$return_string .= ' ';
			}
		}
		if ($html_safe) {
			$return_string = htmlentities($return_string);
		}
		return $return_string;
	}



	// Process header data string - read several values with algorithm and add to target
	//   algorithm is one one the getid3_lib::Something2Something() function names
	//   parts_array is  index => length    -  $target[index] = algorithm(substring(data))
	//   - OR just substring(data) if length is negative!
	//  indexes == 'IGNORE**' are ignored

	public static function readSequence($algorithm, &$target, &$data, $offset, $parts_array) {

		// Loop thru $parts_array
		foreach ($parts_array as $target_string => $length) {

			// Add to target
			if (!strstr($target_string, 'IGNORE')) {

				// substr(....length)
				if ($length < 0) {
					$target[$target_string] = substr($data, $offset, -$length);
				}

				// algorithm(substr(...length))
				else {
					$target[$target_string] = Zend_Id3_ByteConvert::$algorithm(substr($data, $offset, $length));
				}
			}

			// Move pointer
			$offset += abs($length);
		}
	}
}

?>