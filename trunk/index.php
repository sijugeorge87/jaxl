<?php
  
  /* Include Key file */
  include_once("config.ini.php");
  
  /* Include JAXL Class */
  include_once("jaxl.class.php");
  
  /* Create an instance of XMPP Class */
  $jaxl = new JAXL();
  
  try {
    /* Initiate the connection */
    $jaxl->connect();
    
    /* Communicate with Jabber Server */
    while($jaxl->isConnected) {
      $jaxl->getXML();
    }
  }
  catch(Exception $e) {
    die($e->getMessage());
  }

?>
