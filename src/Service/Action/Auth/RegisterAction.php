<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Auth;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Component\UsersAuthComponent;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Controller\Traits\RegisterTrait;
use CakeDC\Users\Exception\UserNotFoundException;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use Cake\Validation\Validator;

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
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->Auth->allow($this->getName());
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates()
    {
        $validator = $this->getUsersTable()->getRegisterValidators($this->_registerOptions());

        $errors = $validator->errors($this->data());
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
        $user = $usersTable->newEntity();
        $options = $this->_registerOptions();
        $requestData = $this->data();
        $event = $this->dispatchEvent(UsersAuthComponent::EVENT_BEFORE_REGISTER, [
            'usersTable' => $usersTable,
            'options' => $options,
            'userEntity' => $user,
        ]);

        if ($event->result instanceof EntityInterface) {
            if ($userSaved = $usersTable->register($user, $event->result->toArray(), $options)) {
                return $this->_afterRegister($userSaved);
            }
        }
        if ($event->isStopped()) {
            return false;
        }
        $userSaved = $usersTable->register($user, $requestData, $options);
        if (!$userSaved) {
            throw new ValidationException(__d('CakeDC/Api', 'The user could not be saved'), 0, null, $user->errors());
        }

        return $this->_afterRegister($userSaved);
    }

    /**
     * Prepare flash messages after registration, and dispatch afterRegister event
     *
     * @param EntityInterface $userSaved User entity saved
     * @return EntityInterface
     */
    protected function _afterRegister(EntityInterface $userSaved)
    {
        $validateEmail = (bool)Configure::read('Users.Email.validate');
        $message = __d('CakeDC/Api', 'You have registered successfully, please log in');
        if ($validateEmail) {
            $message = __d('CakeDC/Api', 'Please validate your account before log in');
        }
        $event = $this->dispatchEvent(UsersAuthComponent::EVENT_AFTER_REGISTER, [
            'user' => $userSaved
        ]);
        if ($event->result instanceof EntityInterface) {
            $userSaved = $event->result;
        }

        $event = $this->dispatchEvent('Action.Auth.onRegisterFormat', ['user' => $userSaved]);
        if ($event->result) {
            $userSaved = $event->result;
        }

        $userSaved['message'] = $message;

        return $userSaved;
    }

    /**
     * Prepare Auth configuration.
     *
     * @return array
     */
    protected function _authConfig()
    {
        return Hash::merge(parent::_authConfig(), [
            'authenticate' => [
                'CakeDC/Api.Form' => []
            ],
        ]);
    }

    /**
     * @return array
     */
    protected function _registerOptions()
    {
        $validateEmail = (bool)Configure::read('Users.Email.validate');
        $useTos = (bool)Configure::read('Users.Tos.required');
        $tokenExpiration = Configure::read('Users.Token.expiration');
        $options = [
            'token_expiration' => $tokenExpiration,
            'validate_email' => $validateEmail,
            'use_tos' => $useTos
        ];

        return $options;
    }
}
