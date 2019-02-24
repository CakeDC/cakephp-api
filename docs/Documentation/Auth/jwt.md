 ## Json Web Token Authentication tutorial
 
 * I take the JWT auth of [Admad cakephp-jwt-auth](https://github.com/ADmad/cakephp-jwt-auth) JwtAuthenticate.php file
 and modified some lines to adapter for this plugin, The good reason for do this is for to be used in
 Frontend JavaScript Frameworks like, [VueJS](https://vuejs.org/v2/guide/), [Angular](https://angular.io/docs), 
 [React](https://reactjs.org/) and others.
 
 ### Configuration
 
1. First before do this, I hope that you read obout [Installation](../installation.md) and [configuration](../configuration.md).
The first step is modified the config file in ``config/api.php``
 
     ```php
         <?
         return [
          'Users' => [
              // token expiration,
              //  - 3600 seconds
              //  - 24 hours
              //  - 31 days
              'Token' => [
                  'expiration' => 3600 * 24 * 31
              ],
             'Api' => [
                 // if service class is not defined we use crud fallback service
                 'ServiceFallback' => '\\CakeDC\\Api\\Service\\FallbackService',
                 // response rendered as JSend
                 'renderer' => 'CakeDC/Api.JSend',
                 // Data parse from cakephp request object
                 'parser' => 'CakeDC/Api.Form',
         
                 //routes inflector: specify underscore, dasherize, or false for neither/no inflection
                 'routesInflectorMethod' => false,
         
                 // version is not used
                 'useVersioning' => false,
                 'versionPrefix' => 'v',
         
                 // auth permission uses require auth strategy
                 'Auth' => [
                     'Crud' => [
                         'default' => 'auth'
                     ],
                 ],
         
                 'Service' => [
                     'default' => [
                         'options' => [],
                         'Action' => [
                             'default' => [
                                 //auth configuration
                                 'Auth' => [
                                     'storage' => 'Memory',
                                     'authorize' => [
                                         'CakeDC/Api.SimpleRbac'
                                     ],
                                     'authenticate' => [
                                         'CakeDC/Api.Jwt' => [
                                             'userModel' => 'CakeDC/Users.Users',
                                             'require_ssl' => false,
                                         ],
                                     ],
                                 ],
                                 // default app extensions
                                 'Extension' => [
                                     // allow request from other domains
                                     'CakeDC/Api.Cors',
                                     // enable sort
                                     'CakeDC/Api.Sort',
                                     // load Hateoas
                                     'CakeDC/Api.CrudHateoas',
                                     // enable relations
                                     'CakeDC/Api.CrudRelations',
                                 ]
                             ],
                             // all index actions configuration
                             'Index' => [
                                 'Extension' => [
                                     // enable pagination for index actions
                                     'CakeDC/Api.Paginate',
                                 ],
                             ],
                         ],
                     ],
                 ],
                 'Log' => [
                     'className' => 'File',
                     'scopes' => ['api'],
                     'levels' => ['error', 'info'],
                     'file' => 'api.log',
                 ]
             ]
         ];
     ```

2. Extend the **AuthService** in `src/Service/Action/.php`
    ````php
        <?
        namespace App\Service;
        
        use App\Service\Action\Auth\LoginAction;
        use CakeDC\Api\Service\AuthService as ApiAuthService;
        
        /**
         * Class AuthService
         *
         * @package CakeDC\Api\Service
         */
        class AuthService extends ApiAuthService
        {
        
            /**
             * @inheritdoc
             * @return void
             */
            public function initialize()
            {
                parent::initialize();
                $methods = ['method' => ['POST'], 'mapCors' => true];
                $this->mapAction('login', LoginAction::class, $methods);
            }
        }
    ````

3. Extend and Rewriting the **LoginAction** in `src/Service/Action/Auth/LoginAction.php`
    ```php
        <?
        namespace App\Service\Action\Auth;
        
        use CakeDC\Api\Service\Action\Auth\LoginAction as ApiLogin;
        use CakeDC\Users\Controller\Component\UsersAuthComponent;
        use CakeDC\Users\Exception\UserNotFoundException;
        use Cake\Core\Configure;
        use Cake\Utility\Security;
        use Firebase\JWT\JWT;
        
        class LoginAction extends ApiLogin
        {
            /**
             * Login JWT action rewrite
             *
             * @return mixed|void
             */
            public function execute()
            {
                $socialLogin = false;
                $event = $this->dispatchEvent(UsersAuthComponent::EVENT_BEFORE_LOGIN);
                if (is_array($event->result)) {
                    $user = $this->_afterIdentifyUser($event->result);
                } else {
                    $user = $this->Auth->identify();
                    $user = $this->_afterIdentifyUser($user, $socialLogin);
                }
                if (empty($user)) {
                    throw new UserNotFoundException(__d('CakeDC/Api', 'User not found'), 401);
                }
        
                $result = [
                    'success' => true,
                    'data' => [
                        'token' => JWT::encode(
                            [
                                'username' => $user['username'],
                                'email' => $user['email'],
                                'name' => $user['first_name'],
                                'sub' => $user['id'],
                                'exp' => time() + Configure::read('Users.Token.expiration')
                            ],
                            Security::getSalt()
                        )
                    ],
                    '_serialize' => ['success', 'data']
                ];
        
                return $result;
            }
        
        }
    ```
4. Extend and Rewriting the **RegisterAction** in `src/Service/Action/Auth/RegisterAction.php`
    ```php
       <?
       namespace App\Service\Action\Auth;
       
       use CakeDC\Api\Exception\ValidationException;
       use CakeDC\Api\Service\Action\Auth\RegisterAction as ApiRegister;
       use CakeDC\Users\Controller\Component\UsersAuthComponent;
       use Cake\Core\Configure;
       use Cake\Datasource\EntityInterface;
       use Cake\Utility\Security;
       use Firebase\JWT\JWT;
       
       class RegisterAction extends ApiRegister
       {
           /**
            * {@inheritdoc}
            */
           public function execute()
           {
               $usersTable = $this->getUsersTable();
               $user = $usersTable->newEntity();
               $options = $this->_registerOptions();
               $requestData = $this->getData();
               $event = $this->dispatchEvent(UsersAuthComponent::EVENT_BEFORE_REGISTER, [
                   'usersTable' => $usersTable,
                   'options' => $options,
                   'userEntity' => $user,
               ]);
       
               if ($event->result instanceof EntityInterface) {
                   if ($userSaved = $usersTable->register($user, $event->result->toArray(), $options)) {
                       return $this->_afterRegister($userSaved);
                   }
               }
               if ($event->isStopped()) {
                   return false;
               }
               $userSaved = $usersTable->register($user, $requestData, $options);
               if (!$userSaved) {
                   throw new ValidationException(__d('CakeDC/Api', 'The user could not be saved'), 0, null, $user->getErrors());
               }
       
               return $this->_afterRegister($userSaved);
           }
       
           /**
            * Prepare flash messages after registration, and dispatch afterRegister event
            *
            * @param EntityInterface $userSaved User entity saved
            * @return EntityInterface
            */
           protected function _afterRegister(EntityInterface $userSaved)
           {
               $validateEmail = (bool)Configure::read('Users.Email.validate');
               $message = __d('CakeDC/Api', 'You have registered successfully, please log in');
               if ($validateEmail) {
                   $message = __d('CakeDC/Api', 'Please validate your account before log in');
               }
               $event = $this->dispatchEvent(UsersAuthComponent::EVENT_AFTER_REGISTER, [
                   'user' => $userSaved
               ]);
               if ($event->result instanceof EntityInterface) {
                   $userSaved = $event->result;
               }
       
               $event = $this->dispatchEvent('Action.Auth.onRegisterFormat', ['user' => $userSaved]);
               if ($event->result) {
                   $userSaved = $event->result;
               }
       
               $result = [
                   'message' => $message,
                   'success' => true,
                   'data' => [
                       'token' => JWT::encode(
                           [
                               'username' => $userSaved['username'],
                               'email' => $userSaved['email'],
                               'name' => $userSaved['first_name'],
                               'sub' => $userSaved['id'],
                               'exp' => time() + Configure::read('Users.Token.expiration')
                           ],
                           Security::getSalt()
                       )
                   ],
                   '_serialize' => ['message', 'success', 'data']
               ];
       
               return $result;
           }
       }
    ```
    
 
 
 
 