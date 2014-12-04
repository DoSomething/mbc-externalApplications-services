<?php
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBStatTracker\StatHat;

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
    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbc-externalApplications-events:');
    $this->statHat->setIsProduction(TRUE);
  }

  /* 
   * Consumer entries in 
   */
  public function consumeQueue($payload) {

    echo '------- mbc-externalApplication-events->consumeQueue() START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

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
      echo 'mobile vote - ' . $message['mobile'] . ': ' . $message['country_code'], PHP_EOL;
    }
    else {
      echo 'ERROR consumeQueue: email not defined - $message: ' . print_r($message, TRUE), PHP_EOL;
    }

    echo '------- mbc-externalApplication-events->consumeQueue() END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
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

    $this->statHat->addStatName('produceInternationalAffiliateEvent');
    $this->statHat->reportCount(1);

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

    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('produceInternationalEvent');
    $this->statHat->reportCount(1);

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

    $config = array();
    $source = __DIR__ . '/messagebroker-config/mb_config.json';
    $mb_config = new MB_Configuration($source, $this->settings);
    $transactionalExchange = $mb_config->exchangeSettings('transactionalExchange');

    $config['exchange'] = array(
      'name' => $transactionalExchange->name,
      'type' => $transactionalExchange->type,
      'passive' => $transactionalExchange->passive,
      'durable' => $transactionalExchange->durable,
      'auto_delete' => $transactionalExchange->auto_delete,
    );
    $config['queue'][] = array(
      'name' => $transactionalExchange->queues->transactionalQueue->name,
      'passive' => $transactionalExchange->queues->transactionalQueue->passive,
      'durable' => $transactionalExchange->queues->transactionalQueue->durable,
      'exclusive' => $transactionalExchange->queues->transactionalQueue->exclusive,
      'auto_delete' => $transactionalExchange->queues->transactionalQueue->auto_delete,
      'binding_pattern' => $transactionalExchange->queues->transactionalQueue->binding_pattern,
    );
    $config['routing_key'] = 'vote.cgg.transactional';

    $payload = serialize($message);

    $mb = new MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);
    echo 'produceTransactionalEmail() - email: ' . $message['email'] . ' message sent to consumer: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('produceTransactionalEmail');
    $this->statHat->reportCount(1);
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

    echo 'produceMailchimpAffilate()', PHP_EOL;
    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('produceMailchimpAffilate');
    $this->statHat->reportCount(1);
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

    echo 'produceMailchimpInternational()', PHP_EOL;
    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('produceMailchimpInternational');
    $this->statHat->reportCount(1);
  }

  /**
   * Produce emailService message.
   *
   * @param array $message
   *   Details about the transaction that has triggered producing Message
   *   Broker message.
   */
  private function sendEmailServiceMessage($message) {

    $config = array();
    $source = __DIR__ . '/messagebroker-config/mb_config.json';
    $mb_config = new MB_Configuration($source, $this->settings);
    $emailServiceExchange = $mb_config->exchangeSettings('topicEmailService');

    $config['exchange'] = array(
      'name' => $emailServiceExchange->name,
      'type' => $emailServiceExchange->type,
      'passive' => $emailServiceExchange->passive,
      'durable' => $emailServiceExchange->durable,
      'auto_delete' => $emailServiceExchange->auto_delete,
    );
    $config['queue'][] = array(
      'name' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->name,
      'passive' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->passive,
      'durable' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->durable,
      'exclusive' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->exclusive,
      'auto_delete' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->auto_delete,
      'binding_pattern' => $emailServiceExchange->queues->mailchimpSubscriptionQueue->binding_pattern,
    );
    $config['routing_key'] = 'subscribe.mailchimp.cgg';

    $payload = serialize($message);

    $mb = new MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);
    echo 'sendEmailServiceMessage() - email: ' . $message['email'] . ' message sent to queue: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $this->statHat->clearAddedStatNames();
    $this->statHat->addStatName('sendEmailServiceMessage');
    $this->statHat->reportCount(1);
  }

}
