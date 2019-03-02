<?php

namespace App\Html\Form;

use \Zippy\WebApplication;
use \Zippy\Interfaces\Requestable;
use \Zippy\Event;
use \Zippy\Interfaces\EventReceiver;

/**
 * Компонент  тэга  &lt;input type=&quot;text&quot;&gt; с  автозавершением
 */
class BindedTextInput extends TextInput implements Requestable
{

    public $minChars = 2;
    public $timeout = 100;
    private $key = 0;
    private $event2 = null;
    private $event = null;
    public $count = 12;
    public $matcher = "return true;";

    /**
     * Конструктор
     * @param  Zippy ID
     * @param  Минимальное  количество  символов
     * @param  Таймаут в  мс.
     */
    public function __construct($id, $binded, $minChars = 2, $timeout = 100, $bgupdate = false)
    {
        parent::__construct($id);
        $this->minChars = $minChars;
        $this->timeout = $timeout;
        $this->bgupdate = $bgupdate;
        $this->binded = $binded;
         
    }

    protected function onAdded()
    {
        if ($this->bgupdate) {
            $page = $this->getPageOwner();
            $this->onChange($page, 'OnBackgroundUpdate', true);
        }
    }

    public function RenderImpl()
    {
        TextInput::RenderImpl();

        $onchange = "null";

        if ($this->event2 != null) {
            $formid = $this->getFormOwner()->id;

            if ($this->event2->isajax == false) {

                $url = $this->owner->getURLNode() . '::' . $this->id;
                $url = substr($url, 2 + strpos($url, 'q='));
                $onchange = " { $('#" . $formid . "_q').attr('value','" . $url . "');$('#" . $formid . "_s').trigger('click');}";
            } else {
                $url = $this->owner->getURLNode() . "::" . $this->id;
                $url = substr($url, 2 + strpos($url, 'q='));
                $_BASEURL = WebApplication::$app->getResponse()->getHostUrl();
                $onchange = "  { $('#" . $formid . "_q').attr('value','" . $url . "'); submitForm('{$formid}','{$_BASEURL}/?ajax=true'); }";
            }
        }
        $url = $this->owner->getURLNode() . "::" . $this->id . "&ajax=true";
        
        if(strlen($this->matcher)>0){
            $matcher ="matcher:function(item) {
                    {$this->matcher}
               }, ";
        }
        $js = " 

        $('#{$this->id}').keyup(function(){  //ToDo Вытащить CSS
          $.getJSON('{$url}&text=' + this.value, function (data) {
            $('{$this->binded} tbody tr').remove();
              $.each(data, function(key, row){
                
                var tr = $('<tr>').on('click', function(event){
                    var id = $(event.target).closest('tr').find('td:first').text();
                    var text = $(event.target).closest('tr').find('td').eq(1).text();
                    
                    if($('.selectedItems #item_'+id.replace(/\./g, '-')).length == 0){
                        $('.selectedItems').append(
                            '<div class=\"item row\" id=\"item_'+ id.replace(/\./g, \"-\") +'\">' +
                                '<div class=\"col-11\">'+
                                    '<div>'+text+'</div>' +
                                    '<div class=\"row\">' +
                                        '<div class=\"form-group col-6\">' +
                                            '<label for=\"editquantity\">Количество</label>' +
                                            '<input autocomplete=\"off\" class=\"form-control qty\" type=\"text\" required=\"required\" pattern=\"[0-9\.]+\"onchange=\"window.addItems();\"/>' +
                                        '</div>' +
                                        '<div class=\"col-6 form-group\">' +
                                            '<label for=\"editprice\">Цена</label>' +
                                            '<input autocomplete=\"off\" class=\"form-control price\" type=\"text\" required=\"required\" pattern=\"[0-9\.]+\" onchange=\"window.addItems();\" />' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class=\"col-1 remove\" style=\"text-align: center;\">' +
                                    '<a>' +
                                        '<i class=\"fa fa-trash\"></i>' +
                                    '</a>' +    
                                '</div>' +
                                '<input type=\"hidden\" class=\"item_code\" value=\"'+id+'\"/>'+
                            '</div>'
                        );

                        $('#item_'+ id.replace(/\./g, \"-\")+' .remove').on('click', function(){
                            $('#item_'+ id.replace(/\./g, \"-\")).remove();
                            window.addItems();
                        });
                    }
                });

                var td = '';
                if(row.item_code) td = td + '<td>'+row.item_code+'</td>'; 
                if(row.itemname) td = td + '<td>'+row.itemname+'</td>';
                if(row.qty) td = td + '<td>'+row.qty+'</td>';
                if(row.msr) td = td + '<td>'+row.msr+'</td>';
                // if(row.item_code) td = td + '<td>'+row.item_code+'</td>';

                tr.append(td);

                $('{$this->binded} tbody').append(tr);
              });
          });
        });

        window.addItems = function(){
            var value = '';
            $('.selectedItems').children().each(function(key, group){
                var item_code = $(group).find('input.item_code').val();
                var qty = $(group).find('input.qty').val();
                var price = $(group).find('input.price').val();

                if(item_code){
                    if(key>0 && value) {
                        value = value + '||';
                    }
                    value = value+item_code+'_'+qty+'_'+price; 

                }
            });
            $(\"#{$this->id}_id\").val(value);
            console.log(value);
        }
        
        $('#{$this->id}').after('<input type=\"hidden\" id=\"{$this->id}_id\" name=\"{$this->id}_id\"  value=\"{$this->key}\"/>');

        ";

        //  $this->setAttribute("data-key", $this->key);
        $this->setAttribute("autocomplete", 'off');

        WebApplication::$app->getResponse()->addJavaScript($js, true);
    }

    /**
     * @see Requestable
     */
    public function RequestHandle()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->OnEvent2();
            return;
        }


        $this->setValue($_REQUEST['text']);
        $arr = $this->OnAutocomplete();
        if (is_array($arr)) {
            foreach ($arr as $key => $value) {
                //$posts[] = array("id"=>$key, "value"=> $value);
                $posts[] = $key . "_" . $value;
            }
        }

        $posts =$this->OnAutocomplete();


        WebApplication::$app->getResponse()->addAjaxResponse(json_encode($posts));
    }

    /**
     * Событие при автозавершении.
     * Вызывает  обработчик который  должен  вернуть  массив строк для  выпадающего списка.
     */
    public function OnAutocomplete()
    {
        if ($this->event != null) {
            return $this->event->onEvent($this);
        }
        return null;
    }

    /**
     * Устанавливает  событие
     * @param Event
     */
    public function onText(EventReceiver $receiver, $handler)
    {

        $this->event = new Event($receiver, $handler);
    }

    /**
     * @see SubmitDataRequest
     */
    public function getRequestData()
    {
        $this->setValue($_REQUEST[$this->id]);
        $this->key = $_REQUEST[$this->id . "_id"];
 
        if (strlen(trim($this->getValue())) == 0)
            $this->key = 0;
    }

    //возвращает  ключ  для   выбранного значения
    public function getKey()
    {
        return $this->key;
    }

    //
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @see  ChangeListener
     */
    public function onChange(EventReceiver $receiver, $handler, $ajax = false)
    {
        $this->event2 = new Event($receiver, $handler);
        $this->event2->isajax = $ajax;
    }

    /**
     * @see ChangeListener
     */
    public function OnEvent2()
    {
        if ($this->event2 != null) {
            $this->event2->onEvent($this);
        }
    }

     public function clean(){
        $this->setKey(0); 
        $this->setText(''); 
     }
}
