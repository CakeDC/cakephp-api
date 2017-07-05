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

use Cake\Core\InstanceConfigTrait;
use Cake\Http\Response;

class HateoasRenderer implements PayloadRendererInterface
{

    use InstanceConfigTrait;

    /**
     * Default configuration.
     * - ...
     *
     * @var array
     */
    protected $_defaultConfig = [
        // e.g. headers names
    ];

    /**
     * Constructor.
     *
     * @param array $config Config array.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * {@inheritDoc}
     */
    public function applyToResponse(Response $response, array $payload)
    {
        if (empty($payload['links'])) {
            return $response;
        }
        $links = $payload['links'];

//Link: <http://localhost:8888/api/items?page=1>; rel=self, <http://localhost:8888/api/items?page=2>; rel=next, <http://localhost:8888/api/items?page=29>; rel=last
//        $response = $response->withHeader('Link', $this->_formatLinks($links));

        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function applyToResultData($data, array $payload)
    {
        return $data;
    }
}
