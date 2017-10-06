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

namespace CakeDC\Api\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class ArticlesTagsFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'article_id' => ['type' => 'integer', 'null' => false],
        'tag_id' => ['type' => 'integer', 'null' => false],
        '_constraints' => [
            'unique_tag' => ['type' => 'primary', 'columns' => ['article_id', 'tag_id']],
            'tag_idx' => [
                'type' => 'foreign',
                'columns' => ['tag_id'],
                'references' => ['tags', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['article_id' => 1, 'tag_id' => 1],
        ['article_id' => 1, 'tag_id' => 2],
        ['article_id' => 2, 'tag_id' => 1],
        ['article_id' => 2, 'tag_id' => 3],
        ['article_id' => 3, 'tag_id' => 1],
        ['article_id' => 4, 'tag_id' => 2],
        ['article_id' => 5, 'tag_id' => 1],
        ['article_id' => 6, 'tag_id' => 3],
        ['article_id' => 7, 'tag_id' => 1],
        ['article_id' => 8, 'tag_id' => 2],
        ['article_id' => 9, 'tag_id' => 1],
        ['article_id' => 10, 'tag_id' => 3],
    ];
}
