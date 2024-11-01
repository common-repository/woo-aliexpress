<?php

/**
 * Class AEOrderStatus
 * @package AEBase
 *
 * Enumeración con los estados posibles xde los pedidos de aliexpress
 */
abstract class AEOrderStatus
{
    /**
     *  Esperando el pago del comprador
     */
    const PLACE_ORDER_SUCCESS = "PLACE_ORDER_SUCCESS";

    /**
     * Solicitud de cancelación del comprador
     */
    const IN_CANCEL = "IN_CANCEL";

    /**
     *  Pendiente de envío
     */
    const WAIT_SELLER_SEND_GOODS = "WAIT_SELLER_SEND_GOODS";

    /**
     *  Pedido entregado parcialmente
     */
    const SELLER_PART_SEND_GOODS = "SELLER_PART_SEND_GOODS";

    /**
     * Esperando confirmación del comprador
     */
    const WAIT_BUYER_ACCEPT_GOODS = "WAIT_BUYER_ACCEPT_GOODS";

    /**
     *  Aliesxpress está procesando el pago al vendedor
     */
    const FUND_PROCESSING = "FUND_PROCESSING";

    /**
     *  Pedido en disputa
     */
    const IN_ISSUE = "IN_ISSUE";

    /**
     *  Pedido suspenso
     */
    const IN_FROZEN = "IN_FROZEN";

    const WAIT_SELLER_EXAMINE_MONEY = "WAIT_SELLER_EXAMINE_MONEY";
    const RISK_CONTROL = "RISK_CONTROL";
    const FINISH = "FINISH";
}

abstract class AEOrderInfo {
    const DISPUTE_ORDER_INFO =  0b10000;
    const LOAN_ORDER_INFO =     0b01000;
    const LOGISTIC_ORDER_INFO = 0b00100;
    const BUYER_ORDER_INFO =    0b00010;
    const REFUND_ORDER_INFO =   0b00001;

    static function get_string($info_bits) {
        $string_bits = "";

        if (self::DISPUTE_ORDER_INFO & $info_bits) {
            $string_bits = $string_bits . "1" ;
        }
        else {
            $string_bits = $string_bits . "0" ;
        }
        if (self::LOAN_ORDER_INFO & $info_bits) {
            $string_bits = $string_bits . "1" ;
        }
        else {
            $string_bits = $string_bits . "0" ;
        }
        if (self::LOGISTIC_ORDER_INFO & $info_bits) {
            $string_bits = $string_bits . "1" ;
        }
        else {
            $string_bits = $string_bits . "0" ;
        }
        if (self::BUYER_ORDER_INFO & $info_bits) {
            $string_bits = $string_bits . "1" ;
        }
        else {
            $string_bits = $string_bits . "0" ;
        }
        if (self::REFUND_ORDER_INFO & $info_bits) {
            $string_bits = $string_bits . "1" ;
        }
        else {
            $string_bits = $string_bits . "0" ;
        }

        return $string_bits;
    }
}