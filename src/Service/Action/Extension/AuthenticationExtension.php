<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Extension;

use ArrayAccess;
use Authentication\AuthenticationServiceInterface;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\UnauthenticatedException;
use Authentication\IdentityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Utility\Hash;
use CakeDC\Api\Service\Action\Action;
use Exception;
use RuntimeException;

/**
 * Class UserFormattingExtension
 *
 * @package CakeDC\Api\Service\Action\Extension\Auth
 */
class AuthenticationExtension extends Extension implements EventListenerInterface
{
    // use EventDispatcherTrait;

    /**
     * Configuration options
     *
     * - `logoutRedirect` - The route/URL to direct users to after logout()
     * - `requireIdentity` - By default AuthenticationComponent will require an
     *   identity to be present whenever it is active. You can set the option to
     *   false to disable that behavior. See allowUnauthenticated() as well.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'logoutRedirect' => false,
        'requireIdentity' => true,
        'identityAttribute' => 'identity',
    ];

    /**
     * List of actions that don't require authentication.
     */
    protected array $unauthenticatedActions = [];

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Action.Auth.onAuthentication' => 'onAuthentication',
            'Action.onAuth' => 'onAuthentication',
        ];
    }

    /**
     * On authentication event listener
     *
     * @param \Cake\Event\Event $event Event object instance.
     * @return void
     * @throws \Exception when request is missing or has an invalid AuthenticationService
     * @throws \Authentication\Authenticator\UnauthenticatedException when requireIdentity is true
     *         and request is missing an identity
     */
    public function onAuthentication(Event $event): void
    {
        if (!$this->getConfig('requireIdentity')) {
            return;
        }

        $request = $this->getAction()->getService()->getRequest();
        $action = $this->getAction()->getName();
        if (in_array($action, $this->unauthenticatedActions)) {
            return;
        }

        $identity = $request->getAttribute($this->getConfig('identityAttribute'));
        if (!$identity) {
            throw new UnauthenticatedException();
        }
    }

    /**
     * Returns authentication service.
     *
     * @return \Authentication\AuthenticationServiceInterface
     * @throws \Exception
     */
    public function getAuthenticationService(): AuthenticationServiceInterface
    {
        $request = $this->getAction()->getService()->getRequest();
        $service = $request->getAttribute('authentication');
        if ($service === null) {
            throw new Exception('The request object does not contain the required `authentication` attribute');
        }

        if (!($service instanceof AuthenticationServiceInterface)) {
            throw new Exception('Authentication service does not implement ' . AuthenticationServiceInterface::class);
        }

        return $service;
    }

    /**
     * Set the list of actions that don't require an authentication identity to be present.
     *
     * Actions not in this list will require an identity to be present. Any
     * valid identity will pass this constraint.
     *
     * @param array $actions The action list.
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
     * @param array $actions The action or actions to append.
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
     * @return array
     */
    public function getUnauthenticatedActions()
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
        $request = $this->getAction()->getService()->getRequest();
        $identity = $request->getAttribute($this->getConfig('identityAttribute'));

        return $identity;
    }

    /**
     * Returns the identity used in the authentication attempt.
     *
     * @param string $path Path to return from the data.
     * @return mixed
     * @throws \RuntimeException If the identity has not been found.
     */
    public function getIdentityData($path)
    {
        $identity = $this->getIdentity();

        if ($identity === null) {
            throw new RuntimeException('The identity has not been found.');
        }

        return Hash::get($identity, $path);
    }

    /**
     * @return \CakeDC\Api\Service\Action\Action
     */
    public function getAction(): Action
    {
        return $this->getConfig('action');
    }

    /**
     * Set identity data to all authenticators that are loaded and support persistence.
     *
     * @param \ArrayAccess $identity Identity data to persist.
     * @return $this
     */
    public function setIdentity(ArrayAccess $identity)
    {
        $request = $this->getAction()->getService()->getRequest();

        $result = $this->getAuthenticationService()->persistIdentity(
            $this->getAction()->getService()->getRequest(),
            $this->getAction()->getService()->getResponse(),
            $identity
        );

        $this->getAction()->getService()->setRequest($result['request']);
        $this->getAction()->getService()->setResponse($result['response']);

        return $this;
    }
}
