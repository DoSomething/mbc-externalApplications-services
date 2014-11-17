<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_ExternalApplications_User class - functionality related to the Message Broker
 * consumer mbc-externalApplications-user.
 */
class MBC_ExternalApplications_User
{

  /**
   * Setting from external services - StatHat.
   *
   * @var array
   */
  private $statHat;

  /**
   * Constructor for MBC_ExternalApplications_User
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

    echo '------- mbc-externalApplication-events->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', "\n";


    echo '------- mbc-externalApplication-events->consumeQueue() END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

}
