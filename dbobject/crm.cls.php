<?php

/* CRM classes for LedgerSMB
 * 
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
 *   $company = new Company;
 *   $company->connect('foo', 'me', 'mypasswd', 'localhost', '5432');
 *  
 * or
 * 
 *   $company->dbhandle = $pg_handle_resource;
 *
 * To get a company
 *
 *   $company->get($id)
 * 
 * To save a company
 * 
 *   $company->save();
 * 
 */

namespace LedgerSMB\CRM;

class company extends \LedgerSMB\DBObject {
    public $id;
    public $entity_id;
    public $control_code;
    public $legal_name;
    public $tax_id;         # 1.4 only
    public $sales_tax_id;   # 1.4 only
    public $license_number; # 1.4 only
    public $sic_code;
    public $created;
    public $entity_class;
    /*  function get($id)
     *  Retrieves the company from the database by specified id, and sets
     *  all properties.
     *
     *  returns null when not found.
     */
    public function get($id){
        # In 1.3, the function name is company_retrieve, but in 1.4 this was
        # changed to company__get for code clarity reasons
        $procname = 'company__get';
        if ('1.3' == \LedgerSMB\Config\DBVERSION){
           $procname = 'company_retrieve';
        }
        $data = array_pop($this->call_procedure(
                 $procname = $procname, $args = array($id)
        ));
        print_r($data);
        if (null == $data['entity_id']){
            return null;
        }
        $company = new self; 
        $company->merge($data);
        $company->dbhandle = $this->{dbhandle};
        return $company;
    }
    /* function save()
     * Does a full round-trip to the db, and sets any properties to their 
     * default values.  After save() is called, the object will represent what
     * is saved in the database.
     * 
     * Note that due to a quirk on how LedgerSMB 1.3 works you must set
     * entity_class before saving.
     */
    public function save(){
        $procname = 'company__save';
        if ('1.3' == \LedgerSMB\Config\DBVERSION){
           $procname = 'company_save';
        }
        $data = array_pop($this->exec_mapped_method($procname));
        print_r($data);
        #$this->merge($data);
    }
}
