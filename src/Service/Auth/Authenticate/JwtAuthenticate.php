<?php
declare(strict_types=1);

namespace CakeDC\Api\Service\Auth\Authenticate;

use Cake\Core\Configure;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use Cake\Utility\Security;
use CakeDC\Api\Service\Action\Action;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * An authentication adapter for authenticating using JSON Web Tokens.
 *
 * ```
 *  $config[
 *      'CakeDC/Api.Jwt' => [
 *          'header' => 'authorization',
 *          'prefix' => 'bearer',
 *          'parameter' => 'token',
 *          'userModel' => 'Users',
 *          'fields' => [
 *              'username' => 'id'
 *          ],
 *      ]
 *  ]);
 * ```
 *
 * @copyright 2015-2018 ADmad
 * @license MIT
 *
 * This Auth adapter was modified and adapted for this App
 * @see http://jwt.io
 * @see http://tools.ietf.org/html/draft-ietf-oauth-json-web-token
 */
class JwtAuthenticate extends BaseAuthenticate
{
    /**
     * Parsed token.
     */
    protected ?string $_token = null;

    /**
     * Payload data.
     */
    protected ?object $_payload = null;

    /**
     * Exception.
     */
    protected ?\Exception $_error = null;

    /**
     * Constructor.
     *
     * Settings for this object.
     *
     * - `header` - Header name to check. Defaults to `'authorization'`.
     * - `prefix` - Token prefix. Defaults to `'bearer'`.
     * - `parameter` - The url parameter name of the token. Defaults to `token`.
     *   First $_SERVER['HTTP_AUTHORIZATION'] is checked for token value.
     *   Its value should be of form "Bearer <token>". If empty this query string
     *   paramater is checked.
     * - `allowedAlgs` - List of supported verification algorithms.
     *   Defaults to ['HS512']. See API of JWT::decode() for more info.
     * - `queryDatasource` - Boolean indicating whether the `sub` claim of JWT
     *   token should be used to query the user model and get user record. If
     *   set to `false` JWT's payload is directly retured. Defaults to `true`.
     * - `userModel` - The model name of users, defaults to `Users`.
     * - `fields` - Key `username` denotes the identifier field for fetching user
     *   record. The `sub` claim of JWT must contain identifier value.
     *   Defaults to ['username' => 'id'].
     * - `finder` - Finder method.
     * - `unauthenticatedException` - Fully namespaced exception name. Exception to
     *   throw if authentication fails. Set to false to do nothing.
     *   Defaults to '\Cake\Http\Exception\UnauthorizedException'.
     * - `key` - The key, or map of keys used to decode JWT. If not set, value
     *   of Security::salt() will be used.
     *
     * @param \CakeDC\Api\Service\Action\Action $action AbstractClass with implementations
     * used on this request.
     * @param array $config Array of config to use.
     */
    public function __construct(Action $action, array $config = [])
    {
        $config = Hash::merge([
            'header' => 'authorization',
            'prefix' => 'bearer',
            'parameter' => 'token',
            'queryDatasource' => true,
            'fields' => [
                'username' => 'id',
            ],
            'unauthenticatedException' => UnauthorizedException::class,
            'key' => null,
        ], $config);

        $this->setConfig($config);

        if (empty($config['allowedAlgs'])) {
            $config['allowedAlgs'] = 'HS512';
        }

        parent::__construct($action, $config);
    }

    /**
     * Get user record based on info available in JWT.
     *
     * @param \Cake\Http\ServerRequest $request The request object.
     * @param \Cake\Http\Response $response Response object.
     * @throws \Exception
     * @return bool|array User record array or false on failure.
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Get user record based on info available in JWT.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @throws \Exception
     * @return bool|array User record array or false on failure.
     */
    public function getUser(ServerRequest $request)
    {
        $payload = $this->getPayload($request);

        if (empty($payload)) {
            return false;
        }

        if (!$this->_config['queryDatasource']) {
            return json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        if (!(property_exists($payload, 'sub') && $payload->sub !== null)) {
            return false;
        }

        $user = $this->_findUser($payload->sub);
        if (!$user) {
            return false;
        }

        unset($user[$this->_config['fields']['password']]);

        return $user;
    }

    /**
     * Get payload data.
     *
     * @param \Cake\Http\ServerRequest|null $request Request instance or null
     * @throws \Exception
     * @return object|null Payload object on success, null on failures.
     */
    public function getPayload($request = null)
    {
        if ($request === null) {
            return $this->_payload;
        }

        $payload = null;

        $token = $this->getToken($request);
        if ($token) {
            $payload = $this->_decode($token);
        }

        return $this->_payload = $payload;
    }

    /**
     * Get token from header or query string.
     *
     * @param \Cake\Http\ServerRequest|null $request Request object.
     * @return string|null Token string if found else null.
     */
    public function getToken($request = null)
    {
        $config = $this->_config;

        if ($request === null) {
            return $this->_token;
        }

        $header = $request->getHeaderLine($config['header']);
        if ($header && stripos($header, (string)$config['prefix']) === 0) {
            return $this->_token = str_ireplace($config['prefix'] . ' ', '', $header);
        }

        if (!empty($this->_config['parameter'])) {
            $token = $request->getQuery($this->_config['parameter']);
            if ($token !== null) {
                $token = (string)$token;
            }

            return $this->_token = $token;
        }

        return $this->_token;
    }

    /**
     * Decode JWT token.
     *
     * @param string $token JWT token to decode.
     * @throws \Exception
     * @return object|null The JWT's payload as a PHP object, null on failure.
     */
    protected function _decode(string $token)
    {
        $config = $this->_config;
        try {
            return JWT::decode(
                $token,
                new Key($config['key'] ?: Security::getSalt(), $config['allowedAlgs'])
            );
        } catch (Exception $e) {
            if (Configure::read('debug')) {
                throw $e;
            }
            $this->_error = $e;
        }

        return null;
    }

    /**
     * Handles an unauthenticated access attempt. Depending on value of config
     * `unauthenticatedException` either throws the specified exception or returns
     * null.
     *
     * @param \Cake\Http\ServerRequest $request A request object.
     * @param \Cake\Http\Response $response A response object.
     * @throws \Cake\Http\Exception\UnauthorizedException Or any other
     *   configured exception.
     * @return void
     */
    public function unauthenticated(ServerRequest $request, Response $response): void
    {
        if (!$this->_config['unauthenticatedException']) {
            return;
        }

        $message = $this->_error !== null
            ? $this->_error->getMessage()
            : $this->_action->Auth->getConfig('authError');

        $exception = new $this->_config['unauthenticatedException']($message);
        throw $exception;
    }
}
