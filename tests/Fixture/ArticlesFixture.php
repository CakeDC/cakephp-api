<?php
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

use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 *
 */
class ArticlesFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'author_id' => ['type' => 'integer', 'null' => true],
        'title' => ['type' => 'string', 'null' => true],
        'body' => 'text',
        'published' => ['type' => 'string', 'length' => 1, 'default' => 'N'],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['id' => 1, 'author_id' => 1, 'title' => 'First Article', 'body' => 'First Article Body', 'published' => 'Y'],
        ['id' => 2, 'author_id' => 3, 'title' => 'Second Article', 'body' => 'Second Article Body', 'published' => 'Y'],
        ['id' => 3, 'author_id' => 2, 'title' => 'Third Article', 'body' => 'Third Article Body', 'published' => 'Y'],
        ['id' => 4, 'author_id' => 4, 'title' => 'Article N4', 'body' => 'Article N4 Body', 'published' => 'Y'],
        ['id' => 5, 'author_id' => 7, 'title' => 'Article N5', 'body' => 'Article N5 Body', 'published' => 'Y'],
        ['id' => 6, 'author_id' => 2, 'title' => 'Article N6', 'body' => 'Article N6 Body', 'published' => 'Y'],
        ['id' => 7, 'author_id' => 3, 'title' => 'Article N7', 'body' => 'Article N7 Body', 'published' => 'Y'],
        ['id' => 8, 'author_id' => 4, 'title' => 'Article N8', 'body' => 'Article N8 Body', 'published' => 'Y'],
        ['id' => 9, 'author_id' => 2, 'title' => 'Article N9', 'body' => 'Article N9 Body', 'published' => 'Y'],
        ['id' => 10, 'author_id' => 1, 'title' => 'Article N10', 'body' => 'Article N10 Body', 'published' => 'Y'],
        ['id' => 11, 'author_id' => 3, 'title' => 'Article N11', 'body' => 'Article N11 Body', 'published' => 'Y'],
        ['id' => 12, 'author_id' => 5, 'title' => 'Article N12', 'body' => 'Article N12 Body', 'published' => 'Y'],
        ['id' => 13, 'author_id' => 1, 'title' => 'Article N13', 'body' => 'Article N13 Body', 'published' => 'Y'],
        ['id' => 14, 'author_id' => 6, 'title' => 'Article N14', 'body' => 'Article N14 Body', 'published' => 'Y'],
        ['id' => 15, 'author_id' => 1, 'title' => 'Article N15', 'body' => 'Article N15 Body', 'published' => 'Y'],
    ];

    public function insert(ConnectionInterface $db)
    {
        parent::insert($db);

        if ($db->getDriver() instanceof Postgres) {
            foreach (range(1, count($this->records)) as $i) {
                $db->execute('select nextval(\'articles_id_seq\'::regclass)');
            }
        }
    }
}
