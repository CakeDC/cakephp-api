<?php
/**
 * Copyright 2016, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Controller;

use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Exception;

class ApiController extends AppController
{

    /**
     * @var ServiceRegistry
     */
    public $Services;

    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['process', 'describe', 'listing']);
        if ($this->components()->has('RememberMe')) {
            $this->components()->unload('RememberMe');
        }
    }

    /**
     * Process api request
     *
     * @return \Cake\Http\Client\Response|\Cake\Network\Response|null
     */
    public function process()
    {
        return $this->_process();
    }

    /**
     * Process listing api request.
     *
     * @return void
     */
    public function listing()
    {
        $this->request['service'] = 'listing';
        $options = [
            'className' => 'CakeDC/Api.Listing'
        ];
        $this->_process($options);
    }

    /**
     * Process describe api request.
     *
     * @return void
     */
    public function describe()
    {
        $this->request['service'] = 'describe';
        $options = [
            'className' => 'CakeDC/Api.Describe'
        ];
        $this->_process($options);
    }

    /**
     * Process api request
     *
     * @param array $options Options
     * @return \Cake\Http\Client\Response|\Cake\Network\Response|null
     */
    protected function _process($options = [])
    {
        $this->autoRender = false;
        try {
            if (!empty($this->request['service'])) {
                $service = $this->request['service'];
                $version = null;
                if (!empty($this->request['version'])) {
                    $version = $this->request['version'];
                }
                $options += [
                    'version' => $version,
                    'controller' => $this,
                ];
                $options += (new ConfigReader())->serviceOptions($service, $version);
                $Service = ServiceRegistry::get($service, $options);
                $result = $Service->dispatch();

                return $Service->respond($result);
            }
            $this->response->statusCode(404);
            $this->response->body(__('Service not found'));
        } catch (Exception $e) {
            $this->response->statusCode(400);
            $this->response->body($e->getMessage());
        }

        return $this->response;
    }
}
