<?php
namespace Cms;

class User
{
    protected $name;
    protected $password;
    protected $email;
    protected $role;
    
    public function getName()
    {
        return $this->$name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    function getPassword() {
        return $this->password;
    }
    
    function setPassword($password) {
        $this->password = $password;
    }

    function getEmail() {
        return $this->email;
    }
    
    function setEmail($email) {
        $this->email = $email;
    }

    function getRole() {
        return $this->role;
    }
    
    function setRole($role) {
        $this->role = $role;
    }
}