<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Auth\Authenticate;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Network\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use \OutOfBoundsException;

/**
 * Class SocialAuthenticate. Login the uses by Api Key
 */
class SocialAuthenticate extends BaseAuthenticate
{

    const TYPE_QUERYSTRING = 'querystring';
    const TYPE_HEADER = 'header';

    public $types = [self::TYPE_QUERYSTRING, self::TYPE_HEADER];

    protected $_defaultConfig = [
        //type, can be either querystring or header
        'type' => self::TYPE_QUERYSTRING,
        //name to retrieve the provider value from
        'provider_name' => 'provider',
        //name to retrieve the token value from
        'token_name' => 'token',
        //name to retrieve the token secret value from
        'token_secret_name' => 'token_secret',
        //db table where the key is stored
        'table' => 'CakeDC/Users.SocialAccounts',
        //db table where the key is stored
        'userModel' => 'CakeDC/Users.Users',
        //db field where the provider is stored
        'provider_field' => 'provider',
        //db field where the token is stored
        'token_field' => 'token',
        //db field where the token secret is stored
        'token_secret_field' => 'token_secret',
        //require SSL to pass the token. You should always require SSL to use tokens for Auth
        'require_ssl' => true,
        //finder for social accounts,
        'finder' => 'active'
    ];

    /**
     * Authenticate callback
     * Reads the Api Key based on configuration and login the user
     *
     * @param ServerRequest $request Cake request object.
     * @param Response $response Cake response object.
     * @return mixed
     */
    public function authenticate(ServerRequest $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Stateless Authentication System
     *
     * @param ServerRequest $request Cake request object.
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

        list($provider, $token, $tokenSecret) = $this->$type($request);
        if (empty($provider) || empty($token)) {
            return false;
        }

        if ($this->getConfig('require_ssl') && !$request->is('ssl')) {
            throw new ForbiddenException(__d('CakeDC/Api', 'SSL is required for ApiKey Authentication', $type));
        }

        $socialAccount = $this->_socialQuery($provider, $token, $tokenSecret)->first();

        if (empty($socialAccount)) {
            return false;
        }

        $this->_config['fields']['username'] = 'id';
        $this->_config['finder'] = 'all';

        $result = $this->_query($socialAccount->user_id)->first();
        if (empty($result)) {
            return false;
        }

        return $result->toArray();
    }

    /**
     * Get query object for fetching user from database.
     *
     * @param string $provider provider
     * @param string $token token
     * @param string $tokenSecret secret
     * @return \Cake\ORM\Query
     */
    protected function _socialQuery($provider, $token, $tokenSecret)
    {
        $table = TableRegistry::get($this->getConfig('table'));

        $conditions = [
            $table->aliasField($this->getConfig('provider_field')) => $provider,
            $table->aliasField($this->getConfig('token_field')) => $token,
            $table->aliasField($this->getConfig('token_secret_field')) . ' IS' => $tokenSecret,
        ];
        $query = $table->find($this->getConfig('finder'))->where($conditions);

        return $query;
    }

    /**
     * Get the api key from the querystring
     *
     * @param ServerRequest $request request
     * @return string api key
     */
    public function querystring(ServerRequest $request)
    {
        $providerName = $this->getConfig('provider_name');
        $tokenName = $this->getConfig('token_name');
        $tokenSecret = $this->getConfig('token_secret_name');

        return [$request->getQuery($providerName), $request->getQuery($tokenName), $request->getQuery($tokenSecret)];
    }

    /**
     * Get the api key from the header
     *
     * @param ServerRequest $request request
     * @return string api key
     */
    public function header(ServerRequest $request)
    {
        $providerName = $this->getConfig('provider_name');
        $tokenName = $this->getConfig('token_name');
        $tokenSecret = $this->getConfig('token_secret_name');

        return [Hash::get($request->getHeader($providerName), 0), Hash::get($request->getHeader($tokenName), 0), Hash::get($request->getHeader($tokenSecret), 0)];
    }
}
