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

use Cake\Datasource\EntityInterface;
use Cake\Utility\Hash;
use Cake\Validation\Validator;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\Action;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use CakeDC\Users\Exception\MissingEmailException;
use CakeDC\Users\Exception\TokenExpiredException;
use CakeDC\Users\Exception\UserAlreadyActiveException;
use CakeDC\Users\Exception\UserNotActiveException;
use CakeDC\Users\Exception\UserNotFoundException;
use Exception;

/**
 * Class SocialLoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class SocialLoginAction extends Action
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
            ->requirePresence('data', 'create');
        $validator
            ->requirePresence('options', 'create');
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
        $input = $this->getData();
        $data = $input['data'];
        $options = $input['options'];

        try {
            $result = $this->getUsersTable()->socialLogin($data, $options);
            if ($result instanceof EntityInterface) {
                $result = $result->toArray();
            }

            return $result;
        } catch (UserNotActiveException $ex) {
            throw new Exception(__d('CakeDC/Api', 'User account has not validated yet'), 501);
        } catch (UserAlreadyActiveException $ex) {
            throw new Exception($ex->getMessage(), 502);
        } catch (UserNotFoundException $ex) {
            throw new Exception(__d('CakeDC/Api', 'Invalid token or user account already validated'), 503);
        } catch (TokenExpiredException $ex) {
            throw new Exception($ex->getMessage(), 504);
        } catch (MissingEmailException $ex) {
            throw new Exception($ex->getMessage(), 505);
        } catch (\Exception $ex) {
            throw new Exception($ex->getMessage(), 500);
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
                'CakeDC/Api.Form' => [],
            ],
        ]);
    }
}
