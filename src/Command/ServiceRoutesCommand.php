<?php
declare(strict_types=1);

/**
 * Copyright 2016 - 2024, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2016 - 2024, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace CakeDC\Api\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use CakeDC\Api\Service\ServiceRegistry;

/**
 * Provides interactive CLI tools for CakeDC Api routing.
 */
class ServiceRoutesCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'service routes';
    }

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The option parser to update
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);
        $parser->setDescription(__('Display all routes in a service'));
        $parser->addArgument('service', [
            'help' => __('The name of the service to display routes for.'),
            'required' => true,
        ]);

        return $parser;
    }

    /**
     * Display all routes in an application
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $serviceName = $args->getArgument('service');
        $header = ['Route name', 'Method(s)', 'URI template', 'Service', 'Action', 'Plugin'];
        if ($args->getOption('verbose')) {
            $header[] = 'Defaults';
        }

        $service = ServiceRegistry::getServiceLocator()->get($serviceName);
        if ($service === null) {
            $io->error(__('Service "{0}" not found', $serviceName));
            return Command::CODE_ERROR;
        }

        $availableRoutes = $service->routes();

        $output = $duplicateRoutesCounter = [];

        foreach ($availableRoutes as $route) {
            $methods = isset($route->defaults['_method']) ? (array)$route->defaults['_method'] : [''];

            $item = [
                $route->options['_name'] ?? $route->getName(),
                implode(', ', $methods),
                $route->template,
                $route->defaults['controller'] ?? '',
                $route->defaults['action'] ?? '',
                $route->defaults['plugin'] ?? '',
            ];

            if ($args->getOption('verbose')) {
                ksort($route->defaults);
                $item[] = json_encode($route->defaults);
            }

            $output[] = $item;

            foreach ($methods as $method) {
                if (!isset($duplicateRoutesCounter[$route->template][$method])) {
                    $duplicateRoutesCounter[$route->template][$method] = 0;
                }

                $duplicateRoutesCounter[$route->template][$method]++;
            }
        }

        if ($args->getOption('sort')) {
            usort($output, function ($a, $b) {
                return strcasecmp($a[0], $b[0]);
            });
        }

        array_unshift($output, $header);

        $io->helper('table')->output($output);
        $io->out();

        $duplicateRoutes = [];

        foreach ($availableRoutes as $route) {
            $methods = isset($route->defaults['_method']) ? (array)$route->defaults['_method'] : [''];

            foreach ($methods as $method) {
                if (
                    $duplicateRoutesCounter[$route->template][$method] > 1 ||
                    ($method === '' && count($duplicateRoutesCounter[$route->template]) > 1) ||
                    ($method !== '' && isset($duplicateRoutesCounter[$route->template]['']))
                ) {
                    $duplicateRoutes[] = [
                        $route->options['_name'] ?? $route->getName(),
                        $route->template,
                        $route->defaults['plugin'] ?? '',
                        $route->defaults['prefix'] ?? '',
                        $route->defaults['controller'] ?? '',
                        $route->defaults['action'] ?? '',
                        implode(', ', $methods),
                    ];

                    break;
                }
            }
        }

        if ($duplicateRoutes) {
            array_unshift($duplicateRoutes, $header);
            $io->warning('The following possible route collisions were detected.');
            $io->helper('table')->output($duplicateRoutes);
            $io->out();
        }

        return static::CODE_SUCCESS;
    }
}
