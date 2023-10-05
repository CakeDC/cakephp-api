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

use Cake\Utility\Hash;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class AuthenticateAdapter extends BaseAdapter
{
    /**
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public function getOptions(): PublicKeyCredentialRequestOptions
    {
        $userEntity = $this->getUserEntity();
        $allowed = array_map(function (PublicKeyCredentialSource $credential) {
            return $credential->getPublicKeyCredentialDescriptor();
        }, $this->repository->findAllForUserEntity($userEntity));
        \Cake\Log\Log::error(print_r($allowed, true));

        $options = $this->server->generatePublicKeyCredentialRequestOptions(
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $allowed
        );
        $storeEntity = $this->readStore();
        \Cake\Log\Log::error(print_r($storeEntity, true));
        $storeEntity['store'] = [];
        $storeEntity = $this->patchStore($storeEntity, 'authenticateOptions', base64_encode(serialize($options)));
        $res = $this->store->save($storeEntity);
        \Cake\Log\Log::error(print_r($storeEntity, true));
        \Cake\Log\Log::error(print_r($res, true));

        return $options;
    }

    /**
     * Verify the registration response
     *
     * @return \Webauthn\PublicKeyCredentialSource
     */
    public function verifyResponse(): \Webauthn\PublicKeyCredentialSource
    {
        $storeEntity = $this->readStore();
        \Cake\Log\Log::error(print_r($storeEntity, true));
        $options = $this->getStore($storeEntity, 'authenticateOptions');
        if ($options) {
            $options = unserialize(base64_decode($options));
        }
        \Cake\Log\Log::error(print_r($options, true));

        return $this->loadAndCheckAssertionResponse($options);
    }

    /**
     * @param \Webauthn\PublicKeyCredentialRequestOptions $options request options
     * @return \Webauthn\PublicKeyCredentialSource
     */
    protected function loadAndCheckAssertionResponse($options): PublicKeyCredentialSource
    {
        return $this->server->loadAndCheckAssertionResponse(
            json_encode($this->request->getData()),
            $options,
            $this->getUserEntity(),
            $this->request
        );
    }
}
