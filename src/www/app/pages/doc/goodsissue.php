<?php

namespace App\Pages\Doc;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\AutocompleteTextInput;
use \App\Html\Form\BindedTextInput;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Date;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use \App\Entity\Messure;
use \App\Entity\Currency;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;
use \App\System;
use \App\Application as App;

/**
 * Страница  ввода  расходной накладной
 */
class GoodsIssue extends \App\Pages\Base
{

    public $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;
    private $_order_id = 0;
    private $_discount;
    
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new Date('sent_date'));
        $this->docform->add(new Date('delivery_date'));
        $this->docform->add(new CheckBox('planned'));
        $this->docform->add(new CheckBox('payed'));


        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()))->onChange($this, 'OnChangeStore');

        $this->docform->add(new Label('discount'))->setVisible(false);
         $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()))->onChange($this, 'OnChangePriceType');
        $this->docform->add(new DropDownChoice('emp', \App\Entity\Employee::findArray('emp_name', '', 'emp_name')));
  
        $this->docform->add(new DropDownChoice('delivery', array(1 => 'Самовывоз', 2 => 'Курьер', 3 => 'Почта'),1))->onChange($this, 'OnDelivery');

        $this->docform->add(new TextInput('order'));
   
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new TextInput('ship_number'));
        $this->docform->add(new TextInput('ship_address'));


        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addRowOnClick');
        $this->docform->add(new SubmitLink('addrows'))->onClick($this, 'addRowsOnClick');

        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'saveDocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'saveDocOnClick');
        $this->docform->add(new SubmitButton('senddoc'))->onClick($this, 'saveDocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');

        $this->docform->add(new Label('total_amount'));
        $this->docform->add(new Label('total_quantity'));

        // Массовое добавление  товаров в счет-фактуру
        $this->add(new Form('additems'))->setVisible(false);
        $this->additems->add(new BindedTextInput('addItem', ".reference table"))->onText($this, 'OnAutoItem2');

        $this->additems->add(new Button('cancelAddItems'))->onClick($this, 'cancelAddItemsOnClick');
        $this->additems->add(new SubmitButton('saveAddedItems'))->onClick($this, 'saveAddedItemsOnClick');


        //Добавление нового товара в счет-фактуру
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true); //ToDo Выяснить что за метод!

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelRowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saveRowOnClick');

        //добавление нового контрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->planned->setChecked($this->_doc->headerdata['planned']);

            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->sent_date->setDate($this->_doc->headerdata['sent_date']);
            $this->docform->delivery_date->setDate($this->_doc->headerdata['delivery_date']);
            $this->docform->ship_number->setText($this->_doc->headerdata['ship_number']);
            $this->docform->ship_address->setText($this->_doc->headerdata['ship_address']);
            $this->docform->emp->setValue($this->_doc->headerdata['emp_id']);
            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);

            $this->docform->store->setValue($this->_doc->headerdata['store']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->_order_id = $this->_doc->headerdata['order_id'];


            foreach ($this->_doc->detaildata as $item) {
                $stock = new Stock($item);
                $this->_tovarlist[$stock->stock_id] = $stock;
            }
        } else {
            $this->_doc = Document::create('GoodsIssue');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                    if ($basedoc->meta_name == 'Order') {
                        $this->_order_id = $basedocid;
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                        $this->OnChangeCustomer($this->docform->customer);

                        $this->docform->pricetype->setValue($basedoc->headerdata['pricetype']);
                        $this->docform->store->setValue($basedoc->headerdata['store']);
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                        $this->docform->ship_address->setText($basedoc->headerdata['address']);
                        $this->docform->delivery->setValue($basedoc->headerdata['delivery']);
                        $this->docform->sent_date->setDate($basedoc->headerdata['sent_date']);
                        $this->docform->delivery_date->setDate($basedoc->headerdata['delivery']);

                        $notfound = array();
                        $order = $basedoc->cast();

                        $ttn = false;
                        //проверяем  что уже есть отправка
                        $list = $order->ConnectedDocList();
                        foreach ($list as $d) {
                            if ($d->meta_name == 'GoodsIssue') {
                                $ttn = true;
                            }
                        }

                        if ($ttn) {
                            $this->setWarn('У заказа  уже  есть отправка');
                        }

                        foreach ($order->detaildata as $item) {
                            $stlist = Stock::pickup($order->headerdata['store'], $item['item_id'], $item['quantity']);
                            if (count($stlist) == 0) {
                                $notfound[] = $item['itemname'] . "({$item['quantity']}шт) ";
                                ;
                            } else {
                                foreach ($stlist as $st) {
                                    $st->price = $item['price'];
                                    $this->_tovarlist[$st->stock_id] = $st;
                                }
                            }
                        }
                        //если  не  все  партии найдены
                        if (count($notfound) > 0) {
                            $this->setWarn('Не найдено достаточное количество для  ' . implode(',', $notfound));
                        }
                    }
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
        $this->docform->payed->setChecked($this->_doc->datatag == $this->_doc->amount);
        $this->calcTotal();
        $this->OnDelivery($this->docform->delivery);
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('tovar', $item->itemname));
        // $row->add(new Label('partion', $item->partion));
        $row->add(new Label('barcode', $item->bar_code));
        $row->add(new Label('msr', $item->msr));

        $row->add(new Label('quantity', H::fqty($item->quantity)));

        $row->add(new Label('price_1', H::famt($item->price)));
        $row->add(new Label('amount_1', H::famt($item->quantity * $item->price)));

        $row->add(new Label('price_2', H::famt($item->price)));
        $row->add(new Label('amount_2', H::famt($item->quantity * $item->price)));

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function editOnClick($sender) {
        $stock = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($stock->quantity);
        $this->editdetail->editprice->setText($stock->price);

        $this->editdetail->edittovar->setKey($stock->stock_id);
        $this->editdetail->edittovar->setText($stock->itemname);


        // $this->editdetail->qtystock->setText(Stock::getQuantity($stock->stock_id));

        $this->_rowid = $stock->stock_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;

        $tovar = $sender->owner->getDataItem();
        // unset($this->_tovarlist[$tovar->tovar_id]);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($tovar->stock_id => $this->_tovarlist[$tovar->stock_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

     //Добавление одного товара
    public function addRowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->docform->setVisible(true);
        $this->_rowid = 0;
    }

    public function saveRowOnClick($sender) {

        $id = $this->editdetail->edittovar->getKey();
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }

        $stock = Stock::load($id);
        $stock->quantity = $this->editdetail->editquantity->getText();


        $stock->price = $this->editdetail->editprice->getText();


        unset($this->_tovarlist[$this->_rowid]);
        $this->_tovarlist[$stock->stock_id] = $stock;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
        $this->calcTotal();
    }

    public function cancelRowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");

        $this->editdetail->editprice->setText("");
    }

     // Массовый ввод    
    public function addRowsOnClick($sender) {
        $this->additems->setVisible(true);
        $this->docform->setVisible(false);
    }

    public function cancelAddItemsOnClick(){
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function saveAddedItemsOnClick(){
        $isItemsLacks;
        $value = $this->additems->addItem->getKey();

        $arr = explode("||", $value);
        if (count($arr) == 1 && count(explode("_", $arr[0]))<3) {
            $this->setError("Не выбран товар");
            return;
        }

        foreach ($arr as $key => $item) {
            $arr2 = explode("_", $item);
            $stock_id = $arr2[0];
            $quantity = $arr2[1]; 
            $price = $arr2[2];

            $stock = Stock::findOne("stock_id='".$stock_id."'");
            $item = Item::load($stock->item_id);
            $item->quantity = $quantity;

            //ToDo подготовит полноценную валидацию количества товара
            // $msr = Messure::findOne("messure_id='".$item->msr_id."'");

            // if($item->quantity > $stock->qty){
            //     $this->setError("Нет такого количества товара в наличии. На складе всего ". $stock->qty ."".$msr->messure_short_name. " ". $item->itemname);
            //     $isItemsLacks = true;
            //     break;
            // }

            $item->stock_id = $stock_id;
            $item->price = $stock->partion;
            $item->price_selling = $price;

            unset($this->_tovarlist[$this->_rowid]);
            $this->_tovarlist[$item->item_id] = $item;            
        }

        // if($isItemsLacks) return;

        $this->additems->addItem->clean();
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
    }

    // Сохранение всего документа

    public function saveDocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->order = $this->docform->order->getText();

        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }
        $order = Document::load($this->_order_id);

        $this->calcTotal();
        $old = $this->_doc->cast();

        $this->_doc->headerdata = array(
            'order' => $this->docform->order->getText(),
            'ship_address' => $this->docform->ship_address->getText(),
            'ship_number' => $this->docform->ship_number->getText(),
            'delivery' => $this->docform->delivery->getValue(),
            'planned' => $this->docform->planned->isChecked() ? 1 : 0,
            'store' => $this->docform->store->getValue(),
            'emp_id' => $this->docform->emp->getValue(),
            'emp_name' => $this->docform->emp->getValueName(),
            'pricetype' => $this->docform->pricetype->getValue(),
            'pricetypename' => $this->docform->pricetype->getValueName(),
            'delivery_date' => $this->docform->delivery_date->getDate(),
            'sent_date' => $this->docform->sent_date->getDate(),
            'order_id' => $this->_order_id
        );
        $this->_doc->detaildata = array();
        foreach ($this->_tovarlist as $tovar) {
            $this->_doc->detaildata[] = $tovar->getData();
        }

        $this->_doc->amount = $this->docform->total_amount->getText();
        if ($this->docform->payed->isChecked() == true && $this->_doc->datatag < $this->_doc->amount) {

            $this->_doc->addPayment(System::getUser()->user_id, $this->_doc->amount - $this->_doc->datatag);
            $this->_doc->datatag = $this->_doc->amount;
        }
        $isEdited = $this->_doc->document_id > 0;



        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                
                $order = Document::load($this->_doc->headerdata['order_id']);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_DELIVERED);

                }
            } else   
                if ($sender->id == 'senddoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                $this->_doc->updateStatus(Document::STATE_INSHIPMENT);
                $this->_doc->headerdata['sent_date']=time();
                $this->_doc->save();                
                
                $order = Document::load($this->_doc->headerdata['order_id']);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_INSHIPMENT);

                }
            } else {
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_INPROCESS);
                }
            }

            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }
            $conn->CommitTrans();
            if ($isEdited)
                App::RedirectBack();
            else
                App::Redirect("\\App\\Pages\\Register\\GIList");
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);
            return;
        }
    }

    /**
     * Расчет  общего количества
     *
     */
    private function calcOrderQuantity() {

        $quantity = 0;

        foreach ($this->_tovarlist as $item) {
            $quantity = $quantity + $item->quantity;
        }
        $this->docform->total_quantity->setText(H::fqty($quantity));
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_tovarlist as $item) {
            $item->amount = $item->price * $item->quantity;

            $total = $total + $item->amount;
        }
        $this->docform->total_amount->setText(H::famt($total));
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (count($this->_tovarlist) == 0) {
            $this->setError("Не веден ни один  товар");
        }
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не выбран  склад");
        }
        if ($this->docform->customer->getKey() == 0 && trim($this->docform->customer->getText()) == '') {
            //$this->setError("Неверно введен  покупатель");
        }
        return !$this->isError();
    }

    public function beforeRender() {
        parent::beforeRender();

        $this->calcTotal();
        $this->calcOrderQuantity();
    }

    public function backtolistOnClick($sender) {
        App::RedirectBack();
    }

    public function OnChangeStore($sender) {
        //очистка  списка  товаров
        $this->_tovarlist = array();
        $this->docform->detail->Reload();
    }

    public function OnChangeItem($sender) {
        // $id = $sender->getKey();
        // $stock = Stock::load($id);
        // $this->editdetail->qtystock->setText($stock->qty);

        // $item = Item::load($stock->item_id);
        // $price = $item->getPrice($this->docform->pricetype->getValue(), $stock->partion > 0 ? $stock->partion : 0);
        // $price = $price - $price / 100 * $this->_discount;



        // $this->editdetail->editprice->setText($price);

        // $this->updateAjax(array('qtystock', 'editprice'));
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "Customer_name like " . $text);
    }

    public function OnChangeCustomer($sender) {
        $this->_discount = 0;
        $customer_id = $this->docform->customer->getKey();
        if ($customer_id > 0) {
            $customer = Customer::load($customer_id);
            $this->_discount = $customer->discount;
            $this->docform->ship_address->setText($customer->address);
        }
        if ($this->_discount > 0) {
            $this->docform->discount->setVisible(true);
            $this->docform->discount->setText('Скидка ' . $this->_discount . '%');
        } else {
            $this->docform->discount->setVisible(false);
        }        
    }

    // public function OnAutoItem($sender) {
    //     $store_id = $this->docform->store->getValue();
    //     $text = Item::qstr('%' . $sender->getText() . '%');
    //     return Stock::findArrayEx("store_id={$store_id} and qty>0    and (itemname like {$text} or item_code like {$text}) ");
    // }

    public function OnAutoItem($sender) {
        $req = $sender->getText();

        $array = $this->getStockList($req, "list");

        return $array;
    }

    public function OnAutoItem2($sender) {
        $req = $sender->getText();

        $array = $this->getStockList($req, "grid");

        return $array;
    }

    private function getStockList($req, $type){
        $where = "qty <> 0 ";

        $store = $this->docform->store->getValue();
        if ($store > 0) {
            $where = $where . " and store_id=" . $store;
        }

        $text = Stock::qstr('%' . $req . '%');
 
        $res = Stock::find($where . " and (itemname like {$text} or item_code like {$text} ) ", "itemname asc");

        $formated_resp_array = array();
        $messures = Messure::findArray("messure_short_name");
        $currency_name = Currency::findArray("currency_main_name")[System::getOptions("common")["default_currency"]];

        foreach ($res as $stock_item) { 
            $messure = $messures[Item::findOne("item_id=".$stock_item->item_id)->msr_id];

            if($type == "list"){
                $formated_res = $stock_item->itemname. " | ". H::famt($stock_item->partion) . " ".$currency_name. " | " . H::fqty($stock_item->qty) ." ". $messure;
            } else if ($type == "grid") {
                $formated_res = array();
                $formated_res["stock_id"] = $stock_item->stock_id;
                $formated_res["item_code"] = $stock_item->item_code;
                $formated_res["itemname"] = $stock_item->itemname;
                $formated_res["price"] = $stock_item->partion;
                $formated_res["currency_name"] = $currency_name;
                $formated_res["qty"] = $stock_item->qty-$stock_item->reserved_quantity;
                $formated_res["msr"] = $messure;
            }

            $formated_res_array[$stock_item->stock_id] = $formated_res;
        }
        
        return $formated_res_array;
    }

    public function OnChangePriceType($sender) {
        foreach ($this->_tovarlist as $stock) {
            $item = Item::load($stock->item_id);
            $price = $item->getPrice($this->docform->pricetype->getValue(), $stock->partion > 0 ? $stock->partion : 0);
        }
        $this->calcTotal();
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustname->getText();
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->discount->setVisible(false);
        $this->_discount = 0;
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }
    
    public function OnDelivery($sender) {

        if ($sender->getValue() == 2 || $sender->getValue() == 3) {
            $this->docform->senddoc->setVisible(true);
            $this->docform->execdoc->setVisible(false);
            $this->docform->ship_address->setVisible(true);
            $this->docform->ship_number->setVisible(true);
            $this->docform->sent_date->setVisible(true);
            $this->docform->sent_date->setVisible(true);
            $this->docform->delivery_date->setVisible(true);
            $this->docform->emp->setVisible(true);
        } else {
            $this->docform->senddoc->setVisible(false);
            $this->docform->execdoc->setVisible(true);
            $this->docform->ship_address->setVisible(false);
            $this->docform->ship_number->setVisible(false);
            $this->docform->sent_date->setVisible(false);
            $this->docform->sent_date->setVisible(false);
            $this->docform->delivery_date->setVisible(false);            
            $this->docform->emp->setVisible(false);            
        }
    }   
}
