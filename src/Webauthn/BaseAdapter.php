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
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use CakeDC\Api\Model\Entity\AuthStore;
use CakeDC\Api\Utility\RequestParser;
use CakeDC\Api\Webauthn\Repository\UserCredentialSourceRepository;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class BaseAdapter
{
    public const STORE_PREFIX = 'api.Webauthn2fa';

    protected \Cake\Http\ServerRequest $request;

    protected \CakeDC\Api\Webauthn\Repository\UserCredentialSourceRepository $repository;

    private \Cake\Datasource\EntityInterface $user;

    /**
     * @var \Webauthn\PublicKeyCredentialRpEntity
     */
    protected PublicKeyCredentialRpEntity $rpEntity;

    /**
     * @var \CakeDC\Api\Model\Table\AuthStoreTable
     */
    protected $store;

    /**
     * @var \Webauthn\AttestationStatement\AttestationStatementSupportManager|null
     */
    protected ?AttestationStatementSupportManager $attestationStatementSupportManager = null;

    /**
     * @var \Cose\Algorithm\Manager|null
     */
    protected ?Manager $algorithmManager = null;

    /**
     * Constructor.
     *
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Cake\ORM\Table|null $usersTable The users table.
     * @param \Cake\Datasource\EntityInterface|\CakeDC\Users\Model\Entity\User $userData The user data.
     */
    public function __construct(ServerRequest $request, ?Table $usersTable, $userData)
    {
        $this->request = $request;
        /** @var \CakeDC\Api\Model\Table\AuthStoreTable $store */
        $store = TableRegistry::getTableLocator()->get('CakeDC/Api.AuthStore');
        $this->store = $store;
        $this->readStore();
        $this->rpEntity = new PublicKeyCredentialRpEntity(
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
    }

    /**
     * Get the user entity.
     *
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
     * Get the user.
     *
     * @return mixed|array|null
     */
    public function getUser(): mixed
    {
        return $this->user;
    }

    /**
     * Check if the user has a credential.
     *
     * @return bool
     */
    public function hasCredential(): bool
    {
        return (bool)$this->repository->findAllForUserEntity(
            $this->getUserEntity()
        );
    }

    /**
     * Read the store data.
     *
     * @return \CakeDC\Api\Model\Entity\AuthStore
     */
    public function readStore(): \CakeDC\Api\Model\Entity\AuthStore
    {
        /** @var \CakeDC\Api\Model\Entity\AuthStore|null $entity */
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

    /**
     * Save the store data.
     *
     * @param array $data The data to save.
     * @return \CakeDC\Api\Model\Entity\AuthStore|false
     */
    public function saveStore($data): \CakeDC\Api\Model\Entity\AuthStore|false
    {
        $entity = $this->readStore();
        $entity->store = $data;

        return $this->store->save($entity);
    }

    /**
     * Delete the store data.
     *
     * @return bool
     */
    public function deleteStore(): bool
    {
        $entity = $this->readStore();

        return $this->store->delete($entity);
    }

    /**
     * Get the store key.
     *
     * @return string
     */
    public function getStoreKey(): string
    {
        $authHeader = $this->request->getHeader('Authorization');
        $authHeader = array_pop($authHeader);

        $options = [
            'tokenPrefix' => 'bearer',
        ];

        return str_ireplace($options['tokenPrefix'] . ' ', '', $authHeader);
    }

    /**
     * Patch the store data.
     *
     * @param \CakeDC\Api\Model\Entity\AuthStore $entity The entity to patch.
     * @param string $name The name of the data.
     * @param string|array $options The options to patch.
     * @return \CakeDC\Api\Model\Entity\AuthStore
     */
    public function patchStore($entity, $name, $options): \CakeDC\Api\Model\Entity\AuthStore
    {
        $entity->store['api']['Webauthn2fa'][$this->getDomain()][$name] = $options;

        return $entity;
    }

    /**
     * Get the store data.
     *
     * @param \CakeDC\Api\Model\Entity\AuthStore $entity The entity to get data from.
     * @param string $name The name of the data to get.
     * @return mixed|null
     */
    public function getStore(AuthStore $entity, $name): mixed
    {
        $path = self::STORE_PREFIX . '.' . $this->getDomain() . '.' . $name;

        return Hash::get($entity->store ?? [], $path);
    }

    /**
     * Get the current domain.
     *
     * @param bool $replace Whether to replace the domain.
     * @return string
     */
    public function getDomain($replace = true): string
    {
        return RequestParser::getDomain($this->request, $replace);
    }

    /**
     * @param \Webauthn\AttestationStatement\AttestationStatementSupportManager $attestationStatementSupportManager manager instance
     * @return void
     */
    public function setAttestationStatementSupportManager(
        AttestationStatementSupportManager $attestationStatementSupportManager
    ): void {
        $this->attestationStatementSupportManager = $attestationStatementSupportManager;
    }

    /**
     * @return \Webauthn\AttestationStatement\AttestationStatementSupportManager
     */
    protected function getAttestationStatementSupportManager(): AttestationStatementSupportManager
    {
        if (!$this->attestationStatementSupportManager instanceof \Webauthn\AttestationStatement\AttestationStatementSupportManager) {
            $this->attestationStatementSupportManager = new AttestationStatementSupportManager();
            $this->attestationStatementSupportManager
                ->add(new NoneAttestationStatementSupport());
        }

        return $this->attestationStatementSupportManager;
    }

    /**
     * @return \CakeDC\Api\Webauthn\PublicKeyCredentialLoader
     */
    protected function createPublicKeyCredentialLoader(): PublicKeyCredentialLoader
    {
        $attestationObjectLoader = new AttestationObjectLoader(
            $this->getAttestationStatementSupportManager()
        );

        return new PublicKeyCredentialLoader(
            $attestationObjectLoader
        );
    }

    /**
     * @return \Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler
     */
    protected function createExtensionOutputCheckerHandler(): ExtensionOutputCheckerHandler
    {
        return new ExtensionOutputCheckerHandler();
    }

    /**
     * @return \Cose\Algorithm\Manager
     */
    protected function getAlgorithmManager(): Manager
    {
        if (!$this->algorithmManager instanceof \Cose\Algorithm\Manager) {
            $this->algorithmManager = Manager::create()->add(
                ES256::create(),
                ES256K::create(),
                ES384::create(),
                ES512::create(),
                RS256::create(),
                RS384::create(),
                RS512::create(),
                PS256::create(),
                PS384::create(),
                PS512::create(),
                Ed256::create(),
                Ed512::create(),
            );
        }

        return $this->algorithmManager;
    }
}
