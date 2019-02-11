<?php

namespace App\Entity;

/**
 * Клас-сущность  записи  о  движении товара на  складе.
 *
 * @table=store_stock
 * @view=store_stock_view
 * @keyfield=stock_id
 */
class Stock extends \ZCL\DB\Entity
{

    protected function init() {
        $this->stock_id = 0;
        $this->deleted = 0;
    }

    /**
     * Метод  для   получения  имени  ТМЦ  с  ценой
     *
     * @param mixed $criteria
     * @return []
     * @static
     */
    public static function findArrayEx($criteria = "", $orderbyfield = null, $orderbydir = null, $count = -1, $offset = -1) {
        if ($orderbyfield == null) {
            $orderbyfield = "itemname";
            $orderbydir = "asc";
        }
        if(strlen($criteria)>0){
           $criteria = 'qty <> 0 and ' . $criteria ; 
        } else {
           $criteria = 'qty <> 0 ' ; 
        }
        
        $entitylist = self::find($criteria, $orderbyfield, $count, $offset);

        $list = array();
        foreach ($entitylist as $key => $value) {
            $list[$key] = $value->itemname . ', ' . $value->partion;
            //  if (strlen($value->item_code) > 0)
            //     $list[$key] = $value->item_code . ', ' . $list[$key];
        }

        return $list;
    }

    /**
     * Возвращает запись  со  склада по  цене (партии  для  оптового)  товара.
     *
     * @param mixed $store_id Склад
     * @param mixed $tovar_id Товар
     * @param mixed $price Цена
     * @param mixed $create Создать  если  не   существует
     */
    public static function getStock($store_id, $item_id, $price, $create = false) {

        $where = "store_id = {$store_id} and item_id = {$item_id} and partion = {$price} ";

        $conn = \ZDB\DB::getConnect();

        //на  случай если удален
        //$conn->Execute("update store_stock set deleted=0 where " . $where);

        $stock = self::findOne($where);
        if ($stock == null && $create == true) {
            $stock = new Stock();
            $stock->store_id = $store_id;
            $stock->item_id = $item_id;
            $stock->partion = $price;

            $stock->save();
        }
        
        return $stock;
    }

    /**
     * Количество в  партии на складе на  дату
     *
     * @param mixed $stock_id
     * @param mixed $date
     * @param mixed $acc Синтетический счет
     *
     */
    public static function getQuantity($stock_id, $date = null) {
        if($stock_id >0){
            $stock = Stock::load($stock_id);
            if ($date >0) {
                $conn = \ZDB\DB::getConnect();
                $where = "   stock_id = {$stock_id} and date(document_date) <= " . $conn->DBDate($date);
                $sql = " select coalesce(sum(quantity),0) AS quantity  from entrylist_view  where " . $where;
                return $conn->GetOne($sql);
                
            } else {
              return $stock->qty;
            }   
            return 0;   
        }
        
       
    }

    /**
     * Количество зарезервинование  и  ожидаемое после  даты
     *
     * @param mixed $stock_id
     * @param mixed $date
     * @param mixed $acc Синтетический счет
     * @return mixed Массив с  двумя  значениями 'r'  и 'w'
     */
    public static function getQuantityFuture($stock_id, $date) {
        $conn = \ZDB\DB::getConnect();
        $where = "    stock_id = {$stock_id} and date(document_date) > " . $conn->DBDate($date);
        $sql = " select sum(quantity)  from entrylist_view  where  " . $where;
        return $conn->GetRow($sql);
    }

    // Поиск партий
    public static function pickup($store_id, $item_id, $qty) {
        $res = array();
        $where = "store_id = {$store_id} and item_id = {$item_id} and qty >0   ";
        $stlist = self::find($where, 'stock_id');
        foreach ($stlist as $st) {
            if ($st->qty >= $qty) {
                $st->quantity = $qty;
                $res[] = $st;
                $qty = 0;
                break;    //сразу нашлась партия
            } else {
                $st->quantity = $st->qty;
                $res[] = $st;
                $qty = $qty - $st->qty; //остаток
            }
        }
        if ($qty > 0) {  // если не  достаточно
            return array();
        }
        return $res;
    }

}

/*
select 
`st`.`stock_id` AS `stock_id`,
`st`.`item_id` AS `item_id`,
`st`.`partion` AS `partion`,
`st`.`store_id` AS `store_id`,
`i`.`itemname` AS `itemname`,
`i`.`item_code` AS `item_code`,
`i`.`cat_id` AS `cat_id`,
`i`.`msr_id` AS `msr_id`,
`i`.`bar_code` AS `bar_code`,
`i`.`cat_name` AS `cat_name`,
`stores`.`storename` AS `storename`,
`st`.`qty` AS `qty` 
from 
((`store_stock` `st` join `items_view` `i` on((`i`.`item_id` = `st`.`item_id`))) join `stores` on((`stores`.`store_id` = `st`.`store_id`))) 

where (`st`.`qty` <> 0)

*/