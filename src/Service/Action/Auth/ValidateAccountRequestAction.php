<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Auth;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Component\UsersAuthComponent;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Exception\UserAlreadyActiveException;
use CakeDC\Users\Exception\UserNotFoundException;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use Exception;

/**
 * Class ValidateAccountRequestAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ValidateAccountRequestAction extends Action
{

    use CustomUsersTableTrait;

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
        $validator = new Validator();
        $validator
            ->requirePresence('reference', 'create')
            ->notEmpty('reference');
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
     * @throws Exception
     */
    public function execute()
    {
        $data = $this->data();
        $reference = $data['reference'];
        try {
            if ($this->getUsersTable()->resetToken($reference, [
                'expiration' => Configure::read('Users.Token.expiration'),
                'checkActive' => true,
                'sendEmail' => true,
                'emailTemplate' => 'CakeDC/Users.validation'
            ])) {
                return __d('CakeDC/Api', 'Token has been reset successfully. Please check your email.');
            } else {
                throw new Exception(__d('CakeDC/Api', 'Token could not be reset'), 500);
            }
        } catch (UserNotFoundException $ex) {
            throw new Exception(__d('CakeDC/Api', 'User {0} was not found', $reference), 404);
        } catch (UserAlreadyActiveException $ex) {
            throw new Exception(__d('CakeDC/Api', 'User {0} is already active', $reference), 404);
        } catch (Exception $ex) {
            throw new Exception(__d('CakeDC/Api', 'Token could not be reset'), 500);
        }
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
}
