<?php

require_once ("AEProduct.php");
require_once ("AEJob.php");

class AECombination {
    public $attributes = []; // [at1 => val1, at2 => val2]
    public $price;
    public $quantity;
    public $discount_price;
    public $idAE;

    public $package_length = 10;
    public $package_height = 10;
    public $package_width= 10;
    public $package_weight = 0.1;
    public $url_images;
    public $ean;
    public $reference;
    private $product;

    public function __construct($prod)
    {
        $this->product = $prod;
    }

    public function get_combination_array(){
        $keys = array_keys($this->attributes);
        $json_attribs = [];
        foreach($keys as $key) {
            $json_attribs[$key] = $this->attributes[$key];
        }

        $min_combination = [
            "sku_code" => $this->reference,
            "inventory" => $this->quantity,
            "price" => $this->price,
            "sku_attributes" => $json_attribs,
            "ean_code" => $this->ean,
        ];
        if($this->discount_price != null) {
            $min_combination['discount_price'] = $this->discount_price;
        }
        return $min_combination;
    }

    public function get_combination_array_update($job_type){
        if(AEJobType::STOCK_TYPE == $job_type) {
            $min_combination = [
                "sku_code" => $this->reference,
                "inventory" => $this->quantity,
            ];
        }
        else {
            $min_combination = [
                "sku_code" => $this->reference,
                "price" => $this->price,
            ];
        }
        return $min_combination;
    }

    /*
     * Parameters:
     *  $name: Attribute name, p.e: Size, Color,...
     *  $value: Attribute value, p.pe: "S", "M", "L", "XL", "Red", "Blue", ...
     *  $other_values: Attribute properties array. Some attributes has propeties, p.e: "Color"
     *  has alias and sku_image_url.
     *      ["alias" => "azabache", "sku_image_url" => "http://myimageazabache.url"]
     */
    public function set_attribute($name, $value, $other_values = null) {
        if($schema = $this->product->get_schema()) {
            $schema_attributes = $schema["properties"]["sku_info_list"]["items"]["properties"]["sku_attributes"]["properties"];
            try {
                $schema_attrib = $schema_attributes[$name];
                foreach($schema_attrib["properties"]["value"]["oneOf"] as $one) {
                    if($one["title"] == $value) {
                        $attrib_id = $one["const"];

                        if(isset($other_values)) { //Otras propiedades del atributo
                            $other_values_keys = array_keys($other_values);
                            foreach ($other_values_keys as $key) {
                                if(isset($schema_attrib["properties"][$key]))
                                    $this->attributes[$name][$key] = $other_values[$key];
                            }
                        }
                        $this->attributes[$name]["value"] = $attrib_id ;

                        return $attrib_id;
                    }
                }
                return false;
            }
            catch(Exception $ex) {
                return false;
            }
        }
        return false;
    }

    public function save() {
        $this->product->add_combination($this);
    }
}

?>