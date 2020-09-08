<?php namespace Database;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

class MySQLException extends \Exception{
    const ER_TOO_MANY_FIELDS = 1117;
    const ER_TOO_MANY_ROWS = 1172;
    const ER_TOO_FEW_FIELDS = 3117; // Custom created errno
    const ER_TOO_FEW_ROWS = 3172; // Custom created errno
    const ER_DUP_ENTRY = 1062;
}