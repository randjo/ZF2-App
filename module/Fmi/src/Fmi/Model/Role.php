<?php
namespace Cms;

class Role
{
    protected $name1;
    
    public function getName()
    {
        return $this->name1;
    }
    
    public function setName($name)
    {
        $this->name1 = $name;
    }
}