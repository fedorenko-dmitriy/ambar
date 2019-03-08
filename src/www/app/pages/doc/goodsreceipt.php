<?php

namespace App\Pages\Doc;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\AutocompleteTextInput;
use App\Html\Form\BindedTextInput;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Link\SubmitLink;
use App\Entity\Customer;
use App\Entity\Doc\Document;
use App\Entity\Item;
use App\Entity\Store;
use App\Entity\Messure;
use App\Entity\Currency;
use App\Helper as H;
use App\System;
use App\Application as App;

/**
 * Страница  ввода  приходной  накладной
 */
class GoodsReceipt extends \App\Pages\Base
{

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;
    private $_order_id = 0;
      
    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $common = System::getOptions("common");

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));
        $this->docform->add(new TextInput('notes'));
        $this->docform->add(new CheckBox('planned'));
        $this->docform->add(new CheckBox('payed'));


        $this->docform->add(new DropDownChoice('document_currency', Currency::findArray("currency_name"), $common["default_currency"]))->onChange($this,"onChangeCurrency");
        $this->docform->add(new TextInput('document_currency_rate'));
        $this->docform->document_currency_rate->setAttribute("disabled","disabled");

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addRowOnClick');
        $this->docform->add(new SubmitLink('addrows'))->onClick($this, 'addRowsOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'saveDocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'saveDocOnClick');
        $this->docform->add(new TextInput('order'));

        $this->docform->add(new Label('total_amount_income'));
        $this->docform->add(new Label('total_amount'));
        $this->docform->add(new Label('total_quantity'));

        $this->docform->document_currency->setVisible($common['useval'] == true);
        $this->docform->document_currency_rate->setVisible($common['useval'] == true);
        $this->docform->total_amount->setVisible($common['useval'] == true);

        //Добавление нового товара в заказ
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelRowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saveRowOnClick');

        // Массовое добавление  товаров в заказ
        $this->add(new Form('additems'))->setVisible(false);
        $this->additems->add(new BindedTextInput('addItem', ".reference table"))->onText($this, 'OnAutoItem2');

        $this->additems->add(new Button('cancelAddItems'))->onClick($this, 'cancelAddItemsOnClick');
        $this->additems->add(new SubmitButton('saveAddedItems'))->onClick($this, 'saveAddedItemsOnClick');

        //добавление нового товара в справочник номенклатуры
        $this->add(new Form('editnewitem'))->setVisible(false);
        $this->editnewitem->add(new TextInput('editnewitemname'));
        $this->editnewitem->add(new TextInput('editnewitemcode'));
        $this->editnewitem->add(new Button('cancelnewitem'))->onClick($this, 'cancelnewitemOnClick');
        $this->editnewitem->add(new SubmitButton('savenewitem'))->onClick($this, 'savenewitemOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $headerdata = $this->_doc->headerdata;

            $this->docform->document_number->setText($this->_doc->document_number);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->document_currency->setValue($headerdata['currency_id']);
            $this->docform->document_currency_rate->setValue($headerdata['currency_rate']);

            if( $headerdata['currency_id'] != $common["default_currency"]){
                $this->docform->document_currency_rate->setAttribute("disabled", null);
            }

            $this->docform->planned->setChecked($this->_doc->headerdata['planned']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->order->setText($this->_doc->headerdata['order']);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            $this->docform->store->setValue($this->_doc->headerdata['store']);

            foreach ($this->_doc->detaildata as $item) {
                $item = new Item($item);
                $item->old = true;
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('GoodsReceipt');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                   if ($basedoc->meta_name == 'OrderCust') {
                        $this->_order_id = $basedocid;
                        $this->docform->customer->setKey($basedoc->customer_id);
                        $this->docform->customer->setText($basedoc->customer_name);
                       
                        $this->_orderid = $basedocid;
                        $this->docform->order->setText($basedoc->document_number);
                  
                        $notfound = array();
                        $order = $basedoc->cast();

                        $ttn = false;
                        //проверяем  что уже есть приход
                        $list = $order->ConnectedDocList();
                        foreach ($list as $d) {
                            if ($d->meta_name == 'GoodsReceipt') {
                                $ttn = true;
                            }
                        }

                        if ($ttn) {
                            $this->setWarn('У заказа  уже  есть приход');
                        }

                        foreach ($order->detaildata as $_item) {
                             $item = new Item($_item);
                             $this->_itemlist[$item->item_id] = $item;
                        }
                    }
                     
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;

        $this->docform->payed->setChecked($this->_doc->datatag == $this->_doc->amount);

        $this->docform->document_currency->setVisible($common['useval'] == true);
        $this->docform->document_currency_rate->setVisible($common['useval'] == true);
    }

    public function detailOnRow($row) {
        $common = System::getOptions("common");
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('bar_code', $item->bar_code));
        $row->add(new Label('msr', Messure::findArray("messure_short_name")[$item->msr_id]));
        $row->add(new Label('quantity', H::fqty($item->quantity)));

        $row->add(new Label('price_income', H::famt($item->price_income)));
        $row->add(new Label('amount_income', H::famt($item->quantity * $item->price_income)));

        if($common['useval'] == true){
            if(!isset($item->price)){
                $item->price = $item->price_income * $this->getCurrencyRate();
            }
            $row->add(new Label('price', H::famt($item->price)));
            $row->add(new Label('amount', H::famt($item->quantity * $item->price)));
        }

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price_income);

        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);

        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        // unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        // $this->calcTotal(); //ToDO

        $this->docform->detail->Reload();
    }

    // Добавление одной позиции
    public function addRowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(true);
        $this->_rowid = 0;
    }

    public function saveRowOnClick($sender) {
        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
        $currency_rate = $this->docform->document_currency_rate->getValue();
        $currency_rate = empty($currency_rate) ? 1 : $currency_rate;

        if ($id == 0 && strlen($name) < 2) {
            $this->setError("Не выбран товар");
            return;
        }
        if ($id == 0) {
            $item = new Item();  // создаем новый
            $item->itemname = $name;
            //todo наценка по дефолту
            $item->save();
            $id = $item->item_id;
        }

        $item = Item::load($id);
        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price_income = $this->editdetail->editprice->getText();
        $item->price = $item->price_income * $currency_rate;

        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        // $this->calcTotal();  //ToDO

        //очищаем  форму
        $this->editdetail->edititem->setKey(0);
        $this->editdetail->edititem->setText('');
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
    }

    public function cancelRowOnClick($sender) {
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
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
        $value = $this->additems->addItem->getKey();
        $currency_rate = $this->docform->document_currency_rate->getValue();
        $currency_rate = empty($currency_rate) ? 1 : $currency_rate;

        $arr = explode("||", $value);
        if (count($arr) == 1 && count(explode("_", $arr[0]))<3) {
            $this->setError("Не выбран товар");
            return;
        }

        foreach ($arr as $key => $item) {
            $arr2 = explode("_", $item);
            $item_id = $arr2[0];
            $quantity = $arr2[1]; 
            $price = $arr2[2];

            $item = Item::findOne("item_id='".$item_id."'");
            $item->quantity = $quantity;
            $item->price_income = $price;
            $item->price = $item->price_income * $currency_rate;

            unset($this->_itemlist[$this->_rowid]);
            $this->_itemlist[$item->item_id] = $item;            
        }

        $this->additems->addItem->clean();
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
    }

    public function saveDocOnClick($sender) {
        $common = System::getOptions("common");
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }
        $old = $this->_doc->cast();

        $this->calcTotal();
        $this->convertCurrency();

        $this->_doc->headerdata = array(
            'order' => $this->docform->order->getText(),
            'store' => $this->docform->store->getValue(),
            'planned' => $this->docform->planned->isChecked() ? 1 : 0,
            'total' => $this->docform->total_amount->getText(),
            'order_id' => $this->_order_id,
            'currency_id' => $this->docform->document_currency->getValue(),
            'currency_rate' => $this->getCurrencyRate()
        );

        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $item = $item->getData();
            $item['currency_id'] = $this->_doc->headerdata['currency_id'];
            $item['currency_rate'] = $this->_doc->headerdata['currency_rate'];
            $this->_doc->detaildata[] = $item;
        }

        $this->_doc->amount = intval($this->docform->total_amount->getText());
        $isEdited = $this->_doc->document_id > 0;

        if ($this->docform->payed->isChecked() == true && $this->_doc->datatag < $this->_doc->amount) {

            $this->_doc->addPayment(System::getUser()->user_id, $this->_doc->amount == $this->_doc->datatag);
            $this->_doc->datatag = $this->_doc->amount;
        }

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();
            $order = Document::load($this->_doc->headerdata['order_id']);
    
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                // var_dump($this->_doc); die();

                $this->_doc->updateStatus(Document::STATE_EXECUTED);
                if ($order instanceof Document) {
                    $order->updateStatus(Document::STATE_CLOSED);
                }                
            } else {
                
                $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);
            }

            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }

            $conn->CommitTrans();

            if ($isEdited){          
                App::RedirectBack();
            } 
            else{
                App::Redirect("\\App\\Pages\\Register\\GRList");
            }
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            $this->setError($ee->getMessage());
            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return;
        }
        App::RedirectBack();
    }


    /**
     * Расчет  общего количества
     *
     */
    private function calcOrderQuantity() {

        $quantity = 0;

        foreach ($this->_itemlist as $item) {
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
        $currency_rate = $this->getCurrencyRate();

        foreach ($this->_itemlist as $item) {
            $item->amount_income = $item->price_income * $item->quantity;
            $item->amount = ($item->amount_income * $currency_rate);
            $total = $total + $item->amount_income;
        }
        $this->docform->total_amount_income->setText(H::famt($total));
        $this->docform->total_amount->setText(H::famt($total * $this->getCurrencyRate()));
    }

    /**
     * Конвертаця валюты
     *
     */
    private function convertCurrency() {
        $common = System::getOptions("common");

        foreach ($this->_itemlist as $item) {
            $item->price = $item->price_income * $this->getCurrencyRate();
        }
    }

    private function getCurrencyRate(){
        $currency_id = $this->docform->document_currency->getValue();
        $currency_rate = $this->docform->document_currency_rate->getValue();

        if(empty($currency_rate) || $currency_id == $common['default_currency']){
            $currency_rate = 1;
        }

        return $currency_rate;
    }

    /**
     * Валидация   формы
     *
     */
    private function checkForm() {
        if (strlen($this->_doc->document_number) == 0) {
            $this->setError('Введите номер документа');
        }
        if (count($this->_itemlist) == 0) {
            $this->setError("Не введен ни один  товар");
        }
        if ($this->docform->store->getValue() == 0) {
            $this->setError("Не выбран  склад");
        }
        if ($this->docform->customer->getKey() == 0) {
            $this->setError("Не выбран  поставщик");
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

    public function OnAutoItem($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        return Item::findArray('itemname', "itemname like {$text} or item_code like {$text} or bar_code={$text}");
        // return Item::findArray('itemname', "(itemname like {$text} or item_code like {$text} or bar_code={$text}) and disabled <> 1"); //ToDo
    }

    public function OnAutoItem2($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $res = Item::find("(itemname like {$text} or item_code like {$text})");

        $array1 = array();
        $array2 = array();

        foreach ($res as $item) {
            $array1["item_id"] = $item->item_id;
            $array1["item_code"] = $item->item_code;
            $array1["itemname"] = $item->itemname;
            $array1["msr"] = Messure::findArray("messure_short_name")[$item->msr_id];
            $array1["item_id"] = $item->item_id;

            $array2[] = $array1;
        }

        // var_dump($array2); die();

        return $array2;
    }

    public function OnAutoCustomer($sender) {
        $text = Customer::qstr('%' . $sender->getText() . '%');
        return Customer::findArray("customer_name", "Customer_name like " . $text);
    }

    public function onChangeCurrency($sender) {
        $common = System::getOptions("common");
        if($sender->getValue() != $common["default_currency"]){
            $this->docform->document_currency_rate->setAttribute("disabled",null);
        } else {
            $this->docform->document_currency_rate->setValue("");
            $this->docform->document_currency_rate->setAttribute("disabled", "disabled");
        }

        $this->updateAjax(array('document_currency_rate'));
    }

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->editnewitemname->setText('');
        $this->editnewitem->editnewitemcode->setText('');

        var_dump(new ItemList());
    }

    public function savenewitemOnClick($sender) {
        $itemname = trim($this->editnewitem->editnewitemname->getText());
        if (strlen($itemname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $item = new Item();
        $item->itemname = $itemname;
        $item->item_code = $this->editnewitem->editnewitemcode->getText();
        $item->save();
        $this->editdetail->edititem->setText($item->itemname);
        $this->editdetail->edititem->setKey($item->item_id);

        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }

    public function cancelnewitemOnClick($sender) {
        $this->editnewitem->setVisible(false);
        $this->editdetail->setVisible(true);
    }
}