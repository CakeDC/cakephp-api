<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action\Auth;

use CakeDC\Api\Service\ServiceRegistry;
use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;

/**
 * A software WebAuthn authenticator and JWT helpers for the 2FA integration tests.
 * Registers ES256 credentials with a `none` attestation and signs assertions.
 *
 * The using class defines `RP_ID` and `ORIGIN`, the relying party id and origin of the tests.
 */
trait SoftwareAuthenticatorTrait
{
    /**
     * @param string $data Binary data.
     * @return string
     */
    protected static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodes the claims of a JWT without verifying it.
     *
     * @param string $token JWT.
     * @return array<string, mixed>
     */
    protected function jwtPayload(string $token): array
    {
        $parts = explode('.', $token);

        return json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Sends a request with a JWT and returns the decoded JSend body.
     *
     * @param string $url Url.
     * @param string $method HTTP method.
     * @param array $data Request data.
     * @param string $token JWT.
     * @param string|null $referer Referer header, which selects the WebAuthn domain.
     * @return array
     */
    protected function withToken(string $url, string $method, array $data, string $token, ?string $referer = null): array
    {
        ServiceRegistry::getServiceLocator()->clear();
        $headers = ['Authorization' => 'Bearer ' . $token];
        if ($referer !== null) {
            $headers['Referer'] = $referer;
        }
        $this->sendRequest($url, $method, $data, null, $headers);

        return (array)json_decode((string)$this->_response->getBody(), true);
    }

    /**
     * Logs user-1 in with the password and returns the token response data.
     *
     * @return array
     */
    protected function login(): array
    {
        ServiceRegistry::getServiceLocator()->clear();
        $this->sendRequest('/auth/jwt_login', 'POST', ['username' => 'user-1', 'password' => '12345']);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        return $result['data'];
    }

    /**
     * @return resource|\OpenSSLAsymmetricKey A new P-256 key pair, the software authenticator's credential key.
     */
    protected function newKey()
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $this->assertNotFalse($key);

        return $key;
    }

    /**
     * Builds the registration response a browser sends after navigator.credentials.create().
     *
     * @param resource|\OpenSSLAsymmetricKey $key Credential key.
     * @param string $credentialId Credential id.
     * @param string $challenge Base64url challenge from the creation options.
     * @param string $origin Origin the browser reports.
     * @return array
     */
    protected function attestation($key, string $credentialId, string $challenge, string $origin = self::ORIGIN): array
    {
        $ec = openssl_pkey_get_details($key)['ec'];
        $coseKey = MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2)) // kty: EC2
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7)) // alg: ES256
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1)) // crv: P-256
            ->add(NegativeIntegerObject::create(-2), ByteStringObject::create(str_pad($ec['x'], 32, "\0", STR_PAD_LEFT)))
            ->add(NegativeIntegerObject::create(-3), ByteStringObject::create(str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)));
        $authData = hash('sha256', self::RP_ID, true)
            . chr(0x45) // user present, user verified, attested credential data
            . pack('N', 0)
            . str_repeat("\0", 16) // AAGUID
            . pack('n', strlen($credentialId)) . $credentialId
            . $coseKey;
        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));
        $clientData = json_encode(['type' => 'webauthn.create', 'challenge' => $challenge, 'origin' => $origin], JSON_THROW_ON_ERROR);

        return [
            'id' => static::b64url($credentialId),
            'rawId' => base64_encode($credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => static::b64url($clientData),
                'attestationObject' => static::b64url((string)$attestationObject),
            ],
        ];
    }

    /**
     * Builds the authentication response a browser sends after navigator.credentials.get().
     *
     * @param resource|\OpenSSLAsymmetricKey $key Key that signs the assertion.
     * @param string $credentialId Credential id.
     * @param string $challenge Base64url challenge from the request options.
     * @param int $signCount Signature counter.
     * @return array
     */
    protected function assertion($key, string $credentialId, string $challenge, int $signCount = 1): array
    {
        $authData = hash('sha256', self::RP_ID, true) . chr(0x05) . pack('N', $signCount); // user present, user verified
        $clientData = json_encode(['type' => 'webauthn.get', 'challenge' => $challenge, 'origin' => self::ORIGIN], JSON_THROW_ON_ERROR);
        $this->assertTrue(openssl_sign($authData . hash('sha256', $clientData, true), $signature, $key, OPENSSL_ALGO_SHA256));

        return [
            'id' => static::b64url($credentialId),
            'rawId' => base64_encode($credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => static::b64url($clientData),
                'authenticatorData' => static::b64url($authData),
                'signature' => base64_encode($signature),
            ],
        ];
    }

    /**
     * Logs in and registers a new credential.
     *
     * @return array{0: array, 1: resource|\OpenSSLAsymmetricKey, 2: string} Login data, key and credential id.
     */
    protected function loginAndRegister(): array
    {
        $login = $this->login();
        $key = $this->newKey();
        $credentialId = random_bytes(16);

        $options = $this->withToken('/auth/webauthn2fa_register_options', 'GET', [], $login['access_token']);
        $this->assertSame('success', $options['status'], (string)$this->_response->getBody());
        $register = $this->withToken(
            '/auth/webauthn2fa_register',
            'POST',
            $this->attestation($key, $credentialId, $options['data']['challenge']),
            $login['access_token'],
        );
        $this->assertSame('success', $register['status'], (string)$this->_response->getBody());

        return [$login, $key, $credentialId];
    }
}
