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

namespace CakeDC\Api\Service\Action\Extension\Auth;

use CakeDC\Api\Service\Action\Extension\Extension;
use CakeDC\Users\Controller\Traits\CustomUsersTableTrait;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;

/**
 * Class UserFormattingExtension
 *
 * @package CakeDC\Api\Service\Action\Extension\Auth
 */
class UserFormattingExtension extends Extension implements EventListenerInterface
{

    use CustomUsersTableTrait;

    /**
     * Returns a list of events this object is implementing. When the class is registered
     * in an event manager, each individual method will be associated with the respective event.
     *
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'Action.Auth.onLoginFormat' => 'onLoginFormat',
            'Action.Auth.onRegisterFormat' => 'onRegisterFormat',
        ];
    }

    /**
     * On Login Format.
     *
     * @param Event $event An Event instance
     * @return EntityInterface
     */
    public function onLoginFormat(Event $event)
    {
        return $this->_userCleanup($event->data['user']);
    }

    /**
     * On Register Format.
     *
     * @param Event $event An Event instance
     * @return EntityInterface
     */
    public function onRegisterFormat(Event $event)
    {
        return $this->_userCleanup($event->data['user']);
    }

    /**
     * @param array $user User data
     * @return EntityInterface
     */
    protected function _userCleanup($user)
    {
        if (empty($user)) {
            return $user;
        }

        $currentUser = $this
            ->getUsersTable()
            ->find()
            ->where(['id' => $user['id']])
            ->first();

        $user = $currentUser->toArray();
        $user['api_token'] = $currentUser['api_token'];

        $cleanup = ['created', 'modified', 'is_superuser', 'role'];
        foreach ($cleanup as $field) {
            unset($user[$field]);
        }

        return $user;
    }
}
