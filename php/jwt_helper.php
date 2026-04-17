<?php
if (!class_exists('JWT')) {
    class JWT {
        // Configurable secret - CHANGE THIS IN PRODUCTION
        private static $secret_key = 'HopeFinderAuth2025!SecureKeyX7Kp9mZqW2vR8tY3uL5jN1oP4sB6';

        public static function setSecret($key) {
            self::$secret_key = $key;
        }

        public static function encode($payload) {
            $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
            $header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

            $payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

            $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, self::$secret_key, true);
            $signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
        }

        public static function decode($token) {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $header = $parts[0];
            $payload = $parts[1];
            $signature = $parts[2];

            $expected_signature = hash_hmac('sha256', $header . "." . $payload, self::$secret_key, true);
            $expected_signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expected_signature));

            if (!hash_equals($signature, $expected_signature_encoded)) {
                return false;
            }

            $payload_decoded = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            if ($payload_decoded === null) {
                return false;
            }
            
            // Validate expiration
            if (isset($payload_decoded['exp']) && $payload_decoded['exp'] < time()) {
                return false;
            }
            
            // Validate issued at (optional - not too far in past)
            if (isset($payload_decoded['iat']) && $payload_decoded['iat'] < (time() - 3600 * 24 * 7)) { // 7 days
                return false;
            }
            
            return $payload_decoded;
        }

        public static function generateToken() {
            return bin2hex(random_bytes(32));
        }
    }
}
?>

