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
use Exception;

/**
 * Class ValidateAccountAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ValidateAccountAction extends Action
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
     * @throws \Exception
     */
    public function execute()
    {
        $data = $this->getData();
        $token = $data['token'];

        try {
            $result = $this->getUsersTable()->validate($token, 'activateUser');
            if ($result) {
                return __d('CakeDC/Api', 'User account validated successfully');
            } else {
                throw new Exception(__d('CakeDC/Api', 'User account could not be validated'), 500);
            }
        } catch (UserAlreadyActiveException $exception) {
            throw new Exception(__d('CakeDC/Api', 'User already active'), 500);
        } catch (UserNotFoundException $ex) {
            throw new Exception(__d('CakeDC/Api', 'Invalid token or user account already validated'), 500);
        } catch (TokenExpiredException $ex) {
            throw new Exception(__d('CakeDC/Api', 'Token already expired'), 500);
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
