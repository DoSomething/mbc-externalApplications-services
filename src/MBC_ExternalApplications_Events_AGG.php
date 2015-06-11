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
class MBC_ExternalApplications_Events_AGG
{

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Collection of secret connection settings.
   *
   * @var array
   */
  private $credentials;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $statHat;

  /**
   * Setting from external services - StatHat.
   *
   * @var object
   */
  private $toolbox;

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

  /**
   * Produce domestic (US) voyr event transaction.
   *
   * @param array $message
   *   Details about the transaction for US based signups.
   */
  public function produceUSEvent($message) {

    $payload = array(
      'mobile' => $message['mobile'],
      'first_name' => '',
      'birthdate_timestamp' => $message['candidate_name'],
      'candidate_name' => $message['candidate_name'],
      'candidate_gender' => $message['candidate_gender'],
      'activity' => $message['activity'],
      'mc_opt_in_path_id' => $message['mc_opt_in_path_id']
    );
    $payload = serialize($payload);

    $mbConfig = new MB_Configuration($this->settings, CONFIG_PATH . '/mb_config.json');
    $config = $mbConfig->constructConfig('transactionalExchange', array('mobileCommonsQueue'));
    $config['routingKey'] = 'user.registration.agg';

    $mbMobileCommons = new \MessageBroker($this->credentials, $config);
    $mbMobileCommons->publishMessage($payload);

    echo '- produceUSEvent() - SMS vote message sent to queue: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: AGG: produceUSEvent - mobile vote', 1);
  }

  /**
   * Produce international event (vote).
   *
   * @param array $message
   *   Details about the transaction that has triggered producing international,
   *   non-affiliate Message Broker functionality.
   */
  public function produceInternationalEvent($message) {

    $message['merge_vars']['MEMBER_COUNT'] = $this->toolbox->getDSMemberCount();
    $this->produceTransactionalEmail($message);

    echo '- produceInternationalEvent - email: ' . $message['email'] . ' country_code: ' . $message['country_code'], PHP_EOL;
    $this->statHat->ezCount('mbc-externalApplications-events: produceInternationalEvent', 1);
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
    $config['routingKey'] = 'vote.agg.transactional';

    $payload = serialize($message);

    $mb = new \MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);
    echo '- produceTransactionalEmail() - email: ' . $message['email'] . ' message sent to consumer: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $this->statHat->ezCount('mbc-externalApplications-events: produceTransactionalEmail', 1);
  }

}
