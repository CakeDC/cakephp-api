<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Test\Base;

use Cake\ORM\TableRegistry;

// @codingStandardsIgnoreStart
trait BaseTraitTest
{

    /**
     * Sets up the session as a logged in user for an user with id $id
     *
     * @param $id
     * @return void
     */
    protected function loginAsUserId($id)
    {
        $data = TableRegistry::get('CakeDC/Users.Users')->get($id)->toArray();
        $this->session(['Auth' => ['User' => $data]]);
    }
}
