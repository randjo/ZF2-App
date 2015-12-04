<?php
namespace Auth\Form;

use Zend\Form\Form;

class AuthForm extends Form
{
    public function __construct($name = null)
    {
        parent::__construct('auth');
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name' => 'usr_name',
            'attributes' => array(
                'type' => 'text',
            ),
            'options' => array(
                'label' => 'Username',
            ),
        ));
        
        $this->add(array(
            'name' => 'usr_password',
            'attributes' => array(
                'type' => 'password',
            ),
            'options' => array(
                'label' => 'Password',
            ),
        ));
        
        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Go',
                'id' => 'submitbutton',
            ),
        ));
        
        $this->add(array(
            'name' => 'forgotten',
            'type' => 'Zend\Form\Element\Button',
            'attributes' => array(
                'id' => 'forgotten',
            ),
            'options' => array(
                'label' => 'Forgotten password',
            ),
        ));
    }
}