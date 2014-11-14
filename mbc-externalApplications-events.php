<?php
/**
 * mbc-externalApplications-events.php
 *
 * Consume queue entries in externalApplicationsEventsQueue to process messages
 * from external applications regarding events created in the application.
 *
 * Each entry will result in:
 *   - User votes result in tagging user accounts in Mailchimp with vote
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

require __DIR__ . '/MBC_externalApplications_event.class.inc';

class MessageBrokerConfig
{
  /**
   * Report consumer activity to StatHat service.
   *
   * @var array
   */
  private $statHat;
  
  /**
   * All Message Broker configuration settings - the source of truth.
   *
   * @var array
   */
  private $settings;

  /**
   * Constructor for MessageBroker-Config
   *
   * @param array $source
   *   The source of configuration settings. This can be from a file or an
   *   endpoint.
   */
  public function __construct($source) {

    $this->settings = $this->_gatherSettings($source);

    $this->statHat = new StatHat($settings['stathat_ez_key'], 'messagebroker-config:');
    $this->statHat->setIsProduction(FALSE);
  }
  
  /*
   * Gather all setting for a specific exchange
   */
  public function exchangeSettings($targetExchange) {
    
    foreach($this->settings['rabbit']['exchanges'] as $exchange => $exchangeSettings) {
      if ($exchange == $targetExchange) {
        $settings = $exchangeSettings;
      }
    }
    
    return $settings;
  }
  
  /*
   * Gather all Message Broker configuration settings from the defined source.
   *
   * @param string $source
   *   Source can be the path to a file or a URL to an endpoint.
   */
  private function _gatherSettings($source) {
    
    if (strpos('http://', $source) == TRUE) {
      echo 'cURL sources are not currently supported.', "\n";
    }
    else {
      if (file_exists($source)) {
        $settings = json_decode(implode(file($source)));
        return $settings;
      }
      else {
        echo 'Source: ' . $source . ' not fount.', "\n";
      }
    }

  }
  
}


// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);
$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
);

$config = array();
$source = __DIR__ . '/mb-config.json';
$mb_config = new MessageBrokerConfig($source);
$mb_exchange_directExternalApplicationsExchange = $mb_config->exchangeSettings('directExternalApplicationsExchange');

$config['exchange'] = array(
  'name' => $mb_exchange_directExternalApplicationsExchange['exchange']['name'],
  'type' => $mb_exchange_directExternalApplicationsExchange['exchange']['type'],
  'passive' => $mb_exchange_directExternalApplicationsExchange['exchange']['passive'],
  'durable' => $mb_exchange_directExternalApplicationsExchange['exchange']['durable'],
  'auto_delete' => $mb_exchange_directExternalApplicationsExchange['exchange']['auto+delete'],
);
$config['queue'] = array(
  'name' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['name'],
  'passive' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['passive'],
  'durable' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['durable'],
  'exclusive' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['exclusive'],
  'auto_delete' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['auto_delete'],
  'bindingKey' => $mb_exchange_directExternalApplicationsExchange['queue']['queue']['externalApplicationEventQueue']['binding_key'],
);


echo '------- mbc-externalApplications-events START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_externalApplications_events($settings), 'consumeQueue'));

echo '------- mbc-externalApplications-events END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
