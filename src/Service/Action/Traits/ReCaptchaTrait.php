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
        $domain = RequestParser::getDomain($this->getService()->getRequest());
        $reCaptcha = Configure::read('Api.reCaptcha.' . $domain);
        if ($reCaptcha['disabled'] ?? false) {
            return true;
        }

        $recaptcha = $this->_getReCaptchaInstance();
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
