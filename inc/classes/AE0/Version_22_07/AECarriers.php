<?php

class AECarriers extends AEObject
{
    public function __construct($factory)
    {
        parent::__construct();
        $this->factory = $factory;
    }

    /**
     *  Obtener todos los transportistas
     * @return bool|false|string Lista de transportistas de Aliexpress
     */
    public function get_all_carriers() {
        try {
            $json = $this->factory->wrapper->getCarriers();
            return $json;
        }
        catch(Exception $ex) {
            return false;
        }
    }

    public function get_order_carrier($orderID = false) {
        try {
            $json = $this->factory->wrapper->getCarrierOrder($orderID);
            return $json;
        }
        catch(Exception $ex) {
            return false;
        }
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
            $json = json_decode($this->factory->wrapper->fulfill_order(
                $service_name,
                $tracking_website,
                $order_id,
                $send_type,
                $description,
                $tracking_no
            ), true);

            if(isset($json["result"]["result_success"]) && $json["result"]["result_success"] == "true")
                return true;
            else
                return [$json["sub_code"] => $json["sub_msg"]];

        }
        catch(Exception $ex) {
            return false;
        }
    }

    public function get_json_string($para) {}
}

/**
 * Class AECarrier_Send_type Enumeracion con los tipos de envio, total o parcial.
 */
abstract class AECarrier_Send_type {
    const PART_SEND_TYPE = "part";
    const ALL_SEND_TYPE = "all";
}