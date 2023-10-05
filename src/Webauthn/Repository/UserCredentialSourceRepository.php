<?php
declare(strict_types=1);

namespace CakeDC\Api\Webauthn\Repository;

use Base64Url\Base64Url;
use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use CakeDC\Users\Model\Table\UsersTable;
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
     * @param \Cake\Datasource\EntityInterface $user The user.
     * @param \CakeDC\Users\Model\Table\UsersTable|null $usersTable The table.
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
        $encodedId = Base64Url::encode($publicKeyCredentialId);
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
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $credentials = $this->getUserData($this->user);
        $id = Base64Url::encode($publicKeyCredentialSource->getPublicKeyCredentialId());
        $credentials[$id] = json_decode(json_encode($publicKeyCredentialSource), true);
        $this->patchUserData($this->user, $credentials);
        $res = $this->usersTable->saveOrFail($this->user);
    }

    public function patchUserData($entity, $options)
    {
        $entity['additional_data'] = $entity['additional_data'] ?? [];
        $entity['additional_data']['api'] = $entity['additional_data']['api'] ?? [];
        $entity['additional_data']['api'][$this->getDomain()] = $entity['additional_data']['api'][$this->getDomain()] ?? [];
        $entity['additional_data']['api'][$this->getDomain()]['webauthn_credentials'] = $options;

        return $entity;
    }

    public function getUserData($entity)
    {
        $path = 'additional_data.api.' . $this->getDomain() . '.webauthn_credentials';

        return Hash::get($entity, $path, []);
    }

    public function getDomain()
    {
        $domain = null;
        if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
            $domain = parse_url($_SERVER['HTTP_REFERER']);
        }
        if ($domain !==null && $domain['host']) {
            $host = $domain['host'];
        } else {
            $host = $this->request->domain();
        }

        return str_replace('.', '$', $host);
    }

}
