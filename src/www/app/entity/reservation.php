<?php

namespace App\Entity;

/**
 * Клас-сущность  записи  о  зерезервированом товаре на складе.
 *
 * @table=reservation_list
 * @keyfield=reservation_id
 */
class Reservation extends \ZCL\DB\Entity
{

    protected function init() {
        $this->reservation_id = 0;
        $this->deleted = 0;
    }

    public function createItems($items, $document_id){
      $conn = \ZDB\DB::getConnect();
      $conn->StartTrans();

      foreach ($items as $row) {
        $reserved = new Reservation();
        $reserved->stock_id = $row["stock_id"];
        $reserved->document_id = $document_id;
        $reserved->quantity = $row["quantity"];
        
        $reserved->save();
      }
      
      $conn->CompleteTrans();
      return true;
    }

    public function updateItems($items, $document_id){
    
      $reserved_items = Reservation::findOne("document_id='".$document_id."'");

      foreach ($reserved_items as $reserved_item) {
        $reserved = Reservation::load($reserved_item["reservation_id"]);

        foreach ($items as $row) {;
          if($reserved->stock_id == $row["stock_id"]){
            Reservation::updateItem($reserved, $row);
          } else {
            Reservation::removeItem($reserved->stock_id, $document_id);
          }
        }
      }
    }

    private function updateItem($reserved, $update){
      $conn = \ZDB\DB::getConnect();
      $conn->BeginTrans();
      try{
        $reserved->quantity = $update["quantity"];

        $reserved->save();

        $conn->CommitTrans();
      } catch (\Exception $ee) {

      }
      return true;
    }

    public function removeItem($stock_id, $document_id){
      $conn = \ZDB\DB::getConnect();
      $conn->StartTrans();
      $conn->Execute("delete from reservation_list where stock_id='".$row['stock_id']."' AND document_id='".$document_id."'");

      $conn->CompleteTrans();

      return true;
    }

    public function removeAllItems($document_id){
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();
        $conn->Execute("delete from reservation_list where document_id =" . $document_id);
        $conn->CompleteTrans();

        return true;
    }
}

/*!50003 CREATE*/ /*!50017  */ /*!50003 TRIGGER `reservation_list_after_ins_tr` AFTER INSERT ON `reservation_list`
  FOR EACH ROW
BEGIN

 IF new.stock_id >0 then
  update store_stock set reserved_quantity=(select  coalesce(sum(quantity),0) from reservation_list where stock_id=new.stock_id) where store_stock.stock_id = new.stock_id;
 END IF;
END ;;  */


/*!50003 CREATE*/ /*!50017  */ /*!50003 TRIGGER `reservation_list_after_upd_tr` AFTER UPDATE ON `reservation_list`
  FOR EACH ROW
BEGIN

 IF new.stock_id >0 then
  update store_stock set reserved_quantity=(select  coalesce(sum(quantity),0) from reservation_list where stock_id=new.stock_id) where store_stock.stock_id = new.stock_id;
 END IF;
END ;;*/


/*!50003 CREATE*/ /*!50017  */ /*!50003 TRIGGER `eservation_list_after_del_tr` AFTER DELETE ON `eservation_list`
  FOR EACH ROW
BEGIN

 IF old.stock_id >0 then
  update store_stock set reserved_quantity=(select  coalesce(sum(quantity),0) from eservation_list where stock_id=old.stock_id) where store_stock.stock_id = old.stock_id;
 END IF;
END ;; */

