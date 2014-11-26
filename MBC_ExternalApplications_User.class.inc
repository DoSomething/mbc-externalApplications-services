<?php

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_ExternalApplications_User class - functionality related to the Message Broker
 * consumer mbc-externalApplications-user.
 */
class MBC_ExternalApplications_User
{

  /**
   * Access credentials settings
   *
   * @var object
   */
  private $credentials;

  /**
   * Service settings
   *
   * @var array
   */
  private $settings;

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
  public function __construct($credentials, $settings) {

    $this->credentials = $credentials;
    $this->settings = $settings;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbc-externalApplications-events:');
    $this->statHat->setIsProduction(FALSE);
  }

  /* 
   * Consumer entries in externalApplicationUserQueue
   *
   * @param string $payload
   *   The contents of the message in a serial format
   */
  public function consumeQueue($payload) {
    echo '------- mbc-externalApplication-users->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $message = unserialize($payload->body);

    // US based user created, Send SMS
    if (empty($message->mobile)) {
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

    $config = array();
    $source = __DIR__ . '/messagebroker-config/mb_config.json';
    $mb_config = new MB_Configuration($source, $this->settings);
    $mobileCommonsExchange = $mb_config->exchangeSettings('transactionalExchange');

    $config['exchange'] = array(
      'name' => $mobileCommonsExchange->name,
      'type' => $mobileCommonsExchange->type,
      'passive' => $mobileCommonsExchange->passive,
      'durable' => $mobileCommonsExchange->durable,
      'auto_delete' => $mobileCommonsExchange->auto_delete,
    );
    $config['queue'][] = array(
      'name' => $mobileCommonsExchange->queues->mobileCommonsQueue->name,
      'passive' => $mobileCommonsExchange->queues->mobileCommonsQueue->passive,
      'durable' => $mobileCommonsExchange->queues->mobileCommonsQueue->durable,
      'exclusive' => $mobileCommonsExchange->queues->mobileCommonsQueue->exclusive,
      'auto_delete' => $mobileCommonsExchange->queues->mobileCommonsQueue->auto_delete,
      'binding_pattern' => $mobileCommonsExchange->queues->mobileCommonsQueue->binding_pattern,
    );
    $config['routing_key'] = 'user.registration.cgg';

    $mbMobileCommons = new MessageBroker($this->credentials, $config);
    $payload = serialize($message);
    $mbMobileCommons->publishMessage($payload);
    echo 'produceUSUser() - SMS message sent to consumer: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
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
