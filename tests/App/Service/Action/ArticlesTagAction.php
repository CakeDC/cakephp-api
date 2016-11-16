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

namespace CakeDC\Api\Test\App\Service\Action;

use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\CrudAction;
use Cake\Network\Exception\NotImplementedException;
use Cake\Validation\Validator;

class ArticlesTagAction extends CrudAction
{

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->Auth->allow($this->name());
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates()
    {
        $validator = new Validator();
        $validator
            ->requirePresence('tag_id', 'create')
            ->notEmpty('tag_id');
        $errors = $validator->errors($this->data());
        if (!empty($errors)) {
            throw new ValidationException(__('Validation failed'), 0, null, $errors);
        }

        return true;
    }

    public function execute()
    {
        throw new NotImplementedException('It will never thrown as action is defined');
    }

    /**
     * Execute action.
     *
     * @param int $tag_id the tag id
     * @return mixed
     */
    public function action($tag_id)
    {
        return true;
    }
}
