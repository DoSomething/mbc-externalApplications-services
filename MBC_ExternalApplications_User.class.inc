<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_ExternalApplications_User class - functionality related to the Message Broker
 * consumer mbc-externalApplications-user.
 */
class MBC_ExternalApplications_User
{

  /**
   * Message Broker object that details the connection to RabbitMQ.
   *
   * @var object
   */
  private $messageBroker;

  /**
   * Details of the channel connection in use by RabbitMQ.
   *
   * @var object
   */
  private $channel;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $toolbox;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $statHat;

  /**
   * Constructor for MBC_ExternalApplications_User
   *
   * @param array $settings
   *   Settings of additional services used by the class.
   */
  public function __construct($mb, $settings) {

    $this->messageBroker = $mb;
    $connection = $this->messageBroker->connection;
    $this->channel = $connection->channel();

    $this->toolbox = new MB_Toolbox();

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbc-externalApplications-events:');
    $this->statHat->setIsProduction(FALSE);
  }

  /* 
   * Consumer entries in externalApplicationUserQueue
   *
   * @param string $payload
   *   The contents of the message in a serial format
   */
  public function consumerQueue($payload) {
    echo '------- mbc-externalApplication-users->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $message = unserialize($payload->body);

    // US based user created, Send SMS
    if (!is_null($message->mobile)) {
      $this->produceUSUser($message);

    // Only create Drupal users in international affiliate sites when the user
    // is submitting from the specific affiliate country.
    }
    elseif (!is_null($message->email) && $this->toolbox->isDSAffiliate($message->country_code)) {
      $this->produceInternationalAffilateUser($message);
    }
    // International, non-affiliate origin - Send email only
    elseif (!is_null($message->email)) {
      $this->produceInternationalUser($payloadDetails);
    }
    else {
      echo 'ERROR consumerQueue: email and mobile not defined - $message: ' . print_r($message, TRUE), PHP_EOL;
    }

    echo '------- mbc-externalApplication-users->consumeQueue() END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
  }

  /**
   * Produce domestic (US) based users creation transaction.
   *
   * @param array $message
   *   Details about the transaction for US based signups.
   */
  private function produceUSUser($message) {

  }

  /**
   * Produce international affiliate users.
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   Message Broker functionality.
   */
  private function produceInternationalAffilateUser($message) {

  }

  /**
   * Produce international users.
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   non-affiliate Message Broker functionality.
   */
  private function produceInternational($message) {

  }

}
