<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Auth;

use Authentication\Authenticator\JwtAuthenticator;
use Authentication\Identifier\JwtSubjectIdentifier;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;

/**
 * Class RefreshAction
 *
 * @package CakeDC\Api\Service\Action
 */
class JwtRefreshAction extends Action
{
    use JwtTokenTrait;

    /**
     * @var array $user
     */
    protected $user;

    /**
     * Initialize an action instance
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        $authHeader = $this->getService()->getRequest()->getHeader('Authorization');
        if (is_array($authHeader)) {
            $authHeader = array_pop($authHeader);
        }

        $identifier = new JwtSubjectIdentifier();
        $options = [
            'header' => 'Authorization',
            'queryParam' => 'token',
            'tokenPrefix' => 'bearer',
            'algorithms' => ['HS256', 'HS512'],
            'returnPayload' => false,
            'secretKey' => Configure::read('Api.Jwt.RefreshToken.secret'),
        ];
        $auth = new JwtAuthenticator($identifier, $options);
        $result = $auth->authenticate($this->getService()->getRequest());

        if (!$result->isValid()) {
            throw new ValidationException('Invalid token provided', 401);
        }
        $this->user = $result->getData();
        if ($this->user instanceof EntityInterface) {
            $this->user = $this->user->toArray();
        }

        $modelAlias = Configure::read('Users.table');
        $UsersTable = TableRegistry::getTableLocator()->get($modelAlias);
        $model = $UsersTable->getAlias();

        $table = TableRegistry::getTableLocator()->get('CakeDC/Api.JwtRefreshTokens');
        $entity = $table->find()
            ->where([
                'model' => $model,
                'foreign_key' => $this->user['id'],
            ])->first();

        if (!$entity || $entity['token'] != $authHeader) {
            throw new ValidationException('Invalid token provided', 401);
        }

        $payload = $auth->getPayload();

        return true;
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        if (empty($this->user)) {
            return false;
        }

        return $this->generateTokenResponse($this->user);
    }
}
