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

use Cake\Http\Exception\BadRequestException;
use Webauthn\AuthenticationExtensions\AuthenticationExtensions;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

class AuthenticateAdapter extends BaseAdapter
{
    /**
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public function getOptions(): PublicKeyCredentialRequestOptions
    {
        $userEntity = $this->getUserEntity();
        $allowedCredentials = array_map(fn(CredentialRecord $credential): PublicKeyCredentialDescriptor => $credential->getPublicKeyCredentialDescriptor(), $this->repository->findAllForUserEntity($userEntity));

        $options = PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            rpId: $this->rpEntity->id,
            allowCredentials: $allowedCredentials,
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            extensions: AuthenticationExtensions::create(),
        );

        $storeEntity = $this->readStore();
        $storeEntity->store = [];
        $storeEntity = $this->patchStore($storeEntity, 'authenticateOptions', base64_encode($this->serializeOptions($options)));

        $this->store->save($storeEntity);

        return $options;
    }

    /**
     * Verify the registration response
     *
     * @return \Webauthn\CredentialRecord
     * @throws \Throwable
     */
    public function verifyResponse(): CredentialRecord
    {
        $storeEntity = $this->readStore();
        $options = $this->getStore($storeEntity, 'authenticateOptions');
        if ($options) {
            $options = $this->createSerializer()->deserialize(
                base64_decode($options),
                PublicKeyCredentialRequestOptions::class,
                'json',
            );
        }

        $publicKeyCredentialLoader = $this->createPublicKeyCredentialLoader();
        $publicKeyCredential = $publicKeyCredentialLoader->loadArray($this->request->getData());
        $authenticatorAssertionResponse = $publicKeyCredential->response;
        if ($authenticatorAssertionResponse instanceof AuthenticatorAssertionResponse) {
            $storedCredential = $this->repository->findOneByCredentialId($publicKeyCredential->rawId);
            if (!$storedCredential instanceof CredentialRecord) {
                throw new BadRequestException(__d('cake_d_c/api', 'Could not validate credential response for authentication'));
            }
            $authenticatorAssertionResponseValidator = AuthenticatorAssertionResponseValidator::create(
                $this->createCeremonyStepManagerFactory()->requestCeremony(),
            );

            $credential = $authenticatorAssertionResponseValidator->check(
                $storedCredential,
                $authenticatorAssertionResponse,
                $options,
                (string)$this->request->host(),
                $this->getUserEntity()->id,
            );

            $this->repository->saveCredentialSource($credential);

            return $credential;
        }

        throw new BadRequestException(__d('cake_d_c/api', 'Could not validate credential response for authentication'));
    }
}
