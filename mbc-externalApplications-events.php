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

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_ExternalApplications\MBC_ExternalApplications_Events;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-externalApplications-events.config.inc';


echo '------- mbc-externalApplications-events START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ExternalApplications_Events($credentials, $settings), 'consumeQueue'));

echo '------- mbc-externalApplications-events END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
