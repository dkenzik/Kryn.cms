<?php

global $kryn;
if( !$kryn ) die();
    
dbExec('ALTER TABLE %pfx%_system_acl DROP INDEX %pfx%_idx_kryn_system_acl_code');
dbExec('ALTER TABLE %pfx%_system_acl CHANGE code  code TEXT NULL DEFAULT NULL');

exec('patch -Np1 < inc/modules/hotfix201107/fix-37.patch'); //acl bug, #37

copy('inc/modules/hotfix201107/tiny_mce.js', 'inc/tinymce/jscripts/tiny_mce/tiny_mce.js'); //fix tinymce context menu bug, #6

exec('patch -Np1 < inc/modules/hotfix201107/fix-35-28-11.patch'); //#35, #28, #11 
    
?>