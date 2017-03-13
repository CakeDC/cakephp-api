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

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace CakeDC\Api\Service\Auth\Authorize;

use CakeDC\Api\Service\Action\Action;
use Cake\Core\InstanceConfigTrait;
use Cake\Network\Request;

/**
 * Abstract base authorization adapter for Api Auth.
 */
abstract class BaseAuthorize
{

    use InstanceConfigTrait;

    /**
     * ComponentRegistry instance for getting more components.
     *
     * @var \CakeDC\Api\Service\Action\Action
     */
    protected $_action;

    /**
     * Default config for authorize objects.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Constructor
     *
     * @param Action $action An Action instance.
     * @param array $config An array of config. This class does not use any config.
     */
    public function __construct(Action $action, array $config = [])
    {
        $this->setAction($action);
        $this->setConfig($config);
    }

    /**
     * Checks user authorization.
     *
     * @param array $user Active user data
     * @param \Cake\Network\Request $request Request instance.
     * @return bool
     */
    abstract public function authorize($user, Request $request);

    /**
     * Action setter.
     *
     * @param Action $action An Action instance.
     */
    public function setAction(Action $action)
    {
        $this->_action = $action;
    }
}
