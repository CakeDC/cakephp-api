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

namespace CakeDC\Api\Test\TestCase\Integration\Service;

use CakeDC\Api\TestSuite\IntegrationTestCase;
use CakeDC\Api\Test\FixturesTrait;
use Cake\Core\Configure;

class FallbackServiceTest extends IntegrationTestCase
{

    use FixturesTrait;

    /**
     * setUp
     *
     * @return void
     */
    public function setUp()
    {
        Configure::write('App.fullBaseUrl', 'http://example.com');
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
        $json = json_decode((string)$this->_response->getBody(), true);
    }
}
