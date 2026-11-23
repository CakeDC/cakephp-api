<?php
declare(strict_types=1);

namespace CakeDC\Api\Webauthn\Repository;

use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use CakeDC\Api\Utility\RequestParser;
use CakeDC\Users\Webauthn\Base64Utility;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class UserCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    private \Cake\Http\ServerRequest $request;

    private \Cake\Datasource\EntityInterface $user;

    private ?\Cake\ORM\Table $usersTable;

    /**
     * @param \Cake\Http\ServerRequest $request The request.
     * @param \Cake\Datasource\EntityInterface $user The user.
     * @param \Cake\ORM\Table|null $usersTable The users table.
     */
    public function __construct(ServerRequest $request, EntityInterface $user, ?Table $usersTable = null)
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
        if ($publicKeyCredentialUserEntity->getId() != $this->user->get('id')) {
            return [];
        }
        $credentials = $this->getUserData($this->user);

        $list = [];
        foreach ($credentials as $credential) {
            $list[] = PublicKeyCredentialSource::createFromArray($credential);
        }

        return $list;
    }

    /**
     * Whether the user has a credential under any domain, not only the current one.
     *
     * @return bool
     */
    public function hasCredentialOnAnyDomain(): bool
    {
        return static::userHasCredentialOnAnyDomain($this->user);
    }

    /**
     * Whether a user record has a credential under any domain.
     *
     * Needs no WebAuthn settings, so it also works for a domain that has none.
     *
     * @param \Cake\Datasource\EntityInterface|array $user User record.
     * @return bool
     */
    public static function userHasCredentialOnAnyDomain(EntityInterface|array $user): bool
    {
        $apiData = Hash::get($user, 'additional_data.api', []);
        if (!is_array($apiData)) {
            return false;
        }
        foreach ($apiData as $domainData) {
            if (is_array($domainData) && !empty($domainData['webauthn_credentials'])) {
                return true;
            }
        }

        return false;
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
        $this->usersTable->saveOrFail($this->user);
    }

    /**
     * Patch user data with webauthn credentials.
     *
     * @param \Cake\Datasource\EntityInterface $entity The user entity
     * @param array $options The webauthn credentials
     * @return \Cake\Datasource\EntityInterface
     */
    public function patchUserData($entity, $options): \Cake\Datasource\EntityInterface
    {
        $additionalData = $entity->get('additional_data');
        if (!is_array($additionalData)) {
            $additionalData = [];
        }
        $apiData = $additionalData['api'] ?? [];
        $apiData[$this->getDomain()] ??= [];
        $apiData[$this->getDomain()]['webauthn_credentials'] = $options;
        $additionalData['api'] = $apiData;
        $entity->set('additional_data', $additionalData);

        return $entity;
    }

    /**
     * Get user data for webauthn credentials.
     *
     * @param \Cake\Datasource\EntityInterface $entity The user entity
     * @return array
     */
    public function getUserData(\ArrayAccess|array $entity): mixed
    {
        $path = 'additional_data.api.' . $this->getDomain() . '.webauthn_credentials';

        return Hash::get($entity, $path, []);
    }

    /**
     * Get the current domain.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return RequestParser::getDomain($this->request);
    }
}
