<?php

namespace App\Pages\Reference;

use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use App\Entity\Category;

class CategoryList extends \App\Pages\Base
{

    private $_category;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('CategoryList'))
            return;

        $this->add(new Panel('categorytable'))->setVisible(true);
        $this->categorytable->add(new DataView('categorylist', new \ZCL\DB\EntityDataSource('\App\Entity\Category'), $this, 'categorylistOnRow'))->Reload();
        $this->categorytable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');

        $this->add(new Form('categorydetail'))->setVisible(false);
        $this->categorydetail->add(new TextInput('editcat_code'));
        $this->categorydetail->add(new TextInput('editcat_name'));
        $this->categorydetail->add(new DropDownChoice('editcat_group', Category::findArray("cat_name")));
        $this->categorydetail->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->categorydetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
    }

    public function categorylistOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('cat_code', $item->cat_code));
        $row->add(new Label('cat_name', $item->cat_name));
        $row->add(new Label('cat_group', $item->cat_group)); 

        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList'))
            return;


        $cat_id = $sender->owner->getDataItem()->cat_id;
        $cnt = \App\Entity\Item::findCnt("  disabled <> 1  and cat_id=" . $cat_id);
        if ($cnt > 0) {
            $this->setError('Нельзя удалить категорию с товарами');
            return;
        }
        Category::delete($cat_id);
        $this->categorytable->categorylist->Reload();
    }

    public function editOnClick($sender) {
        $this->_category = $sender->owner->getDataItem();

        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        $this->categorydetail->editcat_code->setText($this->_category->cat_code);
        $this->categorydetail->editcat_code->setAttribute('readonly', 'readonly');
        $this->categorydetail->editcat_name->setText($this->_category->cat_name);
        $this->categorydetail->editcat_group->setOptionList($this->createOptionsList());
        $this->categorydetail->editcat_group->setValue(key(Category::find("`cat_code` = '". $this->_category->cat_group."'")));
        $this->categorydetail->editcat_group->setAttribute('disabled', 'disabled');

        var_dump(Category::find("`cat_code` = '". $this->_category->cat_group."'"));
    }

    public function addOnClick($sender) {
        $this->categorytable->setVisible(false);
        $this->categorydetail->setVisible(true);
        // Очищаем  форму
        $this->categorydetail->clean();
        $this->categorydetail->editcat_code->setAttribute('readonly', null);
        $this->categorydetail->editcat_group->setOptionList($this->createOptionsList());
        $this->categorydetail->editcat_group->setAttribute('disabled', null);

        $this->_category = new Category();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('CategoryList'))
            return;

        $parent_cat_cod = Category::findArray("cat_code")[$this->categorydetail->editcat_group->getValue()];

        $this->_category->cat_code = $parent_cat_cod .".". $this->categorydetail->editcat_code->getText();
        $this->_category->cat_name = $this->categorydetail->editcat_name->getText();
        $this->_category->cat_group = $parent_cat_cod;

        if ($this->_category->cat_code == '') {
            $this->setError("Введите код"); //ToDO Локализация
            return;
        }

        if ($this->_category->cat_name == '') {
            $this->setError("Введите наименование"); //ToDo локализация
            return;
        }

        $this->_category->Save();
        $this->categorydetail->setVisible(false);
        $this->categorytable->setVisible(true);
        $this->categorytable->categorylist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->categorytable->setVisible(true);
        $this->categorydetail->setVisible(false);
    }

    public function createOptionsList(){
        $optionsList = array();
        $categories = Category::find();

        if($this->_category->cat_group == 0){
            $max_length = 0;
        } else {
            $max_length = count(explode(".", $this->_category->cat_group));
        }

        foreach ($categories as $key => $item) {
            if($item->cat_group == 0){
                $length = 0;
            } else {
                $length = count(explode(".", $item->cat_group));
            }

            // echo $length ."(".$item->cat_group.")". "<". $max_length."(".$this->_category->cat_group.")". " || ";

            $del="";

            for ($i=0; $i < $length; $i++) { 
                $del = $del ."-";
            }

            // if($length <= $max_length && $item->cat_group != $this->_category->cat_group){
                $optionsList[$key] = $del." ".$item->cat_name;
            // }
        }

        return $optionsList;
    }

}
