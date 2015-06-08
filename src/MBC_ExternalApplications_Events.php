<?php
/**
 * MBC_ExternalApplications_Events: Class to perform user event activities
 * submitted by external applications.
 */
namespace DoSomething\MBC_ExternalApplications;

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;

/**
 * MBC_UserEvent class - functionality related to the Message Broker
 * producer mbp-user-event.
 */
class MBC_ExternalApplications_Events
{

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $toolbox;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Constructor for MBC_UserEvent
   *
   * @param array $settings
   *   Settings of additional services used by the class.
   */
  public function __construct($credentials, $settings) {

    $this->credentials = $credentials;
    $this->settings = $settings;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);
  }

  /* 
   * Consumer entries in 
   */
  public function consumeQueue($payload) {

    echo '------- mbc-externalApplication->MBC_ExternalApplications_Events->consumeQueue() START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $message = unserialize($payload->body);

    $isAffiliate = FALSE;
    if (isset($message['country_code'])) {
      $isAffiliate = $this->toolbox->isDSAffiliate($message['country_code']);
    }

    if ($message['email'] !== NULL && $isAffiliate) {
      $this->produceInternationalAffilateEvent($message, $isAffiliate);
    }
    elseif ($message['email'] !== NULL) {
      $this->produceInternationalEvent($message);
    }
    elseif ($message['email'] === NULL && isset($message['mobile'])) {
      $this->produceUSEvent($message);
      echo 'mobile vote - ' . $message['mobile'] . ': ' . $message['country_code'], PHP_EOL;
    }
    else {
      echo 'ERROR consumeQueue: email not defined - $message: ' . print_r($message, TRUE), PHP_EOL;
    }

    echo '------- mbc-externalApplication->MBC_ExternalApplications_Events->consumeQueue() END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

}
