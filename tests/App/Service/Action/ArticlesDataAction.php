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

namespace CakeDC\Api\Test\App\Service\Action;

use CakeDC\Api\Service\Action\CrudAction;
use CakeDC\Api\Test\App\DI\Service\TestService;

class ArticlesDataAction extends CrudAction
{
    private TestService $testService;

    public function __construct(TestService $testService, array $config = [])
    {
        $this->testService = $testService;
        parent::__construct($config);
    }

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
        return true;
    }

    public function execute()
    {
        return $this->testService->data();
    }

    public function getTestService()
    {
        return $this->testService;
    }
}
