<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2023, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2023, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service;

use Cake\Utility\Hash;
use CakeDC\Api\Service\Action\Recaptcha\ValidateAction;

/**
 * Class RecaptchaService
 *
 * @package CakeDC\Api\Service
 */
class RecaptchaService extends Service
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $methods = ['method' => ['POST'], 'mapCors' => true];

        $this->mapAction('validate', ValidateAction::class, $methods);
    }
}
