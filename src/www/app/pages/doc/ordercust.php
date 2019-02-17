<?php

namespace App\Pages\Doc;

use \Zippy\WebApplication;

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
// use App\Html\Form\TextInput;
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
 * Страница  ввода  заявки  поставщику
 */
class OrderCust extends \App\Pages\Base
{

    public $_itemlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));
        $this->docform->add(new Date('document_date'))->setDate(time());
        $this->docform->add(new DropDownChoice('document_currency', Currency::findArray("currency_name")));
        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->add(new TextInput('notes')); 

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addrows'))->onClick($this, 'addRowsOnClick');
        
        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'savedocOnClick');
        $this->docform->add(new SubmitButton('apprdoc'))->onClick($this, 'savedocOnClick');

        $this->docform->add(new Label('total'));
        $this->docform->add(new Label('order_quantity'));

        //Добавление нового товара в заказ
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edititem'))->onText($this, 'OnAutoItem');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelRowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saveRowOnClick');

        // Массовое добавление  товаров в заказ 
        $this->add(new Form('additems'))->setVisible(false);
        $this->additems->add(new BindedTextInput('addItem', ".reference table"))->onText($this, 'OnAutoItem2');
        // $this->additems->add(new SubmitLink('addnewitem'))->onClick($this, 'addnewitemOnClick');
        // $this->additems->add(new TextInput('addItemQuantity'))->setText("1");
        // $this->additems->add(new TextInput('addItemPrice'));

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
            $this->docform->document_number->setText($this->_doc->document_number);
    
            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->customer->setKey($this->_doc->customer_id);
            $this->docform->customer->setText($this->_doc->customer_name);

            foreach ($this->_doc->detaildata as $item) {         
                $item = new Item($item);
                $item->old = true;
                $this->_itemlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('OrderCust');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_itemlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::mfqty($item->price)));
        $row->add(new Label('msr', Messure::findArray("messure_short_name")[$item->msr_id])); 

        $row->add(new Label('amount', H::mfqty($item->quantity * $item->price)));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->edit->setVisible($item->old != true);

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price);


        $this->editdetail->edititem->setKey($item->item_id);
        $this->editdetail->edititem->setText($item->itemname);


        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $item = $sender->owner->getDataItem();
        unset($this->_itemlist[$item->item_id]);

        $this->_itemlist = array_diff_key($this->_itemlist, array($item->item_id => $this->_itemlist[$item->item_id]));
        $this->docform->detail->Reload();
    }

    public function addrowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(true);
        $this->_rowid = 0;
    }

    public function addRowsOnClick($sender) {
        $this->additems->setVisible(true);
        $this->docform->setVisible(false);
    }

    public function saveRowOnClick($sender) {
        $id = $this->editdetail->edititem->getKey();
        $name = trim($this->editdetail->edititem->getText());
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
        $item->price = $this->editdetail->editprice->getText();

        unset($this->_itemlist[$this->_rowid]);
        $this->_itemlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();

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

    public function cancelAddItemsOnClick(){
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function saveAddedItemsOnClick(){
        $value = $this->additems->addItem->getKey();
        $arr = explode("||", $value);
        if (count($arr) == 0) { //ToDO сделать вывод ошибки
            $this->setError("Не выбран товар");
            return;
        }

        foreach ($arr as $key => $item) {
            $arr2 = explode("_", $item);
            $item_code = $arr2[0];
            $quantity = $arr2[1]; 
            $price = $arr2[2];

            $item = Item::findOne("item_code='".$item_code."'");
            $item->quantity = $quantity;
            $item->price = $price;

            unset($this->_itemlist[$this->_rowid]);
            $this->_itemlist[$item->item_id] = $item;            
        }

        $this->additems->addItem->clean();
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
    }

    public function savedocOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;


        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = $this->docform->document_date->getDate();
        $this->_doc->currency_id = $this->docform->document_currency->getValue();

        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }
        $old = $this->_doc->cast();
        $this->calcTotal();

        // $common = System::getOptions("common");
        $order_quantity = 0;
        $currency_id = $this->docform->document_currency->getValue();
        foreach ($this->_itemlist as $item) {
        //     if ($item->old == true)
        //         continue;
        //     if ($common['useval'] != true)
        //         continue;
            $order_quantity = $order_quantity + intval($item->quantity);
            $item->currency_id = $currency_id;
        }

        $this->_doc->headerdata = array(
            'currency_id' => $currency_id,
            'order_quantity' => $order_quantity,
            'total' => $this->docform->total->getText()
        );
        $this->_doc->detaildata = array();
        foreach ($this->_itemlist as $item) {
            $this->_doc->detaildata[] = $item->getData();
        }

        $this->_doc->amount = $this->docform->total->getText();
        $isEdited = $this->_doc->document_id > 0;
 
        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            
            $this->_doc->save();

           
            if ($sender->id == 'execdoc') {
                if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_INPROCESS);
            } else if ($sender->id == 'apprdoc') {
                    if (!$isEdited)
                    $this->_doc->updateStatus(Document::STATE_NEW);

                $this->_doc->updateStatus(Document::STATE_WA);
               
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
                App::Redirect("\\App\\Pages\\Register\\OrderCustList");
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
        $this->docform->order_quantity->setText($quantity);
    }

    /**
     * Расчет  итого
     *
     */
    private function calcTotal() {

        $total = 0;

        foreach ($this->_itemlist as $item) {
            $item->amount = $item->price * $item->quantity;
            $total = $total + $item->amount;
        }
        $this->docform->total->setText($total);
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
        return Item::findArray("itemname", "(itemname like {$text} or item_code like {$text} or bar_code like {$text})");
    }

    public function OnAutoItem2($sender) {
        $text = Item::qstr('%' . $sender->getText() . '%');
        $res = Item::find("(itemname like {$text} or item_code like {$text})");

        $array1 = array();
        $array2 = array();

        foreach ($res as $item) { 
            $array1["item_code"] = $item->item_code;
            $array1["itemname"] = $item->itemname;
            $array1["msr"] = $item->msr;
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

    //добавление нового товара
    public function addnewitemOnClick($sender) {
        $this->editnewitem->setVisible(true);
        $this->editdetail->setVisible(false);

        $this->editnewitem->editnewitemname->setText('');
        $this->editnewitem->editnewitemcode->setText('');
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


    public function warehouseOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('item', $item->itemname));
        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::mfqty($item->price)));
        $row->add(new Label('msr', Messure::findArray("messure_short_name")[$item->msr_id])); 
    }

}
