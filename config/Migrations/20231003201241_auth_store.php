<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

use Migrations\BaseMigration;

class AuthStore extends BaseMigration
{
    public function change(): void
    {
        $this->table('auth_store', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'string', [
                'limit' => 500,
                'null' => false,
            ])
            ->addColumn('store', 'text', [
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->create();
    }
}
