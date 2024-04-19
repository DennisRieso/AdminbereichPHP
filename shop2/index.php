<?php

session_start();

include( "class/DB.class" );
include( "class/Artikel.class" );
include( "class/User.class" );
include( "class/Kategorie.class" );
include( "class/Warenkorb.class" );
include( "class/Staffelpreis.class" );
include( "class/Art_2_kat.class" );
include( "class/Art_2_war.class" );

include( "inc/system.php" );

## inital DB Connect machen
$db_con = new DB();

/*
http://127.0.0.1/shop/index.php?act=login
http://127.0.0.1/shop/index.php?act=logout
http://127.0.0.1/shop/index.php?act=manage_user
http://127.0.0.1/shop/index.php?act=list_user
http://127.0.0.1/shop/index.php
*/

### ROUTING ###
### Alle Funktionen ab hier enden im DIE() !!!

if( g('act') != null )
{
	$func_name = "act_". g('act');
	call_user_func( $func_name );
}
else
{
	home();
}


?>