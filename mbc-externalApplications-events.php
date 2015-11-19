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
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on the same queue.
define('QOS_SIZE', 1);

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_ExternalApplications\MBC_ExternalApplications_Events_Consumer;

require_once __DIR__ . '/mbc-externalApplications-events.config.inc';

// Kick off
echo '------- mbc-externalApplications-events START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

$mb = $mbConfig->getProperty('messageBroker');
$mb->consumeMessage(array(new MBC_ExternalApplications_Events_Consumer(), 'consumeExternalApplicationEventQueue'), QOS_SIZE);

echo '------- mbc-externalApplications-events END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
