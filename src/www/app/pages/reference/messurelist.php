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
use App\Entity\Messure;
use App\Helper as H;

//справочник валют 

class MessureList extends \App\Pages\Base
{

    public $_messure = null;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('MessureList'))
            return;

        $messurepanel = $this->add(new Panel('messuretable'));
        $messurepanel->add(new DataView('messurelist', new \ZCL\DB\EntityDataSource('\App\Entity\Messure'), $this, 'messureListOnRow'));
        $messurepanel->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->add(new Form('messureform'))->setVisible(false);

        $this->messureform->add(new TextInput('edit_messure_short_name'));
        $this->messureform->add(new TextInput('edit_messure_main_name'));
        $this->messureform->add(new TextInput('edit_messure_second_name'));
   
        $this->messureform->add(new SubmitButton('save'))->onClick($this, 'messureSaveOnClick');
        $this->messureform->add(new Button('cancel'))->onClick($this, 'messureCancelOnClick');
        $this->messuretable->messurelist->Reload();
    }

    public function messureListOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('messure_id', $item->messure_id));
        $row->add(new Label('messure_short_name', $item->messure_short_name));
        $row->add(new Label('messure_main_name', $item->messure_main_name));
        $row->add(new Label('messure_second_name', $item->messure_second_name));

        $row->add(new ClickLink('edit'))->onClick($this, 'messureEditOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'messureDeleteOnClick');
    }

    public function messureEditOnClick($sender) {
        $this->_messure = $sender->owner->getDataItem();
        $this->messuretable->setVisible(false);
        $this->messureform->setVisible(true);

        $this->messureform->edit_messure_short_name->setText($this->_messure->messure_short_name);
        $this->messureform->edit_messure_main_name->setText($this->_messure->messure_main_name);
        $this->messureform->edit_messure_second_name->setText($this->_messure->messure_second_name);
    }

    public function messureDeleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('MesureList'))
            return;

        if (false == Messure::delete($sender->owner->getDataItem()->messure_id)) {
            $this->setError("Нельзя удалить эту единицу измерения"); //ToDo вынести в локаль
            return;
        }

        $this->messuretable->messurelist->Reload();
    }

    public function addOnClick($sender) {
        $this->messuretable->setVisible(false);
        $this->messureform->setVisible(true);

        $this->messureform->edit_messure_short_name->setText('');
        $this->messureform->edit_messure_main_name->setText('');
        $this->messureform->edit_messure_second_name->setText('');

        $this->_messure = new Messure();
    }

    public function messureSaveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('MessureList'))
            return;

        $this->_messure->messure_short_name = $this->messureform->edit_messure_short_name->getText();
        $this->_messure->messure_main_name = $this->messureform->edit_messure_main_name->getText();
        $this->_messure->messure_second_name = $this->messureform->edit_messure_second_name->getText();

        if ($this->_messure->messure_name == '') {
            $this->setError("Введите наименование"); //ToDO move to language file
            return;
        }

        $this->_messure->Save();
        $this->messureform->setVisible(false);
        $this->messuretable->setVisible(true);
        $this->messuretable->messurelist->Reload();
    }

    public function messureCancelOnClick($sender) {
        $this->messureform->setVisible(false);
        $this->messuretable->setVisible(true);
    }

}
