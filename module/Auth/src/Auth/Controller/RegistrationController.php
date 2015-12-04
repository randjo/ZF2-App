<?php

namespace Auth\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Auth\Form\RegistrationForm;
use Auth\Form\RegistrationFilter;
use Auth\Model\Auth;
use Auth\Model\UsersTable;
use Zend\Db\ResultSet\ResultSet;
use \Zend\Db\TableGateway\TableGateway;
use Zend\Mail\Message;
use Auth\Form\ForgottenPasswordForm;
use Auth\Form\ForgottenPasswordFilter;

class RegistrationController extends AbstractActionController
{
    
    protected $usersTable;


    public function indexAction()
    {
        $form = new RegistrationForm();
        $form->get('submit')->setValue('Register');
        $request = $this->getRequest();// съдържа данните преди валидация
        if($request->isPost())
        {
            $form->setInputFilter(new RegistrationFilter($this->getServiceLocator()));
            $form->setData($request->getPost());
            if($form->isValid())
            {
               $data = $form->getData();//съдържа данните след валидация
               $data = $this->prepareData($data);
               $auth = new Auth();
               $auth->exchangeArray($data);
               //echo '<pre>';
               //print_r($auth);
               //echo '</pre>';
               /*
               //Manualy, without ServiceManager
               $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
               $resultSetPrototype = new ResultSet();
               $resultSetPrototype->setArrayObjectPrototype(new Auth());
               $tableGateway = new TableGateway('users', $dbAdapter, null, $resultSetPrototype);
               $usersTable = new UsersTable($tableGateway);
               $usersTable->saveUser($auth);
               $userIvan = $usersTable->getUser(5);
               
               //without $userTable
               $rowset = $tableGateway->select(array('usr_id' => 5));
               $user3 = $rowset->current();
               echo '<pre>';
               print_r($user3);
               echo '</pre>';

                */
               //using the ServiceManager
               $this->getUsersTable()->saveUser($auth);
               $this->sendConfirmationEmail($auth);
               $this->flashMessenger()->addMessage($auth->usr_email);
               return $this->redirect()->toRoute('auth/default', array('controller'=>'registration', 'action'=>'registration-success'));
               
            }
        }
        return new viewModel(array('form' => $form));
    }
    
    public function registrationSuccessAction()
    {
        $usr_email = null;
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach($flashMessenger->getMessages() as $key => $value) {
                $usr_email .=  $value;
            }
        }
        return new ViewModel(array('usr_email' => $usr_email));
    }
    
    public function confirmEmailAction()
    {
        $token = $this->params()->fromRoute('id');
        $viewModel = new ViewModel(array('token' => $token));
        try {
            $user = $this->getUsersTable()->getUserByToken($token);
            $usr_id = $user->usr_id;
            $this->getUsersTable()->activateUser($usr_id);
        }
        catch(\Exception $e) {
            $viewModel->setTemplate('auth/registration/confirm-email-error.phtml');
        }
        return $viewModel;
    }
    
    public function forgottenPasswordAction()
    {
        $form = new ForgottenPasswordForm();
        //$form->get('submit')->setValue('Send');
        $request = $this->getRequest();
        if ($request->isPost()){
            $form->setInputFilter(new ForgottenPasswordFilter($this->getServiceLocator()));
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $usr_email = $data['usr_email'];
                $usersTable = $this->getUsersTable();
                $auth = $usersTable->getUserByEmail($usr_email);
                $password = $this->generatePassword(8, 2, 2, 2);
                $auth->usr_password = $this->encriptPassword($this->getStaticSalt(), $password, $auth->usr_password_salt);
//		$usersTable->changePassword($auth->usr_id, $password); - tableDataGateway or
                $usersTable->saveUser($auth); // DataMapper
                $this->sendPasswordByEmail($usr_email, $password);
                $this->flashMessenger()->addMessage($usr_email);
                return $this->redirect()->toRoute('auth/default', array('controller'=>'registration', 'action'=>'password-change-success'));
            }					
        }		
            return new ViewModel(array('form' => $form));			
    }
    
    public function passwordChangeSuccessAction()
    {
        $usr_email = null;
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            foreach($flashMessenger->getMessages() as $key => $value) {
                $usr_email .=  $value;
            }
        }
        return new ViewModel(array('usr_email' => $usr_email));
    }	
    
    public function prepareData($data)
    {
        $data['usr_active'] = 0;
        $data['usr_password_salt'] = $this->generateDynamicSalt();				
        $data['usr_password'] = $this->encriptPassword(
            $this->getStaticSalt(), 
            $data['usr_password'], 
            $data['usr_password_salt']
        );
        $data['usrl_id'] = 2;
        $data['lng_id'] = 1;
//	$data['usr_registration_date'] = date('Y-m-d H:i:s');
        $date = new \DateTime();
        $data['usr_registration_date'] = $date->format('Y-m-d H:i:s');
        $data['usr_registration_token'] = md5(uniqid(mt_rand(), true)); // $this->generateDynamicSalt();
//	$data['usr_registration_token'] = uniqid(php_uname('n'), true);	
        $data['usr_email_confirmed'] = 0;
        return $data;
    }
    
    public function generateDynamicSalt()
    {
        $dynamicSalt = '';
            for ($i = 0; $i < 50; $i++) {
                $dynamicSalt .= chr(rand(33, 126));
            }
        return $dynamicSalt;
    }
    
    public function getStaticSalt()
    {
        $staticSalt = '';
        $config = $this->getServiceLocator()->get('Config');
        $staticSalt = $config['static_salt'];		
        return $staticSalt;
    }
    
    public function encriptPassword($staticSalt, $password, $dynamicSalt)
    {
        return $password = md5($staticSalt . $password . $dynamicSalt);
    }
    
    public function generatePassword($l = 8, $c = 0, $n = 0, $s = 0)
    {
        // get count of all required minimum special chars
        $count = $c + $n + $s;
        $out = '';
        // sanitize inputs; should be self-explanatory
        if(!is_int($l) || !is_int($c) || !is_int($n) || !is_int($s)) {
            trigger_error('Argument(s) not an integer', E_USER_WARNING);
            return false;
        }
        elseif($l < 0 || $l > 20 || $c < 0 || $n < 0 || $s < 0) {
            trigger_error('Argument(s) out of range', E_USER_WARNING);
            return false;
        }
        elseif($c > $l) {
            trigger_error('Number of password capitals required exceeds password length', E_USER_WARNING);
            return false;
        }
        elseif($n > $l) {
            trigger_error('Number of password numerals exceeds password length', E_USER_WARNING);
            return false;
        }
        elseif($s > $l) {
            trigger_error('Number of password capitals exceeds password length', E_USER_WARNING);
            return false;
        }
        elseif($count > $l) {
            trigger_error('Number of password special characters exceeds specified password length', E_USER_WARNING);
            return false;
        }
        // all inputs clean, proceed to build password
        // change these strings if you want to include or exclude possible password characters
        $chars = "abcdefghijklmnopqrstuvwxyz";
        $caps = strtoupper($chars);
        $nums = "0123456789";
        $syms = "!@#$%^&*()-+?";
        // build the base password of all lower-case letters
        for($i = 0; $i < $l; $i++) {
            $out .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        // create arrays if special character(s) required
        if($count) {
            // split base password to array; create special chars array
            $tmp1 = str_split($out);
            $tmp2 = array();
            // add required special character(s) to second array
            for($i = 0; $i < $c; $i++) {
                array_push($tmp2, substr($caps, mt_rand(0, strlen($caps) - 1), 1));
            }
            for($i = 0; $i < $n; $i++) {
                array_push($tmp2, substr($nums, mt_rand(0, strlen($nums) - 1), 1));
            }
            for($i = 0; $i < $s; $i++) {
                array_push($tmp2, substr($syms, mt_rand(0, strlen($syms) - 1), 1));
            }
            // hack off a chunk of the base password array that's as big as the special chars array
            $tmp1 = array_slice($tmp1, 0, $l - $count);
            // merge special character(s) array with base password array
            $tmp1 = array_merge($tmp1, $tmp2);
            // mix the characters up
            shuffle($tmp1);
            // convert to string for output
            $out = implode('', $tmp1);
        }
        return $out;
    }
    
    public function getUsersTable()
    {
        if (!$this->usersTable) {
            $sm = $this->getServiceLocator();
            $this->usersTable = $sm->get('Auth\Model\UsersTable');
        }
        return $this->usersTable;
    }
    
    public function sendConfirmationEmail($auth)
    {
        // $view = $this->getServiceLocator()->get('View');
        $transport = $this->getServiceLocator()->get('mail.transport');
        $message = new Message();
        $this->getRequest()->getServer();  //Server vars
        $message->addTo($auth->usr_email)
            ->addFrom('rangelvmarinov@gmail.com')
            ->setSubject('Please, confirm your registration!')
            ->setBody("Please, click the link to confirm your registration => " . 
                'http://' . 
                $this->getRequest()->getServer('HTTP_HOST') . 
                $this->url()->fromRoute('auth/default', array(
                    'controller' => 'registration', 
                    'action' => 'confirm-email', 
                    'id' => $auth->usr_registration_token)));
        $transport->send($message);
    }
    
    public function sendPasswordByEmail($usr_email, $password)
    {
        $transport = $this->getServiceLocator()->get('mail.transport');
        $message = new Message();
        $this->getRequest()->getServer();  //Server vars
        $message->addTo($usr_email)
            ->addFrom('rangelvmarinov@gmail.com')
            ->setSubject('Your password has been changed!')
            ->setBody("Your password at  " . 
                $this->getRequest()->getServer('HTTP_HOST') .
                ' has been changed. Your new password is: ' .
                $password
            );
        $transport->send($message);		
    }	
}