<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;

/**
 * Intended DAG — NOT as-built. Extract from the as-built IMPORTS matrix (2026-08-14).
 *
 * Layer model (split by ROLE, not folder nesting):
 *   Service (hub)          service classes + registries + ConfigReader
 *   Action                 action classes (Auth/, Collection/, Recaptcha/,
 *                          Traits/, Result/). Auth\ is folder organization,
 *                          not a layer.
 *   ActionExtension        action-level extensions: filters, paginators, sort,
 *                          hateoas, relations, cursor, cors (Service\Action\Extension\*)
 *   ServiceExtension       service-level extensions: features FOR the service
 *                          (Service\Extension\*: Collection, Log, OptionsHandler)
 *   ServiceAuth            Auth component + 2FA checkers (used by actions)
 *   ServiceRenderer        renderers (strategies of the hub)
 *   ServiceParser          request parsers (strategies of the hub)
 *   ServiceLocator         locator infra
 *   Webauthn / Transformer / Model / Contract   foundation leaves
 *
 * Note: ServiceExtension -> Action is a real edge — CollectionExtension registers
 * bulk actions (AddEditAction/DeleteAction), OptionsHandlerExtension uses
 * DummyAction/Result. A service extension references the actions it adds.
 *
 * Direction & classification:
 *   - Hub -> strategy is allowed. Strategy -> Service back-references are BY DESIGN
 *     where the component needs the request/response/parser (no other way) or locates
 *     Service instances (ServiceLocator, like Cake's TableLocator). Narrow ports live
 *     in the leaf `Contract` layer (ServiceContextInterface), which renderers/parsers
 *     depend on instead of the concrete hub.
 *   - Action -> Service (and back) is ACCEPTED hub-and-spoke coupling, NOT a
 *     hidden cycle: the Service hub's full public surface IS the contract actions code
 *     against. Thousands of custom actions in real projects call arbitrary Service
 *     methods (getTable, getResult, respond, ...); a narrow port would be a god-interface
 *     or an incomplete lie. This bidirectional edge is deliberate and documented, so the
 *     only red left is the true sibling cycle below.
 *
 * Expected CATCH (red, decisions documented in the ruleset):
 *   - ServiceAuth -> Action  Service\Auth\Auth holds an Action while Action
 *     holds an Auth — the one true sibling cycle. NOTE: Auth itself is legacy
 *     (CakePHP 2.x AuthComponent skeleton, allow()/allowedActions never consumed,
 *     no authenticate() method) — candidate for removal.
 *
 * External/framework classes (Cake\*, CakeDC\Users, Authentication\*, lcobucci,
 * web-auth) are always allowed and are NOT registered as layers.
 */
return Architecture::define()
    // Foundation / leaves — order matters: specific first, first match wins.
    // ->layerPattern('Exception', [
        // '/^CakeDC\\\\Api\\\\Exception\\\\/',
        // '/^CakeDC\\\\Api\\\\Service\\\\Exception\\\\/',
    // ])
    // ->layerPattern('Util', [
        // '/^CakeDC\\\\Api\\\\Utility\\\\/',
        // '/^CakeDC\\\\Api\\\\Routing\\\\/',
        // '/^CakeDC\\\\Api\\\\Service\\\\Utility\\\\/',
    // ])
    ->layerPattern('Contract', '/^CakeDC\\\\Api\\\\Contract\\\\/')
    ->layerPattern('Model', '/^CakeDC\\\\Api\\\\Model\\\\/')
    ->layerPattern('Transformer', '/^CakeDC\\\\Api\\\\Transformer\\\\/')
    ->layerPattern('Webauthn', '/^CakeDC\\\\Api\\\\Webauthn\\\\/')
    // Service module — split by ROLE, not by folder nesting:
    //   Service        = hub: service classes + registries + ConfigReader
    //   Action         = ALL action classes (incl. Auth/, Collection/, Recaptcha/,
    //                    Traits/, Result/). Auth\ is not a separate layer — it is
    //                    just folder organization of action classes.
    //   ActionExtension = action-level extensions (Service\Action\Extension\*)
    //   ServiceExtension = service-level extensions (Service\Extension\*)
    //   ServiceAuth    = Auth component + 2FA checkers (used BY actions).
    //   ServiceRenderer / ServiceParser / ServiceLocator = strategies of the hub.
    //   ServiceExtension = legacy Service-level extensions (Service\Extension\*).
    // The broad 'Service' glob must exclude its sub-namespaces, otherwise
    // resolveAll() assigns every Service\* class to BOTH its specific layer and
    // 'Service', collapsing the split into one layer.
    ->layerPattern('ServiceLocator', '/^CakeDC\\\\Api\\\\Service\\\\Locator\\\\/')
    ->layerPattern('ServiceParser', '/^CakeDC\\\\Api\\\\Service\\\\RequestParser\\\\/')
    ->layerPattern('ServiceRenderer', '/^CakeDC\\\\Api\\\\Service\\\\Renderer\\\\/')
    ->layerPattern('ServiceAuth', '/^CakeDC\\\\Api\\\\Service\\\\Auth\\\\/')
    // Two DISTINCT extension roles:
    //   ActionExtension   = features FOR actions (filters, paginators, sort,
    //                       hateoas, relations, cursor, cors)
    //   ServiceExtension  = features FOR the service (Collection, Log, OptionsHandler)
    ->layerPattern('ActionExtension', '/^CakeDC\\\\Api\\\\Service\\\\Action\\\\Extension\\\\/')
    ->layerPattern('ServiceExtension', '/^CakeDC\\\\Api\\\\Service\\\\Extension\\\\/')
    ->layerPattern('Action', '/^CakeDC\\\\Api\\\\Service\\\\Action\\\\/', [
        '/^CakeDC\\\\Api\\\\Service\\\\Action\\\\Extension\\\\/',
    ])
    ->layerPattern('Service', '/^CakeDC\\\\Api\\\\Service\\\\/', [
        '~^CakeDC\\\\Api\\\\Service\\\\(Action|Auth|Extension|Renderer|RequestParser|Locator|Exception|Utility)\\\\~',
    ])
    // Delivery / wiring.
    ->layerPattern('Rbac', '/^CakeDC\\\\Api\\\\Rbac\\\\/')
    ->layerPattern('Middleware', '/^CakeDC\\\\Api\\\\Middleware\\\\/')
    ->layerPattern('Controller', '/^CakeDC\\\\Api\\\\Controller\\\\/')
    ->layerPattern('Command', '/^CakeDC\\\\Api\\\\Command\\\\/')
    ->layerPattern('TestSuite', '/^CakeDC\\\\Api\\\\TestSuite\\\\/')
    ->layerPattern('Init', '/^CakeDC\\\\Api\\\\ApiInitializer$/')
    ->layerPattern('Plugin', [
        '/^CakeDC\\\\Api\\\\ApiPlugin$/',
        '/^CakeDC\\\\Api\\\\Plugin$/',
    ])
    ->ruleset([
        // Foundation.
        // 'Exception' => [],
        // 'Util' => ['Exception'],
        'Contract' => [],
        'Model' => [],
        'Transformer' => ['Model'],
        'Webauthn' => ['Model', 'Transformer'],
        // Service hub sits ABOVE its strategies (renderer/parser/extensions/locator/auth/actions).
        // Hub -> component is the allowed direction. The inverse back-reference
        // (component holds the concrete Service) is left RED as CATCH:
        //   components should take a narrow ServiceContextInterface, not the concrete hub.
        'Service' => [
            'Action', 'ActionExtension', 'ServiceAuth', 'ServiceExtension',
            'ServiceRenderer', 'ServiceParser', 'ServiceLocator',
            'Contract', 'Exception', 'Model', 'Transformer', 'Webauthn',
        ],
        'Action' => ['Service', 'ServiceAuth', 'ServiceExtension', 'Transformer', 'Exception', 'Webauthn', 'Model', 'Contract'],
        'ActionExtension' => ['Action', 'Contract', 'Exception', 'Model', 'Transformer', 'Webauthn'],
        'ServiceAuth' => [],
        'ServiceExtension' => ['Action', 'Service'],
        'ServiceRenderer' => ['Action', 'Contract'],
        'ServiceParser' => ['Contract'],
        'ServiceLocator' => ['Service'], // by design: locator locates Service instances, like Cake's TableLocator
        // Delivery / wiring — entry layers reach down into the hub.
        'Rbac' => [],
        'Middleware' => ['Service', 'ServiceLocator'],
        'Controller' => ['Service'],
        'Command' => ['Service'],
        'TestSuite' => ['Service'],
        'Init' => ['Rbac', 'Service'],
        'Plugin' => ['Command', 'Middleware', 'Service'],
    ]);
