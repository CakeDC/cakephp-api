<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (https://cakedc.com)
 * @license MIT License (https://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\Core\Configure;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * The reCaptcha validate endpoint.
 */
class RecaptchaValidateTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_authAccess();
        Configure::write('App.fullBaseUrl', 'http://localhost');
    }

    protected function tearDown(): void
    {
        Configure::delete('Test.Api.Config');
        parent::tearDown();
    }

    /**
     * @param array $reCaptcha `Api.reCaptcha` settings.
     * @return void
     */
    protected function configureReCaptcha(array $reCaptcha): void
    {
        $config = include dirname(__DIR__, 4) . DS . 'Config' . DS . 'api.php';
        $config['Api']['reCaptcha'] = $reCaptcha;
        Configure::write('Test.Api.Config', $config);
    }

    public function testGlobalDisabledPasses(): void
    {
        $this->configureReCaptcha(['disabled' => true]);
        $this->sendRequest('/recaptcha/validate', 'POST', ['g-recaptcha-response' => 'token']);
        $result = $this->getJsonResponse();

        $this->assertSuccess($result);
        $this->assertTrue($result['data']);
    }

    public function testDomainDisabledWithoutSecretFails(): void
    {
        $this->configureReCaptcha(['localhost' => ['disabled' => true]]);
        $this->sendRequest('/recaptcha/validate', 'POST', ['g-recaptcha-response' => 'token']);
        $result = $this->getJsonResponse();

        $this->assertSuccess($result);
        $this->assertFalse($result['data']);
    }
}
