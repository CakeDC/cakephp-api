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
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Exception\TokenExpiredException;
use CakeDC\Users\Exception\UserAlreadyActiveException;
use CakeDC\Users\Exception\UserNotFoundException;
use CakeDC\Users\Exception\WrongPasswordException;
use Exception;

/**
 * Class ResetPasswordAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ResetPasswordAction extends Action
{
    use CustomUsersTableTrait;

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
        $validator = new Validator();
        $validator
            ->requirePresence('token', 'create')
            ->notBlank('token');

        $validator
            ->requirePresence('password_confirm', 'create')
            ->notBlank('password_confirm');

        $validator
            ->requirePresence('password', 'create')
            ->notBlank('password')
            ->add('password', [
                'password_confirm_check' => [
                    'rule' => ['compareWith', 'password_confirm'],
                    'message' => __d(
                        'cake_d_c/users',
                        'Your password does not match your confirm password. Please try again'
                    ),
                    'allowEmpty' => false,
                ]]);

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
     * @throws \Exception
     */
    public function execute()
    {
        $data = $this->getData();
        $token = $data['token'];

        try {
            $result = $this->getUsersTable()->validate($token);
            if (!empty($result)) {
                return $this->_changePassword($result->id);
            } else {
                throw new Exception(__d('CakeDC/Api', 'Reset password token could not be validated'));
            }
        } catch (UserAlreadyActiveException $exception) {
            throw new Exception(__d('CakeDC/Api', 'User already active'), 500, $exception);
        } catch (UserNotFoundException $ex) {
            throw new Exception(__d('CakeDC/Api', 'Invalid token or user account already validated'), 500, $ex);
        } catch (TokenExpiredException $ex) {
            throw new Exception(__d('CakeDC/Api', 'Token already expired'), 500, $ex);
        }
    }

    /**
     * Change password.
     *
     * @param string $userId User id.
     * @return string
     * @throws \Exception
     */
    protected function _changePassword($userId)
    {
        $user = $this->getUsersTable()->newEntity([], ['validate' => false]);
        $user->id = $userId;
        try {
            $data = $this->getData();
            $user->password = $data['password'];
            $user = $this->getUsersTable()->changePassword($user);
            if ($user) {
                return __d('CakeDC/Api', 'Password has been changed successfully');
            } else {
                throw new Exception(__d('CakeDC/Api', 'Password could not be changed'), 500);
            }
        } catch (UserNotFoundException $exception) {
            throw new Exception(__d('CakeDC/Api', 'User was not found'), 404, $exception);
        } catch (WrongPasswordException $wpe) {
            throw new Exception(__d('CakeDC/Api', '{0}', $wpe->getMessage()), 500, $wpe);
        } catch (Exception $exception) {
            throw new Exception(__d('CakeDC/Api', 'Password could not be changed'), 500, $exception);
        }
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
}
