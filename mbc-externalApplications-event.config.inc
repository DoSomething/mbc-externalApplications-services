<?php
/**
 * Message Broker configuration settings for mbp-user-import
 */
 
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

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
$configSource = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($configSource, $settings);
$externalApplicationsExchange = $mb_config->exchangeSettings('directExternalApplicationsExchange');

$config = array(
  'exchange' => array(
    'name' => $externalApplicationsExchange->name,
    'type' => $externalApplicationsExchange->type,
    'passive' => $externalApplicationsExchange->passive,
    'durable' => $externalApplicationsExchange->durable,
    'auto_delete' => $externalApplicationsExchange->auto_delete,
  ),
  'queue' => array(
    array(
      'name' => $externalApplicationsExchange->queues->externalApplicationEventQueue->name,
      'passive' => $externalApplicationsExchange->queues->externalApplicationEventQueue->passive,
      'durable' =>  $externalApplicationsExchange->queues->externalApplicationEventQueue->durable,
      'exclusive' =>  $externalApplicationsExchange->queues->externalApplicationEventQueue->exclusive,
      'auto_delete' =>  $externalApplicationsExchange->queues->externalApplicationEventQueue->auto_delete,
      'bindingKey' => $externalApplicationsExchange->queues->externalApplicationEventQueue->binding_key,
    ),
  ),
);