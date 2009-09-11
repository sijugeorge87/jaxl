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
   * JAXL extends XMPP and should be the starting point for all your applications
   * You should never try to change XMPP class until you are confident about it
   *
   * Methods you might be interested in:
   *    eventMessage(), eventPresence()
   *    sendMessage($jid,$message), sendStatus($status)
   *    subscribe($jid)
   *    roster('get')
   *    roster('add',$jid) 
   *    roster('remove',$jid)
   *    roster('update',$jid,$name,$groups)
   * ==================================== IMPORTANT =========================================
  */
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  class JAXL extends XMPP {
    
    // List of question contained in an array
    var $questions = array();

    // List of answers corresponding to above questions
    var $answers = array();

    // list of answers which are not allowed for any question
    var $answers_not_allowed = array('start','stop','options');

    // last sent question key (basically index value of question in questions array)
    var $last_question_key = -1;

    // an associative array storing user scores
    var $user_scores = array();

    // stores jabber id of users currently in the arena
    var $user_jids = array();

    // game status
    var $game_status = FALSE;

    /* START OF STANDARD JAXL METHODS */
    function eventMessage($fromJid, $content, $offline = FALSE) {
      // Take action only if the message received is online
      if(!$offline) {
         // trim incoming content
         $content = trim($content);

         // get bare jid for the user
	 $fromJid = $this->getBareJid($fromJid);
	 switch($content) {
	   case 'start':
	     $this->add_user_to_arena($fromJid);
	     break;
	   case 'stop':
	     $this->remove_user_from_arena($fromJid);
	     break;
	   case 'options':
	     $this->display_options($fromJid);
	     break;
	   default:
	     $this->handle_user_message($fromJid, $content);
	     break;
	 }
      }
    }
    
    // not required for this gaming demo
    function eventPresence($fromJid, $status, $photo) {
    
    }
    
    // set the status for our gaming bot
    function setStatus() {
      // Set a custom status or use $this->status
      $this->sendStatus("Type *options* for getting started");
      print "Setting Status...\n";
      print "Done\n";

      // initialize game
      if(!$this->game_status) {
        $this->logger->logger('Initializing gaming arena....');
        $this->init();
        $this->game_status = TRUE;
      }
    }
    /* END OF STANDARD JAXL METHOD */

    /* START OF GAME CODE METHODS */
    function init() {
      // called when the bot starts
      // read the list of questions and their answers from a txt file
      // populate the $question and $answers array
      // HARDCODING arrays for DEMO purpose.
      $this->questions = array('q1','q2','q3','q4','q5');
      $this->answers = array('a1','a2','a3','a4','a5');
      return TRUE;
    }

    function broadcast_message($message, $except=array()) {
      foreach($this->user_jids as $jid => $info) {
	if(in_array($jid, $except)) continue;
   	else if($this->user_jids[$jid]['status'] == 'online') {
          $this->sendMessage($jid, $message);
        }
      }
      return TRUE;
    }

    function add_user_to_arena($jid) {
       // check if user visited the game before
       // you may want to send some custom welcome messages depending upon the user type
       if(!isset($this->user_jids[$jid])) {
         $this->logger->logger('Adding user_jids key for: '.$jid);
	 $this->user_jids[$jid] = array();
       }

       $this->user_jids[$jid]['status'] = 'online';
       $this->user_jids[$jid]['start_time'] = time();
       $this->logger->logger($jid.' joined the arena: '.json_encode($this->user_jids[$jid]));
       
       $this->send_current_question($jid);
       return TRUE;
    }

    function send_current_question($jid) {
      // is this the 1st user in the arena
      if($this->last_question_key == -1) $this->last_question_key++;
      $current_question = $this->questions[$this->last_question_key];

      $this->logger->logger('Sending current question at index: '.$this->last_question_key.', question: '.$current_question.' to: '.$jid); 
      $this->sendMessage($jid, $current_question);
      return TRUE;
    }

    function broadcast_next_question($except=array()) {
      if($this->last_question_key == count($this->questions)-1) $this->last_question_key = 0; 
      else $this->last_question_key++;
      
      $this->broadcast_message($this->questions[$this->last_question_key], $except);
      return TRUE;
    }

    function broadcast_right_answer($fromJid, $answer, $except) {
      $message = '*'.$fromJid.'* gave the right answer: '.$answer;
      $this->broadcast_message($message, array($fromJid));
      return TRUE;
    }

    function remove_user_from_arena($jid) { 
       if(isset($this->user_jids[$jid])) {
	 $this->user_jids[$jid]['status'] = 'offline';
         $this->user_jids[$jid]['end_time'] = time();
       }
       return TRUE;
    }

    function display_options($jid) {
      $options = '*start* To join the arena, *stop* To quit the arena, *options* To display this help';
      $this->sendMessage($jid, $options);
      return TRUE;
    }

    function handle_user_message($jid, $message) {
      // check if user already exists in the arena
      if(!isset($this->user_jids[$jid]) || $this->user_jids[$jid]['status'] == 'offline') {
        $this->display_options($jid);     
        return TRUE;
      }

      // we treat this message as an answer
      $current_answer = $this->answers[$this->last_question_key];
      if($message == $current_answer) {
        $this->increase_user_points($jid);
        $this->broadcast_right_answer($jid, $message, array($jid));
        $this->broadcast_next_question(array());
      }
      else {
        $message = $message.' is a wrong answer. Try again!';
        $this->sendMessage($jid, $message);
      }
      return TRUE;
    }

    function increase_user_points($jid) {
      if(!isset($this->user_jids[$jid]['points'])) $this->user_jids[$jid]['points'] = 0;
      $this->user_jids[$jid]['points'] += 1;
    }
    
  }
  
?>
