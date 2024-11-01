<?php

require_once("AEFactory.php");
require_once("AEObject.php");

/**
 * Class AECategory
 * @package AEBase
 *
 *  Categoria de productos de aliexpress
 */
class AECategory extends AEObject {

    /**
     * @var Array de lookups inversas de atributos por categoria
     */
    private $reverse_attributes;

    /**
     * @var Array de lookups de nombres de atributos por categoria
     */
    private $category_attributes_names;

    /**
     * @var Identificador de aliexpress
     */
    public $category_id;

    /**
     * @var esquema json de validación de los mensajes de esta categoria
     */
    private $schema;

    /**
     * @var array de productos creados en esta categoria. Se usa para generar el mensaje por lotes
     */
    private $products;

    /**
     * @var array de atributos de categoria definidos por el usuario.
     *      [name1 => value1, name2 => value2, ...]
     */
    public $category_user_attributes;

    /**
     * AECategory constructor.
     * @param $id
     */
    public function __construct($id, $factory)
    {
        $this->factory = $factory;
        $this->category_id = $id;
    }

    public function get_factory() {
        return $this->factory;
    }

    /**
     * @return el|esquema|bool|mixed
     */
    public function get_schema() {
        if(isset($this->schema)) {
            return $this->schema;
        }
        else {
            $schema = $this->factory->get_category_schema($this->category_id);
            try {
                if (strval($schema["properties"]["category_id"]["const"]) == strval($this->category_id)) {
                    $this->schema = $schema;

                    return $schema;
                }
                return false;
            }
            catch(\Exception $e) {
                return false;
            }
        }
    }

    //<editor-fold desc="Attributes names y values"> ---------------------------------------------------------------------------

    /**
     * @param $category_id
     * @return array|bool array de nombres de atributos de la categoria
     */
    public function get_attributes_names() {

        if($this->get_schema()) {
            try {
                $attributes_names = $this->generate_attributes_names();
                $this->category_attributes_names = $attributes_names;
                return $attributes_names;
            }
            catch(\Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * @return bool|array array de atributos de categoria obligatorios
     *      null: no es posible obtener el schema de la categoria
     *      []: no tiene atributos obligatorios
     *      [atttribute_name1, atttribute_name2,...]: atributos obligatorios
     */
    public function get_category_required_attributes() {
        $sche = $this->get_schema();

        $attribs = [];
        if(isset($sche)) {
            $attribs = $sche['properties']["category_attributes"]["required"];
        }
        else
            return false;
        return $attribs;
    }

    /**
     * Obtiene los atributos de categoria que permiten modificar el texto. Normalmente tienen id=4
     *
     * @return array|bool asociativo de la forma key => [valor, free_field]. por ejemplo Patron => [
     *
     */
    public function get_category_free_attribute_value() {
        $sche = $this->get_schema();

        if(isset($sche)) {
            $attribs = [];
            $attrib_schema = $sche["properties"]["category_attributes"]["properties"];
            $keys_attrib = array_keys($sche["properties"]["category_attributes"]["properties"]);
            foreach($keys_attrib as $key) {
                if(isset($attrib_schema[$key]["then"]["required"])) {
                    $attribs[$key] = [
                        "value" => $attrib_schema[$key]["if"]["properties"]["value"]["const"],
                        "free" => $attrib_schema[$key]["properties"]["customValue"]["title"]
                    ];
                }
                else if(isset($attrib_schema[$key]["properties"]["value"]["title"])
                    && !isset($attrib_schema[$key]["properties"]["value"]["oneOf"])
                    && !isset($attrib_schema[$key]["properties"]["value"]["items"]["oneOf"])) {
                    $attribs[$key] = $attrib_schema[$key]["properties"]["value"];
                }
            }
            return $attribs;
        }
        return false;
    }

    /**
     * @return array|bool devuelve un array (attribute_name => [attribute_value => attribute_value_code]
     */
    public function get_category_attributes_values() {
        $sche = $this->get_schema();

        if(isset($sche)) {
            $attribs = [];
            $attrib_schema = $sche['properties']["category_attributes"]["properties"];
            $names = array_keys($attrib_schema);
            foreach($names as $name) {
                $oneOf = null;
                if(isset($attrib_schema[$name]["properties"]["value"]["oneOf"])) {
                    $oneOf = $attrib_schema[$name]["properties"]["value"]["oneOf"];
                }
                else if(isset($attrib_schema[$name]["properties"]["value"]["items"]["oneOf"])) {
                    $oneOf = $attrib_schema[$name]["properties"]["value"]["items"]["oneOf"];
                }
                else if(isset($attrib_schema[$name]["properties"]["unit"]["oneOf"])) {
                    $oneOf = $attrib_schema[$name]["properties"]["unit"]["oneOf"];
                }
                if(isset($oneOf)) {
                    foreach ($oneOf as $one) {
                        $attribs[$name][$one["title"]] = $one["const"];
                    }
                }
                else {
                    $attribs[$name] = $attrib_schema[$name]["properties"]["value"];
                }
            }
            return $attribs;
        }
        else
            return false;
    }

    /**
     * Obtiene el tipo del atributo: object o array
     *
     * @param $category_id
     * @return array|bool
     */
    public function get_attributes_types() {
        $sche = $this->get_schema();

        if(isset($sche)) {
            $attribs = [];
            $attrib_schema = $sche["properties"]["category_attributes"]["properties"];
            $names = array_keys($attrib_schema);
            foreach($names as $name) {
                if(isset($attrib_schema[$name]["properties"]["value"]["type"])) {
                    $attribs[$name] = $attrib_schema[$name]["properties"]["value"]["type"];
                }
            }
            return $attribs;
        }
        else
            return false;
    }


    public function get_required_attributes_default_values() {
        $required = $this->get_category_required_attributes();
        $values = $this->get_category_attributes_values();
        $type = $this->get_attributes_types();
        $free = $this->get_category_free_attribute_value();
        $key_values = array_keys($values);
        foreach($key_values as $key) {
            if(!in_array($key, $required))
                unset($values[$key]);
        }
        $default_values = [];

        foreach($values as $value=>$options) {
            if(isset($options['None']))
                $default_values[$value] = ['None' => $options['None']];
            else if(isset($options['ESNone']))
                $default_values[$value] = ['ESNone' => $options['ESNone']];
            else if(isset($options['No']))
                $default_values[$value] = ['No' => $options['No']];
            else if(isset($options['Other'])) {
                if(isset($free[$value])) {
                    if($type[$value] == 'array')
                        $tmp = ['Other' => [$options['Other'] => [$free[$value] => ['Other']]]];
                    else
                        $tmp = ['Other' => [$options['Other'] => [$free[$value] => 'Other']]];
                }
            }
            else
                $default_values[$value] = null;
        }

        return $default_values;
    }

    /**
     * @return array|bool Array de nombre y valor de atributos de categoria
     */
    public function generate_attributes_names() {
        $json_attributes_names = json_decode($this->factory->wrapper->getAttribute($this->category_id), true);
        $lookup = [];

        if(isset($json_attributes_names["result"]["attributes"]["aeop_attribute_dto"])) {
            $attribs = $json_attributes_names["result"]["attributes"]["aeop_attribute_dto"];
            foreach($attribs as $attrib) {
                $names = json_decode($attrib["names"], true);
                $lookup[$attrib["id"]] = $names["en"];
            }
            return $lookup;
        }
        return false;
    }
    //</editor-fold>

    /**
     * @return mixed obtiene el nombre de la categoria en diferentes idiomas
     */
    public function get_info() {
        return $this->factory->wrapper->getCategoryById($this->category_id);
    }

    /**
     * @return array obtiene las categorias hijas de esta
     */
    public function get_children() {
        $json_children = json_decode($this->factory->wrapper->getCategoryList($this->category_id));

        $children_array = [];
        if(isset($json_children->result->aeop_post_category_list->aeop_post_category_dto)){
            $children = $json_children->result->aeop_post_category_list->aeop_post_category_dto;
            foreach($children as $child) {
                $name = json_decode($child->names, true);
                $children_array[] = [
                    "category_id" => $child->id,
                    "category_name" => $name["es"]
                ];
            }
        }
        return $children_array;
    }

    /**
     * @return array de tipos de unidad de aliexpress: P.e. pieza, docena, litro, kg, etc.
     */
    public function get_category_unit_types() {
        $sche = $this->get_schema();
        $unit_types = [];
        $uts = $sche["properties"]["product_units_type"]["oneOf"];
        foreach($uts as $ut) {
            $unit_types[$ut["title"]] = $ut["const"];
        }
        return $unit_types;
    }

    /**
     * @return AEProduct_Base crea un producto nuevo en esta categoria
     */
    public function create_product() {
        $ae_product = $this->factory->create_product($this->category_id);
        return $ae_product;
    }

    /**
     * @param $product añade el producto a la lista de productos de categoria
     */
    public function add_product($product) {
        $this->products[] = $product;
    }

    public function set_category_user_attributes($user_attributes) {
        $this->category_user_attributes = $user_attributes;
    }

    public function add_category_user_attribute($name, $value) {
        $this->category_user_attributes[$name] = $value;
    }

    public function remove_category_user_attribute($name) {
        unset($this->category_user_attributes[$name]);
    }

    //<editor-fold desc = "Batch processes" >--------------------------------------------------------------------------

    /**
     * @return void propio objeto codificado a json para incorporarse a un mensaje
     */
    public function get_json_string($products_list = null) {
        if(!isset($products_list)) {
            $products_list = $this->products;
        }

        foreach($products_list as $product) {
            if($product->category_id == $this->category_id)
                $json[] = $product->get_json_string();
        }
        return $json;
    }

    public function get_json_batch_array($products_list = null, $add_idAE = null) {
        if(!isset($products_list)) {
            $products_list = $this->products;
        }
        $json = [];
        foreach($products_list as $product) {
            if($product->category_id == $this->category_id) {
                $json_prod_array = json_decode($product->get_json_string(), true);
                if(isset($add_idAE) && $add_idAE)
                    $json_prod_array = array("aliexpress_product_id" => $product->idAE) + $json_prod_array;
                $json[] = [
                    "item_content" => $json_prod_array,
                    "item_content_id" => $product->id
                ];
            }
        }

        return $json;
    }

    public function get_json_batch_update_array($products_list = null, $job_type) {
        if(!isset($products_list)) {
            $products_list = $this->products;
        }
        $json = [];
        foreach($products_list as $product) {
            if($product->category_id == $this->category_id) {
                $json_prod_array = $product->get_json_array_update($job_type);
                $json[] = [
                    "item_content" => $json_prod_array,
                    "item_content_id" => $product->id
                ];
            }
        }

        return $json;
    }

    //</editor-fold>
}

// class AECategoryForecastMode {
//     public const $PRECISIONSAFE = 1;
//     public const $FUZZY = 2;
// }
