<?php 
	/** 
	* teste
	* 
	* @package Membership Manager Pro
	* @author wojoscripts.com
	* @copyright 2022
	* @version Id: teste.php, v4.0 2022-03-16 02:50:58 gewa Exp $
	*/
 
	 define("_WOJO", true); 
	 require_once("init.php");
 
?> 
 
 <?php include(FRONTBASE . "/header.tpl.php");?> 
 
 
	 <?php if(Membership::is_valid([1])): ?>
 
	 <h1>User has valid membership, you can display your protected content here</h1>.
 
	 <?php else: ?>
 
	 <h1>User membership is't not valid. Show your custom error message here</h1>
 
	 <?php endif; ?>
 
 
 <?php include(FRONTBASE . "/footer.tpl.php");?> 
 
 
