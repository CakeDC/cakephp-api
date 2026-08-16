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

namespace CakeDC\Api\Test\TestCase\Service;

use CakeDC\Api\Service\Action\Traits\ReCaptchaTrait;
use CakeDC\Api\Service\Service;

class RecaptchaTraitHarness
{
    use ReCaptchaTrait;

    private ?object $recaptchaInstance = null;

    private ?Service $service = null;

    public function __construct(?Service $service = null, ?object $recaptchaInstance = null)
    {
        $this->service = $service;
        $this->recaptchaInstance = $recaptchaInstance;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function getReCaptchaInstance(): ?object
    {
        return $this->recaptchaInstance;
    }

    public function exposedValidateReCaptcha(?string $response = null): bool
    {
        return $this->validateReCaptcha($response);
    }
}
