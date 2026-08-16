<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2026, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\TestCase\Integration\Service\Action;

use Cake\Core\Configure;
use CakeDC\Api\Test\ConfigTrait;
use CakeDC\Api\Test\Settings;
use CakeDC\Api\TestSuite\IntegrationTestCase;

/**
 * Proves the lazy transformer pattern works end-to-end:
 * `find()->where()->all()->map(fn($e) => $transformer->transform($e))` inside a real action.
 */
class TransformerActionTest extends IntegrationTestCase
{
    use ConfigTrait;

    protected array $fixtures = [
        'plugin.CakeDC/Api.Articles',
        'plugin.CakeDC/Api.Authors',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('App.fullBaseUrl', 'http://example.com');
        $this->_tokenAccess();
        $this->getDefaultUser(Settings::USER1);
    }

    public function testFeaturedActionAppliesTransformerLazily(): void
    {
        $this->sendRequest('/articles/featured', 'GET', []);
        $result = $this->getJsonResponse();
        $this->assertSuccess($result);

        $data = $result['data'];
        $this->assertNotEmpty($data);

        foreach ($data as $article) {
            $this->assertArrayHasKey('id', $article);
            $this->assertArrayHasKey('title', $article);
            $this->assertArrayHasKey('published', $article);
            // transformed away: body is not part of the response shape
            $this->assertArrayNotHasKey('body', $article);
            // transformed type: 'Y' string became boolean true
            $this->assertTrue($article['published']);
        }

        // the endpoint is a real resource route of the articles service
        $this->assertResponseContains('First Article');
    }
}
