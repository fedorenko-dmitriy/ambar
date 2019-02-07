<?php

namespace App\Pages\Reference;

use \Zippy\Html\DataList\DataView;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\TextInput;
use \Zippy\Html\Form\TextArea;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\Button;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Label;
use \Zippy\Html\Link\ClickLink;
use \Zippy\Html\Panel;
use \App\Entity\Item;
use \App\Entity\Category;
use \App\Entity\Messure;
use \App\System;

class ItemList extends \App\Pages\Base
{

    private $_item;

    public function __construct($add = false) {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('ItemList'))
            return;

        $this->add(new Form('filter'))->onSubmit($this, 'OnFilter');
        $this->filter->add(new TextInput('searchkey'));
        $this->filter->add(new DropDownChoice('searchcat', Category::findArray("cat_name", "", "cat_name"), 0));

        $this->add(new Panel('itemtable'))->setVisible(true);
        $this->itemtable->add(new DataView('itemlist', new ItemDataSource($this), $this, 'itemlistOnRow'));
        $this->itemtable->add(new Label('cat_tree', self::createCategoryTree()));
        $this->itemtable->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
        $this->itemtable->itemlist->setPageSize(25);
        $this->itemtable->add(new \Zippy\Html\DataList\Paginator('pag', $this->itemtable->itemlist));


        $this->add(new Form('itemdetail'))->setVisible(false);
        $this->itemdetail->add(new TextInput('editname'));
        $this->itemdetail->add(new TextInput('editprice1'));
        $this->itemdetail->add(new TextInput('editprice2'));
        $this->itemdetail->add(new TextInput('editprice3'));
        $this->itemdetail->add(new TextInput('editprice4'));
        $this->itemdetail->add(new TextInput('editprice5'));
        $common = System::getOptions('common');
        if (strlen($common['price1']) > 0) {
            $this->itemdetail->editprice1->setVisible(true);
            $this->itemdetail->editprice1->setAttribute('placeholder', $common['price1']);
        } else {
            $this->itemdetail->editprice1->setVisible(false);
        }
        if (strlen($common['price2']) > 0) {
            $this->itemdetail->editprice2->setVisible(true);
            $this->itemdetail->editprice2->setAttribute('placeholder', $common['price2']);
        } else {
            $this->itemdetail->editprice2->setVisible(false);
        }
        if (strlen($common['price3']) > 0) {
            $this->itemdetail->editprice3->setVisible(true);
            $this->itemdetail->editprice3->setAttribute('placeholder', $common['price3']);
        } else {
            $this->itemdetail->editprice3->setVisible(false);
        }
        if (strlen($common['price4']) > 0) {
            $this->itemdetail->editprice4->setVisible(true);
            $this->itemdetail->editprice4->setAttribute('placeholder', $common['price4']);
        } else {
            $this->itemdetail->editprice4->setVisible(false);
        }
        if (strlen($common['price5']) > 0) {
            $this->itemdetail->editprice5->setVisible(true);
            $this->itemdetail->editprice5->setAttribute('placeholder', $common['price5']);
        } else {
            $this->itemdetail->editprice5->setVisible(false);
        }
        $this->itemdetail->add(new TextInput('editbarcode'));
    
        $this->itemdetail->add(new DropDownChoice('editmsr', Messure::findArray("messure_main_name")));
        $this->itemdetail->add(new DropDownChoice('editcat', Category::findArray("cat_name", "", "cat_name"), 0)); 
        $this->itemdetail->add(new TextInput('editcode'));
        $this->itemdetail->add(new TextArea('editdescription'));


        $this->itemdetail->add(new SubmitButton('save'))->onClick($this, 'OnSubmit');
        $this->itemdetail->add(new Button('cancel'))->onClick($this, 'cancelOnClick');

        if ($add == false) {
            $this->itemtable->itemlist->Reload();
        } else {
            $this->addOnClick(null);
        }
    }

    public function itemlistOnRow($row) { 
        $item = $row->getDataItem();
        $row->add(new Label('item_name', $item->itemname));
        $row->add(new Label('item_code', $item->item_code));
        $row->add(new Label('msr', Messure::findArray("messure_short_name")[$item->msr_id]));
        // $plist = array();
        // if ($item->price1 > 0)
        //     $plist[] = $item->price1;
        // if ($item->price2 > 0)
        //     $plist[] = $item->price2;
        // if ($item->price3 > 0)
        //     $plist[] = $item->price3;
        // if ($item->price4 > 0)
        //     $plist[] = $item->price4;
        // if ($item->price5 > 0)
        //     $plist[] = $item->price5;
        $row->add(new Label('price', implode(',', $plist)));
        $row->add(new Label('qty', $item->qty));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
        $row->add(new ClickLink('delete'))->onClick($this, 'deleteOnClick');

        $codes = explode(".", $item->item_code);
        $class = "";

        foreach ($codes as $key => $value) {
            if($key > 1){
                $class .= " ";
            }   
            $class .= implode(".", array_slice($codes, 0, $key));
        }

        $row->setAttribute("class", $class);
    }

    public function createCategoryTree(){
        $categories = Category::find();

        foreach (Category::find() as $key => $value) {
            $categories[$key] = $value;
        }
        // var_dump($categories);

        function buildTree($elements, $parentId = 0) {
            $branch = array();
            $newelement = array();

            foreach ($elements as $element) {            
                $newelement["cat_id"] = $element->cat_id;
                $newelement["cat_code"] = $element->cat_code;
                $newelement["cat_group"] = $element->cat_group;
                $newelement["text"] = $element->cat_name;
                $newelement["a_attr"] = array("class"=>$element->cat_code);

                if ($element->cat_group == $parentId) {

                    $children = buildTree($elements, $element->cat_code);
                    if ($children) {
                        $newelement['children'] = $children;
                    }

                    $branch[] = $newelement;
                }
            }

            return $branch;
        }

        $tree = buildTree($categories);

        echo "<script>var data=".json_encode($tree)."</script>";
    }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkEditRef('ItemList'))
            return;

        $item = $sender->owner->getDataItem();
        //проверка на партии
        if ($item->checkDelete()) {
            Item::delete($item->item_id);
        } else {
            $this->setError("Нельзя удалить  товар");
            return;
        }

        $this->itemtable->itemlist->Reload();
    }

    public function editOnClick($sender) {
        $this->_item = $sender->owner->getDataItem();
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);

        $this->itemdetail->editname->setText($this->_item->itemname);
        $this->itemdetail->editprice1->setText($this->_item->price1);
        $this->itemdetail->editprice2->setText($this->_item->price2);
        $this->itemdetail->editprice3->setText($this->_item->price3);
        $this->itemdetail->editprice4->setText($this->_item->price4);
        $this->itemdetail->editprice5->setText($this->_item->price5);
        $this->itemdetail->editcat->setValue($this->_item->cat_id);

        $this->itemdetail->editdescription->setText($this->_item->description);
        $this->itemdetail->editcode->setText($this->_item->item_code);
        $this->itemdetail->editbarcode->setText($this->_item->bar_code);
        $this->itemdetail->editmsr->setValue($this->_item->msr_id);
    }

    public function addOnClick($sender) {
        $this->itemtable->setVisible(false);
        $this->itemdetail->setVisible(true);
        // Очищаем  форму
        $this->itemdetail->clean();
        $this->itemdetail->editmsr->setValue();
        $this->_item = new Item();
    }

    public function cancelOnClick($sender) {
        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);
    }

    public function OnFilter($sender) {
        $this->itemtable->itemlist->Reload();
    }

    public function OnSubmit($sender) {
        if (false == \App\ACL::checkEditRef('ItemList'))
            return;

        $this->itemtable->setVisible(true);
        $this->itemdetail->setVisible(false);

        $this->_item->itemname = $this->itemdetail->editname->getText();
        $this->_item->cat_id = $this->itemdetail->editcat->getValue();
        $this->_item->price1 = $this->itemdetail->editprice1->getText();
        $this->_item->price2 = $this->itemdetail->editprice2->getText();
        $this->_item->price3 = $this->itemdetail->editprice3->getText();
        $this->_item->price4 = $this->itemdetail->editprice4->getText();
        $this->_item->price5 = $this->itemdetail->editprice5->getText();

        $this->_item->item_code = $this->itemdetail->editcode->getText();

        $this->_item->bar_code = $this->itemdetail->editbarcode->getText();
        $this->_item->msr_id = $this->itemdetail->editmsr->getValue();
        $this->_item->description = $this->itemdetail->editdescription->getText();


        $cat_code = Category::findArray("cat_code")[$this->_item->cat_id];
        $item_code = end(explode(".", $this->itemdetail->editcode->getText()));

        if($cat_code){
            $this->_item->item_code = $cat_code.".".$item_code;
        } else {
            $this->_item->item_code = $item_code;
        }
        

        $this->_item->Save();

        $this->itemtable->itemlist->Reload();
    }

}

class ItemDataSource implements \Zippy\Interfaces\DataSource
{

    private $page;

    public function __construct($page) {
        $this->page = $page;
    }

    private function getWhere() {

        $form = $this->page->filter;
        $where = "1=1";
        $text = trim($form->searchkey->getText());
        $cat = $form->searchcat->getValue();

        if ($cat > 0) {
            $where = $where . " and cat_id=" . $cat;
        }
        if (strlen($text) > 0) {
            $text = Item::qstr('%' . $text . '%');
            $where = $where . " and (itemname like {$text} or item_code like {$text} )  ";
        }
        return $where;
    }

    public function getItemCount() {
        return Item::findCnt($this->getWhere());
    }

    public function getItems($start, $count, $sortfield = null, $asc = null) {
        $arr = Item::find($this->getWhere(), "itemname asc", $count, $start);
        ksort($arr);
        // var_dump($arr);
        return $arr;
    }

    public function getItem($id) {
        return Item::load($id);
    }
}

/*

select 
    `items`.`item_id` AS `item_id`,
    `items`.`item_code` AS `item_code`,
    `items`.`itemname` AS `itemname`,
    `items`.`description` AS `description`,
    `items`.`detail` AS `detail`,
    `items`.`bar_code` AS `bar_code`,
    `items`.`msr_id` AS `msr_id`,
    `items`.`cat_id` AS `cat_id`,
    `item_cat`.`cat_group` AS `cat_group`,
    (select sum(`store_stock`.`qty`) from `store_stock` where (`store_stock`.`item_id` = `items`.`item_id`)) AS `qty` 
     from (`items` left join `item_cat` on((`items`.`cat_id` = `item_cat`.`cat_id`)))
UNION select 
    '' AS `item_id`,
    `item_cat`.`cat_code` AS `item_code`,
    `item_cat`.`cat_name` AS `itemname`,
    '' AS `description`,
    '' AS `detail`,
    '' AS `bar_code`,
    '' AS `msr_id`,
    `item_cat`.`cat_id` AS `cat_id`,
    `item_cat`.`cat_group` AS `cat_group`,
    '' AS `qty`
     from `item_cat`

*/
