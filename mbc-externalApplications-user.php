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

 date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_ExternalApplications\MBC_ExternalApplications_User;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-externalApplications-user.config.inc';


echo '------- mbc-externalApplications-user START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ExternalApplications_User($credentials, $settings), 'consumeQueue'));

echo '------- mbc-externalApplications-user END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
