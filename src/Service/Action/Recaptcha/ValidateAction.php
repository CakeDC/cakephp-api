<?php
declare(strict_types=1);

/**
 * Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2018 - 2020, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Recaptcha;

use CakeDC\Api\Service\Action\Action;
use CakeDC\Api\Service\Action\Traits\ReCaptchaTrait;
use CakeDC\Api\Utility\RequestParser;

/**
 * Class SocialLoginAction
 *
 * @package CakeDC\Api\Service\Action
 */
class ValidateAction extends Action
{
    use ReCaptchaTrait;

    /**
     * Execute action.
     *
     * @return mixed
     * @throws \CakeDC\Api\Service\Action\Exception
     */
    public function execute()
    {
        return $this->validateReCaptcha();
    }

}
