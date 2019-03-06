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
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Link\SubmitLink;
use \App\Entity\Customer;
use \App\Entity\Doc\Document;
use App\Entity\Messure;
use \App\Entity\Item;
use \App\Entity\Stock;
use \App\Entity\Store;
use \App\Helper as H;
use \App\Application as App;

/**
 * Страница  ввода  заказа
 */
class Order extends \App\Pages\Base
{

    public $_tovarlist = array();
    private $_doc;
    private $_basedocid = 0;
    private $_rowid = 0;
    private $_discount;

    public function __construct($docid = 0, $basedocid = 0) {
        parent::__construct();

        $this->add(new Form('docform'));
        $this->docform->add(new TextInput('document_number'));

        $this->docform->add(new Date('document_date'))->setDate(time());

        $this->docform->add(new DropDownChoice('store', Store::getList(), H::getDefStore()));

        $this->docform->add(new AutocompleteTextInput('customer'))->onText($this, 'OnAutoCustomer');
        $this->docform->customer->onChange($this, 'OnChangeCustomer');

        $this->docform->add(new TextArea('notes'));


        $this->docform->add(new Label('discount'))->setVisible(false);
        $this->docform->add(new DropDownChoice('pricetype', Item::getPriceTypeList()))->onChange($this, 'OnChangePriceType');

        $this->docform->add(new DropDownChoice('delivery', array(1 => 'Самовывоз', 2 => 'Курьер', 3 => 'Почта')))->onChange($this, 'OnDelivery');
        $this->docform->add(new TextInput('email'));
        $this->docform->add(new TextInput('phone'));
        $this->docform->add(new TextInput('address'))->setVisible(false);

        $this->docform->add(new SubmitLink('addcust'))->onClick($this, 'addcustOnClick');

        $this->docform->add(new SubmitLink('addrow'))->onClick($this, 'addrowOnClick');
        $this->docform->add(new SubmitLink('addrows'))->onClick($this, 'addRowsOnClick');
        
        $this->docform->add(new SubmitButton('savedoc'))->onClick($this, 'saveDocOnClick');
        $this->docform->add(new SubmitButton('execdoc'))->onClick($this, 'saveDocOnClick');

        $this->docform->add(new Button('backtolist'))->onClick($this, 'backtolistOnClick');
        $this->docform->add(new Label('total_amount'));
        $this->docform->add(new Label('total_quantity'));

        //Добавление нового товара в счет-фактуру
        $this->add(new Form('editdetail'))->setVisible(false);
        $this->editdetail->add(new AutocompleteTextInput('edittovar'))->onText($this, 'OnAutoItem');
        $this->editdetail->add(new TextInput('editquantity'))->setText("1");
        $this->editdetail->add(new TextInput('editprice'));

        $this->editdetail->edittovar->onChange($this, 'OnChangeItem', true); //ToDo Выяснить что за метод!

        $this->editdetail->add(new Button('cancelrow'))->onClick($this, 'cancelRowOnClick');
        $this->editdetail->add(new SubmitButton('saverow'))->onClick($this, 'saveRowOnClick');

        // Массовое добавление  товаров в счет-фактуру
        $this->add(new Form('additems'))->setVisible(false);
        $this->additems->add(new BindedTextInput('addItem', ".reference table"))->onText($this, 'OnAutoItem2');

        $this->additems->add(new Button('cancelAddItems'))->onClick($this, 'cancelAddItemsOnClick');
        $this->additems->add(new SubmitButton('saveAddedItems'))->onClick($this, 'saveAddedItemsOnClick');

        //добавление нового кантрагента
        $this->add(new Form('editcust'))->setVisible(false);
        $this->editcust->add(new TextInput('editcustname'));
        $this->editcust->add(new TextInput('editcustphone'));
        $this->editcust->add(new Button('cancelcust'))->onClick($this, 'cancelcustOnClick');
        $this->editcust->add(new SubmitButton('savecust'))->onClick($this, 'savecustOnClick');

        if ($docid > 0) {    //загружаем   содержимок  документа настраницу
            $this->_doc = Document::load($docid);
            $this->docform->document_number->setText($this->_doc->document_number);

            $this->docform->document_date->setDate($this->_doc->document_date);
            $this->docform->pricetype->setValue($this->_doc->headerdata['pricetype']);

            $this->docform->delivery->setValue($this->_doc->headerdata['delivery']);
            $this->OnDelivery($this->docform->delivery);
            $this->docform->store->setValue($this->_doc->headerdata['store']);

            $this->docform->notes->setText($this->_doc->notes);
            $this->docform->email->setText($this->_doc->headerdata['email']);
            $this->docform->phone->setText($this->_doc->headerdata['phone']);
            $this->docform->address->setText($this->_doc->headerdata['address']);

            foreach ($this->_doc->detaildata as $_item) {
                $item = new Item($_item);
                $this->_tovarlist[$item->item_id] = $item;
            }
        } else {
            $this->_doc = Document::create('Order');
            $this->docform->document_number->setText($this->_doc->nextNumber());

            if ($basedocid > 0) {  //создание на  основании
                $basedoc = Document::load($basedocid);
                if ($basedoc instanceof Document) {
                    $this->_basedocid = $basedocid;
                }
            }
        }

        $this->docform->add(new DataView('detail', new \Zippy\Html\DataList\ArrayDataSource(new \Zippy\Binding\PropertyBinding($this, '_tovarlist')), $this, 'detailOnRow'))->Reload();
        if (false == \App\ACL::checkShowDoc($this->_doc))
            return;
    }

    public function detailOnRow($row) {
        $item = $row->getDataItem();

        // var_dump($item); die();

        $row->add(new Label('code', $item->item_code));
        $row->add(new Label('tovar', $item->itemname));
        $row->add(new Label('barcode', $item->bar_code));
        $row->add(new Label('msr',  Messure::findArray("messure_short_name")[$item->msr_id]));
        $row->add(new Label('quantity', H::fqty($item->quantity)));
        $row->add(new Label('price', H::famt($item->price)));
        $row->add(new Label('amount', H::famt($item->quantity * $item->price)));
        $row->add(new Label('price_selling', H::famt($item->price_selling)));
        $row->add(new Label('amount_selling', H::famt($item->quantity * $item->price_selling)));

        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
    }

    public function editOnClick($sender) {
        $item = $sender->getOwner()->getDataItem();
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(false);

        $this->editdetail->editquantity->setText($item->quantity);
        $this->editdetail->editprice->setText($item->price_selling);

        $this->editdetail->edittovar->setKey($item->item_id);
        $this->editdetail->edittovar->setText($item->itemname);

        $this->_rowid = $item->item_id;
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $tovar = $sender->owner->getDataItem();
        // unset($this->_tovarlist[$tovar->tovar_id]);

        $this->_tovarlist = array_diff_key($this->_tovarlist, array($tovar->item_id => $this->_tovarlist[$tovar->item_id]));
        $this->docform->detail->Reload();
        $this->calcTotal();
    }

    //Добавление одного товара
    public function addRowOnClick($sender) {
        $this->editdetail->setVisible(true);
        $this->docform->setVisible(true);
        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("0");
        $this->_rowid = 0;
    }

    public function saveRowOnClick($sender) {
        if (false == \App\ACL::checkEditDoc($this->_doc))
            return;
        $stock_id = $this->editdetail->edittovar->getKey();
        $stock = Stock::findOne("stock_id='".$stock_id."'");

        $id = $stock->item_id;
        if ($id == 0) {
            $this->setError("Не выбран товар");
            return;
        }

        $item = Item::load($id);
        $item->quantity = $this->editdetail->editquantity->getText();
        $item->price = $stock->partion;
        $item->price_selling = $this->editdetail->editprice->getText();

        unset($this->_tovarlist[$this->_rowid]);
        $this->_tovarlist[$item->item_id] = $item;
        $this->editdetail->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
        $this->calcTotal();
        //очищаем  форму
        $this->editdetail->edittovar->setKey(0);
        $this->editdetail->edittovar->setText('');

        $this->editdetail->editquantity->setText("1");
        $this->editdetail->editprice->setText("");
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
        $value = $this->additems->addItem->getKey();

        $arr = explode("||", $value);
        if (count($arr) == 0) { //ToDO сделать вывод ошибки
            $this->setError("Не выбран товар");
            return;
        }

        foreach ($arr as $key => $item) {
            $arr2 = explode("_", $item);
            $stock_id = $arr2[0];
            $quantity = $arr2[1]; 
            $price = $arr2[2];

            $stock = Stock::findOne("stock_id='".$stock_id."'");
            $id = $stock->item_id;

            $item = Item::load($id);
            $item->quantity = $quantity;
            $item->price = $stock->partion;
            $item->price_selling = $price;

            unset($this->_tovarlist[$this->_rowid]);
            $this->_tovarlist[$item->item_id] = $item;            
        }

        $this->additems->addItem->clean();
        $this->additems->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->detail->Reload();
    }

    // Сохранение всего документа
    public function saveDocOnClick($sender) {
        $this->_doc->document_number = $this->docform->document_number->getText();
        $this->_doc->document_date = strtotime($this->docform->document_date->getText());
        $this->_doc->notes = $this->docform->notes->getText();
        $this->_doc->customer_id = $this->docform->customer->getKey();
        if ($this->checkForm() == false) {
            return;
        }

        $this->calcTotal();
        $old = $this->_doc->cast();
        $ttn = $this->_doc->headerdata['ttn']; //запоминаем ТТН если  была
        $this->_doc->headerdata = array(
            'ttn' => $ttn,
            'delivery' => $this->docform->delivery->getValue(),
            'delivery_name' => $this->docform->delivery->getValueName(),
            'address' => $this->docform->address->getText(),
            'email' => $this->docform->email->getText(),
            'pricetype' => $this->docform->pricetype->getValue(),
            'store' => $this->docform->store->getValue(),
            'total' => $this->docform->total_amount->getText()
        );
        $this->_doc->detaildata = array();
        foreach ($this->_tovarlist as $tovar) {
            $this->_doc->detaildata[] = $tovar->getData();
        }

        $this->_doc->amount = $this->docform->total_amount->getText();
        //$this->_doc->datatag = $this->_doc->amount;
        $isEdited = $this->_doc->document_id > 0;

        $conn = \ZDB\DB::getConnect();
        $conn->BeginTrans();
        try {
            $this->_doc->save();

            $this->_doc->updateStatus($isEdited ? Document::STATE_EDITED : Document::STATE_NEW);


            if ($sender->id == 'execdoc') {
                // $this->_doc->updateStatus(Document::STATE_INPROCESS);       
            }


            if ($this->_basedocid > 0) {
                $this->_doc->AddConnectedDoc($this->_basedocid);
                $this->_basedocid = 0;
            }
            $conn->CommitTrans();
            if ($sender->id == 'execdoc') {
                App::Redirect("\\App\\Pages\\Doc\\GoodsIssue", 0, $this->_doc->document_id);
                return;
            }

            if ($isEdited)
                App::RedirectBack();
            else
                App::Redirect("\\App\\Pages\\Register\\OrderList");
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
            $item->amount = $item->price_selling * $item->quantity;

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

    public function OnChangeItem($sender) { // Применение дискаунта
        $id = $sender->getKey();
        $item = Item::load($id);
        $price = $item->getPrice($this->docform->pricetype->getValue());
        if($this->_discount){
            $price = $price - $price / 100 * $this->_discount;
        }

        // $this->editdetail->qtystock->setText(H::fqty(Item::getQuantity($id,$this->docform->store->getValue())));
        $this->editdetail->editprice->setText($price);

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
            $this->docform->phone->setText($customer->phone);
            $this->docform->email->setText($customer->email);
            $this->docform->address->setText($customer->address);
        }
        $this->calcTotal();
        if ($this->_discount > 0) {
            $this->docform->discount->setVisible(true);
            $this->docform->discount->setText('Скидка ' . $this->_discount . '%');
        } else {
            $this->docform->discount->setVisible(false);
        }
    }

    public function OnAutoItem($sender) {
       $where = "qty <> 0 ";

        $store = $this->docform->store->getValue();
        if ($store > 0) {
            $where = $where . " and store_id=" . $store;
        }

        $text = Stock::qstr('%' . $sender->getText() . '%');
 
        $res = Stock::find($where . " and (itemname like {$text} or item_code like {$text} ) ", "itemname asc");

        $array = array();
        $messures = Messure::findArray("messure_short_name");

        foreach ($res as $item) { 
            $messure = $messures[Item::findOne("item_id=".$item->item_id)->msr_id];
            $array[$item->stock_id] = $item->itemname. " | " . $item->qty ." ". $messure;
        }

        return $array;
    }

    public function OnAutoItem2($sender) {
        $where = "qty <> 0 ";

        $store = $this->docform->store->getValue();
        if ($store > 0) {
            $where = $where . " and store_id=" . $store;
        }

        $text = Stock::qstr('%' . $sender->getText() . '%');
 
        $res = Stock::find($where . " and (itemname like {$text} or item_code like {$text} ) ", "itemname asc");

        $array1 = array();
        $array2 = array();

        $messures = Messure::findArray("messure_short_name");

        foreach ($res as $item) {
            $array1["id"] = $item->stock_id;
            $array1["item_code"] = $item->item_code;
            $array1["itemname"] = $item->itemname;
            $array1["msr"] = $messures[Item::findOne("item_id=".$item->item_id)->msr_id];
            $array1["qty"] = $item->qty;

            $array2[] = $array1;
        }

        return $array2;
    }

    //добавление нового контрагента
    public function addcustOnClick($sender) {
        $this->editcust->setVisible(true);
        $this->docform->setVisible(false);

        $this->editcust->editcustname->setText('');
        $this->editcust->editcustphone->setText('');
    }

    public function savecustOnClick($sender) {
        $custname = trim($this->editcust->editcustname->getText());
        if (strlen($custname) == 0) {
            $this->setError("Не введено имя");
            return;
        }
        $cust = new Customer();
        $cust->customer_name = $custname;
        $cust->phone = $this->editcust->editcustphone->getText();
        $cust->save();
        $this->docform->customer->setText($cust->customer_name);
        $this->docform->customer->setKey($cust->customer_id);

        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
        $this->docform->discount->setVisible(false);
        $this->_discount = 0;
        $this->docform->phone->setText($cust->phone);
    }

    public function cancelcustOnClick($sender) {
        $this->editcust->setVisible(false);
        $this->docform->setVisible(true);
    }

    public function OnDelivery($sender) {

        if ($sender->getValue() == 2 || $sender->getValue() == 3) {
            $this->docform->address->setVisible(true);
        } else {
            $this->docform->address->setVisible(false);
        }
    }

    public function OnChangePriceType($sender) {
        foreach ($this->_tovarlist as $item) {
            //$item = Item::load($item->item_id);
            $price = $item->getPrice($this->docform->pricetype->getValue());
            $item->price = $price - $price / 100 * $this->_discount;
        }
        $this->calcTotal();
        $this->docform->detail->Reload();
        $this->calcTotal();
    }
}
