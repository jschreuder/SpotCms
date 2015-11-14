<?php

namespace Spot\Api\Common\TOTP;

/**
 * Based on PHPGangsta_GoogleAuthenticator class for handling Google Authenticator
 * 2-factor authentication
 *
 * @author     Michael Kliewe (PHPGangsta_GoogleAuthenticator), Jelmer Schreuder (modifications)
 * @copyright  2012 Michael Kliewe
 * @license    http://www.opensource.org/licenses/bsd-license.php BSD License
 */
class TOTP
{
    /** @var  int */
    private $codeLength = 6;

    /**
     * @param  int $codeLength
     */
    public function __construct($codeLength = 6)
    {
        if (!is_int($codeLength) || $codeLength < 6) {
            throw new \OutOfRangeException('Code length must be a valid integer and be 6 or larger.');
        }
        $this->codeLength = $codeLength;
    }

    /** @return  int */
    protected function getCodeLength()
    {
        return $this->codeLength;
    }

    /**
     * Create new secret.
     * 16 characters, randomly chosen from the allowed base32 characters.
     *
     * @param   int $secretLength
     * @return  string
     */
    public function createSecret($secretLength = 16)
    {
        $validChars = $this->getBase32LookupTable();
        unset($validChars[32]);

        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[array_rand($validChars)];
        }
        return $secret;
    }

    /**
     * Calculate the code, with given secret and point in time
     *
     * @param   string $secret
     * @param   int|null $timeSlice
     * @return  string
     */
    public function getCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        $secretKey = $this->base32Decode($secret);

        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        // Hash it with users secret key
        $hm = hash_hmac('SHA1', $time, $secretKey, true);
        // Use last nipple of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        // grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        // Unpak binary value
        $value = unpack('N', $hashpart);
        $value = $value[1];
        // Only 32 bits
        $value = $value & 0x7FFFFFFF;

        $modulo = pow(10, $this->getCodeLength());
        return str_pad($value % $modulo, $this->getCodeLength(), '0', STR_PAD_LEFT);
    }

    /**
     * Get QR-Code URL for image, from google charts
     *
     * @param   string $name
     * @param   string $secret
     * @param   string $title
     * @return   string
     */
    public function getQRCodeUrl($name, $secret, $title = null)
    {
        $url = 'otpauth://totp/' . urlencode($name) . '?';

        $vars = ['secret' => $secret];
        if(!is_null($title)) {
            $vars['issuer'] = $title;
        }

        return $url . http_build_query($vars);
    }

    /**
     * Check if the code is correct. This will accept codes starting from
     * $discrepancy*30sec ago to $discrepancy*30sec from now
     *
     * @param   string $secret
     * @param   string $code
     * @param   int $discrepancy This is the allowed time drift in 30 second
     *          units (8 means 4 minutes before or after)
     * @param   int|null $currentTimeSlice time slice if we want use other
     *          that time()
     * @return  bool
     */
    public function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null)
    {
        if (strlen($code) !== $this->getCodeLength()) {
            throw new \InvalidArgumentException('Code must have exact length: ' . $this->getCodeLength());
        }

        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }

        // Attempt to match by going through codes ($discrepancy * 30 seconds) before and after
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        // No match found
        return false;
    }

    /**
     * Helper class to decode base32
     *
     * @param   $secret
     * @return  string
     */
    protected function base32Decode($secret)
    {
        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty');
        }

        $base32chars = $this->getBase32LookupTable();
        $base32charsFlipped = array_flip($base32chars);
        $paddingCharCount = substr_count($secret, $base32chars[32]);
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) {
            return false;
        }

        for ($i = 0; $i < 4; $i++) {
            if (
                $paddingCharCount === $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) !== str_repeat($base32chars[32], $allowedValues[$i])
            ) {
                throw new \InvalidArgumentException(
                    'Invalid padding encountered in secret, all = characters must be at the end'
                );
            }
        }

        $secret = str_replace('=','', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        for ($i = 0; $i < count($secret); $i = $i+8) {
            if (!in_array($secret[$i], $base32chars)) {
                throw new \InvalidArgumentException('Invalid character encountered in secret: ' . $secret[$i]);
            }

            $x = '';
            for ($j = 0; $j < 8; $j++) {
                $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
            }

            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : '';
            }
        }

        return $binaryString;
    }

    /**
     * Helper class to encode base32
     *
     * @param   string $secret
     * @param   bool $padding
     * @return  string
     */
    protected function base32Encode($secret, $padding = true)
    {
        if (empty($secret)) {
            throw new \InvalidArgumentException('Secret cannot be empty');
        }

        $base32chars = $this->getBase32LookupTable();
        $secret = str_split($secret);
        $binaryString = '';
        for ($i = 0; $i < count($secret); $i++) {
            $binaryString .= str_pad(base_convert(ord($secret[$i]), 10, 2), 8, '0', STR_PAD_LEFT);
        }

        $fiveBitBinaryArray = str_split($binaryString, 5);
        $base32 = '';
        $i = 0;
        while ($i < count($fiveBitBinaryArray)) {
            $base32 .= $base32chars[base_convert(str_pad($fiveBitBinaryArray[$i], 5, '0'), 2, 10)];
            $i++;
        }

        if ($padding && ($x = strlen($binaryString) % 40) !== 0) {
            if ($x === 8) {
                $base32 .= str_repeat($base32chars[32], 6);
            } elseif ($x === 16) {
                $base32 .= str_repeat($base32chars[32], 4);
            } elseif ($x === 24) {
                $base32 .= str_repeat($base32chars[32], 3);
            } elseif ($x === 32) {
                $base32 .= $base32chars[32];
            }
        }
        return $base32;
    }

    /**
     * Get array with all 32 characters for decoding from/encoding to base32
     *
     * @return  array
     */
    protected function getBase32LookupTable()
    {
        return [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', //  7
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', // 15
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', // 23
            'Y', 'Z', '2', '3', '4', '5', '6', '7', // 31
            '='  // padding char
        ];
    }
}
