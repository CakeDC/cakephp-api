<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service;

use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;

class FallbackServiceTest extends IntegrationTestCase
{
    use FixturesTrait;
    use ConfigTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        $this->_publicAccess();
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
    }

    public function testArticlesIndex()
    {
        $this->sendRequest('/articles', 'GET', ['limit' => 5]);
        $this->assertResponseOk();
    }
}
