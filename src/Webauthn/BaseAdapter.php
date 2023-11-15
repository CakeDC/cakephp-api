<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2023, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2023, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Webauthn;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use CakeDC\Users\Model\Table\UsersTable;
use CakeDC\Api\Utility\RequestParser;
use CakeDC\Api\Webauthn\Repository\UserCredentialSourceRepository;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\Server;

class BaseAdapter
{
    const STORE_PREFIX = 'api.Webauthn2fa';

    /**
     * @var \Cake\Http\ServerRequest
     */
    protected $request;
    /**
     * @var \CakeDC\Users\Webauthn\Repository\UserCredentialSourceRepository
     */
    protected $repository;
    /**
     * @var \Webauthn\Server
     */
    protected $server;
    /**
     * @var \Cake\Datasource\EntityInterface|\CakeDC\Users\Model\Entity\User
     */
    private $user;
    /**
     * @var \CakeDC\Api\Model\Table\AuthStoreTable
     */
    protected $store;

    /**
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \CakeDC\Users\Model\Table\UsersTable|null $usersTable The users table.
     */
    public function __construct(ServerRequest $request, ?UsersTable $usersTable, $userData)
    {
        $this->request = $request;
        $this->store = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $session = $this->readStore();
        $rpEntity = new PublicKeyCredentialRpEntity(
            Configure::read('Api.Webauthn2fa.' . $this->getDomain() . '.appName'), // The application name
            Configure::read('Api.Webauthn2fa.' . $this->getDomain() . '.id')
        );
        /**
         * @var \Cake\ORM\Entity $userSession
         */
        $userSession = $userData;
        $this->user = $usersTable->get($userSession->id);
        $this->repository = new UserCredentialSourceRepository(
            $request,
            $this->user,
            $usersTable
        );

        $this->server = new Server(
            $rpEntity,
            $this->repository
        );
    }

    /**
     * @return \Webauthn\PublicKeyCredentialUserEntity
     */
    protected function getUserEntity(): PublicKeyCredentialUserEntity
    {
        $user = $this->getUser();

        return new PublicKeyCredentialUserEntity(
            $user->webauthn_username ?? $user->username,
            (string)$user->id,
            (string)$user->first_name
        );
    }

    /**
     * @return array|mixed|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function hasCredential(): bool
    {
        return (bool)$this->repository->findAllForUserEntity(
            $this->getUserEntity()
        );
    }

    public function readStore()
    {
        $entity = $this->store->find()->where(['id' => $this->getStoreKey()])->first();
        if ($entity === null) {
            $entity = $this->store->newEmptyEntity();
            $entity->id = $this->getStoreKey();
        }
        if (empty($entity->store)) {
            $entity->store = [];
        }

        return $entity;
    }

    public function saveStore($data)
    {
        $entity = $this->readStore();
        $entity->store = $data;
        return $this->store->save($entity);
    }

    public function deleteStore()
    {
        $entity = $this->readStore();

        return $this->store->delete($entity);
    }

    public function getStoreKey()
    {
        $authHeader = $this->request->getHeader('Authorization');
        if (is_array($authHeader)) {
            $authHeader = array_pop($authHeader);
        }
        $options = [
            'tokenPrefix' => 'bearer',
        ];

        return str_ireplace($options['tokenPrefix'] . ' ', '', $authHeader);
    }

    public function patchStore($entity, $name, $options)
    {
        $entity['store']['api']['Webauthn2fa'][$this->getDomain()][$name] = $options;

        return $entity;
    }

    public function getStore($entity, $name)
    {
        $path = self::STORE_PREFIX . '.' . $this->getDomain() . '.' . $name;

        return Hash::get($entity['store'], $path, null);
    }

    public function getDomain($replace = true)
    {
        return RequestParser::getDomain($this->request, $replace);
    }
}
