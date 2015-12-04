<?php
namespace Auth;
return array(
    'static_salt' => 'aFGQ475SDsdfsaf2342',
    'controllers' => array(
        'invokables' => array(
            'Auth\Controller\Index' => 'Auth\Controller\IndexController',
            'Auth\Controller\Registration' => 'Auth\Controller\RegistrationController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'auth' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/auth',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Auth\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[a-zA-Z0-9]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_map' => array(
//           'layout/rangel' => __DIR__ . '/../view/layout/rangel.phtml',		
        ),
        'template_path_stack' => array(
            'auth' => __DIR__ . '/../view'
        ),	
        'display_exceptions' => true,
    ),
);