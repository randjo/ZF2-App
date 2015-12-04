<?php

namespace Auth\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Auth\Form\AuthForm;
use Auth\Model\Auth;
use Zend\Authentication\Result;
use Zend\Authentication\AuthenticationService;
use Zend\Authentication\Storage\Session as SessionStorage;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Authentication\Adapter\DbTable as AuthAdapter;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new viewModel();
    }
    
    public function loginAction()
    {
        $messages = null;
        $form = new AuthForm();
        $form->get('submit')->setValue('Login');
        
        $request = $this->getRequest();
        if ($request->isPost())
        {
            $authFormFilters = new Auth();
            $form->setInputFilter($authFormFilters->getInputFilter());
            $form->setData($request->getPost());
            
            if($form->isValid())
            {
                $data = $form->getData();
                $sm = $this->getServiceLocator();
                $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                $config = $this->getServiceLocator()->get('Config');
                $staticSalt = $config['static_salt'];
                $authAdapter = new AuthAdapter($dbAdapter,
                        'users',//table's name
                        'usr_name',
                        'usr_password',
                        "MD5 (CONCAT('$staticSalt', ?, usr_password_salt)) AND usr_active = 1"
                        );
                $authAdapter
                        ->setIdentity($data['usr_name'])
                        ->setCredential($data['usr_password']);
                $auth = new AuthenticationService();
                $result = $auth->authenticate($authAdapter);

                switch ($result->getCode()) {
                    case Result::FAILURE_IDENTITY_NOT_FOUND:
                            // do stuff for nonexistent identity
                            break;
                    case Result::FAILURE_CREDENTIAL_INVALID:
                            // do stuff for invalid credential
                            break;
                    case Result::SUCCESS:
                            $storage = $auth->getStorage();
                            $storage->write($authAdapter->getResultRowObject(
                                    null,
                                    'usr_password'
                            ));
                            /*$time = 1209600; // 14 days 1209600/3600 = 336 hours => 336/24 = 14 days
//						if ($data['rememberme']) $storage->getSession()->getManager()->rememberMe($time); // no way to get the session
                            if ($data['rememberme']) {
                                    $sessionManager = new \Zend\Session\SessionManager();
                                    $sessionManager->rememberMe($time);
                            }*/
                            
                            break;
                    default:
                            // do stuff for other failure
                            break;
                }
                foreach ($result->getMessages() as $message) {
                    $messages .= "$message\n"; 
                }
                //echo '<pre>';
                //print_r($_SESSION);
                //echo '</pre>';
            }
            else
            {
                //echo 'Form is not valid!';
            }
        }
        
        return new viewModel(array('form' => $form, 'messages' => $messages));
    }
    
    public function logoutAction()
    {
        $auth = new AuthenticationService();
        $auth->clearIdentity();
        
        return $this->redirect()->toRoute('auth/default', array('controller' => 'index', 'action' => 'login'));
    }
}