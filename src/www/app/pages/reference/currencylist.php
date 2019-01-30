<?php

namespace App\Pages\Reference;

use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\Paginator;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextArea;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Currency;  //ToDo
use App\Helper as H;

//справочник валют 

class CurrencyList extends \App\Pages\Base
{

    public $_currency = null;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CurrencyList'))
            return;

        $currencypanel = $this->add(new Panel('currencytable'));
        $currencypanel->add(new DataView('currencylist', new \ZCL\DB\EntityDataSource('\App\Entity\Currency'), $this, 'currencyListOnRow'));

        $currencypanel->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('currencyform'))->setVisible(false);

        $this->currencyform->add(new TextInput('edit_currency_name'));
        $this->currencyform->add(new TextInput('edit_currency_main_name'));
        $this->currencyform->add(new TextInput('edit_currency_coin_name'));
        $this->currencyform->add(new TextInput('edit_currency_symbol'));
        $this->currencyform->add(new TextInput('edit_iso_code'));
        $this->currencyform->add(new TextInput('edit_iso_number'));

        $this->currencyform->add(new SubmitButton('save'))->onClick($this, 'currencySaveOnClick');
        $this->currencyform->add(new Button('cancel'))->onClick($this, 'currencyCancelOnClick');
        $this->currencytable->currencylist->Reload();
    }

    public function currencyListOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('currency_id', $item->currency_id));
        $row->add(new Label('currency_name', $item->currency_name));
        $row->add(new Label('currency_main_name', $item->currency_main_name));
        $row->add(new Label('currency_coin_name', $item->currency_coin_name));
        $row->add(new Label('currency_symbol', $item->currency_symbol));
        $row->add(new Label('iso_code', $item->iso_code));
        $row->add(new Label('iso_number', $item->iso_number));

        $row->add(new ClickLink('edit'))->onClick($this, 'currencyEditOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'currenDydeleteOnClick');
    }

    public function currencyEditOnClick($sender) {
        $this->_currency = $sender->owner->getDataItem();
        $this->currencytable->setVisible(false);
        $this->currencyform->setVisible(true);

        $this->currencyform->edit_currency_name->setText($this->_currency->currency_name);
        $this->currencyform->edit_currency_main_name->setText($this->_currency->currency_main_name);
        $this->currencyform->edit_currency_coin_name->setText($this->_currency->currency_coin_name);
        $this->currencyform->edit_currency_symbol->setText($this->_currency->currency_symbol);
        $this->currencyform->edit_iso_code->setText($this->_currency->iso_code);
        $this->currencyform->edit_iso_number->setText($this->_currency->iso_number);
    }

    public function currencyDeleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CurrencyList'))
            return;

        if (false == Currency::delete($sender->owner->getDataItem()->currency_id)) {
            $this->setError("Нельзя удалить эту валюту"); //ToDo вынести в локаль
            return;
        }

        $this->currencytable->currencylist->Reload();
    }

    public function addOnClick($sender) {
        $this->currencytable->setVisible(false);
        $this->currencyform->setVisible(true);

        $this->currencyform->edit_currency_name->setText('');
        $this->currencyform->edit_currency_main_name->setText('');
        $this->currencyform->edit_currency_coin_name->setText('');
        $this->currencyform->edit_currency_symbol->setText('');
        $this->currencyform->edit_iso_code->setText('');
        $this->currencyform->edit_iso_number->setText('');

        $this->_currency = new Currency();
    }

    public function currencySaveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CurrencyList'))
            return;

        $this->_currency->currency_name = $this->currencyform->edit_currency_name->getText();
        $this->_currency->currency_main_name = $this->currencyform->edit_currency_main_name->getText();
        $this->_currency->currency_coin_name = $this->currencyform->edit_currency_coin_name->getText();
        $this->_currency->currency_symbol = $this->currencyform->edit_currency_symbol->getText();
        $this->_currency->iso_code = $this->currencyform->edit_iso_code->getText();
        $this->_currency->iso_number = $this->currencyform->edit_iso_number->getText();

        if ($this->_currency->currency_name == '') {
            $this->setError("Введите наименование"); //ToDO move to language file
            return;
        }

        $this->_currency->Save();
        $this->currencyform->setVisible(false);
        $this->currencytable->setVisible(true);
        $this->currencytable->currencylist->Reload();
    }

    public function currencyCancelOnClick($sender) {
        $this->currencyform->setVisible(false);
        $this->currencytable->setVisible(true);
    }

}
