<?php
/**
 * @class getSet
 * @namespace AEBase\GetterSetter
 *
 *  Clase para simular propiedades sobre la marcha
 *
 *  @author Emilio
 *
 *  @since 0.0.1
 */

class getSet {

    private $vars;

    public function __construct(){
        $vars = array();
    }

    public function __call($name, $arguments)
    {
        if(substr($name,0,3)=="get"){
            $variable=strtolower(substr($name,3));
            if($v = $this->getPropertyValue($variable))
                return $v;
            if(isset($this->vars[$variable]))
                return $this->vars[$variable];
        }

        if(substr($name,0,3)=="set"){
            $variable=strtolower(substr($name,3));
            if($v = $this->getPropertyValue($variable)){
                $this->{$variable} = $arguments[0];
                return;
            }
            $this->vars[$variable] = $arguments[0];

        }

    }

    private function getPublicProperties()
    {
        $ret = array();
        $reflection = new ReflectionObject($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $ret[] = $property->getName();
        }
        return $ret;
    }

    private function getPropertyValue($name){
        $props = $this->getPublicProperties();
        if(in_array($name,$props)){
            return $this->{$name};
        }
    }

    public function getVars(){
        return $this->vars;
    }

}

