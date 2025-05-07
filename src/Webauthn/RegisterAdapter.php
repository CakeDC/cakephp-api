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
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;

class RegisterAdapter extends BaseAdapter
{
    /**
     * @return \Webauthn\PublicKeyCredentialCreationOptions
     */
    public function getOptions(): PublicKeyCredentialCreationOptions
    {
        $userEntity = $this->getUserEntity();
        $challenge = random_bytes(16);

        $options = PublicKeyCredentialCreationOptions::create(
            $this->rpEntity,
            $userEntity,
            $challenge,
            $this->getPubKeyCredParams()
        );
        $options = $options
            ->setAuthenticatorSelection(new AuthenticatorSelectionCriteria())
            ->setAttestation(PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE)
            ->setExtensions(new AuthenticationExtensionsClientInputs());

        $storeEntity = $this->readStore();
        $storeEntity = $this->patchStore($storeEntity, 'registerOptions', base64_encode(serialize($options)));
        $this->store->save($storeEntity);

        return $options;
    }

    /**
     * @return array<\Webauthn\PublicKeyCredentialParameters>
     */
    protected function getPubKeyCredParams(): array
    {
        $list = [];
        foreach ($this->getAlgorithmManager()->all() as $algorithm) {
            $list[] = PublicKeyCredentialParameters::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm::identifier()
            );
        }

        return $list;
    }

    /**
     * Verify the registration response
     *
     * @return \Webauthn\PublicKeyCredentialSource
     */
    public function verifyResponse(): \Webauthn\PublicKeyCredentialSource
    {
        $storeEntity = $this->readStore();
        $options = $this->getStore($storeEntity, 'registerOptions');
        if ($options) {
            $options = unserialize(base64_decode($options));
        }

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager();
        $publicKeyCredentialLoader = $this->createPublicKeyCredentialLoader();

        $publicKeyCredential = $publicKeyCredentialLoader->loadArray((array)$this->request->getData());
        $authenticatorAttestationResponse = $publicKeyCredential->getResponse();
        if ($authenticatorAttestationResponse instanceof AuthenticatorAttestationResponse) {
            $extensionOutputCheckerHandler = $this->createExtensionOutputCheckerHandler();

            $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
                $attestationStatementSupportManager,
                $this->repository,
                null, //Token binding is deprecated
                $extensionOutputCheckerHandler
            );

            $credential = $authenticatorAttestationResponseValidator->check(
                $authenticatorAttestationResponse,
                $options,
                $this->request
            );
            $this->repository->saveCredentialSource($credential);

            return $credential;
        }

        throw new BadRequestException(__d('cake_d_c/api', 'Could not create credential response for registration'));
    }
}
