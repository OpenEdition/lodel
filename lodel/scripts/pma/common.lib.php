<?php


//
// this is a selection of common.lib.php
// Ghislain Picard



        /**
         * Adds backquotes on both sides of a database, table or field name.
         * Since MySQL 3.23.6 this allows to use non-alphanumeric characters in
         * these names.
         *
         * @param   mixed    the database, table or field name to "backquote" or
         *                   array of it
         * @param   boolean  a flag to bypass this function (used by dump
         *                   functions)
         *
         * @return  mixed    the "backquoted" database, table or field name if the
         *                   current MySQL release is >= 3.23.6, the original one
         *                   else
         *
         * @access  public
         */
        function PMA_backquote($a_name, $do_it = TRUE)
        {
            if ($do_it
                && PMA_MYSQL_INT_VERSION >= 32306
                && !empty($a_name) && $a_name != '*') {

                if (is_array($a_name)) {
                     $result = array();
                     reset($a_name);
                     foreach ($a_name as $key => $val) {
                         $result[$key] = '`' . $val . '`';
                     }
                     return $result;
                } else {
                    return '`' . $a_name . '`';
                }
            } else {
                return $a_name;
            }
        } // end of the 'PMA_backquote()' function


        /**
         * Defines the <CR><LF> value depending on the user OS.
         *
         * @return  string   the <CR><LF> value to use
         *
         * @access  public
         */
        function PMA_whichCrlf()
        {
            $the_crlf = "\n";

            // The 'PMA_USR_OS' constant is defined in "./libraries/defines.lib.php"
            // Win case
            if (PMA_USR_OS == 'Win') {
                $the_crlf = "\r\n";
            }
            // Mac case
            else if (PMA_USR_OS == 'Mac') {
                $the_crlf = "\r";
            }
            // Others
            else {
                $the_crlf = "\n";
            }

            return $the_crlf;
        } // end of the 'PMA_whichCrlf()' function


        /**
         * Add slashes before "'" and "\" characters so a value containing them can
         * be used in a sql comparison.
         *
         * @param   string   the string to slash
         * @param   boolean  whether the string will be used in a 'LIKE' clause
         *                   (it then requires two more escaped sequences) or not
         * @param   boolean  whether to treat cr/lfs as escape-worthy entities
         *                   (converts \n to \\n, \r to \\r)
         *
         * @return  string   the slashed string
         *
         * @access  public
         */
        function PMA_sqlAddslashes($a_string = '', $is_like = FALSE, $crlf = FALSE)
        {
            if ($is_like) {
                $a_string = str_replace('\\', '\\\\\\\\', $a_string);
            } else {
                $a_string = str_replace('\\', '\\\\', $a_string);
            }

            if ($crlf) {
                $a_string = str_replace("\n", '\n', $a_string);
                $a_string = str_replace("\r", '\r', $a_string);
                $a_string = str_replace("\t", '\t', $a_string);
            }

            $a_string = str_replace('\'', '\\\'', $a_string);

            return $a_string;
        } // end of the 'PMA_sqlAddslashes()' function



function PMA_mysqlDie($error_message = '', $the_query = '',
		      $is_modify_link = TRUE, $back_url = '',
		      $exit = TRUE)
{
	 trigger_error('SQL ERROR :<br />'.$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
}
?>
