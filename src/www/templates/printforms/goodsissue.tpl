 
<table class="ctable" border="0" cellspacing="0" cellpadding="2">
 


    <tr>
        <td></td>
        <td><b>Покупатель</b></td>
        <td colspan="4">{{customername}}</td>
    </tr>
  
    {{#isorder}}   
    <tr>
        <td></td>
        <td><b>Заказ</b></td>
        <td colspan="4">{{order}}</td>
    </tr>
    <tr>
        <td></td>
        <td><b>Адрес</b></td>
        <td colspan="4">{{ship_address}}</td>
    </tr>



    <tr>
        <td></td>
        <td><b>Декларация</b></td>
        <td colspan="4">{{ship_number}} </td>
    </tr>   
    <tr>
        <td></td>
        <td><b>Дата  отправки</b></td>
        <td colspan="4">{{sent_date}} </td>
    </tr>            
    <tr>
        <td></td>
        <td><b>Дата  доставки</b></td>
        <td colspan="4">{{delivery_date}} </td>
    </tr>            
    <tr>
        <td></td>
        <td><b>Ответственный</b></td>
        <td colspan="4"> {{emp_name}}</td>
    </tr> 
    {{/isorder}}           
    <tr>
        <td style="font-weight: bolder;font-size: larger;" align="center" colspan="6" valign="middle">
            Накладная № {{document_number}} от {{date}} <br> 
        </td>
    </tr>

    <tr style="font-weight: bolder;">
        <th style="border-top:1px #000 solid;border-bottom:1px #000 solid;" width="30">№</th>
        <th     style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Наименование</th>
        <th    style="border-top:1px #000 solid;border-bottom:1px #000 solid;text-align: left;">Код</th>
        

        <th   style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Кол.</th>
        <th   style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="60">Цена</th>
        <th   style="text-align: right;border-top:1px #000 solid;border-bottom:1px #000 solid;" width="80">Сумма</th>
    </tr>
    {{#_detail}}
    <tr>
        <td align="right">{{no}}</td>
        <td  >{{tovar_name}}</td>
        <td  >{{tovar_code}}</td>
         

        <td align="right">{{quantity}}</td>
        <td align="right">{{price}}</td>
        <td align="right">{{amount}}</td>
    </tr>
    {{/_detail}}
    <tr style="font-weight: bolder;">
        <td style="border-top:1px #000 solid;" colspan="5" align="right">Итого:</td>
        <td style="border-top:1px #000 solid;" align="right">{{total}}</td>
    </tr>


</table>

