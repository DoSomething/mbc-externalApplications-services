<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_UserEvent class - functionality related to the Message Broker
 * producer mbp-user-event.
 */
class MBC_ExternalApplications_Events
{

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
  public function __construct($settings) {

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbc-externalApplications-events:');
    $this->statHat->setIsProduction(FALSE);
  }

  /* 
   * Consumer entries in 
   */
  public function consumerQueue() {

    echo '------- mbc-externalApplication-events->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;


    echo '------- mbc-externalApplication-events->consumeQueue() END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
  }

}
