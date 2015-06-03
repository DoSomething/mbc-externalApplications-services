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
  'stathat_disable_tracking' => getenv('DISABLE_STAT_TRACKING'),
);

$config = array();
$configSource = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($configSource, $settings);
$userImportExchange = $mb_config->exchangeSettings('directUserImport');

$config = array(
  'exchange' => array(
    'name' => $userImportExchange->name,
    'type' => $userImportExchange->type,
    'passive' => $userImportExchange->passive,
    'durable' => $userImportExchange->durable,
    'auto_delete' => $userImportExchange->auto_delete,
  ),
  'queue' => array(
    array(
      'name' => $userImportExchange->queues->userImportQueue->name,
      'passive' => $userImportExchange->queues->userImportQueue->passive,
      'durable' =>  $userImportExchange->queues->userImportQueue->durable,
      'exclusive' =>  $userImportExchange->queues->userImportQueue->exclusive,
      'auto_delete' =>  $userImportExchange->queues->userImportQueue->auto_delete,
      'bindingKey' => $userImportExchange->queues->userImportQueue->binding_key,
    ),
  ),
  'routingKey' => $userImportExchange->queues->userImportQueue->routing_key,
);
