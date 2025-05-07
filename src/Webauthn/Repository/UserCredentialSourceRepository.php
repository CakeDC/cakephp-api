<?php
declare(strict_types=1);

namespace CakeDC\Api\Webauthn\Repository;

use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use CakeDC\Api\Utility\RequestParser;
use CakeDC\Users\Model\Table\UsersTable;
use CakeDC\Users\Webauthn\Base64Utility;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class UserCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * @var \Cake\Http\ServerRequest
     */
    private $request;
    /**
     * @var \Cake\Datasource\EntityInterface
     */
    private $user;
    /**
     * @var \CakeDC\Users\Model\Table\UsersTable|null
     */
    private $usersTable;

    /**
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Cake\Datasource\EntityInterface $user The user.
     * @param \CakeDC\Users\Model\Table\UsersTable|null $usersTable The users table.
     */
    public function __construct(ServerRequest $request, EntityInterface $user, ?UsersTable $usersTable = null)
    {
        $this->request = $request;
        $this->user = $user;
        $this->usersTable = $usersTable;
    }

    /**
     * @param string $publicKeyCredentialId  Public key credential id
     * @return \Webauthn\PublicKeyCredentialSource|null
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $encodedId = Base64Utility::basicEncode($publicKeyCredentialId);
        $credentials = $this->getUserData($this->user);
        $credential = $credentials[$encodedId] ?? null;

        return $credential
            ? PublicKeyCredentialSource::createFromArray($credential)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        if ($publicKeyCredentialUserEntity->getId() != $this->user->id) {
            return [];
        }
        \Cake\Log\Log::error(print_r($this->user, true));
        $credentials = $this->getUserData($this->user);
        \Cake\Log\Log::error(print_r($credentials, true));

        $list = [];
        foreach ($credentials as $credential) {
            $list[] = PublicKeyCredentialSource::createFromArray($credential);
        }

        return $list;
    }

    /**
     * @param \Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource Public key credential source
     * @return void
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $credentials = $this->getUserData($this->user);
        $id = Base64Utility::basicEncode($publicKeyCredentialSource->getPublicKeyCredentialId());
        $credentials[$id] = json_decode(json_encode($publicKeyCredentialSource), true);
        $this->patchUserData($this->user, $credentials);
        $res = $this->usersTable->saveOrFail($this->user);
    }

    /**
     * Patch user data with webauthn credentials.
     *
     * @param \Cake\Datasource\EntityInterface $entity The user entity
     * @param array $options The webauthn credentials
     * @return \Cake\Datasource\EntityInterface
     */
    public function patchUserData($entity, $options)
    {
        $entity['additional_data'] = $entity['additional_data'] ?? [];
        $apiData = $entity['additional_data']['api'] ?? [];
        $apiData[$this->getDomain()] = $apiData[$this->getDomain()] ?? [];
        $apiData[$this->getDomain()]['webauthn_credentials'] = $options;
        $entity['additional_data']['api'] = $apiData;

        return $entity;
    }

    /**
     * Get user data for webauthn credentials.
     *
     * @param \Cake\Datasource\EntityInterface $entity The user entity
     * @return array
     */
    public function getUserData($entity)
    {
        $path = 'additional_data.api.' . $this->getDomain() . '.webauthn_credentials';

        return Hash::get($entity, $path, []);
    }

    /**
     * Get the current domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return RequestParser::getDomain($this->request);
    }
}
