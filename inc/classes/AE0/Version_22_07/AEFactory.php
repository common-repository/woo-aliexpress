<?php

require_once ("Wrapper/aliexpressWrapper.php");
require_once("AEObject.php");
require_once ("AECategory.php");
require_once ("AEProduct.php");
require_once ("AECarriers.php");
require_once ("AEJob.php");
require_once ("Stack.php");
require_once ("AELocales.php");

class AEFactory extends AEObject
{

    //<editor-fold desc="Variables privados"> ----------------------------------------------------------------------------
    /**
     * @var aliexpressWrapper
     */
    public $wrapper;

    /**
     * @var string cuenta de usuario de Aliexpress
     */
    public $email; //= "gestion@prestaimport.com";

    /**
     * @var Array de esquemas por categoria
     */
    private $category_schemas;

    /**
     * @var Array de lookups inversas de atributos por categoria
     */
    private $reverse_attributes;

    /**
     * @var array de categorias cargadas
     */
    private $categories;

    /**
     * @var array de productos creados y/o cargados
     */
    private $products;

    private $token;
    private $appKey = "26011608";
    private $appSecret = "918592f703c60460b789d43dcc642b16";

    public $locale;

    public $price_by_country;

    //</editor-fold>

    /**
     * AEFactory constructor.
     *
     * @param $token string con el token de aliexpress. Si es null, no puede comunicarse con la api de aliexpress
     */
    public function __construct($token = null, $locale = AELocales::SPANISH)
    {
        parent::__construct();
        $this->token = $token;
        $this->locale = $locale;
        $this->wrapper = new aliexpressWrapper($token, $this->appKey, $this->appSecret);
    }

    public function get_json_string($parameters = null)
    {
        // TODO: Implement get_json_string() method.
    }

    private function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        die();
    }

    /**
     * @param $category_id Identificador de Aliexpress
     * @return bool|mixed schema de la categoria
     */
    public function get_category_schema($category_id)
    {
        if (!isset($this->category_schemas[$category_id])) {
            $schema = json_decode($this->wrapper->getProductScheme($category_id), true);
            try {
                if (strval($schema["properties"]["category_id"]["const"]) == $category_id) {
                    $this->category_schemas[$category_id] = $schema;

                    return $schema;
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }

        return $this->category_schemas[$category_id];
    }

    /**
     *  Devuelve todos los esquemas de categorías
     *
     * @return array [category_id => category_schema]
     */
    public function get_all_schemas() {
        return $this->category_schemas;
    }

    /**
     * @param $schema_list array asociativo de categorías [category_id => category_schema]
     */
    public function set_all_schemas($schema_list) {
        $this->category_schemas = $schema_list;
    }

    /**
     * Devuelve la categoria requerida si está almacenada, sino la crea.
     *
     * @param $category_id
     * @return AECategory La categoria de aliexpress con id $category_id
     */
    public  function get_category($category_id) {
        if(isset($this->categories[$category_id]))
            return $this->categories[$category_id];
        else {
            $category = $this->create_category($category_id);
            return $category;
        }
    }

    /**
     * Crea una categoria de aliexprexx a partir de un identificador de categoria.
     * Las categorías deben ser creadas antes de las instancias de producto.
     *
     * @param null $category_id Poner null para obtener categorías raiz
     */
    public function create_category($category_id = null)
    {
        $category = new AECategory($category_id, $this);
        $this->category_schemas[$category_id] = $category->get_schema();
        $this->categories[$category_id] = $category;
        return $category;
    }

    /**
     * Crea una categoria a partir de un esquema dado
     *
     * @param $schema
     * @return AECategory
     */
    public function create_category_from_schema($schema) {
        $schema_struct = json_decode($schema, true);
        $category_id = $schema_struct['properties']['category_id']['const'];
        $category = new AECategory($category_id, $this);
        $category->set_schema($schema_struct);
        $this->category_schemas[$category_id] = $schema_struct;
        $this->categories[$category_id] = $category;
        return $category;
    }

    // <editor-fold desc="Attributes"> ------------------------------------------------------------------------

    /**
     * @param $category_id
     * @return array|bool array inverso de identificadores de atributos
     */
    public function get_reverse_attributes($category_id)
    {
        if (!isset($this->reverse_attributes[$category_id])) {
            try {
                $reverse = $this->generate_reverse($category_id);
                $this->reverse_attributes[$category_id] = $reverse;
                return $reverse;
            } catch (\Exception $e) {
                return false;
            }
        }
        return $this->reverse_attributes[$category_id];
    }

    /**
     * Devuelve el(os) id de categoría más probable(s) a partir de la descripción de un producto
     * @param $descriptor string con la descripción. Debe contener descriptores con atributos de categoria,
     * por ejemplo: camisa, pantalón, chaqueta; manga corta, cuello de pico; hombre, niña, ...
     * @param int $mode AECategoryForecastMode Modo de predicción: PRECISION - muestra solo la categoría más probable,
     * FUZZY - muestra una lista de categoróas y su respectiva probabilidad
     * @param string $locale string con la cadena de idioma
     * @return false|string
     */
    public function getCategoryForecast($descriptor, $mode = AECategoryForecastMode::FUZZY, $locale = null) {
        if(!isset($locale)) $locale = $this->locale;
        $json = $this->wrapper->getCategoryForecast($descriptor, $mode, $locale);

        return $json;
    }

    /**
     * @param $category_id
     * @return array|bool
     *
     * Usada por la function anterior
     */

    private function generate_reverse($category_id)
    {
        $schema = $this->get_category_schema($category_id);

        $reverse = [];

        //Product units
        if (isset($schema["properties"]["product_units_type"]["oneOf"])) {
            $oneOf = $schema["properties"]["product_units_type"]["oneOf"];
            foreach ($oneOf as $one) {
                $reverse[$one["const"]] = [$one["title"], "product units type"];
            }
        } else {
            return false;
        }
        //Category attributes
        $propkeys = array_keys($schema["properties"]["category_attributes"]["properties"]);
        $props = $schema["properties"]["category_attributes"]["properties"];
        foreach ($propkeys as $key) {
            if (isset($props[$key]["properties"]["value"]["oneOf"])) {
                $oneOf = $props[$key]["properties"]["value"]["oneOf"];
                foreach ($oneOf as $one) {
                    $reverse[$one["const"]] = [$one["title"], $key];
                }
            }
        }

        //sku_info_list
        $sku_attribs = $schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"];
        $sku_attribs_keys = array_keys($sku_attribs);
        foreach ($sku_attribs_keys as $key) {
            if (isset($sku_attribs[$key]["properties"]["value"]["oneOf"])) {
                $oneOf = $sku_attribs[$key]["properties"]["value"]["oneOf"];
                foreach ($oneOf as $one) {
                    $reverse[$one["const"]] = [$one["title"], $key];
                }
            }
        }
        return $reverse;
    }

    /**
     * @param $category_id
     * @return array|bool Valores de atributos de categorias con los id de aliexpress
     */

    public function get_category_attributes_values($category_id) {
        $sche = $this->get_category_schema($category_id);

        if(isset($sche)) {
            $attribs = [];
            $attrib_schema = $sche["properties"]["category_attributes"]["properties"];
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
    public function get_category_attributes_types($category_id) {
        $sche = $this->get_category_schema($category_id);

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
    /**
     * @param $category_id
     * @return array|bool|mixed Atributos de categorias requeridos
     */
    public function get_category_required_attributes($category_id) {
        $schema = $this->get_category_schema($category_id);

        $attribs = [];
        if(isset($schema)) {
            $attribs = $schema["properties"]["category_attributes"]["required"];
        }
        else
            return false;
        return $attribs;
    }

    public function get_category_required_attributes_default_values($category_id) {
        $required = $this->get_category_required_attributes($category_id);
        $values = $this->get_category_attributes_values($category_id);
        $type = $this->get_category_attributes_types($category_id);
        $free = $this->get_category_free_attribute_value($category_id);
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
     * @param $category_id
     * @return array Nombres de atributos de combinaciones
     */
    public function get_sku_attributes_names ($category_id) {
        $schema = $this->get_category_schema($category_id);
        return array_keys($schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"]);
    }

    /**
     * @param $category_id
     * @return array tabla de atributos de combinaciones con sus valores permidos y ids de aliexpress
     */
    public function get_sku_attributes_schema_values($category_id) {
        $schema = $this->get_category_schema($category_id);
        $sku_attributes = $schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"];
        $sku_names = array_keys($sku_attributes);
        $props_values = [];
        foreach($sku_names as $sku_name) { //size, color...
            $attrib_props = $sku_attributes[$sku_name]["properties"]["value"];
            if(isset($attrib_props["oneOf"])) {
                foreach($attrib_props["oneOf"] as $one)
                    $props_values[$sku_name][$one["const"]] = $one["title"];
            }
            else
                $props_values[$sku_name] = [];

        }
        return $props_values;
    }

    /**
     * @param $category_id string Identificador de categoria
     * @return array con las propiedades válidas de un sku attribute
     */
    public function get_sku_attributes_properties($category_id) {
        $schema = $this->get_category_schema($category_id);
        $sku_attributes = $schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"];
        $sku_names = array_keys($sku_attributes);
        $all_sku_props = [];
        foreach($sku_names as $name) {
            $sku_all_props = $sku_attributes[$name]["properties"];
            $sku_all_props_names = array_keys($sku_all_props);
            $sku_props = [];
            foreach($sku_all_props_names as $prop) {
                $sku_props[$prop] = $sku_all_props[$prop]["type"];
            }
            $all_sku_props[$name] = $sku_props;
        }
        return $all_sku_props;
    }

    /**
     * @param $category_id
     * @return array|bool Devuelve un array con los atributos de categoría (features)
     *  que permiten escribir un otro valor diferente a los disponibles.
     *  array(AttributeKey => [value, titleCuston]
     */
    public function get_category_free_attribute_value($category_id) {
        $sche = $this->get_category_schema($category_id);

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


    //</editor-fold>---------------------------------------------------------------------------------------------------

    /**
     * @param string $category_id . '0' para obtener las categorias raiz
     * @return array json con las categorías hijas
     */
    public function get_category_children($category_id = '0', $idioma = null)
    {
        $json_children = json_decode($this->wrapper->getCategoryList($category_id));
        if($idioma == null) $idioma = $this->locale;
        $hijas_array = [];
        if (isset($json_children->result->aeop_post_category_list->aeop_post_category_dto)) {
            $hijas = $json_children->result->aeop_post_category_list->aeop_post_category_dto;
            if(is_array($hijas)) {
                foreach ($hijas as $hija) {
                    $hijas_array[] = $this->get_hija($hija, $idioma);
                }
            }
            else
                $hijas_array[] = $this->get_hija($hijas, $idioma);
        }
        return $hijas_array;
    }

    private function get_hija($hija, $idioma){
        if (isset($hija->names)) {
            $nombre = json_decode($hija->names, true);
            if (isset($nombre[$idioma]))
                $name = $nombre[$idioma];
            else
                $name = $nombre['en'];
        } else {
            $name = '';
        }
        return ([
            "category_id" => $hija->id,
            "category_name" => $name,
            "is_leaf" => $hija->isleaf == 'false' ? false : true
        ]);
    }

    /**
     *  Carga todo el arbol de categorias en un array. Cada nodo apunta al padre y a los hijos
     * @return array Array que contiene el arbol de categorías
     */
    public function get_all_categories_tree($locale = null) {
        $list = [];
        $node = [
            "category_id" => '0',
            "category_name" => 'Root',
            "is_leaf" => false,
            "id_padre" => 0,
            "children_id" => []
        ];
        $stack = new Stack();
        $stack->push($node);

        $padre = 0;
        while(!$stack->isEmpty()) {
            $nodo = $stack->pop();
            if(!$nodo['is_leaf']) {
                $children_category = $this->get_category_children($nodo['category_id'], $locale);
                foreach ($children_category as $child) {
                    $child["id_padre"] = $nodo["category_id"];
                    $nodo['children_id'][] = $child['category_id'];
                    $stack->push($child);
                }
            }
            $list[$nodo['category_id']] = $nodo;
        }
        return $list;
    }

    /**
     * @return array de tipos de unidad de aliexpress: P.e. pieza, docena, litro, kg, etc.
     */
    public function get_category_unit_types($category_id) {
        $sche = $this->get_category_schema($category_id);
        $unit_types = [];
        $uts = $sche["properties"]["product_units_type"]["oneOf"];
        foreach($uts as $ut) {
            $unit_types[$ut["title"]] = $ut["const"];
        }
        return $unit_types;
    }

    public function get_service_template_id_values($category_id) {
        $sche = $this->get_category_schema($category_id);
        $services = [];
        $schema_values = $sche['properties']['service_template_id']['oneOf'];
        foreach($schema_values as $value) {
            if( $value['const'] != 0)
                $services[$value['title']] = $value['const'];
        }
        return $services;
    }

    public function get_shipping_template_id_values($category_id) {
        $sche = $this->get_category_schema($category_id);
        $shipping = [];
        $schema_values = $sche['properties']['shipping_template_id']['oneOf'];
        foreach($schema_values as $value) {
            if( $value['const'] != '1000')
                $shipping[$value['title']] = $value['const'];
        }
        return $shipping;
    }

    public function get_inventory_deduction_strategy_id_values($category_id) {
        $sche = $this->get_category_schema($category_id);
        $inventory = [];
        $schema_values = $sche['properties']['inventory_deduction_strategy']['oneOf'];
        foreach($schema_values as $value)
            $inventory[$value['title']] = $value['const'];
        return $inventory;
    }

    /**
     * @param string $category_id
     * @return AEProduct_Base
     *
     *  Crea un producto dentro de la categoría con id $category_id
     */
    public function create_product($category_id = '200000386')
    {
        $category = $this->get_category($category_id);
        $ae_product = new AEProduct_Base($category);
        return $ae_product;
    }

    public function add_product($product) {
        $this->products[] = $product;
    }

    /**
     * @param $product_id
     * @return AEProduct_Base|bool
     *
     *  Obtiene el producto con id de Aliexpress
     */
    public function get_product_from_AE($product_id)
    {
        $json_product = json_decode($this->wrapper->getProductFromAE($product_id));

        $producto = $json_product->result;

        if (isset($producto->category_id)) {
            $AEproduct = $this->create_product($producto->category_id);
            $AEproduct->idAE = $product_id;

            if ($AEproduct->load_AE_product_info($producto))
                return $AEproduct;
            else
                return false;
        }
        return false;
    }

    /**
     * Obtiene una lista de productos segun la lista de status.
     *
     * @param $status_type array de valores de la enumeración AEOrderStatus
     * @return false|string una página de la lista de productos que están en uno alguno de los AEOrderStatus de $status_type
     */
    public function get_ae_products_list($status_type, $page = 1) {
        $params["product_status_type"] = $status_type;

        $products = $this->wrapper->get_products_list($params, $page);
        return $products;
    }

    /**
     * Obtiene la información de un producto de aliexpress
     *
     * @param $ae_product_id Identificador de producto de aliexpress
     * @return false|string
     */
    public function get_ae_product_info($ae_product_id) {
        $info = $this->wrapper->get_product_info($ae_product_id);

        return $info;
    }

    //<editor-fold desc="Carriers">------------------------------------------------------------------------------------

    /**
     *  Obtener todos los transportistas
     * @return bool|false|string Lista de transportistas de Aliexpress
     */
    public function get_all_carriers() {
        try {
            $json = $this->wrapper->getCarriers();
            return $json;
        }
        catch(Exception $ex) {
            return false;
        }
    }

    public function get_order_carrier($orderID = false) {
        try {
            $json = $this->wrapper->getCarrierOrder($orderID);
            return $json;
        }
        catch(Exception $ex) {
            return false;
        }
    }


    /**
     *  Devuelve los shipping templates válidos
     *
     * @return array [template_id => ["template_id" => id, "template_name" => name, "is_default" => true|false]]
     */
    public function get_shipping_templates() {
        $json = $this->wrapper->getFreightTemplates();
        $json_array = json_decode($json, true);
        $shipping_templ = [];
        if(isset($json_array["aeop_freight_template_d_t_o_list"]["aeopfreighttemplatedtolist"])) {
            $sts = $json_array["aeop_freight_template_d_t_o_list"]["aeopfreighttemplatedtolist"];
            if(!isset($sts["template_id"])) {
                foreach ($sts as $st) {
                    $shipping_templ[$st["template_id"]] = [
                        "template_id" => $st["template_id"],
                        "template_name" => $st["template_name"],
                        "is_default" => $st["is_default"]
                    ];
                }
            }else {
                $shipping_templ[$sts["template_id"]] = [
                    "template_id" => $sts["template_id"],
                    "template_name" => $sts["template_name"],
                    "is_default" => $sts["is_default"]
                ];
            }
        }

        return $shipping_templ;
    }

    /**
     *  Servir un pedido.
     *
     * @param $service_name Requerido Nombre del servicio como figura en la lista de carriers
     * @param null $tracking_website
     * @param $order_id Requerido Identificador de pedido de aliexpress
     * @param $send_type Requerido Tipo de envío, total o parcial
     *  (AECarrier_Send_type::ALL_SEND_TYPE o AECarrier_Send_type::PART_SEND_TYPE)
     * @param null $description
     * @param $tracking_no Requerido
     * @return bool True si ha tenido exito, false si ha fallad o [codigo => error] si los parametros no eran correctos
     *
     */
    public function fulfill_order(
        $service_name,
        $tracking_website = null,
        $order_id,
        $send_type,
        $description = null,
        $tracking_no) {

        try {
            $json = json_decode($this->wrapper->fulfill_order(
                $service_name,
                $tracking_website,
                $order_id,
                $send_type,
                $description,
                $tracking_no
            ), true);

            if(isset($json["result"]["result_success"]) && $json["result"]["result_success"] == "true")
                return true;
            else {
                if(isset($json["sub_code"]))
                    return [$json["sub_code"] => $json["sub_msg"]];
                else if(isset($json["code"]))
                    return  [$json["code"] => $json["msg"]];
                else
                    return ['error' => 'unknow error'];
            }
        }
        catch(Exception $ex) {
            return false;
        }
    }

    //</editor-fold>

    //<editor-fold desc="Batch processes">-----------------------------------------------------------------------------

    /**
     *  Hace una actualización por lotes de existencias.
     *
     * @param $products_inventory_array [producto_id, sku_code, inventory]
     *
     * @return string identificador de job de aliexpress
     */
    public function batch_set_products_inventory($category_id, $products_list = null) {
        $category = $this->get_category($category_id);
        $json_products = $category->get_json_batch_update_array($products_list, AEJobType::STOCK_TYPE);

        $json_string = json_encode($json_products, JSON_PRETTY_PRINT);
        $job = new AEJob_base($json_string, AEJobType::STOCK_TYPE);

        //$job->send($this);
        //$job.save();

        return $job;
    }

    /**
     *  Actualiza los precio de las combinaciones de productos en un proceso en lote
     *
     * @param $products_price_array [producto_id, sku_code, price, discount_price]
     *
     * @return string identificador de job de aliexpress
     */
    public function batch_set_products_price($category_id, $products_list = null) {
        $category = $this->get_category($category_id);
        $json_products = $category->get_json_batch_update_array($products_list, AEJobType::PRICE_TYPE);

        $json_string = json_encode($json_products, JSON_PRETTY_PRINT);
        $job = new AEJob_base($json_string, AEJobType::PRICE_TYPE);

        //$job->send($this);
        //$job.save();

        return $job;
    }

    /**
     *  Envía un mensaje de creacion de productos de aliexpress
     *
     * @param $category_id Identificador de categoria de aliexpress
     * @param null $products_list array de AEProduct.
     * @return AEJob_base string con el job id del lote
     */
    public function batch_create_products($category_id, $products_list = null) {
        $category = $this->get_category($category_id);
        $json_products = $category->get_json_batch_array($products_list);
        $this->set_multi_country_price_info($json_products);
        $json_string = json_encode($json_products, JSON_PRETTY_PRINT);
        $job = new AEJob_base($json_string, AEJobType::CREATE_TYPE);

        $job->send($this);
        //$job.save();

        return $job;
    }

    /**
     * Envia un mensaje para actualizacion de productos
     *
     * @param $category_id identificador de categoria de aliexpress
     * @param null $products_list lista de AEProduct para actualizar
     * @return AEJob_base
     */
    public function batch_update_products($category_id, $products_list = null) {
        $category = $this->get_category($category_id);
        $json_products = $category->get_json_batch_array($products_list, true);
        $this->set_multi_country_price_info($json_products);
        $json_string = json_encode($json_products, JSON_PRETTY_PRINT);
        $job = new AEJob_base($json_string, AEJobType::UPDATE_TYPE);

        $job->send($this);
        //$job.save();

        return $job;
    }

    /**
     * Envia el feed con la actualización de precios para productos
     */
    public function batch_update_products_price($products_list = null) {
        $this->set_multi_country_price_info($products_list);
        $json_string = json_encode($products_list, JSON_PRETTY_PRINT);
        //die($json_string);
        $job = new AEJob_base($json_string, AEJobType::PRICE_TYPE);
        if($job->send($this)) {
            return $job;
        }else{
            return false;
        }
    }
    /**
     * Envia el feed con la actualización de inventario para productos
     */
    public function batch_update_products_inventory($products_list = null) {
        $json_string = json_encode($products_list, JSON_PRETTY_PRINT);
        $job = new AEJob_base($json_string, AEJobType::STOCK_TYPE);

        if($job->send($this)) {
            return $job;
        }else{
            return false;
        }
        
    }

    /**
     *  Captura los id de producto de aliexpress y los asocia a los id internos.
     *
     * @param $result_json string con el json devuelto por AEJob_Base::query
     * @return array|bool arrau asociativo [id => [id, idAE]
     */
    public function batch_get_array_ids($result_json) {
        if(is_array($result_json))
            $result_array = $result_json;
        else
            $result_array = json_decode($result_json, true);
        if(isset($result_array["result_list"]["single_item_response_dto"])) {
            $items = $result_array["result_list"]["single_item_response_dto"];
            $product_items = [];
            foreach($items as $item) {
                $item_execution = json_decode($item["item_execution_result"], true);
                $product_items[$item["item_content_id"]] = [
                    "id" => $item["item_content_id"],
                    "idAE" => $item_execution["productId"]
                ];
            }
            return $product_items;
        }
        return false;
    }

    /**
     * verifica el estado de un job
     *
     * @param $job_id identificador del job
     * @return mixed string con el json resultado del query.
     */
    public function batch_query_job($job_id) {
        $result = $this->wrapper->feed_query($job_id);
        //return $this->get_job_id(($result));
        return json_decode($result, true);
    }

    /**
     *  Pone como offline en aliexpress los productos de la lista de product_id (id de aliexpress)
     *
     * @param $product_id_list array de identificadores de producto de aliexpress
     * @return mixed string json con el numero de productos que cambiaron de estado (online a offline)
     */
    public function set_products_offline($product_id_list) {
        $result = $this->wrapper->set_product_offline($product_id_list);
        //return $this->get_job_id(($result));
        return json_decode($result, true);
    }

    /**
     *  Pone como online en aliexpress los productos de la lista de product_id (id de aliexpress)
     *
     * @param $product_id_list array de identificadores de producto de aliexpress
     * @return mixed string json con el numero de productos que cambiaron de estado (offline a online)
     */
    public function set_products_online($product_id_list) {
        $result = $this->wrapper->set_product_online($product_id_list);
        //return $this->get_job_id(($result));
        return json_decode($result, true);
    }

    public function set_multi_country_price_info(&$product_list){
        if(!is_array($this->price_by_country) || empty($this->price_by_country)) {
            return;
        }
        if(is_array($product_list)){
            // convertir a array. puede venir como stdClass a veces.
            $array_product_list = json_decode(json_encode($product_list),true);
            foreach ($product_list as $key => &$value) {
                $avalue = $array_product_list[$key];
                if(isset($avalue['item_content'])){
                    if(isset($avalue['item_content']['sku_info_list'])) {
                        // job de creación de productos
                        $o = $avalue['item_content'];
                        $cpl = [];
                        foreach ($this->price_by_country as $iso => $country_value) {
                            $cpl_item = ['ship_to_country' => $iso, 'sku_price_by_country_list' => []];
                            $mul = floatval($country_value) / 100;
                            if(isset($o['sku_info_list'])){
                                // actualización de precios
                                foreach ($o['sku_info_list'] as $pr) {
                                    $i = [
                                        'sku_code' => $pr['sku_code'],
                                        'price' => round($pr['price'] * $mul,4)
                                    ];
                                    $cpl_item['sku_price_by_country_list'][] = $i;
                                }
                                $cpl[] = $cpl_item;
                            }
                        } // country
                        $mcpc = ['price_type' => 'absolute', 'country_price_list' => $cpl];
                        $this->set_io_value($value['item_content'],'multi_country_price_configuration',$mcpc);
                    }else{
                        // job de actualización de precios
                        $o = $avalue['item_content'];
                        $cpl = [];
                        foreach ($this->price_by_country as $iso => $country_value) {
                            $cpl_item = ['ship_to_country' => $iso, 'sku_price_by_country_list' => []];
                            $mul = floatval($country_value) / 100;
                            if(isset($o['multiple_sku_update_list'])){
                                // actualización de precios
                                foreach ($o['multiple_sku_update_list'] as $pr) {
                                    $i = [
                                        'sku_code' => $pr['sku_code'],
                                        'price' => round($pr['price'] * $mul,4)
                                    ];
                                    $cpl_item['sku_price_by_country_list'][] = $i;
                                }
                                $cpl[] = $cpl_item;
                            }
                        } // country
                        $mcpc = ['price_type' => 'absolute', 'country_price_list' => $cpl];
                        //$o->multi_country_price_configuration = $mcpc;
                        $this->set_io_value($value['item_content'],'multi_country_price_configuration',$mcpc);
                    }
                }
            }
        }
    }

    private function set_io_value(&$io, $key, $value){
        if(is_object($io)){
            $io->$key = $value;
        }
        if(is_array($io)){
            $io[$key] = $value;
        }
    }

    private function get_job_id($result) {
        $json_array = json_decode($result, true);
        if(isset($json_array["job_id"])) {
            return $json_array["job_id"];
        }
        else return false;
    }

    //</editor-fold>

    public function get_vendor_profile_info() {
        $result = $this->wrapper->get_vendor_profile_info();
        return $result;
    }

    public function get_countries($category_id) {
        $schema = $this->get_category_schema($category_id);
        $r = $schema['properties']['multi_country_price_configuration']['properties']['country_price_list']['items']['properties']['ship_to_country']['oneOf'];
        return $r;
    }
}