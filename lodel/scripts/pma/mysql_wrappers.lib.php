<?php

/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Functions to wrap around MySQL database functions. This is basically made
 * to allow charset conversion, but later may be useful for other stuff.
 */



if (!defined('PMA_MYSQL_WRAPPERS_LIB_INCLUDED')){
    define('PMA_MYSQL_WRAPPERS_LIB_INCLUDED', 1);

    function PMA_mysql_fetch_array($result, $type = FALSE) {
        global $cfg, $allow_recoding, $charset, $convcharset, $db;

        if ($type != FALSE) {
	    $datas = $result->FetchRow();
	    $data = array();
	    if($datas) {
	        foreach($datas as $d) $data[] = $d;
	    }
//             $data = mysql_fetch_array($result, $type);
        } else {
	    $data = $result->FetchRow();
	    if($data) {
	        foreach($data as $d) $data[] = $d;
	    }
//             $data = mysql_fetch_array($result);
        }
        if (!(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
            /* No recoding -> return data as we got them */
            return $data;
        } else {
            $ret = array();
	    $num = $result->FieldCount();
//             $num = mysql_num_fields($result);
            $i = 0;
            for($i = 0; $i < $num; $i++) {
                $meta = $result->FetchField($i);
                $name = $meta->name;
                if (!$meta) {
                    /* No meta information available -> we guess that it should be converted */
                    if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                    if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = PMA_convert_display_charset($data[$name]);
                } else {
                    /* Meta information available -> check type of field and convert it according to the type */
                    if ($meta->blob || eregi('BINARY', $meta->type)) {
                        if (isset($data[$i])) $ret[$i] = $data[$i];
                        if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = $data[$name];
                    } else {
                        if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                        if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = PMA_convert_display_charset($data[$name]);
                    }
                }
            }
            return $ret;
        }
    }

    function PMA_mysql_fetch_row($result) {
        /* nijel: This is not optimal, but keeps us from duplicating code, if
         * speed really matters, duplicate here code from PMA_mysql_fetch_array
         * with removing rows working with associative array. */
        return PMA_mysql_fetch_array($result, MYSQL_NUM);
    }

    function PMA_mysql_field_name($result, $field_index) {
        return PMA_convert_display_charset($result->FetchField($field_index)->name);
    }

    function PMA_mysql_query($query, $link_identifier = FALSE, $result_mode = FALSE) {
	global $db;

		return $db->Execute(lq(PMA_convert_charset($query)));

    }


    function PMA_mysql_select_db($database_name, $link_identifier = FALSE) {
	    global $db;
	    return $db->SelectDB(PMA_convert_charset($database_name));
    }
