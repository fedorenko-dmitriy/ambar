<?php

namespace App\Entity\Doc;

use App\Entity\Entry;
use App\Helper as H;
use App\Entity\Currency;

/**
 * Класс-сущность  документ приходная  накладая
 *
 */
class OrderCust extends Document
{

    public function generateReport() {


        $i = 1;

        $detail = array();
        foreach ($this->detaildata as $value) {
            // var_dump($value);
            $detail[] = array("no" => $i++,
                "itemname" => $value['itemname'],
                "itemcode" => $value['item_code'],
                "quantity" => H::fqty($value['quantity']),
                "price" => H::mfqty($value['price']),
                "msr" => $value['msr'],
                "amount" => $value['amount'],
                "currency" => Currency::findArray("iso_code")[$value["currency_id"]]
            );
        }

        $header = array('date' => date('d.m.Y', $this->document_date),
            "_detail" => $detail,
            "customer_name" => $this->customer_name,
            "document_number" => $this->document_number,
            "total" => $this->headerdata["total"],
            "currency" => Currency::findArray("iso_code")[$this->headerdata["currency_id"]]
        );


        $report = new \App\Report('ordercust.tpl');

        $html = $report->generate($header );

        return $html;
    }

    public function Execute() {
 
        return true;
    }

    public function getRelationBased() {
        $list = array();

         $list['GoodsReceipt'] = 'Приходная накладная';

        return $list;
    }

}
