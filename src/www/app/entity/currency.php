<?php

namespace App\Entity;

/**
 * Клас-сущность  категория товара
 *
 * @table=currencies
 * @keyfield=currency_id
 */
class Currency extends \ZCL\DB\Entity
{

    protected function init() {
        $this->currency_id = 0;
    }

    // public static function getList() {
    //     return Currency::findArray("storename", "");
    // }

}
