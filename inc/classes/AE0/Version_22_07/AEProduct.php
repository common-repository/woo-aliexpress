<?php

require_once ("AECategory.php");
require_once ("AEObject.php");
require_once ("AEFactory.php");
require_once ("AECombination.php");
require_once ("AEJob.php");

/**
 * Class AEProduct_Base
 * @package AEBase
 *
 * Clase responsable de generar los mensajes json de producto en el envío por lotes
 */
class AEProduct_Base extends AEObject
{
    //<editor-fold desc="Variables publicas"> -------------------------------------------------------------------------
    /**
     * @var identificador de producto de aliexpress. Si el producto es nuevo, no temndrá IdAE hasta que sea enviado
     * en un mensaje.
     */
    public $idAE;

    /**
     * @var identificador de producto interno
     */
    public $id;

    /**
     * @var identificador de categoría de aliexpress
     */
    public $category;
    public $category_id;

    /**
     * @var nombre/titulo del producto
     */
    public $name;

    /**
     * @var descripción larga del producto. Puede contener html
     */
    public $description;

    /**
     * @var decimal cantidad no es obligatoria para el producto. Sí loe para las combinaciones. Solo debe tenerse en cuenta
     *      si el producto tiene una sola combinación
     */
    public $quantity;

    /**
     * @var decimal lo mismo que la cantidad, no es obligatorio
     */
    public $price;

    /**
     * @var string Debe tener un valor válido para categoría del producto. Se obtiene en
     * $this->category->get_category_unit_types()
     */
    public $unit_type = "100000015";

    /**
     * @var dimensiones y peso. Son obligatorios
     */

    public $discount_price;

    public $package_length = 10;
    public $package_height = 10;
    public $package_width = 10;
    public $package_weight = 0.1;

    /**
     * @var usuario de aliexpress
     */
    public $user_id;

    /**
     * @var Array de combinaciones del producto
     */
    public $combinations;

    /**
     * @var otros identificadores
     */
    public $ean;
    public $reference;

    /**
     * @var array de imágenes
     */
    public $url_images; //Array

    /**
     * @var array de características de categoría $features[$name] = $value. Los name y valores permitidos se
     *      obtienen el $this->category->
     */
    public $features;   //Array

    /**
     * @var array de atributos de categoria definidos por el usuario.
     *      [name1 => value1, name2 => value2, ...]
     */
    public $category_user_attributes;

    public $shipping_template = "715319521";
    public $service_template = "0";
    public $preparation_time = 3;
    public $product_group_id = null;

    public $language_product = 'es_ES';
    //</editor-fold>


    //<editor-fold desc="Variables privadas y protegidas" > -----------------------------------------------------------

    protected $json_product_ae_loaded;

    private $no_image_url = "https://dummyimage.com/600x400/fff/000.png&text=No+image";

    //</editor-fold>

    public function __construct($category)
    {
        parent::__construct();
        $this->factory = $category->factory;
        $this->category = $category;
        $this->category_id = $category->category_id;
        $this->factory->add_product($this);
    }

    protected function verify_combination($combination)
    {
        return true;
    }

    public function add_combination($combination)
    {
        $this->combinations[] = $combination;
    }

    public function load_AE_product_info($json_ae) {
        try {
            $this->category_id = $json_ae->category_id;
            $reverse = $this->factory->get_reverse_attributes($this->category_id);
            $attribslookup = $this->get_attributes_names();

            $this->json_product_ae_loaded = $json_ae;
            $this->name = $json_ae->subject;
            $this->description = $this->trim_detail($json_ae->detail);
            $this->url_images = $json_ae->image_u_r_ls;
            $this->unit_type = $reverse[$json_ae->product_unit][0];
            $this->package_weight = $json_ae->gross_weight;
            $this->package_width = $json_ae->package_width;
            $this->package_length = $json_ae->package_length;
            $this->package_height = $json_ae->package_height;
            $this->user_id = $json_ae->owner_member_id;
            $this->id = $this->idAE;
            $this->ean = $this->reference = $this->id;

            $this->features = $this->get_AE_features($json_ae->aeop_ae_product_propertys, $reverse, $attribslookup);

            if(is_array($json_ae->aeop_ae_product_s_k_us->global_aeop_ae_product_sku))
                $this->get_AE_product_skus($json_ae->aeop_ae_product_s_k_us->global_aeop_ae_product_sku, $reverse);
            else
                $this->get_AE_product_uni_sku($json_ae->aeop_ae_product_s_k_us->global_aeop_ae_product_sku, $reverse);
            return true;
        }
        catch(Exception $ex) {
            return false;
        }
    }

    private function get_AE_features($product_properties, $reverse, $attribslookup) {
        $features = [];

        foreach($product_properties->global_aeop_ae_product_property as $prop) {
            $name = null;
            $value = null;
            try {
                if (isset($prop->attr_name))
                    $name = $prop->attr_name;
                if (isset($prop->attr_value))
                    $value = $prop->attr_value;

                if(!isset($name)) {
                    if(isset($attribslookup[$prop->attr_name_id])) {
                        $name = $attribslookup[$prop->attr_name_id];
                    }
                }
                if(!isset($value)) {
                    if (isset($prop->attr_value_id)) {
                        if (isset($reverse[$prop->attr_value_id])) {
                            $value = $reverse[$prop->attr_value_id][0];
                            if(!isset($name))
                                $name = $reverse[$prop->attr_value_id][1];
                        }
                    }
                }

                if(!isset($name))
                    $name = $prop->attr_name_id;
                if(!isset($value))
                    $value = $prop->attr_value_id;

                if(isset($name) && isset($value))
                    $features[$name] = ["value" => $value];

            } catch(Exception $ex){}
        }

        return $features;
    }

    private function get_AE_product_skus($skus, $reverse){
        $combinations = [];
        foreach($skus as $sku) {
            $combinations[] = $this->get_AE_product_uni_sku($sku, $reverse);
        }
    }

    private function get_AE_product_uni_sku($sku, $reverse) {
        $combination = new AECombination($this);
        $combination->reference = $sku->id;
        $combination->price = $sku->sku_price;
        $combination->quantity = $sku->ipm_sku_stock;
        $combination->idAE = $sku->id;

        $ae_attribs = $sku->aeop_s_k_u_property_list->global_aeop_sku_property;
        if(is_array($ae_attribs)) {
            foreach ($ae_attribs as $ae_attrib) {
                $pars = $this->get_array_attribute($ae_attrib, $reverse);
                if (isset($pars[3]))
                    $combination->set_attribute($pars[0], $pars[1], $pars[3]);
                else
                    $combination->set_attribute($pars[0], $pars[1]);
            }
        }
        else {
            $pars = $this->get_array_attribute($ae_attribs, $reverse);
            if (isset($pars[3]))
                $combination->set_attribute($pars[0], $pars[1], $pars[3]);
            else
                $combination->set_attribute($pars[0], $pars[1]);
        }
        $combination->save();
        return $combination;
    }

    private function get_array_attribute($ae_attrib, $reverse) {
        $rev_value = $reverse[(int)($ae_attrib->property_value_id)];
        $keys = array_keys(get_object_vars($ae_attrib));

        $array_attrib = [$rev_value[1], $rev_value[0]];
        foreach($keys as $key) {
            if($key == "sku_property_id" || $key == "property_value_id")
                continue;
            if ($key == "property_value_definition_name")
                $array_attrib[2]["alias"] = $ae_attrib->property_value_definition_name;
            else
                $array_attrib[2][$key] = $ae_attrib->$key;
        }

        return $array_attrib;
    }

    private function trim_detail($detail) {
        $needle = ">";
        $lastPos = 0;
        $positions_ini = array();

        while (($lastPos = strpos($detail, $needle, $lastPos))!== false) {
            $positions_ini[] = $lastPos;
            $lastPos = $lastPos + strlen($needle);
        }
        $needle = "<";
        $lastPos = 0;
        $positions_fin = array();

        while (($lastPos = strpos($detail, $needle, $lastPos))!== false) {
            $positions_fin[] = $lastPos;
            $lastPos = $lastPos + strlen($needle);
        }

        $len = $positions_fin[2]-1 - $positions_ini[1];

        $trimed_detail = substr($detail, $positions_ini[1]+1, $len);
        return $trimed_detail;
    }

    public function save()
    {

        $json_product = $this->get_json_string();

        if (isset($_GET['dbg'])) die('<pre>' . $json_product);

        $ret = $this->factory->wrapper->putProduct($json_product);

        if (!isset($ret)) {
            $ret = json_encode(
                array(
                    "result" => array(
                        "success" => false,
                        "error_code" => "-1",
                        "error_message" => "Sesión expirada"
                    )
                ), JSON_PRETTY_PRINT);
        }

        else  {
            $json_ret = json_decode($ret);
            if(isset($json_ret->result->product_id))
                $this->idAE = $json_ret->result->product_id;
        }
        return $ret;
    }

    public function get_json_string($para = null)
    {
        $combi_temp = [];

        if (empty($this->combinations)) {
            $combi_temp = [
                [
                    "sku_code" => $this->reference,
                    "inventory" => $this->quantity,
                    "price" => $this->price,
                    "ean_code" => $this->ean,
                ]
            ];
            if($this->discount_price != null) {
                $combi_temp[0]['discount_price'] = $this->discount_price;
            }
        } else {
            foreach ($this->combinations as $combination) {
                $combi_temp[] = $combination->get_combination_array();
            }
        }

        if(isset($this->user_attributes) && size_of($this->user_attributes) > 0) {
            $keys = array_keys($this->user_attributes);
            foreach($keys as $key) {
                $user_attribs[] = ["attribute_name" => $key, "attribute_value" => $this->user_attributes[$key]];
            }
        }

        $user_attributes = [];
        if(isset($this->category_user_attributes) && sizeof($this->category_user_attributes) > 0) {
            $keys = array_keys($this->category_user_attributes);
            foreach ($keys as $key) {
                $user_attributes[] = [
                    "attribute_name" => $key,
                    "attribute_value" => $this->category_user_attributes[$key]
                ];
            }
        }

        $json_producto = [
            "category_id" => $this->category_id,
            "title_multi_language_list" => [[
                "locale" => $this->language_product,
                "title" => $this->name
            ]],
            "description_multi_language_list" => [[
                "locale" => $this->language_product,
                "module_list" => [[
                    "type" => "html",
                    "html" => [
                        "content" => $this->description
                    ]
                ]]
            ]],
            "locale" => $this->language_product,
            "product_units_type" => $this->unit_type,
            "image_url_list" => $this->url_images,
            "category_attributes" => $this->features,
            "user_defined_attribute_list" => $user_attributes,
            "sku_info_list" => $combi_temp,
            "inventory_deduction_strategy" => "place_order_withhold",
            "package_weight" => $this->package_weight,
            "package_length" => $this->package_length,
            "package_width" => $this->package_width,
            "package_height" => $this->package_height,
            "shipping_preparation_time" => $this->preparation_time,
            "shipping_template_id" => $this->shipping_template,
            "service_template_id" => $this->service_template
        ];
        if(!empty($this->product_group_id)) {
            $json_producto['product_group_id'] = $this->product_group_id;
        }
        
        return json_encode($json_producto);
    }

    public function get_json_array_update($job_type) {

        $combi_temp = [];

        if (empty($this->combinations)) {
            if(AEJobType::STOCK_TYPE == $job_type) {
                $combi_temp[] = [
                    "sku_code" => $this->reference,
                    "inventory" => $this->quantity,
                ];

            }
            else {
                $combi_temp[] = [
                    "sku_code" => $this->reference,
                    "price" => $this->price,
                ];
            }
        } else {
            foreach ($this->combinations as $combination) {
                $combi_temp[] = $combination->get_combination_array_update($job_type);
            }
        }

        $json_producto = [
            "aliexpress_product_id" => $this->idAE,
            "category_id" => $this->category_id,
            "sku_info_list" => $combi_temp
        ];

        return $json_producto;
    }

    public function get_schema() {
        return $this->category->get_schema();
    }

    public function get_attributes_names () {
        $schema = $this->get_schema();
        return array_keys($schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"]);
    }

    public function get_attributes_values($attribute_name) {
        $schema = $this->get_schema();
        $attrib_props_names = array_keys($schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"][$attribute_name]["properties"]);
        $props_values = [];
        foreach($attrib_props_names as $name) {
            $rama_attrib = $schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"][$attribute_name]["properties"][$name];
            if(isset($rama_attrib["oneOf"])) {
                foreach($rama_attrib["oneOf"] as $one)
                    $props_values[$name][] = $one["title"];
            }
            else
                $props_values[$name] = [];
        }
        return $props_values;
    }

    public function set_product_inventory($inventory, $sku_code) {
        $ret = $this->factory->wrapper->putProductInventory(
            $inventory, $sku_code, $this->idAE );

        return $ret;
    }

    public function set_product_price($price, $discount_price, $sku_code) {
        $ret = $this->factory->wrapper->putProductPrice(
            $price, $discount_price, $sku_code, $this->idAE );

        return $ret;
    }


    /**
     * @param $user_atributes array de atributos de categoria de usuario
     * [name1 => value1, name2 => value2, ...]
     */
    public function set_category_user_attributes($user_attributes) {
        $result = $result=array_diff_key($user_attributes, $this->factory->category_user_attributes);
        if(!isset($result) || size_of($result) == 0)
            $this->category_user_attributes = $user_attributes;
    }

    /**
     * Añade una entrada al array de atributos de categoria de usuario
     *
     * @param $name string nombre del atributo
     * @param $value string Valor id de aliexpress
     * @return array|bool todos los atributos añadidos
     */
    public function add_category_user_attribute($name, $value, $custonValue = null) {
        if(isset ($this->category->category_user_attributes[$name])) {
            if($value == 4 && isset($custonValue))
                $this->category_user_attributes[$name] = [$value, $custonValue];
            else
                $this->category_user_attributes[$name] = $value;
            return $this->category_user_attributes;
        }
        else
            return false;
    }

    /**
     * @param $name
     *
     *  borrar una entrada del array de atributos de categoria de usuario
     */
    public function remove_category_user_attribute($name) {
        unset($this->category_user_attributes[$name]);
    }

    //<editor-fold desc="Atributos de categorias" >--------------------------------------------------------------------

    /**
     * @param $features asigna el array de características de producto
     */
    public function set_features($features) {
        $this->features = $features;
    }

    /**
     * @param $name string nombre de la característica
     * @param $value_id valor de la característica
     * @param null $other_values
     */
    public function add_feature($name, $value_id, $other_values = null) {
        $this->features[$name]["value"] = $value_id;
        if(isset($other_values)) {
            $keys = array_keys($other_values);
            foreach($keys as $key) {
                $this->features[$name][$key] = $other_values[$key];
            }
        }
    }

    public function remove_feature($name) {
        unset($this->features[$name]);
    }

    //</editor-fold>
}

class AEProductStatusType {
    const ONSELLING = "onSelling";
    const OFFLINE = "offline";
    const AUDITING = "auditing";
    const EDITINGREQUIRED = "editingRequired";
}

?>