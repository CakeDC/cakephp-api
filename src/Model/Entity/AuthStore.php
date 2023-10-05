<?php
declare(strict_types=1);

namespace CakeDC\Api\Model\Entity;

use Cake\ORM\Entity;

/**
 * AuthStore Entity
 *
 * @property string $id
 * @property string|null $store
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 */
class AuthStore extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'id' => true,
        'store' => true,
        'created' => true,
        'modified' => true,
    ];
}
