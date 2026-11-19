<?php
declare(strict_types=1);

namespace CakeDC\Api\Webauthn\Repository;

use ArrayAccess;
use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\Utility\Hash;
use CakeDC\Api\Utility\RequestParser;
use CakeDC\Users\Webauthn\Base64Utility;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialUserEntity;

class UserCredentialSourceRepository
{
    private ServerRequest $request;

    private EntityInterface $user;

    private ?Table $usersTable;

    private ?SerializerInterface $serializer = null;

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
     * @param string $publicKeyCredentialId Public key credential id
     * @return \Webauthn\CredentialRecord|null
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        $encodedId = Base64Utility::basicEncode($publicKeyCredentialId);
        $credentials = $this->getUserData($this->user);
        $credential = $credentials[$encodedId] ?? null;

        return $credential
            ? $this->createRecord($credential)
            : null;
    }

    /**
     * @param \Webauthn\PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity User entity.
     * @return array<\Webauthn\CredentialRecord>
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        if ($publicKeyCredentialUserEntity->id != $this->user->get('id')) {
            return [];
        }
        $credentials = $this->getUserData($this->user);

        $list = [];
        foreach ($credentials as $credential) {
            $list[] = $this->createRecord($credential);
        }

        return $list;
    }

    /**
     * @param \Webauthn\CredentialRecord $credentialRecord Credential record
     * @return void
     */
    public function saveCredentialSource(CredentialRecord $credentialRecord): void
    {
        $credentials = $this->getUserData($this->user);
        $id = Base64Utility::basicEncode($credentialRecord->publicKeyCredentialId);
        $credentials[$id] = json_decode(
            $this->getSerializer()->serialize($credentialRecord, 'json'),
            true,
        );
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
    public function patchUserData(EntityInterface $entity, array $options): EntityInterface
    {
        $additionalData = $entity->get('additional_data');
        if (!is_array($additionalData)) {
            $additionalData = [];
        }
        $apiData = $additionalData['api'] ?? [];
        $apiData[$this->getDomain()] ??= [];
        $apiData[$this->getDomain()]['webauthn_credentials'] = $options;
        $entity->set('additional_data', $apiData);

        return $entity;
    }

    /**
     * Get user data for webauthn credentials.
     *
     * @param \Cake\Datasource\EntityInterface $entity The user entity
     * @return array
     */
    public function getUserData(ArrayAccess|array $entity): mixed
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

    /**
     * @param array $data Stored credential data.
     * @return \Webauthn\CredentialRecord
     */
    protected function createRecord(array $data): CredentialRecord
    {
        return $this->getSerializer()->deserialize(
            (string)json_encode($data),
            CredentialRecord::class,
            'json',
        );
    }

    /**
     * @return \Symfony\Component\Serializer\SerializerInterface
     */
    protected function getSerializer(): SerializerInterface
    {
        if (!$this->serializer instanceof SerializerInterface) {
            $factory = new WebauthnSerializerFactory(
                new AttestationStatementSupportManager([
                    new NoneAttestationStatementSupport(),
                ]),
            );
            $this->serializer = $factory->create();
        }

        return $this->serializer;
    }
}
