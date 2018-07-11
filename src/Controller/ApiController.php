<?php
/**
 * Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2018, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Controller;

use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
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
        if ($this->components()->has('Auth')) {
            $this->Auth->allow(['process', 'describe', 'listing']);
        }
        if ($this->components()->has('RememberMe')) {
            $this->components()->unload('RememberMe');
        }
    }

    /**
     * Process api request
     *
     * @return \Cake\Http\Client\Response|\Cake\Http\Response|null
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
        $this->request = $this->request->withParam('service', 'listing');
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
        $this->request = $this->request->withParam('service', 'describe');
        $options = [
            'className' => 'CakeDC/Api.Describe'
        ];
        $this->_process($options);
    }

    /**
     * Process api request
     *
     * @param array $options Options
     * @return \Cake\Http\Client\Response|\Cake\Http\Response|null
     */
    protected function _process($options = [])
    {
        $routesInflectorMethod = Configure::read('Api.routesInflectorMethod', 'underscore');

        $this->autoRender = false;
        try {
            if (!empty($this->request->getParam('service'))) {
                $service = $this->request->getParam('service');
                $version = null;
                if (!empty($this->request->getParam('version'))) {
                    $version = $this->request->getParam('version');
                }

                $url = '/' . $service;
                if (!empty($this->request->getParam('pass'))) {
                    $url .= '/' . join('/', $this->request->getParam('pass'));
                }
                $options += [
                    'version' => $version,
                    'request' => $this->request,
                    'response' => $this->response,
                    'baseUrl' => $routesInflectorMethod === false ? $url : Inflector::{$routesInflectorMethod}($url)
                ];
                $options += (new ConfigReader())->serviceOptions($service, $version);
                $Service = ServiceRegistry::getServiceLocator()->get($service, $options);
                $result = $Service->dispatch();

                return $Service->respond($result);
            }
            $this->response = $this->response->withStringBody(__('Service not found'))->withStatus(404);
        } catch (Exception $e) {
            $this->response = $this->response->withStringBody($e->getMessage())->withStatus(400);
        }

        return $this->response;
    }
}
