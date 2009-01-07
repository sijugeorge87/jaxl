<?php
  
  /*
   * Author:
   *    Abhinav Singh
   *
   * Contact:
   *    mailsforabhinav@gmail.com
   *    admin@abhinavsingh.com
   *
   * Site:
   *    http://abhinavsingh.com
   *    http://abhinavsingh.com/blog
   *
   * Source:
   *    http://code.google.com/p/jaxl
   *
   * About:
   *    JAXL stands for "Just Another XMPP Library"
   *    For geeks, JAXL stands for "Jabber XMPP Library"
   *    
   *    I wrote this library while developing Gtalkbots (http://gtalkbots.com)
   *    I have highly customized it to work with Gtalk Servers and inspite of
   *    production level usage at Gtalkbots, I recommend still not to use this
   *    for any live project.
   *    
   *    Feel free to add me in Gtalk and drop an IM.
   *
  */
  
  /*
   * ==================================== IMPORTANT =========================================
   * This is a sample application code showing the power of JAXL. How without knowing the 
   * inner tit-bits of XMPP protocols you can develop cool applications.
   *
   * To run this sample application you need:
   * 1. JAXL Library (http://code.google.com/p/jaxl)
   * 2. XML_RSS Package (pear install XML_RSS)
   * 3. Create a directory cache/dzone where this application will cache all rss files
   * 4. Include jaxl4dzone.class.php in index.php, instead of jaxl.class.php and run
   *    'php index.php' from command line to start this application
   * 5. For more info read http://abhinavsingh.com/blog/2009/01/how-to-get-dzone-feeds-as-im-using-jaxl-add-dzonegtalkbotscom/
   * ==================================== IMPORTANT =========================================
  */
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  /* Include RSS Parser Class */
  require_once "XML/RSS.php";
  
  class JAXL extends XMPP {
    
    /* Initialize Service Variables */
    var $restrictJid = FALSE;   // If set to TRUE, this bot will 
                                // reply only to Jid's specified 
                                // in $authorizedJid array
    var $authorizedJid = array();
    var $maxResults = 5;
    var $count = 0;
    
    function eventMessage($fromJid, $content, $offline = FALSE) {
      
      // If the message is not an offliner
      if(!$offline) {
        if($this->restrictJid && !in_array($this->getBareJid($fromJid),$this->authorizedJid)) { // Block un-authorized users
          $this->logger->logger($fromJid." tried using this service by sending:\n".$content);
          $message = 'You are not authorized to use this service';
          $this->sendMessage($fromJid,$message);
          return TRUE;
        }
        else {
          $content = strtolower(trim($content));
          
          // Build RSS Feed URL depending upon passed IM
          if($content == "options") {
            $message = "Available options are:\n";
            $message .= "*1. frontpage:* Will send you latest links on frontpage.\n";
            $message .= "*2. queue:* Will send you latest links in queue.\n";
            $message .= "*3. tag:* Send in a tag e.g. php,java,xml and you will get latest links for the particular tag\n";
            $this->sendMessage($fromJid,$message);
            return TRUE;
          }
          else if($content == "frontpage") $url = "http://www.dzone.com/links/feed/frontpage/rss.xml";
          else if($content == "queue") $url = "http://www.dzone.com/links/feed/queue/rss.xml";
          else $url = "http://www.dzone.com/links/feed/frontpage/".$content."/rss.xml";
          
          // Generate cache file location
          $cache_file = "cache/dzone/".md5($url)."-".$content.".rss";
          
          // If cache file is not older than 900 seconds (15 min)
          if(file_exists($cache_file) && (filemtime($cache_file) > time() - 900)) {
            $this->logger->logger("RSS already found cached...");
          }
          else { // else fetch a fresh copy of RSS
            $this->logger->logger("Fetching RSS for url:\n".$url);
            $head = $this->fetchWebURL($url);
            if($head['errno'] == 0 && $head['http_code'] == 200) {
              $this->logger->logger("Fetched successfully...");
              $data = $head['content'];
              $fh = fopen($cache_file,"w");
              fwrite($fh,$data);
              fclose($fh);
            }
            else {
              // Send a message saying no such feed exists
              $this->logger->logger("Failed to fetch the RSS feed...");
              $message = 'No feed exists for the passed keyword: '.$content;
              $this->sendMessage($fromJid,$message);
              return TRUE;
            }
          }
          
          // Parse RSS Feed
          $r = &new XML_RSS($cache_file);
          $r->parse();
          $NumberOfFeeds = count($r->getItems());
          if($NumberOfFeeds > 0) {
            $message = '';
            foreach($r->getItems() as $key => $item) {
              if($this->count < $this->maxResults) {
                $message .= "*".$item["title"]."*\n";
                $message .= $item["link"]."\n";
                $message .= $item["description"]."\n\n";
                $this->count++;
              }
              else {
                break;
              }
            }
            $message .= "powered by *Jaxl* http://code.google.com/p/jaxl"."\n\n";
            $this->sendMessage($fromJid,$message);
            $this->count = 0;
            return TRUE;
          }
          else {
            // Send a message saying no feed found
            $message = 'No feed was found for passed keyword: '.$content;
            $this->sendMessage($fromJid,$message);
            return TRUE;
          }
        }
      }
      else {
        // Send an appropriate message
        $this->logger->logger($fromJid." sent an offliner saying:\n".$content);
        return TRUE;
      }
    }
    
    function fetchWebURL($url) {
      $options = array(
        CURLOPT_RETURNTRANSFER => true,         // return web page
        CURLOPT_HEADER         => false,        // don't return headers
        CURLOPT_FOLLOWLOCATION => true,         // follow redirects
        CURLOPT_ENCODING       => "",           // handle all encodings
        CURLOPT_USERAGENT      => "jaxl4dzone", // who am i
        CURLOPT_AUTOREFERER    => true,         // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,          // timeout on connect
        CURLOPT_TIMEOUT        => 120,          // timeout on response
        CURLOPT_MAXREDIRS      => 10,           // stop after 10 redirects
      );
      
      $ch = curl_init( $url );
      curl_setopt_array( $ch, $options );
      $content = curl_exec( $ch );
      $err = curl_errno( $ch );
      $errmsg = curl_error( $ch );
      $header = curl_getinfo( $ch );
      curl_close( $ch );
      
      $header['errno']   = $err;
      $header['errmsg']  = $errmsg;
      $header['content'] = $content;
      return $header;
    }
    
  }
  
?>
