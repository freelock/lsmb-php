<?php

include "dbobject.cls.php";
include "dbobject/crm.cls.php";
include "config.php";

$company = new LedgerSMB\CRM\Company;

#phpinfo();
$company->dbconnect($dbname = 'mtech_test');
$company->begin();

$company2 = $company->get(1);

print_r($company2);

$company2->legal_name = 'foo';
$company2->entity_class=4;
$company2->control_code=4;

$company2->save();
$company->rollback();
$company->is_allowed_role('users_manage');
