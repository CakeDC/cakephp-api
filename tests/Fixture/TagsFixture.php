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

namespace CakeDC\Api\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class TagFixture
 */
class TagsFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public array $records = [
        ['id' => 1, 'post_id' => 1, 'name' => 'tag1'],
        ['id' => 2, 'post_id' => 1, 'name' => 'tag2'],
        ['id' => 3, 'post_id' => 2, 'name' => 'tag3'],
        ['id' => 4, 'post_id' => 2, 'name' => 'tag4'],
    ];
}
