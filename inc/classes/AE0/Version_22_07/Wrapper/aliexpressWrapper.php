<?php 

require_once "TopSdk.php";

class aliexpressWrapper
{    
    //private $email = '';
    private $appKey = '';
    private $appSecret = '';
    private $token = '';
    public $registered = false;
    private $serverurl = 'https://aliexpress.wecomm.es';
    private $service_uri;

    //function __construct($email)
    function __construct($token, $appKey, $appScret)
    {
        //$this->email = $email;
        $this->appKey = $appKey;
        $this->appSecret = $appScret;
        $this->token = $token;
    }

    public function getProducts()
    {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressSolutionProductListGetRequest;
        $aeop_a_e_product_list_query = new \ItemListQuery;
        $aeop_a_e_product_list_query->current_page="1";
        //$aeop_a_e_product_list_query->excepted_product_ids="[32962333569,32813963253]";
        //$aeop_a_e_product_list_query->off_line_time="7";
        //$aeop_a_e_product_list_query->owner_member_id="0";
        $aeop_a_e_product_list_query->page_size="100";
        //$aeop_a_e_product_list_query->product_id="123";
        $aeop_a_e_product_list_query->product_status_type="onSelling";
        //$aeop_a_e_product_list_query->subject="knew odd";
        //$aeop_a_e_product_list_query->ws_display="expire_offline";
        //$aeop_a_e_product_list_query->have_national_quote="n";
        //$aeop_a_e_product_list_query->group_id="1234";
        $aeop_a_e_product_list_query->gmt_create_start="2012-01-01 12:13:14";
        $aeop_a_e_product_list_query->gmt_create_end="2020-01-01 12:13:14";
        //$aeop_a_e_product_list_query->gmt_modified_start="2012-01-01 12:13:14";
        //$aeop_a_e_product_list_query->gmt_modified_end="2012-01-01 12:13:14";
        $req->setAeopAEProductListQuery(json_encode($aeop_a_e_product_list_query));
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret;  
    }

    public function getProduct($id)
    {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressSolutionProductInfoGetRequest;
        $req->setProductId($id);
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret;  
    }

    public function deleteProducts($products)
    {
        if(is_array($products)){
            $products = implode(',',$products);
        }
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressSolutionBatchProductDeleteRequest;
        $req->setProductIds($products);
        $resp = $c->execute($req, $this->token, $this->service_uri);
        return $resp;
        $ret = json_encode($resp,JSON_PRETTY_PRINT);
        return $ret;
    }


    public function disableProducts($products)
    {
        if(!is_array($products)){
            $products=explode(',',$products);
        }
        return $this->set_product_offline($products);
    }

    public function enableProducts($products)
    {
        if(!is_array($products)){
            $products=explode(',',$products);
        }
        return $this->set_product_online($products);
    }


    public function getCarriers()
    {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressLogisticsRedefiningListlogisticsserviceRequest;
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);
        return $ret;  
    }

    public function getCarrierOrder($orderID = false)
    {
        if($orderID && $orderID > 0) {
            $c = new \TopClient;
            $c->appkey = $this->appKey;
            $c->secretKey = $this->appSecret;
            $req = new \AliexpressLogisticsRedefiningGetonlinelogisticsservicelistbyorderidRequest;
            $req->setOrderId(intval($orderID));
            $resp = $c->execute($req, $this->token);
            $ret = json_encode($resp);
            return $ret;  
        }

        return false;
    }

    public function fulfill_order(
        $service_name,
        $tracking_website = null,
        $order_id,
        $send_type,
        $description = null,
        $tracking_no
    ) {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressSolutionOrderFulfillRequest;
        $req->setServiceName($service_name);
        $req->setTrackingWebsite($tracking_website);
        $req->setOutRef($order_id);
        $req->setSendType($send_type);
        $req->setDescription($description);
        $req->setLogisticsNo($tracking_no);

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);
        return $ret;

    }


    public function getOSSOrders(
        $date_start,
        $date_end = null,
        $status_list,
        $page_size,
        $current_page) {

        $sessionURL = $this->serverurl . '/orders/getossolist/' . $this->appKey . '/' . $this->token . '/' . $this->appSecret;

        $data = json_encode(array(
            'date_start' => $date_start,
            'date_end' => $date_end,
            'status_list' => $status_list,
            'page_size' => $page_size,
            'current_page' => $current_page
        ));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $sessionURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POST, 1 );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));

        $result=curl_exec ($ch);

        return $result;
    }

    public function getOSSOrderId($order_id, $info_bits = null) {
        $sessionURL = $this->serverurl . '/orders/getossoid/' . $this->appKey . '/' . $this->token .
            '/' . $this->appSecret . '/' . $order_id . (isset($info_bits)? '/' . $info_bits: '');
		$ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $sessionURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POST, 0 );
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain'));

        $result=curl_exec ($ch);
        return $result;
    }

   public function getOrderReceiptInfo($order_id, $info_bits = null) {

        $sessionURL = 'https://aliexpress.wecomm.es/orders/getossori/' . $this->appKey . '/' . $this->token .
            '/' . $this->appSecret . '/' . $order_id;

        $result = file_get_contents($sessionURL);
        return $result;
    }

    public function getSkuAttribute($catid)
    {
        if(!$catid){
            return false;
        }
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressSolutionSkuAttributeQueryRequest;
        $query_sku_attribute_info_request = new \SkuAttributeInfoQueryRequest;
        $query_sku_attribute_info_request->aliexpress_category_id=$catid;
        $query_sku_attribute_info_request->category_id=$catid;
        $req->setQuerySkuAttributeInfoRequest(json_encode($query_sku_attribute_info_request));
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret;        
    }

    public function getAttribute($catid)
    {
        if(!$catid){
            return false;
        }
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressCategoryRedefiningGetchildattributesresultbypostcateidandpathRequest;
        $req->setParam1($catid);
        //$req->setParam2("2=200013977");
        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret;        
    }

    public function addShipment($id,$tracking,$type="all",$carrierName="OTHER_ES_LOCAL",$carrierWeb="")
    {
        if(!$id){
            return false;
        }
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressLogisticsSellershipmentfortopRequest;
        $req->setLogisticsNo($tracking);
        $req->setDescription("Pedido enviado");
        $req->setSendType($type); // all/part
        $req->setOutRef($id);
        if($carrierWeb) $req->setTrackingWebsite($carrierWeb);
        $req->setServiceName($carrierName);
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret; 
    }

    public function productList(){
        $prods = json_decode($this->getProducts(),true);
        if(!isset($prods['result'])) return '';
        $prods=$prods['result'];
        if(!isset($prods['aeop_a_e_product_display_d_t_o_list'])) return '';
        $prods=$prods['aeop_a_e_product_display_d_t_o_list'];
        if(!isset($prods['item_display_dto'])) return '';
        $prods=$prods['item_display_dto'];
        $ret = array();
        foreach($prods as $prod){
            $id=$prod['product_id'];
            $info = json_decode($this->getProduct($id),true);
            if(isset($info['result'])){
                $info=$info["result"];
                $ret[$id] = array(
                    "id" => $info["product_id"],
                    "category" => $info["category_id"],
                    "name" => $info["subject"]
                    );
            }else{
                $ret["error"] = $info;
            }
            
        }
        return print_r($ret,true);
    }
    
    public function getCategoryList($category_id = "0") {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressCategoryRedefiningGetchildrenpostcategorybyidRequest;
        $req->setParam0($category_id);
        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp,JSON_PRETTY_PRINT);   
        return $ret; 
    }

    public function getProductScheme($category_id = "0") {
        $c = new \TopClient();
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressSolutionProductSchemaGetRequest;
        $req->setAliexpressCategoryId($category_id);
        $resp = $c->execute($req, $this->token);  
        $ret = json_decode($resp->result->schema, JSON_PRETTY_PRINT);

        return $resp->result->schema;
    }   
    
    public function getCategoryById($category_id = "0") {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressCategoryRedefiningGetpostcategorybyidRequest;
        $req->setParam0($category_id);
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);
        return $ret; 
    }

    public function getCategoryForecast($descriptor, $locale = 'en', $mode = 2, $filterPermission = 'N') {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressPostproductRedefiningCategoryforecastRequest;
        $req->setSubject($descriptor);
        $req->setLocale($locale);
        $req->setForecastMode($mode);
        $req->setIsFilterByPermission($filterPermission);

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp,JSON_PRETTY_PRINT);
        return $ret;
    }
    
    public function putImage($file_location, $file_name ) {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressPhotobankRedefiningUploadimageforsdkRequest();
        $req->setGroupId("0");
        $req->setImageBytes($file_location);
        $req->setFileName($file_name);
        
        $resp = $c->execute($req, $this->token);  
        $ret = json_decode(json_encode($resp), true);   
        return $ret; 
    }
    
    public function putImageTemp($file_location, $file_name ) {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressImageRedefiningUploadtempimageRequest();
        $req->setFileData($file_location);
        $req->getSrcFileName($file_name);
        
        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp, JSON_PRETTY_PRINT);   
        return $ret; 
    }
    
    public function getPhotoBankInfo() {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressPhotobankRedefiningGetphotobankinfoRequest();
        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp, JSON_PRETTY_PRINT);   
        return $ret;         
    }
    
    public function getPhotoBankPagedQuery(
                $current_page = "0",
                $group_id = "0",
                $location_type = "allGroup",
                $page_size = "50") {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new \AliexpressPhotobankRedefiningListimagepaginationRequest;
        
        $aeop_image_pagination_request = new \AeopImagePaginationRequest;
        $aeop_image_pagination_request->current_page=$current_page;
        $aeop_image_pagination_request->group_id=$group_id;       
        $aeop_image_pagination_request->location_type=$location_type;
        $aeop_image_pagination_request->page_size=$page_size;
        $req->setAeopImagePaginationRequest(json_encode($aeop_image_pagination_request));

        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp, JSON_PRETTY_PRINT);   
        return $ret;         
    }
    
    public function putProduct($json_product) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressSolutionSchemaProductInstancePostRequest();
        $req->setProductInstanceRequest($json_product);
        
        $resp = $c->execute($req, $this->token);  
        $ret = json_encode($resp);   
        return $ret;         
        
    }

    public function putProductAttribute() {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressPostproductRedefiningEditproductcategoryattributesRequest;
        $req->setProductId("33035341446");
        $product_category_attributes = new \AeopAeProductProperty;
        $product_category_attributes->attr_name="Talla";
        $product_category_attributes->attr_name_id="10001";
        $product_category_attributes->attr_value="L";
        $product_category_attributes->attr_value_end="";
        $product_category_attributes->attr_value_start="";
        $product_category_attributes->attr_value_unit="0";
        $product_category_attributes->attr_value_id="1000102";
        $req->setProductCategoryAttributes(json_encode($product_category_attributes));
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);   
        return $ret;
    }

    public function putProductInventory($inventory, $sku_code, $product_id) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new \AliexpressSolutionProductSkuInventoryEditRequest;

        $edit_product_sku_inventory_request = new \EditItemSkuStockDto;
        $edit_product_sku_inventory_request->inventory=$inventory;
        $edit_product_sku_inventory_request->sku_code=$sku_code;
        $edit_product_sku_inventory_request->product_id=$product_id;

        $req->setEditProductSkuInventoryRequest(json_encode($edit_product_sku_inventory_request));

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function get_category_suggest($title,$lang,$image) {

        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressSolutionProductCategorySuggestRequest;
        if($title and $lang) {
            $req->setTitle($title);
            $req->setLanguage($lang);
        }
       
        if($image && $image != '') {
            $req->setImageUrl($image);
        }
        $resp = $c->execute($req, $this->token);
        $ret = json_decode(json_encode($resp, JSON_PRETTY_PRINT), true);
        return $ret;
    }

    public function putProductPrice($price, $discount_price, $sku_code, $product_id) {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new \AliexpressSolutionProductSkuPriceEditRequest;
        $edit_product_sku_price_request = new \EditItemSkuPriceDto;
        $edit_product_sku_price_request->price = $price;
        $edit_product_sku_price_request->discount_price = $discount_price;
        $edit_product_sku_price_request->sku_code = $sku_code;
        $edit_product_sku_price_request->product_id = $product_id;
        $req->setEditProductSkuPriceRequest(json_encode($edit_product_sku_price_request));

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function getProductFromAE($product_id) {
        $c = new \TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressSolutionProductInfoGetRequest;
        $req->setProductId($product_id);

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function getFreightTemplates() {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressFreightRedefiningListfreighttemplateRequest;
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    // Batch jobs -----------------------------------------------------------------------------------------------------

    public function feed_submit($type, $items) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressSolutionFeedSubmitRequest;
        $req->setOperationType($type);
        $req->setItemList($items);
        
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function feed_query($job_id) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressSolutionFeedQueryRequest;
        $req->setJobId($job_id);
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function get_products_list($params, $page = 1) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $aeop_a_e_product_list_query = new ItemListQuery;
        $aeop_a_e_product_list_query->page_size = 100;
        $aeop_a_e_product_list_query->current_page = $page;
        $aeop_a_e_product_list_query->product_status_type = $params["product_status_type"];
        $req = new AliexpressSolutionProductListGetRequest;
        $req->setAeopAEProductListQuery(json_encode($aeop_a_e_product_list_query));

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function get_product_info($product_id) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;

        $req = new AliexpressSolutionProductInfoGetRequest;
        $req->setProductId($product_id);

        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function set_product_offline($product_id_list) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressPostproductRedefiningOfflineaeproductRequest;
        $req->setProductIds(implode(";", $product_id_list));
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function set_product_online($product_id_list) {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressPostproductRedefiningOnlineaeproductRequest;
        $req->setProductIds(implode(";", $product_id_list));
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function get_vendor_profile_info() {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressSolutionMerchantProfileGetRequest;
        $resp = $c->execute($req, $this->token);
        $ret = json_encode($resp, JSON_PRETTY_PRINT);
        return $ret;
    }

    public function get_product_groups() {
        $c = new TopClient;
        $c->appkey = $this->appKey;
        $c->secretKey = $this->appSecret;
        $req = new AliexpressProductProductgroupsGetRequest;
        $resp = $c->execute($req, $this->token, $this->service_uri);
        $ret = json_decode(json_encode($resp), true);
        return $ret;
    }

}
