<?php
/**
 * mbc-externalApplications-user.php
 *
 * Consume queue entries in externalApplicationsUserQueue to process messages
 * from external applications regarding users created in the application.
 *
 * Each entry will result in:
 *   - User creation in the Drupal website
 *   - An entry in mb-users via userAPI
 *   - Mailchimp entry
 *   - Mandrill transactional signup email message if a Drupal user is created
 */

use DoSomething\MBStatTracker\StatHat;

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';

require __DIR__ . '/MBC_ExternalApplications_User.class.inc';
require __DIR__ . '/messagebroker-config/MB_Configuration.class.inc';


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
$source = __DIR__ . '/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$mb_exchange_directExternalApplicationsExchange = $mb_config->exchangeSettings('directExternalApplicationsExchange');

$config['exchange'] = array(
  'name' => $mb_exchange_directExternalApplicationsExchange->name,
  'type' => $mb_exchange_directExternalApplicationsExchange->type,
  'passive' => $mb_exchange_directExternalApplicationsExchange->passive,
  'durable' => $mb_exchange_directExternalApplicationsExchange->durable,
  'auto_delete' => $mb_exchange_directExternalApplicationsExchange->auto_delete,
);
$config['queue'][] = array(
  'name' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->name,
  'passive' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->passive,
  'durable' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->durable,
  'exclusive' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->exclusive,
  'auto_delete' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->auto_delete,
  'bindingKey' => $mb_exchange_directExternalApplicationsExchange->queues->externalApplicationUserQueue->binding_key,
);



echo '------- mbc-externalApplications-user START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_externalApplications_user($settings), 'consumeQueue'));

echo '------- mbc-externalApplications-user END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
