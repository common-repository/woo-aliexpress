<?php

class AEJob_base
{
    public $aejob_id;
    public $json_mesage;
    public $status;
    public $creation_time;
    public $last_query_time;
    public $last_query_result;
    public $type;
    public $resultset;
    public $last_error;

    public static function save($job) {}
    public static function load($job_id){}

    private $type_feed_array = [
        AEJobType::CREATE_TYPE => AEFeedParameter::PRODUCT_CREATE,
        AEJobType::UPDATE_TYPE => AEFeedParameter::PRODUCT_FULL_UPDATE,
        AEJobType::PRICE_TYPE => AEFeedParameter::PRICE_UPDATE,
        AEJobType::STOCK_TYPE => AEFeedParameter::STOCK_UPDATE
    ];

    public function __construct($json_message, $type, $creation_time = null) {
        $this->create($json_message, $type, $creation_time);
    }

    private function create($json_message, $type, $creation_time = null) {
        $this->json_mesage = $json_message;
        $this->creation_time = isset($creation_time) ? $creation_time: new DateTime();
        $this->type = $type;
        $this->last_query_time = $this->creation_time;
        $this->status = AEJobState::CREATED_JOB;
    }

    public function send($factory) {
        if($this->status != AEJobState::CREATED_JOB) {
            $this->$this->last_error = ["error-interno" => "El lote ya ha sido enviado."];
            return false;
        }

        switch($this->type) {
            case AEJobType::CREATE_TYPE:
            case AEJobType::UPDATE_TYPE:
            case AEJobType::PRICE_TYPE:
            case AEJobType::STOCK_TYPE:
                
                $result = $factory->wrapper->feed_submit($this->type_feed_array[$this->type], $this->json_mesage);
                $json = json_decode($result, true);
                if(isset($json["job_id"])) {
                    $this->aejob_id = $json["job_id"];
                    $this->resultset = $result;
                    $this->status = AEJobState::SENT_JOB;
                    return true;
                }
                else {
                    if(isset($json['sub_msg'])){
                        AEW_MAIN::register_error($json['sub_msg'], 0, 'general');
                    }
                    if(isset($json["error_response"]["code"])) {
                        $this->last_error = [$json["error_response"]["code"] => $json["error_response"]["msg"]];
                    }else{
                        $this->last_error = [$json["error_response"]["sub_code"] => $json["error_response"]["sub_msg"]];
                    }
                    return false;
                }
                break;
        }
    }

    public function query($factory) {
        $result = $factory->wrapper->feed_query($this->aejob_id);
        $res = json_decode($result, true);
        $this->last_query_time = new DateTime();
        $this->last_query_result = $res;
        return $res;
    }

    public function end() {}
}

abstract class AEJobType {
    const CREATE_TYPE = 1;
    const UPDATE_TYPE = 2;
    const ONLINE_TYPE = 5;
    const OFFLINE_TYPE = 6;
    const PRICE_TYPE = 7;
    const STOCK_TYPE = 8;
}

abstract class AEFeedParameter{
    const PRODUCT_CREATE = "PRODUCT_CREATE";
    const PRODUCT_FULL_UPDATE = "PRODUCT_FULL_UPDATE";
    const STOCK_UPDATE = "PRODUCT_STOCKS_UPDATE";
    const PRICE_UPDATE = "PRODUCT_PRICES_UPDATE";
}

abstract class AEJobState {
    const CREATED_JOB = 1;
    const SENT_JOB = 2;
    const ENQUEUED_JOB = 3;
    const PROCESSED_JOB = 4;
}


?>