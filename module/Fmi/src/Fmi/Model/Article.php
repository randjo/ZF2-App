<?php
namespace Cms;

class Article
{
    protected $title;


    public function getTitle()
    {
        return $this->title;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }
}