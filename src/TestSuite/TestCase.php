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

namespace CakeDC\Api\TestSuite;

use CakeDC\Api\Service\ServiceRegistry;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase as BaseTestCase;
use Cake\Utility\Hash;

/**
 * Class TestCase
 *
 * @package CakeDC\Api\TestSuite
 */
class TestCase extends BaseTestCase
{

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        ServiceRegistry::clear();
    }
}
