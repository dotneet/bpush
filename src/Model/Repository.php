<?php
namespace BPush\Model;

trait Repository
{
    public function transform($row)
    {
        if ( !$row ) {
            return $row;
        }
        return $this->newTransformObject($row);
    }

    public function transformRows($rows)
    {
        $result = [];
        foreach ( $rows as $row ) {
            $result[] = $this->newTransformObject($row);
        }
        return $result;
    }

    protected function newTransformObject($row)
    {
        global $app;
        $ns = "\\BPush\\Model\\";
        $className = $ns . $this->transformClassName;
        return new $className($app, $row);
    }
}

