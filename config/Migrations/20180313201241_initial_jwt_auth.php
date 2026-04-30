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

class InitialJwtAuth extends BaseMigration
{
    public function change(): void
    {
        $this->table('jwt_refresh_tokens', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('foreign_key', 'uuid', [
                'null' => false,
            ])
            ->addColumn('token', 'string', [
                'default' => null,
                'limit' => 500,
                'null' => true,
            ])
            ->addColumn('expired', 'integer', [
                'limit' => 11,
                'null' => false,
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
