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

class DBObject
{
    public $dbhandle;
    protected $schema = 'public';
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
   /* function exec_mapped_method($procname, $order = null)
    * Maps in object properties into an arg array and calls call_procedure
    *
    * db procedures are checked for argument names and these are stripped of 
    * the "in_" prefix.  After this is complete, a property is matched and
    * mapped in.
    */
    protected function exec_mapped_method($procname, $order = null){
        # variable initializations
        $procargs = array();

        # proc data lookup
        $procquery = "
            SELECT proname, pronargs, proargnames, proargtypes FROM pg_proc 
             WHERE proname = $1
               AND pronamespace = 
                   coalesce((SELECT oid FROM pg_namespace WHERE nspname = $2), 
                        pronamespace)";
         $sth = pg_query_params($this->dbhandle, $procquery, 
                               array($procname, $this->schema));
         $procdata = pg_fetch_assoc($sth);

         if (0 == pg_num_rows($sth)){
            throw new \exception('Function not found');
         }
         # building argument list
         preg_match('/^{(.*)}$/', $procdata['proargnames'], $matches);
         $procargnames = $phpArr = str_getcsv($matches[1]);
         foreach ($procargnames as $argname){
              $argname = preg_replace('/^in_/', '', $argname);
              array_push($procargs, $this->$argname);
         }

         # calling call_procedure
         return $this->call_procedure($procname, $procargs, $order);
    }
    /* function call_procedure($procname, $args = array(), $order = null)
     *
     * generates a query in the form of:
     * select * from $procname($1, $2, etc) ORDER BY colname
     * and runs it.  It returns an array of associative arrays representing the
     * output.
     */
    public function call_procedure($procname, $args = array(), $order = null){
         $results = array();
         # query generation
         $query = "select * from "
                       . pg_escape_identifier($this->schema) . "." 
                       . pg_escape_identifier($procname) . "(";
         $count = 1;
         $first = 1;
         foreach ($args as $arg){
             if (!$first){
                 $query .= ", ";
             }
             $query .= '$' . $count;
             $first = 0;
             ++ $count;
         }
         $query .= ')';
         if ($order){
            $query .= " ORDER BY " . pg_escape_identifier($order);
         }


         # execution and returning results
         $sth = pg_query_params($this->dbhandle, $query, $args);
         if (!$sth){
             return null;
         }
         for ($i = 0; $i < pg_num_rows($sth); $i++){
              print "retrieving row $i \n";
              array_push($results, pg_fetch_assoc($sth, $i));
         }
         return $results;
    }
    /* function merge(array $data)
     * merges data into the current object from an associative array
     * 
     * null or undef values are not set
     */
    public function merge($data){
        foreach ($this as $prop => $value){
             if (array_key_exists($prop, $data) and null != $data[$prop]){
                 $this->$prop = $data[$prop];
             }
        }
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


