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

namespace CakeDC\Api\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * AttributesFixture
 */
class AttributesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        ['id' => 1, 'title' => 'First Attribute', 'body' => 'First Attribute Body', 'published' => 'Y'],
        ['id' => 2, 'title' => 'Second Attribute', 'body' => 'Second Attribute Body', 'published' => 'Y'],
        ['id' => 3, 'title' => 'Third Attribute', 'body' => 'Third Attribute Body', 'published' => 'N'],
    ];
}
