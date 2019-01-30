<?php

namespace App\Entity;

/**
 * Клас-сущность  категория товара
 *
 * @table=messures
 * @keyfield=messure_id
 */
class Messure extends \ZCL\DB\Entity
{

    protected function init() {
        $this->messure_id = 0;
    }

}
