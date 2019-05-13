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

namespace CakeDC\Api\Model\Entity;

interface EntityDescriptionInterface
{
    /**
     * Will describe the fields
     *
     * @return array
     */
    public function describeFields();

    /**
     * Will describe the actions
     *
     * @return array
     */
    public function describeAction();

    /**
     * Will describe the params
     *
     * @return array
     */
    public function describeParams();
}
