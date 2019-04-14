<?php

namespace App\Html\Form;

use \Zippy\Html\Form\HtmlFormDataElement;
use \Zippy\WebApplication;
use \Zippy\Interfaces\Binding;
use \Zippy\Interfaces\ChangeListener;
use \Zippy\Interfaces\Requestable;
use \Zippy\Interfaces\AjaxRender;
use \Zippy\Interfaces\EventReceiver;
use \Zippy\Event;

/**
 * Компонент  тэга  &lt;input type=&quot;text&quot;&gt;
 */
class TextInput extends HtmlFormDataElement implements ChangeListener, Requestable, AjaxRender
{
    /**
     * Конструктор
     * @param mixed  ID
     * @param Значение элемента  или  поле  привязанного объекта
     */
    public function __construct($id, $value = null)
    {
        parent::__construct($id);
        $this->setValue($value);
        $this->setAttribute("name", $this->id);
    }

    /**
     * Возвращает  текстовое  значение
     * @return  string
     */
    public function getText()
    {
        return $this->getValue();
    }

    /**
     * Устанавливает  текстовое  значение
     * @param  string
     */
    public function setText($text)
    {
        $this->setValue($text);
    }

    /**
     * @see  HtmlComponent
     */
    public function RenderImpl()
    {
        // $this->checkInForm();

        $this->setAttribute("name", $this->id);
        $this->setAttribute("id", $this->id);

        if ($this->event != null) {
            $formid = $this->getFormOwner()->id;

            $url = $this->owner->getURLNode() . '::' . $this->id;
            $url = substr($url, 2 + strpos($url, 'q='));

            // if ($this->event->isajax == false) {

                $this->setAttribute("onchange", "javascript:{if(beforeZippy('{$this->id}') ==false) return false; $('#" . $formid . "_q').attr('value','" . $url . "');$('#" . $formid . "').submit();}");
            // } else {
            //     $_BASEURL = WebApplication::$app->getResponse()->getHostUrl();
            //     $this->setAttribute("onchange", "if(beforeZippy('{$this->id}') ==false) return false; $('#" . $formid . "_q').attr('value','" . $url . "'); submitForm('{$formid}','{$_BASEURL}/?ajax=true');");
            // }
        }

        $this->setResponseData();
    }

    protected function setResponseData()
    {
        $this->setAttribute("value", ($this->getValue()));
    }

    /**
     * @see SubmitDataRequest
     */
    public function getRequestData()
    {

        $this->setValue($_REQUEST[$this->id]);
        
    }


    /**
     * @see  ChangeListener
     */
    public function onChange(EventReceiver $receiver, $handler, $ajax = true)
    {

        $this->event = new Event($receiver, $handler);
        $this->event->isajax = $ajax;
    }

    /**
     * @see ChangeListener
     */
    public function OnEvent()
    {
        if ($this->event != null) {
            $this->event->onEvent($this);
        }
    }

    /**
     * @see Requestable
     */
    public function RequestHandle()
    {
        $this->OnEvent();
    }

  

    /**
     * @see AjaxRender
     */
    public function AjaxAnswer()
    {

        $text = $this->getValue();
        return "$('#{$this->id}').val('{$text}')";
    }

    
     public function clean(){
        $this->setText('');
     }
}
