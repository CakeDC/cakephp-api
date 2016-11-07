## Authentication and Authorization.

Auth system was inspired from cakephp framework auth component, and follow ideas that put into this system.


## Auth Configuration

Auth configuration should be defined as part of current Action configuration module.
The default strategy for reading Action configuration described in previous step. 

So global Auth configuration one should put into  `Api.Service.default.Action.default.Auth`.

* `allow` - defines list of allowed for current service actions (could be action name, array of names, or ```'*'```).
* `authorize` - defines what authorizes should be loaded to Authorize access.
* `authenticate` - defines authentication strategy.
 
 Example: 
 
 ```php
        'Auth' => [
            'allow' => '*', //public access
            'authorize' => [
                'CakeDC/Api.Crud' => []
            ],
            'authenticate' => [
                'CakeDC/Api.Token' => [
                    'require_ssl' => false,
                ]
            ],
        ], 
 ```
 ### TokenAuthenticate
 
#### Setup
 
 TokenAuthenticate default configuration is
 ```php
     protected $_defaultConfig = [
         //type, can be either querystring or header
         'type' => self::TYPE_QUERYSTRING,
         //name to retrieve the api key value from
        'name' => 'token',
        //db table where the key is stored
        'table' => 'users',
        //db field where the key is stored
        'field' => 'api_token',
         //require SSL to pass the token. You should always require SSL to use tokens for Auth
         'require_ssl' => true,
     ];
 ```
 
 We are using query strings for passing the api_token token. And we require SSL by default.
 Note you can override these options, passing settings in Auth configuration for TokenAuthenticate.
 
 
 ### Simple Rbac Authorize.
 
 Simple Rbac Authorize is based on CakeDC Users plugin Simple Rbac Authorize with modification to api structure. 
 
 #### Permission rules syntax
  
 * Rules are evaluated top-down, first matching rule will apply
 * Each rule is defined:
 ```php
 [
     'role' => 'REQUIRED_NAME_OF_THE_ROLE_OR_[]_OR_*',
     'version' => 'OPTIONAL_VERSION_USED_OR_[]_OR_*_DEFAULT_NULL',
     'service' => 'REQUIRED_NAME_OF_THE_SERVICE_OR_[]_OR_*'
     'action' => 'REQUIRED_NAME_OF_ACTION_OR_[]_OR_*',
     'allowed' => 'OPTIONAL_BOOLEAN_OR_CALLABLE_OR_INSTANCE_OF_RULE_DEFAULT_TRUE'
 ]
 ```
 * If no rule allowed = true is matched for a given user role and url, default return value will be false
 