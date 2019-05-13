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

namespace CakeDC\Api\Service\Action;

/**
 * Class CrudViewAction
 *
 * @package CakeDC\Api\Service\Action
 */
class CrudViewAction extends CrudAction
{
    /**
     * Execute action.
     *
     * @return mixed
     */
    public function execute()
    {
        $record = $this->_getEntity($this->_id);

        return $record;
    }
}
