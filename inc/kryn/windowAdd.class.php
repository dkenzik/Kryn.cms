<?php



/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */




/**
 * This class have to be used as motherclass in your framework classes, which
 * are defined from the links in your extension.
 * 
 * @author Kryn.labs <info@krynlabs.com>
 * @package Kryn
 * @subpackage FrameworkWindow
 * 
 */

class windowAdd extends windowEdit {

    public $versioning = false;
    
    function saveItem(){
        $tableInfo = $this->db[$this->table];

        $sql = 'INSERT INTO %pfx%'.$this->table.' ';
        foreach( $this->_fields as $key => $field ){
            
            if( $field['fake'] == true ) continue;

            $val = getArgv($key);
            print $key." => ".$val."<br/>";

            $mod = ($field['add']['modifier'])?$field['add']['modifier']:$field['modifier'];
            if( $mod ){
                $val = $this->$mod($val);
            }

            if( !empty($field['customSave']) ){
                continue;
            }

            if( $field['type'] == 'fileList' ){
                $val = json_encode( $val );
            } else if($field['type'] == 'select' && $field['multi'] && !$field['relation']) {
                $val = json_encode( $val);
            }

            $row[ $key ] = $val;
        }
        
        if( getArgv('_kryn_relation_table') ){
		    $relation = database::getRelation( getArgv('_kryn_relation_table'), $this->table );
		    if( $relation ){
                $params = getArgv('_kryn_relation_params');
                foreach( $relation['fields'] as $field_left => $field_right ){
    		          if( !$row[$field_right] ){
    		              $row[$field_right] = $params[ $field_right ];
    		          }
    		    }
		    }
		}
        
        if( $this->multiLanguage ){
        	$curLang = getArgv('lang', 2);
        	$row['lang'] = $curLang;
        }

        dbInsert( $this->table, $row );
        $this->last = database::last_id();
        $_REQUEST[$this->primary[0]] = $this->last;

        //custom saves
        
        foreach( $this->_fields as $key => $field ){
            if( !empty($field['customSave']) ){
                $func = $field['customSave'];
                $this->$func();
            }
        }
        
        //relations
        foreach( $this->_fields as $key => $field ){
            if( $field['relation'] == 'n-n' ){
                $values = json_decode( getArgv($key) );
                foreach( $values as $value ){
                    $sqlInsert = "
                        INSERT INTO %pfx%".$field['n-n']['middle']."
                        ( ".$field['n-n']['middle_keyleft'].", ".$field['n-n']['middle_keyright']." )
                        VALUES ( '".getArgv($field['n-n']['left_key'])."', '$value' );";
                    dbExec( $sqlInsert );
                }
            }
        }
        
        return array('last_id' => $this->last);
    }
}

?>
