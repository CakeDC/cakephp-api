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

namespace CakeDC\Api\Routing;

use Cake\Routing\Route\Route;
use Cake\Routing\Router;

/**
 * Class ApiRouter
 *
 * @package CakeDC\Api\Routing
 */
class ApiRouter extends Router
{
    /**
     * Have routes been loaded
     *
     * @var bool
     */
    public static $initialized = false;

    /**
     * Default route class.
     *
     * @var string
     */
    protected static $_defaultRouteClass = Route::class;

    /**
     * Contains the base string that will be applied to all generated URLs
     * For example `https://example.com`
     *
     * @var string
     */
    protected static $_fullBaseUrl;

    /**
     * Regular expression for action names
     *
     * @var string
     */
    public const ACTION = 'index|show|add|create|edit|update|remove|del|delete|view|item';

    /**
     * Regular expression for years
     *
     * @var string
     */
    public const YEAR = '[12][0-9]{3}';

    /**
     * Regular expression for months
     *
     * @var string
     */
    public const MONTH = '0[1-9]|1[012]';

    /**
     * Regular expression for days
     *
     * @var string
     */
    public const DAY = '0[1-9]|[12][0-9]|3[01]';

    /**
     * Regular expression for auto increment IDs
     *
     * @var string
     */
    public const ID = '[0-9]+';

    /**
     * Regular expression for UUIDs
     *
     * @var string
     */
    public const UUID = '[A-Fa-f0-9]{8}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{4}-[A-Fa-f0-9]{12}';

    /**
     * The route collection routes would be added to.
     *
     * @var \Cake\Routing\RouteCollection
     */
    protected static $_collection;

    /**
     * A hash of request context data.
     *
     * @var array
     */
    protected static $_requestContext = [];

    /**
     * Named expressions
     *
     * @var array
     */
    protected static $_namedExpressions = [
        'Action' => Router::ACTION,
        'Year' => Router::YEAR,
        'Month' => Router::MONTH,
        'Day' => Router::DAY,
        'ID' => Router::ID,
        'UUID' => Router::UUID,
    ];

    /**
     * Maintains the request object stack for the current request.
     * This will contain more than one request object when requestAction is used.
     *
     * @var array
     */
    protected static $_requests = [];

    /**
     * Initial state is populated the first time reload() is called which is at the bottom
     * of this file. This is a cheat as get_class_vars() returns the value of static vars even if they
     * have changed.
     *
     * @var array
     */
    protected static $_initialState = [];

    /**
     * The stack of URL filters to apply against routing URLs before passing the
     * parameters to the route collection.
     *
     * @var array
     */
    protected static $_urlFilters = [];

    /**
     * Default extensions defined with Router::extensions()
     *
     * @var array
     */
    protected static $_defaultExtensions = [];
}
