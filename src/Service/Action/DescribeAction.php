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

namespace CakeDC\Api\Service\Action;

use Cake\Validation\Validator;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\Service\ServiceRegistry;

/**
 * Class DescribeAction
 *
 * @package CakeDC\Api\Service\Action
 */
class DescribeAction extends Action
{
    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        $validator = new Validator();
        $validator
            ->requirePresence('service', 'create')
            ->notBlank('service');
        $errors = $validator->validate($this->getData());
        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed'), 0, null, $errors);
        }

        return true;
    }

    /**
     * Describe service.
     * For services that inherited from CrudService it provides action description using CrudDescribeAction.
     *
     * @return mixed
     */
    public function execute()
    {
        $serviceName = $this->getData()['service'];
        $service = ServiceRegistry::getServiceLocator()->get($serviceName);
        if ($service instanceof CrudService) {
            $route = [
                'plugin' => null,
                'controller' => $serviceName,
                'action' => 'describe',
                '_method' => 'OPTIONS',
                'pass' => [],
                'map' => [],
                '_matchedRoute' => '/' . $serviceName,
            ];
            $action = $service->buildActionClass('\CakeDC\Api\Service\Action\CrudDescribeAction', $route);
            $service->setRequest($this->getService()->getRequest());
            $service->setResponse($this->getService()->getResponse());

            return $action->execute();
        }

        return [];
    }
}
