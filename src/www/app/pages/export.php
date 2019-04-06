<?php

namespace App\Pages;

use Zippy\Html\DataList\DataView;
use App\Entity\User;
use App\Entity\Item;
use App\Entity\Store;
use App\Entity\Category;
use App\Helper as H;
use App\System;
use Zippy\WebApplication as App;
use \ZCL\DB\EntityDataSource;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;
use \Zippy\Html\Form\DropDownChoice;
use \Zippy\Html\Form\Form;
use \Zippy\Html\Form\CheckBox;
use \Zippy\Html\Form\CheckBoxList;
use \Zippy\Html\Form\SubmitButton;
use \Zippy\Html\Form\TextInput;

class Export extends \App\Pages\Base
{

    public function __construct() {
        parent::__construct();
        if (System::getUser()->acltype == 2) {
            App::Redirect('\App\Pages\Error', 'У вас нет права на экспорт');
        }

        $form = $this->add(new Form("exportform"));

        $form->add(new DropDownChoice("export_filetype", 
            array(
                0 => 'Выберите тип файла',
                1 => 'sql'
                // 2 => 'csv'
            ), 1));
        $form->add(new TextInput("export_filename"))->setText('mybackup');
        $form->add(new TextInput("export_filepath"));


        $form->add(new ExportTablesCheckBoxList("export_tables"));
        foreach ($this->getTablesForDump() as $key => $value) {
            $form->export_tables->AddCheckBox( $value["TABLE_NAME"], true, $value["TABLE_NAME"]);
        }

        $form->add(new SubmitButton("export"))->onClick($this, "onExport");
    }

    public function onExport (){
        $export_filename        = $this->exportform->export_filename->getValue();
        $export_filetype        = $this->exportform->export_filetype->getValueName();
        $export_tables          = $this->exportform->export_tables->getCheckedList();

        $export_filename = $export_filename ? $export_filename : "dump";
        $export_file = $export_filename."__".date('H-i-s')."_".date('d-m-Y')."__".rand(1,11111111).$export_filetype;

        $this->exportDatabase($export_tables, $export_file);
        //or add 5th parameter(array) of specific tables:    array("mytable1","mytable2","mytable3") for multiple tables
    }

    private function exportDatabase($tables=false, $backup_name)
    {
        $conn = \ZDB\DB::getConnect();
        $conn->Execute("SET NAMES 'utf8'");

        $target_tables = $this->getTablesForDump($tables);

        foreach($target_tables as $tableObj)
        {   
            $table          =   $tableObj["TABLE_NAME"];
            $table_type     =   $tableObj["TABLE_TYPE"];

            $result         =   $conn->Execute('SELECT * FROM '.$table);  
            $fields_amount  =   $result->recordCount();  
            $rows_num       =   $conn->affected_rows();     
            $res            =   $conn->Execute('SHOW CREATE TABLE '.$table); 
            $TableMLine     =   $res->fetchRow();
            $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine['Create Table'].";\n\n";
            
            if($table_type !=="TABLE") continue;


            for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
            {
                while($row = $result->fetchRow())  
                { //when started (and every after 100 command cycle):
                    if ($st_counter%100 == 0 || $st_counter == 0 )  
                    {
                        $content .= "\nINSERT INTO ".$table." VALUES";
                    }
                    $content .= "\n(";
                    foreach ($row as $j => $value) {
                        $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                        if (isset($row[$j]))
                        {
                            $content .= '"'.$row[$j].'"' ; 
                        }
                        else 
                        {   
                            $content .= '""';
                        }     
                        if ($j<($fields_amount-1))
                        {
                                $content.= ',';
                        }      
                    }
                    $content .=")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
                    {   
                        $content .= ";";
                    } 
                    else 
                    {
                        $content .= ",";
                    } 
                    $st_counter=$st_counter+1;
                }
            } $content .="\n\n\n";
        }
        

        header('Content-Type: application/octet-stream');   
        header("Content-Transfer-Encoding: Binary"); 
        header("Content-disposition: attachment; filename=\"".$backup_name."\"");  
        echo $content; 

        //save file
        $handle = fopen('/var/www/stores/dev.zippy-warehouse.serv/bak/db-backup-'.time().'-'.(md5(implode(',',$tables))).'.sql','w+');
        fwrite($handle, $content);
        fclose($handle);
    }

    public function getTablesForDump($tables){
        $conn = \ZDB\DB::getConnect();
     
        $queryTables = $conn->Execute("SELECT TABLE_NAME, CASE WHEN TABLE_TYPE = 'VIEW' THEN 'VIEW' ELSE 'TABLE' END AS TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dev.mdetail_warehouse'"); 

            while($row = $queryTables->fetchRow()){ 
                if(is_array($tables) && !empty($tables) && !in_array($row["TABLE_NAME"], $tables)){
                    continue;
                } 
                
                $target_tables[] = $row; 
            }  

        return $target_tables;
    }
}

class ExportTablesCheckBoxList extends \Zippy\Html\Form\CheckBoxList{
    public function RenderItem($name, $checked, $caption = "", $attr = "", $delimiter = "") {
        return " 
            <div class=\"form-check\"   >
                <input class=\"form-check-input\"   type=\"checkbox\" name=\"{$name}\" {$attr} {$checked}    >
                <label class=\"form-check-label mr-sm-2\"   >{$caption}</label>
            </div>     
        ";
    }
}

