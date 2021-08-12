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

namespace CakeDC\Api\Service\Action\Auth;

use Cake\Utility\Hash;
use Cake\Validation\Validator;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Traits\LoginTrait;
use CakeDC\Users\Exception\UserNotFoundException;

/**
 * Class LoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class LoginAction extends Action
{
    use LoginTrait;

    protected string $_identifiedField = 'username';
    protected string $_passwordField = 'password';

    /**
     * Initialize an action instance
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config): void
    {
        if (isset($config['identifiedField'])) {
            $this->_identifiedField = $config['identifiedField'];
        }
        if (isset($config['passwordField'])) {
            $this->_passwordField = $config['passwordField'];
        }
        parent::initialize($config);
        $this->Auth->allow($this->getName());
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        $validator = new Validator();
        $validator
            ->requirePresence($this->_identifiedField, 'create')
            ->notBlank($this->_identifiedField);
        $validator
            ->requirePresence($this->_passwordField, 'create')
            ->notBlank($this->_passwordField);
        $errors = $validator->validate($this->getData());
        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed'), 0, null, $errors);
        }

        return true;
    }

    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $socialLogin = false;
        //$event = $this->dispatchEvent(UsersAuthComponent::EVENT_BEFORE_LOGIN);
//        if (is_array($event->getResult())) {
//            $user = $this->_afterIdentifyUser($event->getResult());
//        } else {
            $user = $this->Auth->identify();
            $user = $this->_afterIdentifyUser($user, $socialLogin);
//        }
        if (empty($user)) {
            throw new UserNotFoundException(__d('CakeDC/Api', 'User not found'), 401);
        } else {
            return $user;
        }
    }

    /**
     * Update remember me and determine redirect url after user identified
     *
     * @param array $user user data after identified
     * @param bool $socialLogin is social login
     * @return array
     */
    protected function _afterIdentifyUser($user, $socialLogin = false)
    {
        if (!empty($user)) {
            $this->Auth->setUser($user);
//            $this->dispatchEvent(UsersAuthComponent::EVENT_AFTER_LOGIN, ['user' => $user]);
        }

        $event = $this->dispatchEvent('Action.Auth.onLoginFormat', ['user' => $user]);
        if ($event->getResult()) {
            $user = $event->getResult();
        }

        return $user;
    }

    /**
     * Prepare Auth configuration.
     *
     * @return array
     */
    protected function _authConfig(): array
    {
        return Hash::merge(parent::_authConfig(), [
            'authenticate' => [
                'CakeDC/Api.Form' => [
                    'fields' => [
                        'username' => $this->_identifiedField,
                        'password' => $this->_passwordField,
                    ],
                ],
            ],
        ]);
    }
}
