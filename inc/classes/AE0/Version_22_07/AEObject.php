<?php
/**
 * @class AEObject
 *
 * Clase básica con las propiedades comunes
 *
 * @author Roberto
 *
 * @since 0.0.1
 */


include("GetterSetter/GetSet.php");

abstract class AEObject extends getSet {

    public function __construct()
    {
    }

    /**
     * @var AEFactory Instancia de AEEactory global
     */
    protected $factory;

    /**
     * @return void propio objeto codificado a json para incorporarse a un mensaje
     */
    public abstract function get_json_string($parameters);

}