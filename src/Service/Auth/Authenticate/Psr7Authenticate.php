<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace CakeDC\Api\Service\Auth\Authenticate;

use Authentication\IdentityInterface;
use Authorization\IdentityDecorator;
use Cake\Datasource\EntityInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;

/**
 * Class FormAuthenticate.
 */
class Psr7Authenticate extends BaseAuthenticate
{

    /**
     * Authenticates the identity contained in a request. Will use the `config.userModel`, and `config.fields`
     * to find POST data that is used to find a matching record in the `config.userModel`. Will return false if
     * there is no post data, either username or password is missing, or if the scope conditions have not been met.
     *
     * @param \Cake\Http\ServerRequest $request The request that contains login information.
     * @param \Cake\Http\Response $response Unused response object.
     * @return mixed False on login failure.  An array of User data on success.
     */
    public function authenticate(ServerRequest $request, $response)
    {
        return $this->getUser($request);
    }

    /**
     * Get a user based on information in the request. Primarily used by stateless authentication
     * systems like basic and digest auth.
     *
     * @param \Cake\Http\ServerRequest $request Request object.
     * @return mixed Either false or an array of user information
     */
    public function getUser(ServerRequest $request)
    {
        $authResult = $request->getAttribute('authentication')->getResult();
        if (!$authResult->isValid()) {
            return false;
        }

        $result = $request->getAttribute('identity');

        if ($result instanceof IdentityInterface || $result instanceof IdentityDecorator) {
            $result = $result->getOriginalData();
        }
        if ($result instanceof EntityInterface) {
            return $result->toArray();
        }

        return $result;
    }
}
