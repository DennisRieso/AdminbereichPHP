<?php

// output function
## sollte immer die funktion sein, die finale website anzeigt
## endet mit DIE(), daher ist immer nach aufruf dieser funktion das php beendet

function output($in_content)
{
	## laden der index.html die die grundstruktur unserer seite darstellt
	$out = file_get_contents("view/index.html");
	
	## den CONTENT platzhalter mit dem aktuellen inhalt ersetzen
	$out = str_replace("###CONTENT###", $in_content, $out);
	
	// default den LOGIN link zeigen
	$text="<a href='index.php?act=login'>Login</a>";
	
	//wenn jemand angemeldet ist dann
	if(isset($_SESSION['user_id'])== true)
	{
		## den user aus datenbank laden
		$user = new User($_SESSION['user_id']);
		
		## den login-text mit einm logout text überschreiben
		$text = "Sie sind angemeldet als ". $user->username . "<br>";
		$text.= " <a href='index.php?act=logout'>Logout</a>";
	}
	
	## den LOGOUT platzhalter mit dem vorher erzeugten text ersetzen
	$out = str_replace("###Logout###", $text, $out);
	
	## den html code ausgeben und das PHP beenden
	die($out); ## ENDE DES PHP!
}


// login function
function act_login()
{
	if( g('username')  != null )
	{
		$user_id = User::login( g('username'), g('password') );
		
		if ($user_id > 0)
		{
			$user = new User($user_id);
			$_SESSION['user_id'] = $user->id;
			$_SESSION['user_typ'] = $user->typ;
		}
	}

	if(isset($_SESSION['user_id']) == false )
	{
		$html_output = file_get_contents("view/login.html");
		output( $html_output ); 
	}
	
	// wenn nichts zu tun
	home();
	
	
}


// logout function
function act_logout()
{
	unset($_SESSION['user_id']);
	unset($_SESSION['user_typ']);
	
	output("Sie sind abgemeldet!");
}

######USER######
// manage_user function
function act_manage_user()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_user.html");
	
	$tmp_user = new User( intval( g('id') ) );
	
	$user_info = "neu anlegen";
	
	## user mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$user_info = "bearbeiten (". $tmp_user->id .")";
	}
	## userdaten aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_user->username = g('username');
		
		## nur wenn ein pw eingegeben wurde, überschreiben wir das alte
		if( g('password') != "" )
		{
			$tmp_user->password = md5( g('password') );
		}
		$tmp_user->save();
		
		act_list_user();
	}
	
	## felder anzeigen
	$out = str_replace("###id###"		,$tmp_user->id, $out);
	$out = str_replace("###username###"	,$tmp_user->username, $out);
	$out = str_replace("###password###"	, "", $out);
	
	$out = str_replace("###USER_INFO###", $user_info, $out);
	
	output($out);
	

	
}


// list users
function act_list_user()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_user_ids = User::getAll();
	
	$table_html = file_get_contents( "view/list_user.html" );
	$row_html 	= file_get_contents( "view/list_user_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_user_ids as $one_user_id)
	{
		$tmp_user = new User( $one_user_id );
		
		$tmp_row = str_replace( "###ID###" 			, $tmp_user->id 		, $row_html );
		$tmp_row = str_replace( "###USERNAME###" 	, $tmp_user->username 	, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###USER_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del user
function act_delete_user()
{
	// nur admins dürfen löschen
	if($_SESSION['user_typ']<6)
	die("löschen verboten, kein admin");
	
	$tmp_user = new User( g('id') );
	$tmp_user->del_it();
	
	act_list_user();
}
######USER######

######KAT#######
// manage_kategorie function
function act_manage_kategorie()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_kategorie.html");
	
	$tmp_kategorie = new Kategorie( intval( g('id') ) );
	
	$kategorie_info = "neu anlegen";
	
	## kategorie mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$kategorie_info = "bearbeiten (". $tmp_kategorie->id .")";
	}
	## kategoriedaten aus formular in db speichern
	else if( g('send') != null )
	{
		## das bild aus temp ordner an finale stelle kopieren		
		$quell_pfad = $_FILES['bild']['tmp_name'];
		$ziel_pfad  = "uploads/kat_" . $tmp_kategorie->id;
		
		if(move_uploaded_file( $quell_pfad , $ziel_pfad ) == true)
		{
			$tmp_kategorie->name 		= g('name');
			$tmp_kategorie->parent_id 	= intval( g('parent_id') );
			$tmp_kategorie->bild 		= $ziel_pfad;
			
			$tmp_kategorie->save();
		}
		act_list_kategorie();
	}
	
	## optionsfelder zusammenbauen
	$out_opt = gen_html_options( Kategorie::getAllWithName() , $tmp_kategorie->parent_id , true);
	
	## felder anzeigen
	$out = str_replace("###id###"					,$tmp_kategorie->id, $out);
	$out = str_replace("###name###"					,$tmp_kategorie->name, $out);
	$out = str_replace("###bild###"					,$tmp_kategorie->bild, $out);
	$out = str_replace("###parent_id_options###"	,$out_opt, $out);
	
	$out = str_replace("###KATEGORIE_INFO###", $kategorie_info, $out);
	
	output($out);
	

	
}


// list kategorien
function act_list_kategorie()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_kat_ids = Kategorie::getAll();
	
	$table_html = file_get_contents( "view/list_kategorie.html" );
	$row_html 	= file_get_contents( "view/list_kategorie_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_kat_ids as $one_kat_id)
	{
		$tmp_kat 	= new Kategorie( $one_kat_id );
		$parent_kat = new Kategorie( $tmp_kat->parent_id );
		
		$tmp_row = str_replace( "###ID###" 			, $tmp_kat->id 			, $row_html );
		$tmp_row = str_replace( "###NAME###" 		, $tmp_kat->name 		, $tmp_row );
		$tmp_row = str_replace( "###PARENT_ID###" 	, $parent_kat->name 	, $tmp_row );
		$tmp_row = str_replace( "###BILD###" 		, $tmp_kat->bild 		, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###KATEGORIE_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del kat
function act_delete_kategorie()
{
	$tmp_kat = new Kategorie( g('id') );
	$tmp_kat->del_it();
	
	act_list_kategorie();
}
######KAT#######






function act_fe_add_art_2_war()
{
	## check ob das der ertse artikel im warenkorb ist
	if(  isset( $_SESSION['wk_id'] ) == false )
	{
        $wk = new Warenkorb(0);
		$wk->user_id = $_SESSION['user_id'];
		$wk->save();
		$_SESSION['wk_id'] = $wk->id;
	}			
	$art_id = g('a_id');
	$menge  = g('anz');
	$a2w = new Art_2_war(0);
	$a2w->art_id  = $art_id;
	$a2w->menge   = $menge;
	$a2w->war_id  = $_SESSION['wk_id'];
	$a2w->save();
}








######ARTIKEL#######
// manage_artikel function
function act_manage_artikel()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_artikel.html");
	
	$tmp_artikel = new Artikel( intval( g('id') ) );
	
	$artikel_info = "neu anlegen";
	
	## artikel mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$artikel_info = "bearbeiten (". $tmp_artikel->id .")";
	}
	## artikeldaten aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_artikel->name 			= g('name');
		$tmp_artikel->beschreibung 	= g('beschreibung');
		$tmp_artikel->bild 			= g('bild');
		$tmp_artikel->lagerbestand 	= g('lagerbestand');
		
		$tmp_artikel->save();
		
		act_list_artikel();
	}
	
	## felder anzeigen
	$out = str_replace("###id###"			,$tmp_artikel->id, $out);
	$out = str_replace("###name###"			,$tmp_artikel->name, $out);
	$out = str_replace("###beschreibung###"	,$tmp_artikel->beschreibung, $out);
	$out = str_replace("###bild###"			,$tmp_artikel->bild, $out);
	$out = str_replace("###lagerbestand###"	,$tmp_artikel->lagerbestand, $out);
	
	$out = str_replace("###ARTIKEL_INFO###", $artikel_info, $out);
	
	output($out);
	

	
}


// list artikel
function act_list_artikel()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_art_ids = Artikel::getAll();
	
	$table_html = file_get_contents( "view/list_artikel.html" );
	$row_html 	= file_get_contents( "view/list_artikel_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_art_ids as $one_art_id)
	{
		$tmp_kat = new Artikel( $one_art_id );
		
		$tmp_row = str_replace( "###ID###" 				, $tmp_kat->id 				, $row_html );
		$tmp_row = str_replace( "###NAME###" 			, $tmp_kat->name 			, $tmp_row );
		$tmp_row = str_replace( "###BESCHREIBUNG###" 	, $tmp_kat->beschreibung 	, $tmp_row );
		$tmp_row = str_replace( "###BILD###" 			, $tmp_kat->bild 			, $tmp_row );
		$tmp_row = str_replace( "###LAGERBESTAND###" 	, $tmp_kat->lagerbestand 	, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###ARTIKEL_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del artikel
function act_delete_artikel()
{
	$tmp_art = new Artikel( g('id') );
	$tmp_art->del_it();
	
	act_list_artikel();
}
######ARTIKEL#######







######STAFFELPREISE#######
// manage_staffelpreise function
function act_manage_staffelpreise()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_staffelpreise.html");
	
	$tmp_staffel = new Staffelpreis( intval( g('id') ) );
	
	$staffel_info = "neu anlegen";
	
	## artikel mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$staffel_info = "bearbeiten (". $tmp_staffel->id .")";
	}
	## staffelpreise aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_staffel->art_id 			= g('art_id');
		$tmp_staffel->ab_menge 			= g('ab_menge');
		$tmp_staffel->preis 			= g('preis');
		
		$tmp_staffel->save();
		
		act_list_staffelpreise();
	}
	
		###### html options zusammenbauen ######
	$all_art_ids = Artikel::getAll();
	$out_opt = "";
	
	foreach($all_art_ids as $one_art_id)
	{
		$tmp_art_opt = new Artikel($one_art_id);
		
		$sel = "";
		
		if($tmp_art_opt->id == $tmp_staffel->art_id)
			$sel = "selected";
		
		
		$out_opt .= '<option value="'.$tmp_art_opt->id.'" '.$sel.' > '.$tmp_art_opt->name.' </option>';
	}
	###### zusammenbau ende ######
	
	## felder anzeigen
	$out = str_replace("###id###"				,$tmp_staffel->id, $out);
	$out = str_replace("###art_id_options###"	,$out_opt, $out);
	$out = str_replace("###ab_menge###"			,$tmp_staffel->ab_menge, $out);
	$out = str_replace("###preis###"			,$tmp_staffel->preis, $out);
	
	$out = str_replace("###STAFFEL_INFO###"		, $staffel_info, $out);
	
	output($out);
	

	
}


// list staffelpreise
function act_list_staffelpreise()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_staffel_ids = Staffelpreis::getAll();
	
	$table_html = file_get_contents( "view/list_staffelpreise.html" );
	$row_html 	= file_get_contents( "view/list_staffelpreise_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_staffel_ids as $one_staffel_id)
	{
		$tmp_kat = new Staffelpreis( $one_staffel_id );
		
		$tmp_row = str_replace( "###ID###" 				, $tmp_kat->id 				, $row_html );
		$tmp_row = str_replace( "###ART_ID###" 			, $tmp_kat->art_id 			, $tmp_row );
		$tmp_row = str_replace( "###AB_MENGE###" 		, $tmp_kat->ab_menge 		, $tmp_row );
		$tmp_row = str_replace( "###PREIS###" 			, $tmp_kat->preis 			, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###STAFFELPREIS_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del staffelpreise
function act_delete_staffelpreise()
{
	$tmp_art = new Staffelpreis( g('id') );
	$tmp_art->del_it();
	
	act_list_staffelpreise();
}
######STAFFELPREISE#######







######WARENKORB#######
// manage_warenkorb function
function act_manage_warenkorb()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_warenkorb.html");
	
	$tmp_waren = new Warenkorb( intval( g('id') ) );
	
	$warenkorb_info = "neu anlegen";
	
	## warenkorb mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$warenkorb_info = "bearbeiten (". $tmp_waren->id .")";
	}
	## warenkorb aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_waren->user_id 		= g('user_id');
		$tmp_waren->datum 			= g('datum');
		$tmp_waren->summe 			= g('summe');
		
		$tmp_waren->save();
		
		act_list_warenkorb();
	}
	
	## felder anzeigen
	$out = str_replace("###id###"			,$tmp_waren->id, $out);
	$out = str_replace("###user_id###"		,$tmp_waren->user_id, $out);
	$out = str_replace("###datum###"		,$tmp_waren->datum, $out);
	$out = str_replace("###summe###"		,$tmp_waren->summe, $out);
	
	$out = str_replace("###WARENKORB_INFO###", $warenkorb_info, $out);
	
	output($out);
	

	
}


// list warenkorb
function act_list_warenkorb()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_waren_ids = Warenkorb::getAll();
	
	$table_html = file_get_contents( "view/list_warenkorb.html" );
	$row_html 	= file_get_contents( "view/list_warenkorb_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_waren_ids as $one_waren_id)
	{
		$tmp_war = new Warenkorb( $one_waren_id );
		
		$tmp_row = str_replace( "###ID###" 				, $tmp_war->id 				, $row_html );
		$tmp_row = str_replace( "###USER_ID###" 		, $tmp_war->user_id 		, $tmp_row );
		$tmp_row = str_replace( "###DATUM###" 			, $tmp_war->datum 			, $tmp_row );
		$tmp_row = str_replace( "###SUMME###" 			, $tmp_war->summe 			, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###WARENKORB_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del warenkorb
function act_delete_warenkorb()
{
	$tmp_art = new Warenkorb( g('id') );
	$tmp_art->del_it();
	
	act_list_warenkorb();
}
######WARENKORB#######

######ART2KAT#######
// manage_art2kat function
function act_manage_art2kat()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_art2kat.html");
	
	$tmp_art2kat = new Art_2_Kat( intval( g('id') ) );
	
	$art2kat_info = "neu anlegen";
	
	## Art2Kat mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$art2kat_info = "bearbeiten (". $tmp_art2kat->id .")";
	}
	## Art2Kat aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_art2kat->art_id 			= g('art_id');
		$tmp_art2kat->kat_id 			= g('kat_id');
		
		$tmp_art2kat->save();
		
		act_list_art2kat();
	}
	
	## felder anzeigen
	$out = str_replace("###id###"			,$tmp_art2kat->id, $out);
	$out = str_replace("###art_id###"		,$tmp_art2kat->art_id, $out);
	$out = str_replace("###kat_id###"		,$tmp_art2kat->kat_id, $out);
	
	$out = str_replace("###ART2KAT_INFO###", $art2kat_info, $out);
	
	output($out);
	

	
}


// list art2kat
function act_list_art2kat()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_art2kat_ids = Art_2_Kat::getAll();
	
	$table_html = file_get_contents( "view/list_art2kat.html" );
	$row_html 	= file_get_contents( "view/list_art2kat_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_art2kat_ids as $one_art2kat_id)
	{
		$tmp_war = new Art_2_Kat( $one_art2kat_id );
		
		$tmp_row = str_replace( "###ID###" 				, $tmp_war->id 				, $row_html );
		$tmp_row = str_replace( "###ART_ID###" 			, $tmp_war->art_id 			, $tmp_row );
		$tmp_row = str_replace( "###KAT_ID###" 			, $tmp_war->kat_id 			, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###ART2KAT_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del art2kat
function act_delete_art2kat()
{
	$tmp_art = new Art_2_Kat( g('id') );
	$tmp_art->del_it();
	
	act_list_art2kat();
}
######ART2KAT#######


######ART2WAR#######
// manage_art2war function
function act_manage_art2war()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$out = file_get_contents("view/manage_art2war.html");
	
	$tmp_art2war = new Art_2_War( intval( g('id') ) );
	
	$art2war_info = "neu anlegen";
	
	## Art2War mit der GET id laden
	if( g('id') != null && g('send') == null)
	{
		$art2war_info = "bearbeiten (". $tmp_art2war->id .")";
	}
	## Art2War aus formular in db speichern
	else if( g('send') != null )
	{
		$tmp_art2war->art_id 			= g('art_id');
		$tmp_art2war->war_id 			= g('war_id');
		$tmp_art2war->menge 			= g('menge');
		
		$tmp_art2war->save();
		
		act_list_art2war();
	}
	
	## felder anzeigen
	$out = str_replace("###id###"			,$tmp_art2war->id, $out);
	$out = str_replace("###art_id###"		,$tmp_art2war->art_id, $out);
	$out = str_replace("###war_id###"		,$tmp_art2war->war_id, $out);
	$out = str_replace("###menge###"		,$tmp_art2war->menge, $out);
	
	$out = str_replace("###ART2WAR_INFO###", $art2war_info, $out);
	
	output($out);
	

	
}


// list art2war
function act_list_art2war()
{
	## wenn nicht eingeloggt, dann ab auf die home seite
	if(isset($_SESSION['user_id'])== false)
	{
		home();
	}
	
	$all_art2war_ids = Art_2_War::getAll();
	
	$table_html = file_get_contents( "view/list_art2war.html" );
	$row_html 	= file_get_contents( "view/list_art2war_row.html" );
	
	$all_rows = ""; #<< variable in der wir den row output sammeln
	
	foreach($all_art2war_ids as $one_art2war_id)
	{
		$tmp_war = new Art_2_War( $one_art2war_id );
		
		$tmp_row = str_replace( "###ID###" 				, $tmp_war->id 				, $row_html );
		$tmp_row = str_replace( "###ART_ID###" 			, $tmp_war->art_id 			, $tmp_row );
		$tmp_row = str_replace( "###WAR_ID###" 			, $tmp_war->war_id 			, $tmp_row );
		$tmp_row = str_replace( "###MENGE###" 			, $tmp_war->menge 			, $tmp_row );
		
		$all_rows = $all_rows . $tmp_row;
		
	}
	
	$out = str_replace( "###ART2WAR_ROWS###"  ,$all_rows , $table_html );
	
	output( $out );
}


// del art2war
function act_delete_art2war()
{
	$tmp_art = new Art_2_War( g('id') );
	$tmp_art->del_it();
	
	act_list_art2war();
}
######ART2WAR#######


// home function
function home()
{
	output("Hallo");
}


// $_REQUEST = $_GET + $_POST
function g( $assoc_index )
{
	if( isset($_REQUEST[$assoc_index ]) == false )
	{
		return null;
	}
	return $_REQUEST[$assoc_index];
}


function gen_html_options( $in_data_array , $in_selected_id , $in_add_empty )
{
	$out_opt = "";
	
	if($in_add_empty == true)
	{
		$out_opt .= '<option value=0 > -- KEINE -- </option>';
	}
	
	foreach($in_data_array as $key => $val)
	{
		$sel = "";
		
	if($key == $in_selected_id)
			$sel = "selected";
		
		$out_opt .= '<option value="'.$key.'" '.$sel.' > '.$val.' </option>';
	}
	return $out_opt;
}







?>