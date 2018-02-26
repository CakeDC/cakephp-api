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

namespace CakeDC\Api\Test;

/**
 * Class FixturesTrait
 *
 * @package CakeDC\Api\Test
 */
trait FixturesTrait
{

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.CakeDC/Api.social_accounts',
        'plugin.CakeDC/Api.users',
        'plugin.CakeDC/Api.articles',
        'plugin.CakeDC/Api.authors',
        'plugin.CakeDC/Api.tags',
        'plugin.CakeDC/Api.articles_tags',
    ];
}
