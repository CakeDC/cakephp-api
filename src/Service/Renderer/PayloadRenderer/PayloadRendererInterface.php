<?php
/**
 * Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2017, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Service\Renderer\PayloadRenderer;

use Cake\Http\Response;

interface PayloadRendererInterface
{

    /**
     * ...
     *
     * @param \Cake\Http\Response $response Server response.
     * @param array $payload Payload data.
     * @return \Cake\Http\Response
     */
    public function applyToResponse(Response $response, array $payload);

    /**
     * ...
     *
     * @param mixed $data
     * @param array $payload Payload data.
     * @return mixed
     */
    public function applyToResultData($data, array $payload);
}
