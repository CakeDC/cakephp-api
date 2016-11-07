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

namespace CakeDC\Api\Service\Action;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\CrudService;
use CakeDC\Api\Service\ServiceRegistry;
use Cake\Validation\Validator;

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
     * @return bool|array
     */
    public function validates()
    {
        $validator = new Validator();
        $validator
            ->requirePresence('service', 'create')
            ->notEmpty('service');
        $errors = $validator->errors($this->data());
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
        $serviceName = $this->data()['service'];
        $service = ServiceRegistry::get($serviceName);
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
            $service->controller($this->service()->controller());

            return $action->execute();
        }

        return [];
    }
}
