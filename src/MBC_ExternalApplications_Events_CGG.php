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
class MBC_ExternalApplications_Events_CGG
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

  /**
   * Produce international affiliate event (vote).
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   Message Broker functionality.
   */
  private function produceInternationalAffilateEvent($message, $isAffiliate) {

    $this->produceMailchimpAffilate($message);

    $this->statHat->ezCount('mbc-externalApplications-events: produceInternationalAffiliateEvent', 1);

    $message['merge_vars']['AFFILIATE_URL'] = $isAffiliate['url'];
    $message['merge_vars']['MEMBER_COUNT'] = $this->toolbox->getDSMemberCount();
    $message['email_template'] = 'affiliated-country-voting-confirmation';
    $this->produceTransactionalEmail($message);
    echo '- produceInternationalAffilateEvent - email: ' . $message['email'] . ' country_code: ' . $message['country_code'] . ' - isAffiliate url: ' . $isAffiliate['url'], PHP_EOL;
  }

  /**
   * Produce international event (vote).
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   non-affiliate Message Broker functionality.
   */
  private function produceInternationalEvent($message) {

    $this->produceMailchimpInternational($message);
    $this->statHat->ezCount('mbc-externalApplications-events: produceInternationalEvent', 1);

    $message['merge_vars']['MEMBER_COUNT'] = $this->toolbox->getDSMemberCount();
    $message['email_template'] = 'non-affiliate-voting-confirmation';
    $this->produceTransactionalEmail($message);
    echo '- produceInternationalEvent - email: ' . $message['email'] . ' country_code: ' . $message['country_code'], PHP_EOL;
  }

  /**
   * Produce international affiliate users.
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   Message Broker functionality.
   */
  private function produceTransactionalEmail($message) {

    $mbConfig = new MB_Configuration($this->settings, CONFIG_PATH . '/mb_config.json');
    $config = $mbConfig->constructConfig('transactionalExchange', array('transactionalQueue'));
    $config['routingKey'] = 'vote.cgg.transactional';

    $payload = serialize($message);

    $mb = new \MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);
    echo '- produceTransactionalEmail() - email: ' . $message['email'] . ' message sent to consumer: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $this->statHat->ezCount('mbc-externalApplications-events: produceTransactionalEmail', 1);
  }

  /**
   * Produce affiliate Mailchimp entries.
   *
   * @param array $message
   *   Compose details about international affiliate transaction to send
   *   to Mailchimp.
   */
  private function produceMailchimpAffilate($message) {

    $message['affiliate'] = TRUE;
    $this->sendEmailServiceMessage($message);

    echo '- produceMailchimpAffilate()', PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: produceMailchimpAffilate', 1);
  }

  /**
   * Produce international Mailchimp entries.
   *
   * @param array $message
   *   Compose details about international transaction to send to Mailchimp.
   */
  private function produceMailchimpInternational($message) {

    $message['affiliate'] = FALSE;
    $this->sendEmailServiceMessage($message);

    echo '- produceMailchimpInternational()', PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: produceMailchimpInternational', 1);
  }

  /**
   * Produce emailService message.
   *
   * @param array $message
   *   Details about the transaction that has triggered producing Message
   *   Broker message.
   */
  private function sendEmailServiceMessage($message) {

    $mbConfig = new MB_Configuration($this->settings, CONFIG_PATH . '/mb_config.json');
    $config = $mbConfig->constructConfig('topicEmailService', array('mailchimpSubscriptionQueue'));
    $config['routingKey'] = 'subscribe.mailchimp.cgg';

    $payload = serialize($message);

    $mb = new \MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);

    echo '- sendEmailServiceMessage() - email: ' . $message['email'] . ' message sent to queue: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: sendEmailServiceMessage', 1);
  }

  /**
   * Produce domestic (US) based users creation transaction.
   *
   * @param array $message
   *   Details about the transaction for US based signups.
   */
  private function produceUSEvent($message) {

    $payload = array(
      'mobile' => $message['mobile'],
      'candidate_name' => $message['candidate_name'],
      'activity' => $message['activity'],
      'mc_opt_in_path_id' => $message['mc_opt_in_path_id']
    );
    $payload = serialize($payload);

    $mbConfig = new MB_Configuration($this->settings, CONFIG_PATH . '/mb_config.json');
    $config = $mbConfig->constructConfig('transactionalExchange', array('mobileCommonsQueue'));
    $config['routingKey'] = 'user.registration.cgg';

    $mbMobileCommons = new \MessageBroker($this->credentials, $config);
    $mbMobileCommons->publishMessage($payload);

    echo '- produceUSEvent() - SMS vote message sent to queue: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: produceUSEvent - mobile vote', 1);
  }

}
