<?php
/**
 * Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2019, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\Fixture;

use Cake\Auth\PasswordHasherFactory;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '00000000-0000-0000-0000-000000000001',
            'username' => 'user-1',
            'email' => 'user-1@test.com',
            'password' => '12345',
            'first_name' => 'first1',
            'last_name' => 'last1',
            'token' => 'ae93ddbe32664ce7927cf0c5c5a5e59d',
            'token_expires' => '2035-06-24 17:33:54',
            'api_token' => 'yyy',
            'activation_date' => '2015-06-24 17:33:54',
            'tos_date' => '2015-06-24 17:33:54',
            'active' => true,
            'is_superuser' => true,
            'role' => 'admin',
            'created' => '2015-06-24 17:33:54',
            'modified' => '2015-06-24 17:33:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000002',
            'username' => 'user-2',
            'email' => 'user-2@test.com',
            'password' => '12345',
            'first_name' => 'user',
            'last_name' => 'second',
            'token' => '6614f65816754310a5f0553436dd89e9',
            'token_expires' => '2015-06-24 17:33:54',
            'api_token' => 'xxx',
            'activation_date' => '2015-06-24 17:33:54',
            'tos_date' => '2015-06-24 17:33:54',
            'active' => true,
            'is_superuser' => false,
            'role' => 'admin',
            'created' => '2015-06-24 17:33:54',
            'modified' => '2015-06-24 17:33:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000004',
            'username' => 'user-4',
            'email' => '4@example.com',
            'password' => 'Lorem ipsum dolor sit amet',
            'first_name' => 'FirstName4',
            'last_name' => 'Lorem ipsum dolor sit amet',
            'token' => 'token-4',
            'token_expires' => '2030-06-24 17:33:54',
            'api_token' => 'zzz',
            'activation_date' => '2015-06-24 17:33:54',
            'tos_date' => '2015-06-24 17:33:54',
            'active' => true,
            'is_superuser' => false,
            'role' => 'Lorem ipsum dolor sit amet',
            'created' => '2015-06-24 17:33:54',
            'modified' => '2015-06-24 17:33:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000005',
            'username' => 'user-5',
            'email' => 'test@example.com',
            'password' => '12345',
            'first_name' => 'first-user-5',
            'last_name' => 'firts name 5',
            'token' => 'token-5',
            'token_expires' => '2015-06-24 17:33:54',
            'api_token' => '',
            'activation_date' => '2015-06-24 17:33:54',
            'tos_date' => '2015-06-24 17:33:54',
            'active' => true,
            'is_superuser' => false,
            'role' => 'user',
            'created' => '2015-06-24 17:33:54',
            'modified' => '2015-06-24 17:33:54',
        ],
        [
            'id' => '00000000-0000-0000-0000-000000000006',
            'username' => 'user-6',
            'email' => 'test@example.com',
            'password' => '12345',
            'first_name' => 'first-user-6',
            'last_name' => 'last name 6',
            'token' => 'token-6',
            'token_expires' => '2030-06-24 17:33:54',
            'api_token' => '',
            'activation_date' => null,
            'tos_date' => '2015-06-24 17:33:54',
            'active' => false,
            'is_superuser' => false,
            'role' => 'user',
            'created' => '2015-06-24 17:33:54',
            'modified' => '2015-06-24 17:33:54',
        ],
    ];

    public function __construct()
    {
        $hasher = PasswordHasherFactory::build(\Cake\Auth\DefaultPasswordHasher::class);
        parent::__construct();
        foreach ($this->records as &$record) {
            $record['password'] = $hasher->hash($record['password']);
        }
    }
}
