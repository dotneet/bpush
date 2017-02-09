<?php
namespace BPush\Model;

class RepositoryFactory
{
    private $repositoryCache = array();

    public function __construct($app) {
        $this->app = $app;
    }

    public function __get($name)
    {
        if ( isset($this->repositoryCache[$name]) ) {
            return $this->repositoryCache[$name];
        }
        $ns = "\\BPush\\Model\\";
        $className = $ns . ucfirst($name) . 'Repository';
        $repository = new $className($this->app);
        $this->repositoryCache[$name] = $repository;

        return $repository;
    }
}

