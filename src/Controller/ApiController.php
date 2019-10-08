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

namespace CakeDC\Api\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Utility\Inflector;
use CakeDC\Api\Service\ConfigReader;
use CakeDC\Api\Service\ServiceRegistry;
use Exception;

class ApiController extends AppController
{
    /**
     * @var \CakeDC\Api\Service\ServiceRegistry
     */
    public $Services;

    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize(): void
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
     * @return \Cake\Http\Response|null
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
    public function listing(): void
    {
        $this->setRequest($this->getRequest()->withParam('service', 'listing'));
        $options = [
            'className' => 'CakeDC/Api.Listing',
        ];
        $this->_process($options);
    }

    /**
     * Process describe api request.
     *
     * @return void
     */
    public function describe(): void
    {
        $this->setRequest($this->getRequest()->withParam('service', 'describe'));
        $options = [
            'className' => 'CakeDC/Api.Describe',
        ];
        $this->_process($options);
    }

    /**
     * Process api request
     *
     * @param array $options Options
     * @return \Cake\Http\Response|null
     */
    protected function _process(array $options = []): ?Response
    {
        $routesInflectorMethod = Configure::read('Api.routesInflectorMethod', 'underscore');

        $this->autoRender = false;
        try {
            if (!empty($this->getRequest()->getParam('service'))) {
                $service = $this->getRequest()->getParam('service');
                $version = null;
                if (!empty($this->getRequest()->getParam('version'))) {
                    $version = $this->getRequest()->getParam('version');
                }

                $url = '/' . $service;
                if (!empty($this->getRequest()->getParam('pass'))) {
                    $url .= '/' . join('/', $this->getRequest()->getParam('pass'));
                }
                $options += [
                    'version' => $version,
                    'request' => $this->getRequest(),
                    'response' => $this->getResponse(),
                    'baseUrl' => $routesInflectorMethod === false ? $url : Inflector::{$routesInflectorMethod}($url),
                ];
                $options += (new ConfigReader())->serviceOptions($service, $version);
                $Service = ServiceRegistry::getServiceLocator()->get($service, $options);
                $result = $Service->dispatch();

                return $Service->respond($result);
            }
            $this->setResponse($this->getResponse()->withStringBody(__('Service not found'))->withStatus(404));
        } catch (Exception $e) {
            $this->setResponse($this->getResponse()->withStringBody($e->getMessage())->withStatus(400));
        }

        return $this->getResponse();
    }
    
    public function notFound()
    {
        return $this->getResponse()->withStatus(404);
    }
}
