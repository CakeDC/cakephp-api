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

use Cake\Database\Driver\Postgres;
use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\Fixture\TestFixture;

class PostsFixture extends TestFixture
{
    /**
     * records property
     *
     * @var array
     */
    public array $records = [
        ['id' => 1, 'title' => 'First Post', 'body' => 'First Post Body', 'published' => 'Y'],
        ['id' => 2, 'title' => 'Second Post', 'body' => 'Second Post Body', 'published' => 'Y'],
        ['id' => 3, 'title' => 'Third Post', 'body' => 'Third Post Body', 'published' => 'N'],
        ['id' => 4, 'title' => 'Fourth Post', 'body' => 'Fourth Post Body', 'published' => 'Y'],
    ];

    #[\Override]
    public function insert(ConnectionInterface $db): bool
    {
        $result = parent::insert($db);

        if ($db->getDriver() instanceof Postgres) {
            foreach (range(1, count($this->records)) as $i) {
                $db->execute("select nextval('posts_id_seq'::regclass)");
            }
        }

        return $result;
    }
}
