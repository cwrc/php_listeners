<?php

/**
 * Class to listen for the JMS messages and filter them
 * based on the rules defined in config.xml
 * 
 * @author Richard Wincewicz
 */

$connect = new Connect();
$connect->listen();
unset($connect);

class Connect {

  function __construct() {
    include_once 'message.php';
    include_once 'fedoraConnection.php';
    include_once 'connect.php';
    include_once 'Derivatives.php';
    include_once 'Logging.php';

    // Load config file
    $config_file = file_get_contents('config.xml');
    $this->config_xml = new SimpleXMLElement($config_file);

    // Logging settings
    $log_file = $this->config_xml->log->file;

    $this->log = new Logging();
    $this->log->lfile($log_file);

    $this->fedora_url = 'http://' . $this->config_xml->fedora->host . ':' . $this->config_xml->fedora->port . '/fedora';
    $this->user = new stdClass();
    $this->user->name = $this->config_xml->fedora->username;
    $this->user->pass = $this->config_xml->fedora->password;

    // Set up stomp settings
    $stomp_url = 'tcp://' . $this->config_xml->stomp->host . ':' . $this->config_xml->stomp->port;
    $channel = $this->config_xml->stomp->channel;

    // Make a connection
    $this->con = new Stomp($stomp_url);
    $this->con->sync = TRUE;
    $this->con->setReadTimeout(1);

    // Subscribe to the queue
    try {
      $this->con->subscribe((string) $channel[0], array('activemq.prefetchSize' => 1));
    } catch (Exception $e) {
      $this->log->lwrite("Could not subscribe to the channel $channel - $e", 'SERVER', NULL, NULL, NULL, 'ERROR');
    }
  }

  /**
   * 
   *
   *
   * */
  function listen() {

    // Receive a message from the queue
    if ($this->msg = $this->con->readFrame()) {

      //$this->log->lwrite($this->msg->body, 'SERVER', NULL, NULL, NULL, 'INFO');

      // based on the "trigger" within the config file, apply action 
      if ($this->msg != NULL) {

        $message = new Message($this->msg->body);
        $pid = $this->msg->headers['pid'];
        $modMethod = $this->msg->headers['methodName'];
        $message_dsid = isset($message->dsID) ? $message->dsID : NULL;

        $this->log->lwrite("Method: " . $modMethod, 'SERVER_INFO', $pid, $message_dsid, $message->author);

        // lookup Fedora Object
        try {
          if ( $modMethod !== 'purgeObject' )
          {
            if (fedora_object_exists($this->fedora_url, $this->user, $pid) === FALSE) {
              $this->log->lwrite("Could not find object", 'DELETED_OBJECT', $pid, NULL, $message->author, 'ERROR');
              $this->con->ack($this->msg);
              unset($this->msg);
              return;
            }
            $fedora_object = new ListenerObject($this->user, $this->fedora_url, $pid);
          }
        } catch (Exception $e) {
          $this->log->lwrite("An error occurred accessing the fedora object", 'FAIL_OBJECT', $pid, NULL, $message->author, 'ERROR');
          $this->con->ack($this->msg);
          unset($this->msg);
          return;
        }

        // load "trigger" details
        $triggers = $this->config_xml->xpath('//trigger');

        // apply each trigger
        foreach ($triggers as $trigger) {

          // build array of methods to filter upon 
          $method_array = array();
          foreach ($trigger->method as $item) {
            $method_array[] = (string) $item[0];
          }

          // build array of include files to use while applying action 
          $include_array = array();
          foreach ($trigger->derivative->include_file as $item) {
            $include_array[] = (string) $item[0];
          }
          
          //$this->log->lwrite('Config methods: ' . implode(', ', $method_array), "SERVER_INFO");

          // filter by "method" within the JMS message and "trigger" configuration
          if (in_array($this->msg->headers['methodName'], $method_array)) {

            // include required helper functions and class method
            foreach ($include_array as $item)
            {
              include_once $item;
              //$this->log->lwrite('include: '.implode(', ', $include_array), "SERVER_INFO");
            }

            // call class method specifiec by trigger
            $className = (string) $trigger->derivative->class;
            if (!class_exists($className))
            {
              $this->log->lwrite("Error loading class $className, check your config file", $pid, NULL, $message->author, 'ERROR');
              continue;
            }
            else
            {
              $classMethodName = (string) $trigger->derivative->classMethod;
              $actionObj = new $className($trigger,(string)$trigger->basexdb_dbname,$this->log);
              if (!method_exists($actionObj, $classMethodName)) {
                $this->log->lwrite("Error calling $className->$classMethodName, check your config file", $pid, NULL, $message->author, 'ERROR');
                continue;
              }
              if ( $modMethod !== 'purgeObject' )
              {
                $output = $actionObj->{$classMethodName}($fedora_object->object,$message->updateTime);
              }
              else 
              {
                $output = $actionObj->{$classMethodName}($pid,$message->updateTime);
              }
              if (isset($output)) {
                $this->log->lwrite($output, 'SERVER_INFO');
              }
              $this->log->lwrite("Complete: PID: $pid Class: $className $classMethodName", 'SERVER_INFO');
            }
          }
        }

        // Mark the message as received in the queue
        $this->con->ack($this->msg);
        unset($this->msg);
      }

      // Close log file
      $this->log->lclose();
    }
  }

}

?>
