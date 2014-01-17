<?php

require 'vendor/autoload.php';
require 'app/config.php';


// Bootstrap Eloquent ORM
$container = new \Illuminate\Container\Container();
$connFactory = new \Illuminate\Database\Connectors\ConnectionFactory($container);
$conn = $connFactory->make($settings);
$resolver = new \Illuminate\Database\ConnectionResolver();
$resolver->addConnection('default', $conn);
$resolver->setDefaultConnection('default');
\Illuminate\Database\Eloquent\Model::setConnectionResolver($resolver);

# LOAD CONTROLLERS
require_once 'app/controllers/Response.php';
require_once 'app/controllers/Utilities.php';

# LOAD MODELS
require_once 'app/models/Organization.php';
require_once 'app/models/Role.php';
require_once 'app/models/User.php';
require_once 'app/models/Event.php';

$app = new \Slim\Slim(array(
    'debug' => true,
    'cookies.secret_key' => 'MY_SALTY_PEPPER',
    'cookies.lifetime' => '20 minutes',
    'cookies.cipher' => MCRYPT_RIJNDAEL_256,
    'cookies.cipher_mode' => MCRYPT_MODE_CBC
));

$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => md5(SALT),
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

$authenticate = function ($app) {
            return function () use ($app) {
                        if (!isset($_SESSION['user'])) {
                            $_SESSION['urlRedirect'] = $app->request()->getPathInfo();
                            $app->flash('error', 'Login required');
                            $app->redirect('/login');
                        }
                    };
        };
$authenticateForRole = function ( $role = 'Authenticated' ) {
            return function () use ( $role ) {
                        $role_obj = \User::find(0)->roles()->where('role', '=', $role)->toArray();

                        //die('GOT HERE: '. var_dump($roles));
                        /* if ( $user->belongsToRole($role) === false ) {
                          $app = \Slim\Slim::getInstance();
                          $app->flash('error', 'Login required');
                          } */
                    };
        };

$app->hook('slim.before.dispatch', function() use ($app) {
            $user = null;
            if (isset($_SESSION['user'])) {
                $user = $_SESSION['user'];
            }
            $app->view()->setData('user', $user);
        });

$app->get("/logout", function () use ($app) {
            unset($_SESSION['user']);
            $app->view()->setData('user', null);
        });
$app->post("/login", function () use ($app) {
            $user = $app->request()->headers('Php-Auth-User');
            $pass = md5(md5(SALT) . md5($app->request()->headers('Php-Auth-Pw')));
            $u = new Utilities();
            $field = ($u->isValidEmail($user)) ? 'email' : 'username';
            $user_obj = \User::where($field, '=', $user)->get()->toArray(); //find(0);//::where($field,'=',$user);
            $output = new generateOutput;            
            if (!$user_obj) {
                $output->data = array('system'=>array(
                    'error' => 'User not found!',
                ));
            }
            if ($pass == $user_obj[0]['password']) {
                $output->data = array('system'=>array(
                    'msg' => 'Login successful!',
                ));
                $_SESSION[] = array('user'=>$user_obj[0]['email']);
            } else {
                $output->data = array('system'=>array(
                    'error' => 'Incorrect password!',
                ));
            }
            $output->callback = $app->request()->get('callback');
            echo $output->jsonOut();
        });
$app->get('/organization/get(/:id)', function($id = null) use($app) {
            $output = new generateOutput;
            $output->data = (is_numeric($id)) ? \Organization::find($id) : \Organization::all();
            $output->callback = $app->request()->get('callback');
            echo $output->jsonOut();
        });
$app->get('/user/get(/:id)', $authenticateForRole('admin'), function($id = null) use($app) {
            $output = new generateOutput;
            $output->data = (is_numeric($id)) ? \User::find($id) : \User::all();
            $output->callback = $app->request()->get('callback');
            echo $output->jsonOut();
        });
$app->get('/event/get(/:id)', function($id = null) use($app) {
            $output = new generateOutput;
            $output->data = (is_numeric($id)) ? \Event::find($id) : \Event::all();
            $output->callback = $app->request()->get('callback');
            echo $output->jsonOut();
        });
$app->run();