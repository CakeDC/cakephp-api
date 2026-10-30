<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2023, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2023, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Action\Traits;

use Cake\Core\Configure;
use Cake\Log\Log;
use CakeDC\Api\Utility\RequestParser;
use ReCaptcha\ReCaptcha;

/**
 * Covers registration features and email token validation
 *
 * @property \Cake\Http\ServerRequest $request
 */
trait ReCaptchaTrait
{
    /**
     * Validates reCaptcha response
     *
     * @param string $recaptchaResponse response
     * @return bool
     */
    public function validateReCaptcha($recaptchaResponse = null)
    {
        if (Configure::read('Api.reCaptcha.disabled') === true) {
            return true;
        }
        // turning validation off is a global setting; a domain setting only logs a warning, without a
        // scope so that the application's default logs record it
        $domain = RequestParser::getDomain($this->getService()->getRequest());
        if (Configure::read('Api.reCaptcha.' . $domain . '.disabled')) {
            Log::warning(sprintf(
                '`Api.reCaptcha.%s.disabled` is ignored; set `Api.reCaptcha.disabled` to true instead',
                $domain
            ));
        }

        $recaptcha = $this->_getReCaptchaInstance();
        if ($recaptcha === null) {
            return false;
        }
        if ($recaptchaResponse === null) {
            $recaptchaResponse = $this->getService()->getRequest()->getData('g-recaptcha-response');
        }
        $resp = $recaptcha->verify($recaptchaResponse, $this->getService()->getRequest()->clientIp());

        return $resp->isSuccess();
    }

    /**
     * Create reCaptcha instance if enabled in configuration
     *
     * @return \ReCaptcha\ReCaptcha|null
     */
    protected function _getReCaptchaInstance()
    {
        $domain = RequestParser::getDomain($this->getService()->getRequest());
        $reCaptchaSecret = Configure::read('Api.reCaptcha.' . $domain. '.secret');
        if (!empty($reCaptchaSecret)) {
            return new \ReCaptcha\ReCaptcha($reCaptchaSecret);
        }

        return null;
    }
}
