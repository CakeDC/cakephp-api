<?php
declare(strict_types=1);

namespace CakeDC\Api\Test\TestCase\Command;

use Cake\AttributeResolver\AttributeResolver;
use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use CakeDC\Api\Service\ServiceRegistry;
use CakeDC\Api\Test\ConfigTrait;

class ServiceRoutesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use ConfigTrait;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        $this->_publicAccess();
        parent::setUp();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        ServiceRegistry::getServiceLocator()->clear();
        AttributeResolver::drop('default');
    }

    /**
     * Test help output
     */
    public function testServiceRoutesHelp(): void
    {
        $this->exec('service routes -h');
        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Display all routes in a service');
        $this->assertErrorEmpty();
    }

    /**
     * Test basic route listing
     */
    public function testServiceRoutesList(): void
    {
        $this->exec('service routes Articles');
        $this->assertExitCode(Command::CODE_SUCCESS);

        // Assert table header
        $this->assertOutputContainsRow($this->getHeaderRow());

        // Assert all routes
        foreach ($this->getArticleRoutes() as $route) {
            $this->assertOutputContainsRow($route);
        }
    }

    private function getHeaderRow(): array
    {
        return [
            '<info>Route name</info>',
            '<info>Method(s)</info>',
            '<info>URI template</info>',
            '<info>Service</info>',
            '<info>Action</info>',
            '<info>Plugin</info>',
        ];
    }

    /**
     * Test attribute-declared routes are printed by the command.
     */
    public function testServiceRoutesListsAttributeRoutes(): void
    {
        if (AttributeResolver::getConfig('default') !== null) {
            AttributeResolver::clear('default');
            AttributeResolver::drop('default');
        }
        AttributeResolver::setConfig('default', [
            'paths' => ['tests/App/Service/*.php', 'tests/App/Service/**/*.php'],
            'cache' => false,
        ]);

        $this->exec('service routes attributes');
        $this->assertExitCode(Command::CODE_SUCCESS);

        $this->assertOutputContainsRow($this->getHeaderRow());
        $this->assertOutputContainsRow([
            'attributes:featured',
            'GET',
            '/attributes/featured',
            'attributes',
            'featured',
            '',
        ]);
        $this->assertOutputContainsRow([
            'attributes:index',
            'GET',
            '/attributes',
            'attributes',
            'index',
            '',
        ]);

        // route with a {id} placeholder
        $this->assertOutputContainsRow([
            'attributes:item',
            'GET',
            '/attributes/item/{id}',
            'attributes',
            'item',
            '',
        ]);

        // add is excluded by ApiResource(only: ...) and must not be printed
        $this->assertOutputNotContains('attributes:add');
    }

    private function getArticleRoutes(): array
    {
        return [
            [
                'articles:untag',
                'PUT, POST',
                '/articles/untag/{id}',
                'articles',
                'untag',
                '',
            ],
            [
                'articles:tag',
                'PUT, POST',
                '/articles/tag/{id}',
                'articles',
                'tag',
                '',
            ],
            [
                'articles:view',
                'GET',
                '/articles/{id}',
                'articles',
                'view',
                '',
            ],
            [
                'articles:edit',
                'PUT, PATCH',
                '/articles/{id}',
                'articles',
                'edit',
                '',
            ],
            [
                'articles:delete',
                'DELETE',
                '/articles/{id}',
                'articles',
                'delete',
                '',
            ],
            [
                'articles:describe',
                'OPTIONS',
                '/articles/{id}',
                'articles',
                'describe',
                '',
            ],
            [
                'articles:index',
                'GET',
                '/articles',
                'articles',
                'index',
                '',
            ],
            [
                'articles:add',
                'POST',
                '/articles',
                'articles',
                'add',
                '',
            ],
            [
                'articles:describe',
                'OPTIONS',
                '/articles',
                'articles',
                'describe',
                '',
            ],
        ];
    }
}
