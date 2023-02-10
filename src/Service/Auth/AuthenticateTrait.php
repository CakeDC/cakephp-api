<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Api\Service\Auth;

use ArrayAccess;
use Authentication\AuthenticationServiceInterface;
use Authentication\Authenticator\ImpersonationInterface;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\UnauthenticatedException;
use Authentication\IdentityInterface;
use Cake\Utility\Hash;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class AuthenticateTrait
 *
 * @package CakeDC\Api\Service\Auth
 */
trait AuthenticateTrait
{
    /**
     * List of actions that don't require authentication.
     *
     * @var array<string>
     */
    protected array $unauthenticatedActions = [];

    /**
     * Authentication service instance.
     *
     * @var \Authentication\AuthenticationServiceInterface|null
     */
    protected ?AuthenticationServiceInterface $_authentication = null;

    /**
     * Returns authentication service.
     *
     * @return \Authentication\AuthenticationServiceInterface
     * @throws \Exception
     */
    public function getAuthenticationService(): AuthenticationServiceInterface
    {
        if ($this->_authentication !== null) {
            return $this->_authentication;
        }

        $service = $this->getRequest()->getAttribute('authentication');
        if ($service === null) {
            throw new Exception(
                'The request object does not contain the required `authentication` attribute. Verify the ' .
                'AuthenticationMiddleware has been added.'
            );
        }

        if (!($service instanceof AuthenticationServiceInterface)) {
            throw new Exception('Authentication service does not implement ' . AuthenticationServiceInterface::class);
        }

        $this->_authentication = $service;

        return $service;
    }

    /**
     * Check if the identity presence is required.
     *
     * Also checks if the current action is accessible without authentication.
     *
     * @return void
     * @throws \Exception when request is missing or has an invalid AuthenticationService
     * @throws \Authentication\Authenticator\UnauthenticatedException when requireIdentity is true and request is missing an identity
     */
    protected function doIdentityCheck(): void
    {
        if (!$this->getConfig('requireIdentity')) {
            return;
        }

        $request = $this->getRequest();
        $action = $request->getParam('action');
        if (in_array($action, $this->unauthenticatedActions, true)) {
            return;
        }

        $identity = $request->getAttribute($this->getConfig('identityAttribute'));
        if (!$identity) {
            throw new UnauthenticatedException($this->getConfig('unauthenticatedMessage', ''));
        }
    }

    /**
     * Set the list of actions that don't require an authentication identity to be present.
     *
     * Actions not in this list will require an identity to be present. Any
     * valid identity will pass this constraint.
     *
     * @param array<string> $actions The action list.
     * @return $this
     */
    public function allowUnauthenticated(array $actions)
    {
        $this->unauthenticatedActions = $actions;

        return $this;
    }

    /**
     * Add to the list of actions that don't require an authentication identity to be present.
     *
     * @param array<string> $actions The action or actions to append.
     * @return $this
     */
    public function addUnauthenticatedActions(array $actions)
    {
        $this->unauthenticatedActions = array_merge($this->unauthenticatedActions, $actions);
        $this->unauthenticatedActions = array_values(array_unique($this->unauthenticatedActions));

        return $this;
    }

    /**
     * Get the current list of actions that don't require authentication.
     *
     * @return array<string>
     */
    public function getUnauthenticatedActions(): array
    {
        return $this->unauthenticatedActions;
    }

    /**
     * Gets the result of the last authenticate() call.
     *
     * @return \Authentication\Authenticator\ResultInterface|null Authentication result interface
     */
    public function getResult(): ?ResultInterface
    {
        return $this->getAuthenticationService()->getResult();
    }

    /**
     * Returns the identity used in the authentication attempt.
     *
     * @return \Authentication\IdentityInterface|null
     */
    public function getIdentity(): ?IdentityInterface
    {
        $identity = $this->getRequest()->getAttribute($this->getConfig('identityAttribute'));

        return $identity;
    }

    /**
     * Returns the identity used in the authentication attempt.
     *
     * @param string $path Path to return from the data.
     * @return mixed
     * @throws \RuntimeException If the identity has not been found.
     */
    public function getIdentityData(string $path): mixed
    {
        $identity = $this->getIdentity();

        if ($identity === null) {
            throw new RuntimeException('The identity has not been found.');
        }

        return Hash::get($identity, $path);
    }

    /**
     * Replace the current identity
     *
     * Clear and replace identity data in all authenticators
     * that are loaded and support persistence. The identity
     * is cleared and then set to ensure that privilege escalation
     * and de-escalation include side effects like session rotation.
     *
     * @param \ArrayAccess $identity Identity data to persist.
     * @return $this
     */
    public function setIdentity(ArrayAccess $identity)
    {
        $service = $this->getAuthenticationService();

        $service->clearIdentity($this->getRequest(), $this->getResponse());

        /** @psalm-var array{request: \Cake\Http\ServerRequest, response: \Cake\Http\Response} $result */
        $result = $service->persistIdentity(
            $this->getRequest(),
            $this->getResponse(),
            $identity
        );

        $this->setRequest($result['request']);
        $this->setResponse($result['response']);

        return $this;
    }

    /**
     * Impersonates a user
     *
     * @param \ArrayAccess $impersonated User impersonated
     * @return $this
     * @throws \Exception
     */
    public function impersonate(ArrayAccess $impersonated)
    {
        $service = $this->getImpersonationAuthenticationService();

        $identity = $this->getIdentity();
        if (!$identity) {
            throw new UnauthenticatedException('You must be logged in before impersonating a user.');
        }

        /** @psalm-var array{request: \Cake\Http\ServerRequest, response: \Cake\Http\Response} $result */
        $result = $service->impersonate(
            $this->getRequest(),
            $this->getResponse(),
            $identity,
            $impersonated
        );

        if (!$service->isImpersonating($this->getRequest())) {
            throw new UnexpectedValueException('An error has occurred impersonating user.');
        }

        $this->setRequest($result['request']);
        $this->setResponse($result['response']);

        return $this;
    }

    /**
     * Stops impersonation
     *
     * @return $this
     * @throws \Exception
     */
    public function stopImpersonating()
    {
        $service = $this->getImpersonationAuthenticationService();

        /** @psalm-var array{request: \Cake\Http\ServerRequest, response: \Cake\Http\Response} $result */
        $result = $service->stopImpersonating(
            $this->getRequest(),
            $this->getResponse()
        );

        if ($service->isImpersonating($this->getRequest())) {
            throw new UnexpectedValueException('An error has occurred stopping impersonation.');
        }

        $this->setRequest($result['request']);
        $this->setResponse($result['response']);

        return $this;
    }

    /**
     * Returns true if impersonation is being done
     *
     * @return bool
     * @throws \Exception
     */
    public function isImpersonating(): bool
    {
        $service = $this->getImpersonationAuthenticationService();

        return $service->isImpersonating(
            $this->getRequest()
        );
    }

    /**
     * Get impersonation authentication service
     *
     * @return \Authentication\Authenticator\ImpersonationInterface
     * @throws \Exception
     */
    protected function getImpersonationAuthenticationService(): ImpersonationInterface
    {
        $service = $this->getAuthenticationService();
        if (!($service instanceof ImpersonationInterface)) {
            $className = get_class($service);
            throw new InvalidArgumentException(
                "The {$className} must implement ImpersonationInterface in order to use impersonation."
            );
        }

        return $service;
    }
}
