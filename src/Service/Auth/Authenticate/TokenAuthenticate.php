<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Auth\Authenticate;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use OutOfBoundsException;

/**
 * Class TokenAuthenticate. Login the uses by Api Key
 */
class TokenAuthenticate extends BaseAuthenticate
{
    public const TYPE_QUERYSTRING = 'querystring';
    public const TYPE_HEADER = 'header';

    public array $types = [self::TYPE_QUERYSTRING, self::TYPE_HEADER];

    protected $_defaultConfig = [
        //type, can be either querystring or header
        'type' => self::TYPE_QUERYSTRING,
        //name to retrieve the api key value from
        'name' => 'token',
        //db table where the key is stored
        'table' => 'users',
        //db field where the key is stored
        'field' => 'api_token',
        //require SSL to pass the token. You should always require SSL to use tokens for Auth
        'require_ssl' => true,
    ];

    /**
     * Authenticate callback
     * Reads the Api Key based on configuration and login the user
     *
     * @param \Cake\Http\ServerRequest $request Cake request object.
     * @param \Cake\Http\Response $response Cake response object.
     * @return mixed
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Stateless Authentication System
     *
     * @param \Cake\Http\ServerRequest $request Cake request object.
     * @return mixed
     */
    public function getUser(ServerRequest $request)
    {
        $type = $this->getConfig('type');
        if (!in_array($type, $this->types)) {
            throw new OutOfBoundsException(__d('CakeDC/Api', 'Type {0} is not valid', $type));
        }

        if (!is_callable([$this, $type])) {
            throw new OutOfBoundsException(__d('CakeDC/Api', 'Type {0} has no associated callable', $type));
        }

        $apiKey = $this->$type($request);
        if (empty($apiKey)) {
            return false;
        }

        if ($this->getConfig('require_ssl') && !$request->is('ssl')) {
            throw new ForbiddenException(__d('CakeDC/Api', 'SSL is required for ApiKey Authentication', $type));
        }

        $this->_config['fields']['username'] = $this->getConfig('field');
        $this->_config['userModel'] = $this->getConfig('table');
        $this->_config['finder'] = $this->getConfig('finderAuth') ?: 'all';
        $result = $this->_query($apiKey)->first();

        if (empty($result)) {
            return false;
        }

        return $result->toArray();
        //idea: add array with checks to be passed to $request->is(...)
    }

    /**
     *  Get the api key from the querystring
     *
     * @param \Cake\Http\ServerRequest $request request
     * @return array|null|string api key
     */
    public function querystring(ServerRequest $request)
    {
        $name = $this->getConfig('name');

        return $request->getQuery($name);
    }

    /**
     * Get the api key from the header
     *
     * @param \Cake\Http\ServerRequest $request request
     * @return string api key
     */
    public function header(ServerRequest $request)
    {
        $name = $this->getConfig('name');

        return Hash::get($request->getHeader($name), 0);
    }
}
