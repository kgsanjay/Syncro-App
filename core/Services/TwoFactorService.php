<?php
declare(strict_types=1);

namespace Syncro\Services;

class TwoFactorService
{
    /**
     * Generates a random Base32 secret for the user's authenticator app.
     */
    public function generateSecret(int $length = 16): string
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generates the provisioning URL needed to create the QR code.
     */
    public function getQRCodeUrl(string $companyName, string $userEmail, string $secret): string
    {
        $encodedCompany = rawurlencode($companyName);
        $encodedEmail = rawurlencode($userEmail);
        return "otpauth://totp/{$encodedCompany}:{$encodedEmail}?secret={$secret}&issuer={$encodedCompany}";
    }

    /**
     * Verifies the 6-digit code against the user's secret.
     * Includes a 1-step time drift tolerance (±30 seconds).
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->calculateCode($secret, (int)($currentTimeSlice + $i));
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    private function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $timeData = pack('J', $timeSlice);
        $hmac = hash_hmac('sha1', $timeData, $secretKey, true);
        
        $offset = ord($hmac[19]) & 0x0f;
        $hashPart = unpack('N', substr($hmac, $offset, 4));
        $value = $hashPart[1] & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        
        return str_pad((string)($value % $modulo), 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        if (empty($secret)) return '';
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) return '';
        
        $emptyHack = false;
        $secret = str_replace('=', '', $secret);
        $input = str_split($secret);
        $binaryString = '';
        
        foreach ($input as $char) {
            if (!isset($base32charsFlipped[$char])) return '';
            $binaryString .= str_pad(base_convert((string)$base32charsFlipped[$char], 10, 2), 5, '0', STR_PAD_LEFT);
        }
        
        $result = '';
        foreach (str_split($binaryString, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr((int)base_convert($chunk, 2, 10));
            }
        }
        return $result;
    }
}