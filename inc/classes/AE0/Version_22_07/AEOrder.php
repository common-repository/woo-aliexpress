<?php

include "AEOrderStatus.php";

/**
 * Class AEOrder_Base
 * @package AEBase Obtener pedidos de aliexpress
 */
class AEOrder_Base extends AEObject
{

    /**
     * @var int Tamaño de página por defecto (20 pedidos)
     */
    public $page_size = 2;

    /**
     * @var int Página actual
     */
    public $current_page = 1;

    /**
     * @var int número de pedidos listados en total
     */
    public $total_count_orders = 0;

    /**
     * @var int numero de páginas en total
     */
    public $total_count_page = 0;

    private $fechadesde;
    private $fechahasta;
    private $status_list;

    public function __construct($Factory)
    {
        $this->factory = $Factory;
    }

    public function get_json_string($parameters)
    {
        // TODO: Implement get_json_string() method.
    }

    /*
     * @deprecated
     * @return mixed obtener todos los pedidos
     */
    /*
    public function get_orders() {

        $wrapper = $this->factory->wrapper;

        $resp = $wrapper->getPendingOrders();
        $data = json_decode($resp,true);
        $data = $data["result"];
        $data = $data["target_list"];

        return $data;
    }

    /*
     * @deprecated
     * @param $id identificador de pedido de aliexpress
     * @return mixed datos de pedido
     */
    /*
    public function get_order($id) {

        $wrapper = $this->factory->wrapper;

        $resp = $wrapper->getOrder($id);
        $data = json_decode($resp,true);
        $data = $data["result"];
        $data = $data["data"];

        return $data;
    }
    */

    /**
     * iniciar llamadas sucesivas a una lista paginada de pedidos
     *
     * @param $start_time Fecha inicial
     * @param $end_time Fecha final
     * @param $status_list Lista de Status de pedidos de AEOrderStatus, separados por coma
     * ejemplo: [AEOrderStatus::FINISH, AEOrderStatus::FUND_PROCESSING]
     * @param int $page_size Número de pedidos por página. 20 por defecto.
     */
    public function begin_get_orders($start_time, $end_time, $status_list, $page_size = 20) {
        $this->page_size = $page_size;
        $this->current_page = 1;
        $this->total_count_orders = 0;
        $this->total_count_page = 0;
        $this->fechadesde = $start_time;
        $this->fechahasta = $end_time;
        $this->status_list = $status_list;
    }

    /**
     * Obtiene la siguiente página de pedidos iniciada con begin_get_orders
     * @return bool|false|string Devuelve la página de pedidios, lista vacía si no hay pedidos con
     * los parámetros de begin_get_orders, o false cuando se han entregado todas las páginas.
     */
    public function next_page_orders() {
        if($this->current_page == 1 || $this->current_page <= $this->total_count_page) {
            $json = $this->get_OSS_order_list($this->fechadesde,$this->fechahasta,$this->status_list, $this->page_size, $this->current_page);
            return $json;
        }
        return false;
    }

    /**
     *  Obtener una lista de pedidos con paginación, con control de paginación del usuario.
     *
     * @param $start_time
     * @param $end_time
     * @param $status_list
     * @param null $page_size
     * @param int $current_page
     * @return false|string
     */

    public function get_OSS_order_list($start_time,
                                       $end_time,
                                       $status_list,
                                       $page_size = null,
                                       $current_page = 1) {

        if(isset($page_size)) $this->page_size = $page_size;
        if($current_page > 1) $this->current_page = $current_page;

        try {
            $json = json_decode($this->factory->wrapper->getOSSOrders($start_time,
                $end_time,
                $status_list,
                $this->page_size,
                $this->current_page), true);

            if(isset($json['error'])) return false;
            $this->total_count_orders = $json["result"]["total_count"];
            $this->total_count_page = $json["result"]["total_page"];
            $this->current_page = $current_page + 1;

            return json_encode($json, JSON_PRETTY_PRINT);
        }
        catch(Exception $ex) {
            return false;
        }
    }


    /**
     * @param $start_time
     * @param $end_time
     * @param $status_list
     * @return bool|false|string
     *
     * Obtener una lista de pedidos sin paginación
     */
    public function get_allpages_OSS_order_list(
        $start_time,
        $end_time,
        $status_list) {

        $got_page = 0;
        $orders_list = [];

        try {
            do {
                $json_enc = $this->factory->wrapper->getOSSOrders(
                    $start_time, $end_time, $status_list, $this->page_size, $this->current_page + 1);
                $json = json_decode($json_enc, true);

                if (isset($json) && $json["result"]["success"] == "true") {

                    $current_orders = $json["result"]["target_list"]["order_dto"];
                    $orders_list = array_merge($orders_list, $current_orders);

                    $this->total_count_orders = $json["result"]["total_count"];
                    $this->total_count_page = $json["result"]["total_page"];
                    $this->current_page = $json["result"]["current_page"];
                    $got_page = $got_page + $this->page_size;
                } else
                    return false;

            } while ($got_page < $this->total_count_orders);
            return json_encode($orders_list, JSON_PRETTY_PRINT);

        } catch(Exception $ex) {
            return false;
        }

    }

    public function get_order_by_id($order_id, $info_bits = null)
    {
        //$info = AEOrderInfo::get_string($info_bits);
        $json = $this->factory->wrapper->getOSSOrderId($order_id, $info_bits);
        return $json;
    }

    public function get_order_receipt_info($order_id) {
        $json = $this->factory->wrapper->getOrderReceiptInfo($order_id);
        return $json;
    }
}
?>