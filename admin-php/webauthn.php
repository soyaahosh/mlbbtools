<?php
/**
 * Ketupat MLBB Admin Console - Native FIDO2 / WebAuthn Passkey Engine
 * Supports Google Password Manager, Apple iCloud Keychain, Windows Hello, YubiKey
 */

define('PASSKEYS_FILE', __DIR__ . '/passkeys.json');

class WebAuthnSimpleCBOR {
    private $data;
    private $offset = 0;

    public static function decode($bytes) {
        $decoder = new self($bytes);
        return $decoder->parse();
    }

    public function __construct($data) {
        $this->data = $data;
        $this->offset = 0;
    }

    public function parse() {
        if ($this->offset >= strlen($this->data)) return null;

        $byte = ord($this->data[$this->offset++]);
        $major = $byte >> 5;
        $additional = $byte & 0x1f;

        $val = $this->readLength($additional);

        switch ($major) {
            case 0: // Unsigned int
                return $val;
            case 1: // Negative int (-1 - val)
                return -1 - $val;
            case 2: // Byte string
                $str = substr($this->data, $this->offset, $val);
                $this->offset += $val;
                return $str;
            case 3: // Text string
                $str = substr($this->data, $this->offset, $val);
                $this->offset += $val;
                return $str;
            case 4: // Array
                $arr = [];
                for ($i = 0; $i < $val; $i++) {
                    $arr[] = $this->parse();
                }
                return $arr;
            case 5: // Map
                $map = [];
                for ($i = 0; $i < $val; $i++) {
                    $k = $this->parse();
                    $v = $this->parse();
                    $map[$k] = $v;
                }
                return $map;
            default:
                return null;
        }
    }

    private function readLength($additional) {
        if ($additional < 24) return $additional;
        if ($additional === 24) {
            return ord($this->data[$this->offset++]);
        }
        if ($additional === 25) {
            $val = unpack('n', substr($this->data, $this->offset, 2))[1];
            $this->offset += 2;
            return $val;
        }
        if ($additional === 26) {
            $val = unpack('N', substr($this->data, $this->offset, 4))[1];
            $this->offset += 4;
            return $val;
        }
        if ($additional === 27) {
            $val = unpack('J', substr($this->data, $this->offset, 8))[1];
            $this->offset += 8;
            return $val;
        }
        return 0;
    }
}

class WebAuthnEngine {

    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4 ? strlen($data) + (4 - strlen($data) % 4) : strlen($data), '=', STR_PAD_RIGHT));
    }

    public static function generateChallenge() {
        return self::base64url_encode(random_bytes(32));
    }

    public static function getPasskeys() {
        if (!file_exists(PASSKEYS_FILE)) {
            return [];
        }
        $raw = file_get_contents(PASSKEYS_FILE);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function savePasskey($credId, $pubKeyPem, $name = '', $signCount = 0) {
        $keys = self::getPasskeys();
        // Check if already exists, update if so
        $found = false;
        foreach ($keys as &$k) {
            if ($k['id'] === $credId) {
                $k['publicKey'] = $pubKeyPem;
                $k['signCount'] = $signCount;
                if (!empty($name)) $k['name'] = $name;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $keys[] = [
                'id' => $credId,
                'publicKey' => $pubKeyPem,
                'name' => !empty($name) ? $name : ('Passkey ' . date('M j, Y H:i')),
                'created_at' => date('Y-m-d H:i:s'),
                'signCount' => $signCount
            ];
        }
        return file_put_contents(PASSKEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    public static function deletePasskey($credId) {
        $keys = self::getPasskeys();
        $filtered = array_filter($keys, function($k) use ($credId) {
            return $k['id'] !== $credId;
        });
        return file_put_contents(PASSKEYS_FILE, json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
    }

    public static function coseToPem($coseBytes) {
        $cose = is_array($coseBytes) ? $coseBytes : WebAuthnSimpleCBOR::decode($coseBytes);
        if (!is_array($cose)) return null;

        $kty = $cose[1] ?? null;

        // EC2 key (kty = 2, alg = -7 ES256)
        if ($kty === 2) {
            $x = $cose[-2] ?? null;
            $y = $cose[-3] ?? null;
            if (!$x || !$y || strlen($x) !== 32 || strlen($y) !== 32) return null;

            $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d03010703420004') . $x . $y;
            return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
        }

        // RSA key (kty = 3, alg = -257 RS256)
        if ($kty === 3) {
            $n = $cose[-1] ?? null;
            $e = $cose[-2] ?? null;
            if (!$n || !$e) return null;

            $encodeInt = function($bytes) {
                if (ord($bytes[0]) > 0x7f) $bytes = "\x00" . $bytes;
                $len = strlen($bytes);
                $lenBytes = ($len < 128) ? chr($len) : ("\x82" . pack('n', $len));
                return "\x02" . $lenBytes . $bytes;
            };

            $seq = $encodeInt($n) . $encodeInt($e);
            $seqLen = strlen($seq);
            $seqDer = "\x30" . ($seqLen < 128 ? chr($seqLen) : ("\x82" . pack('n', $seqLen))) . $seq;

            $bitString = "\x03" . (strlen($seqDer) + 1 < 128 ? chr(strlen($seqDer) + 1) : ("\x82" . pack('n', strlen($seqDer) + 1))) . "\x00" . $seqDer;
            $rsaOid = hex2bin('300d06092a864886f70d0101010500');
            $spki = $rsaOid . $bitString;
            $spkiLen = strlen($spki);
            $fullDer = "\x30" . ($spkiLen < 128 ? chr($spkiLen) : ("\x82" . pack('n', $spkiLen))) . $spki;

            return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($fullDer), 64, "\n") . "-----END PUBLIC KEY-----\n";
        }

        return null;
    }

    public static function verifyRegistration($clientDataJSON_b64, $attestationObject_b64, $expectedChallenge) {
        $clientDataJSON = self::base64url_decode($clientDataJSON_b64);
        $clientData = json_decode($clientDataJSON, true);
        if (!is_array($clientData)) {
            throw new Exception('Invalid clientDataJSON format');
        }

        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new Exception('Invalid clientData type: expected webauthn.create');
        }

        if (($clientData['challenge'] ?? '') !== $expectedChallenge) {
            throw new Exception('Challenge mismatch during registration');
        }

        $attestationBytes = self::base64url_decode($attestationObject_b64);
        $attestation = WebAuthnSimpleCBOR::decode($attestationBytes);
        if (!is_array($attestation) || !isset($attestation['authData'])) {
            throw new Exception('Invalid attestationObject CBOR format');
        }

        $authData = $attestation['authData'];
        if (strlen($authData) < 55) {
            throw new Exception('authData too short');
        }

        $flags = ord($authData[32]);
        if (($flags & 0x40) === 0) {
            throw new Exception('Attested credential data flag not present');
        }

        $signCount = unpack('N', substr($authData, 33, 4))[1];
        $credIdLen = unpack('n', substr($authData, 53, 2))[1];
        if (strlen($authData) < 55 + $credIdLen) {
            throw new Exception('Credential ID length exceeds authData');
        }

        $credId = substr($authData, 55, $credIdLen);
        $coseBytes = substr($authData, 55 + $credIdLen);

        $cose = WebAuthnSimpleCBOR::decode($coseBytes);
        $pem = self::coseToPem($cose);
        if (!$pem) {
            throw new Exception('Failed converting COSE public key to PEM format');
        }

        return [
            'credentialId' => self::base64url_encode($credId),
            'publicKey'    => $pem,
            'signCount'    => $signCount
        ];
    }

    public static function verifyAuthentication($credentialId, $clientDataJSON_b64, $authenticatorData_b64, $signature_b64, $expectedChallenge) {
        $passkeys = self::getPasskeys();
        $targetPasskey = null;
        foreach ($passkeys as $pk) {
            if ($pk['id'] === $credentialId) {
                $targetPasskey = $pk;
                break;
            }
        }

        if (!$targetPasskey) {
            throw new Exception('Unrecognized Passkey: ID not found in server registry');
        }

        $clientDataJSON = self::base64url_decode($clientDataJSON_b64);
        $clientData = json_decode($clientDataJSON, true);
        if (!is_array($clientData)) {
            throw new Exception('Invalid clientDataJSON');
        }

        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new Exception('Invalid assertion type: expected webauthn.get');
        }

        if (($clientData['challenge'] ?? '') !== $expectedChallenge) {
            throw new Exception('Challenge mismatch during assertion');
        }

        $authData = self::base64url_decode($authenticatorData_b64);
        $signature = self::base64url_decode($signature_b64);

        $clientDataHash = hash('sha256', $clientDataJSON, true);
        $signedData = $authData . $clientDataHash;

        $isValid = openssl_verify($signedData, $signature, $targetPasskey['publicKey'], OPENSSL_ALGO_SHA256);
        if ($isValid !== 1) {
            throw new Exception('Cryptographic signature verification failed');
        }

        // Update sign count
        if (strlen($authData) >= 37) {
            $newSignCount = unpack('N', substr($authData, 33, 4))[1];
            self::savePasskey($credentialId, $targetPasskey['publicKey'], $targetPasskey['name'] ?? '', $newSignCount);
        }

        return true;
    }
}
