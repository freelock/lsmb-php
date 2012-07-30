<?php

/*
 * lsmbDBOBject - LedgerSMB DB Mapping class for PHP
 *
 * Copyright (C) 2012 Chris Travers
 *
 * Redistribution and use in source and compiled forms with or without 
 * modification, are permitted provided that the following conditions are met:
 *
 *  * Redistributions of source code must retain the above copyright notice, 
 *    this list of conditions and the following disclaimer as the first lines 
 *    of this file unmodified.
 * 
 *  * Redistributions in compiled form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * This class implements basic LedgerSMB query mapping routines for use in
 * integrating PHP scripts with LedgerSMB systems.  It could also be used to
 * write extensions using PHP as a language.
 *
 * Currently this does not provide an interface for role checking but that may
 * change in the future.
 * 
 * SYNPOSIS:
 * 
 * To call a stored procedure with arguments:
 * 
 * $dbobject = new lsmbDBObject;
 * $dbobject->connect('foo', 'me', 'mypasswd', 'localhost', '5432');
 * $dbobject->call_procedure('company__save', $my_array);
 * 
 * Inherited classes can also use exec_mapped_method to map their own properties
 * as methods.
 *
 * Also I would expect that if dbhandle is set to NULL then the last PG
 * connection opened would be used.  Note that the destructor cannot safely
 * close the db connection so this must be done by the calling script.
 */

namespace LedgerSMB;

class DB
{
    public $dbhandle;
    protected $dbobject;
    protected $schema = 'public';
    
    /**
     * function getObject
     *
     * Static method to return singleton DB object
     *
     * @return DB object
     */
    static function getObject() {
        if (!isset(self::$dbobject)) {
            self::$dbobject = new DB();
        }
        return self::$dbobject;
    }
    
    
    /* function dbconnect($dbname, $user, $password, $host, $port)
     * connects to the db with args provided and sets $this->dbhandle to 
     * connection.
     */
    public function dbconnect
                ($dbname = null, $user = null, $password = null, 
                $host = null, $port = null) 
    {
         $connstr = '';
         $args = array('dbname', 'user', 'password', 'host', 'port');
         foreach ($args as $arg){
             if ($$arg){
                 $connstr .= "$arg=" . $$arg . " ";
             }
         }
         $connstr .= "options='--client_encoding=UTF8'";
         $this->dbhandle = \pg_connect($connstr);
    }
 
    # DB SESSION MAINTENANCE FUNCTIONS
    /* function begin()
     * begins a database transaction
     */
    public function begin(){
        pg_query($this->dbhandle, 'BEGIN');
    }
    /* function commit()
     * commits the current database transaction
     */
    public function commit(){
        pg_query($this->dbhandle, 'COMMIT');
    }
    /* function rollback()
     * rolls back the current database transaction
     */
    public function rollback(){
        pg_query($this->dbhandle, 'ROLLBACK');
    }
    /* function is_allowed_role($role)
     * returns true if the user is allowed the role for the specific db 
     * i.e. $role should not include the prefix.  Otherwise it returns false
     */
    public function is_allowed_role($role){
        $query = "
        select pg_has_role(rolname, 'USAGE') as has_role 
          from pg_roles 
         where rolname = coalesce((select value from defaults
                                    where setting_key = 'role_prefix'), 
                                         'lsmb_' || current_database() || '__') 
                                         || $1";
        $sth = pg_query_params($this->dbhandle, $query, array($role));
        $results = pg_fetch_array($sth);
        print_r($results);
        return $results[0] == 't';
    }
} 


