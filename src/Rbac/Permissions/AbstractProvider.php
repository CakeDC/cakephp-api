<?php
/**
 * Copyright 2010 - 2019, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2018, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Rbac\Permissions;

use Cake\Core\InstanceConfigTrait;
use Cake\Log\LogTrait;

/**
 * Class AbstractProvider, handles getting permission from different sources,
 * for example a config file
 */
abstract class AbstractProvider
{
    use InstanceConfigTrait;
    use LogTrait;

    /**
     * Default permissions to be loaded if no provided permissions
     *
     * @var array
     */
    protected $defaultPermissions;

    /**
     * AbstractProvider constructor.
     * @param array $config config
     */
    public function __construct($config = [])
    {
        $this->setConfig($config);
        $this->defaultPermissions = [
            //all bypass
            [
                'service' => 'Auth',
                'action' => [
                    'SocialLogin',
                    'Login',
                    'Register',
                    'ValidateAccount',
                    'ValidateAccountRequest',
                    'ResetPassword',
                    'ResetPasswordRequest',
                ],
                'bypassAuth' => true,
            ],
            //admin role allowed to all the things
            [
                'role' => 'admin',
                'service' => '*',
                'action' => '*',
            ],

            // demo allowing all GET requests for user role
            [
                'role' => 'user',
                'service' => '*',
                'action' => '*',
                'method' => 'GET',
            ],
        ];
    }

    /**
     * Provide permissions array, for example
     * [
     *     [
     *          'role' => '*',
     *          'service' => ['Pages'],
     *          'action' => ['display'],
     *      ],
     * ]
     *
     * @return array Array of permissions
     */
    abstract public function getPermissions();

    /**
     * @return array
     */
    public function getDefaultPermissions()
    {
        return $this->defaultPermissions;
    }

    /**
     * @param array $defaultPermissions default permissions
     * @return void
     */
    public function setDefaultPermissions($defaultPermissions)
    {
        $this->defaultPermissions = $defaultPermissions;
    }
}
