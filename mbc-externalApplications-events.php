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

use DoSomething\MBStatTracker\StatHat;

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/mb-secure-config.inc';

require_once __DIR__ . '/MBC_ExternalApplications_Events.class.inc';
require_once __DIR__ . '/messagebroker-config/MB_Configuration.class.inc';


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
  'ds_drupal_api_host' => getenv('DS_DRUPAL_API_HOST'),
  'ds_drupal_api_port' => getenv('DS_DRUPAL_API_PORT'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$externalApplicationsExchange = $mb_config->exchangeSettings('directExternalApplicationsExchange');

$config['exchange'] = array(
  'name' => $externalApplicationsExchange->name,
  'type' => $externalApplicationsExchange->type,
  'passive' => $externalApplicationsExchange->passive,
  'durable' => $externalApplicationsExchange->durable,
  'auto_delete' => $externalApplicationsExchange->auto_delete,
);
$config['queue'][] = array(
  'name' => $externalApplicationsExchange->queues->externalApplicationEventQueue->name,
  'passive' => $externalApplicationsExchange->queues->externalApplicationEventQueue->passive,
  'durable' => $externalApplicationsExchange->queues->externalApplicationEventQueue->durable,
  'exclusive' => $externalApplicationsExchange->queues->externalApplicationEventQueue->exclusive,
  'auto_delete' => $externalApplicationsExchange->queues->externalApplicationEventQueue->auto_delete,
  'bindingKey' => $externalApplicationsExchange->queues->externalApplicationEventQueue->binding_key,
);



echo '------- mbc-externalApplications-events START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_externalApplications_events($credentials, $settings), 'consumeQueue'));

echo '------- mbc-externalApplications-events END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
