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

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Controller\Traits\RegisterTrait;
use CakeDC\Users\Plugin;

/**
 * Class RegisterAction
 *
 * @package CakeDC\Api\Service\Action
 */
class RegisterAction extends Action
{
    use CustomUsersTableTrait;
    use RegisterTrait;

    /**
     * Initialize an action instance
     *
     * @param array $config Configuration options passed to the constructor
     * @return void
     */
    public function initialize(array $config): void
    {
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
        $validator = $this->getUsersTable()->getRegisterValidators($this->_registerOptions());

        $errors = $validator->errors($this->getData());
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
        $usersTable = $this->getUsersTable();
        $user = $usersTable->newEntity([]);
        $options = $this->_registerOptions();
        $requestData = $this->getData();
        $event = $this->dispatchEvent(Plugin::EVENT_BEFORE_REGISTER, [
            'usersTable' => $usersTable,
            'options' => $options,
            'userEntity' => $user,
        ]);

        if ($event->getResult() instanceof EntityInterface) {
            $userSaved = $usersTable->register($user, $event->getResult()->toArray(), $options);
            if ($userSaved) {
                return $this->_afterRegister($userSaved);
            }
        }
        if ($event->isStopped()) {
            return false;
        }
        $userSaved = $usersTable->register($user, $requestData, $options);
        if (!$userSaved) {
            $message = __d('CakeDC/Api', 'The user could not be saved');
            throw new ValidationException($message, 0, null, $user->getErrors());
        }

        return $this->_afterRegister($userSaved);
    }

    /**
     * Prepare flash messages after registration, and dispatch afterRegister event
     *
     * @param \Cake\Datasource\EntityInterface $userSaved User entity saved
     * @return \Cake\Datasource\EntityInterface
     */
    protected function _afterRegister(EntityInterface $userSaved)
    {
        $validateEmail = (bool)Configure::read('Users.Email.validate');
        $message = __d('CakeDC/Api', 'You have registered successfully, please log in');
        if ($validateEmail) {
            $message = __d('CakeDC/Api', 'Please validate your account before log in');
        }
        $event = $this->dispatchEvent(Plugin::EVENT_BEFORE_REGISTER, [
            'user' => $userSaved,
        ]);
        if ($event->getResult() instanceof EntityInterface) {
            $userSaved = $event->getResult();
        }

        $event = $this->dispatchEvent('Action.Auth.onRegisterFormat', ['user' => $userSaved]);
        if ($event->getResult()) {
            $userSaved = $event->getResult();
        }

        $userSaved['message'] = $message;

        return $userSaved;
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
                'CakeDC/Api.Form' => [],
            ],
        ]);
    }

    /**
     * @return array
     */
    protected function _registerOptions(): array
    {
        $validateEmail = (bool)Configure::read('Users.Email.validate');
        $useTos = (bool)Configure::read('Users.Tos.required');
        $tokenExpiration = Configure::read('Users.Token.expiration');
        $options = [
            'token_expiration' => $tokenExpiration,
            'validate_email' => $validateEmail,
            'use_tos' => $useTos,
        ];

        return $options;
    }
}
