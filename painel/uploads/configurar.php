<?php 
	/** 
	* configurar
	* 
	* @package Membership Manager Pro
	* @author wojoscripts.com
	* @copyright 2024
	* @version Id: configurar.php, v4.0 2024-03-26 16:54:10 gewa Exp $
	*/
 
	 define("_WOJO", true); 
	 require_once("init.php");
 
?> 
 
 <?php include(FRONTBASE . "/header.tpl.php");?> 
 
 
	 <?php if(Membership::is_valid([3,2,1])): ?>
 
	 <h1>Página em construção, entre em contato conosco pelo WhatsApp</h1>.
 
	 <?php else: ?>
 
	 <h1>Ops. Você ainda não possui uma assinatura.</h1>
 
	 <?php endif; ?>
 
 
 <?php include(FRONTBASE . "/footer.tpl.php");?> 
 
 
