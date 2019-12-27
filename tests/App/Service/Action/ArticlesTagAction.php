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

namespace CakeDC\Api\Test\App\Service\Action;

use Cake\Http\Exception\NotImplementedException;
use Cake\Validation\Validator;
use CakeDC\Api\Exception\ValidationException;
use CakeDC\Api\Service\Action\CrudAction;

class ArticlesTagAction extends CrudAction
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->Auth->allow($this->getName());
    }

    /**
     * Apply validation process.
     *
     * @return bool
     */
    public function validates(): bool
    {
        $validator = new Validator();
        $validator
            ->requirePresence('tag_id', 'create')
            ->notEmptyString('tag_id');
        $errors = $validator->validate($this->getData());
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
    // @codingStandardsIgnoreStart
    public function action($tag_id)
    {
        return true;
    }
}
