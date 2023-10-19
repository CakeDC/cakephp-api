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
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Exception\UserNotActiveException;
use CakeDC\Users\Exception\UserNotFoundException;
use Exception;

/**
 * Class ResetPasswordAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ResetPasswordRequestAction extends Action
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
            ->requirePresence('reference', 'create')
            ->notBlank('reference');
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
        $reference = $data['reference'];
        $baseUrl = $data['base_url'] ?? null;
        try {
            $options = [
                'expiration' => Configure::read('Users.Token.expiration'),
                'checkActive' => false,
                'sendEmail' => true,
                'type' => 'password',
                'ensureActive' => Configure::read('Users.Registration.ensureActive'),
            ];
            if (!empty($baseUrl)) {
                $options['linkGenerator'] = function($token) use ($baseUrl) {
                    return $baseUrl . '?token=' . $token;
                };
            }

            $resetUser = $this->getUsersTable()->resetToken($reference, $options);
            if ($resetUser) {
                return __d('CakeDC/Api', 'Please check your email to continue with password reset process');
            } else {
                $message = __d('CakeDC/Api', 'The password token could not be generated. Please try again');
                throw new Exception($message, 500);
            }
        } catch (UserNotFoundException $exception) {
            throw new Exception(__d('CakeDC/Api', 'User {0} was not found', $reference), 404, $exception);
        } catch (UserNotActiveException $exception) {
            throw new Exception(__d('CakeDC/Api', 'The user is not active'), 404, $exception);
        } catch (Exception $exception) {
            throw new Exception(__d('CakeDC/Api', 'Token could not be reset'), 500, $exception);
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
