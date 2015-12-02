<?php
/**
 * MBC_ExternalApplications_Events_Consumer: Class to perform user event activities
 * submitted by external applications.
 */
namespace DoSomething\MBC_ExternalApplications;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;

/**
 * MBC_ExternalApplications_Events_Consumer class - functionality related to the Message Broker
 * consumer mbc-externalApplication-services_events.
 */
class MBC_ExternalApplications_Events_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * Singleton instance of MB_Configuration application settings and service objects
   * @var object $mbConfig
   */
  protected $mbConfig;

  /**
   * Message Broker connection to RabbitMQ
   * @var object $messageBrokerService
   */
  protected $messageBrokerService;

  /**
   * Compiled values for generation of message to send request to email and SMS services
   * @var array $submission
   */
  protected $submission;
  
    /**
   * Extend the base consumer class.
   */
  public function __construct() {

    parent::__construct();
    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBrokerService = $this->mbConfig->getProperty('messageBrokerServices');
    $this->mbToolbox = $this->mbConfig->getProperty('mbToolbox');
  }

  /* 
   * Consume entries in externalApplicationEventQueue. Events are activities that are not specific to
   * managing a user account.
   *
   * Currently supports external applications:
   *   - Celebrities Gone Good (CGG)
   *   - Athletes Gone Good (AGG)
   */
  public function consumeExternalApplicationEventQueue($payload) {

    echo '------- mbc-externalApplication->MBC_ExternalApplications_Events->consumeExternalApplicationEventQueue() START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    parent::consumeQueue($payload);
    echo '** Consuming: ' . $this->message['email'], PHP_EOL;
    
    if ($this->canProcess()) {

      try {
        $this->setter($this->message);
        $this->process();
        $this->messageBroker->sendAck($this->message['payload']);
      }
      catch(Exception $e) {
        echo 'Error sending transactional event messages for: ' . $this->message['email'] . '. Error: ' . $e->getMessage() . PHP_EOL;
        $errorDetails = $e->getMessage();
        // @todo: Send error submission to userMailchimpStatusQueue for processing by mb-user-api
        // See issue: https://github.com/DoSomething/mbc-transactional-email/issues/26 and
        // https://github.com/DoSomething/mb-toolbox/issues/54
      }

    }
    else {
      echo '- ' . $this->message['email'] . ' failed canProcess(), removing from queue.', PHP_EOL;
      $this->messageBroker->sendAck($this->message['payload']);
    }
    
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (!(isset($this->message['email']))) {
      echo '- canProcess(), email not set.', PHP_EOL;
      return FALSE;
    }

   if (filter_var($this->message['email'], FILTER_VALIDATE_EMAIL) === false) {
      echo '- canProcess(), failed FILTER_VALIDATE_EMAIL: ' . $this->message['email'], PHP_EOL;
      return FALSE;
    }
    else {
      $this->message['email'] = filter_var($this->message['email'], FILTER_VALIDATE_EMAIL);
    }
    if (!(isset($this->message['mailchimp_list_id']))) {
      echo '- canProcess(), mailchimp_list_id not set.', PHP_EOL;
      return FALSE;
    }
    if (!(isset($this->message['email_template']))) {
      echo '- canProcess(), email_template not set.', PHP_EOL;
      return FALSE;
    }

    if (!(isset($this->message['application_id']))) {
      echo '- canProcess(), application_id not set.', PHP_EOL;
      return FALSE;
    }

    if (!(isset($this->message['user_language']))) {
      echo '- canProcess(), WARNING: user_language not set.', PHP_EOL;
    }

    if (!(isset($this->message['user_country']))) {
      echo '- canProcess(), WARNING: user_country not set.', PHP_EOL;
    }

    return TRUE;
  }

  /**
   * Construct values for submission to email and SMS services.
   *
   * @param array $message
   *   The message to process based on what was collected from the queue being processed.
   */
  protected function setter($message) {

    $this->submission = [];

    // Required
    $this->submission['activity'] = $message['activity'];
    $this->submission['activity_timestamp'] = $message['activity_timestamp'];
    $this->submission['email'] = $message['email'];
    $this->submission['email_template'] = $message['email_template'];
    $this->submission['mailchimp_list_id'] = $message['mailchimp_list_id'];
    $this->submission['application_id'] = $message['application_id'];
    $this->submission['source'] = $message['application_id'];

    // Optionals
    // Email
    if (isset($message['merge_vars']['FNAME'])) {
      $this->submission['fname'] = $message['merge_vars']['FNAME'];
      $this->submission['merge_vars']['FNAME'] = $message['merge_vars']['FNAME'];
    }
    if (isset($message['merge_vars']['CANDIDATE_NAME'])) {
      $this->submission['merge_vars']['CANDIDATE_NAME'] = $message['merge_vars']['CANDIDATE_NAME'];
    }
    if (isset($message['merge_vars']['CANDIDATE_LINK'])) {
      $this->submission['merge_vars']['CANDIDATE_LINK'] = $message['merge_vars']['CANDIDATE_LINK'];
    }
    $this->submission['merge_vars']['MEMBER_COUNT'] = $this->mbToolbox->getDSMemberCount();
    if (isset($message['email_tags'])) {
      $this->submission['email_tags'] = $message['email_tags'];
    }
    
    // Mobile
    if (isset($message['mobile'])) {
      $this->submission['mobile'] = $message['mobile'];
    }
    if (isset($message['mobile_opt_in_path_id'])) {
      $this->submission['mobile_opt_in_path_id'] = $message['mobile_opt_in_path_id'];
    }
    if (isset($message['mobile_tags'])) {
      $this->submission['mobile_tags'] = $message['mobile_tags'];
    }

    // User details
    // Extract user_country if not set or default to "US".
    if (!(isset($message['user_country'])) && isset($message['email_template'])) {
      $this->submission['user_country'] = $this->countryFromTemplateName($message['email_template']);
    }
    elseif (isset($message['user_country'])) {
       $this->submission['user_country'] = $message['user_country'];
    }
    else {
      $this->submission['user_country'] = 'US';
    }
    if (isset($message['user_language'])) {
      $this->submission['user_language'] = $message['user_language'];
    }
    if (isset($message['uid'])) {
      $this->submission['uid'] = $message['uid'];
    }
    if (isset($message['birthdate_timestamp'])) {
      $this->submission['birthdate_timestamp'] = (int)$message['birthdate_timestamp'];
    }
    
    // Vote details
    if (isset($message['candidate_id'])) {
      $this->submission['candidate_id'] = $message['candidate_id'];
    }
    if (isset($message['candidate_name'])) {
      $this->submission['candidate_name'] = $message['candidate_name'];
    }

  }  

  /**
   * process(): Submit vote details to appropreate service.
   *
   * email
   * - transactionalQueue - *.*.transactional
   * - userRegistrationQueue - user.registration.*
   *
   * mobile
   * - mobileCommonsQueue - user.registration.*
   */
  protected function process() {
    
    $message = serialize($this->submission);

    // Email transactional
    $this->messageBrokerService->publish($message, 'ccg.vote.transactional');

    // Email and SMS services
    $this->messageBrokerService->publish($message, 'user.registration.ccg');
  }

  /*
   * logEvent: Log the event generated by the external application. Log entries can be used to
   * generate timed messaging after the event.
   *
   * @parm array $message
   */
  private function logEvent($message) {

    $mbConfig = new MB_Configuration($this->settings, CONFIG_PATH . '/mb_config.json');
    $config = $mbConfig->constructConfig('directLoggingGateway', array('loggingGatewayQueue'));
    $config['routingKey'] = 'loggingGateway';

    $payload = array(
      'log-type' => 'vote',
      'email' => $message['email'],
      'source' => $message['application_id'],
      'activity' => $message['activity'],
      'activity_date' => date('c'),
      'activity_timestamp' => time(),
      'activity_details' => $message,
    );
    $payload = serialize($payload);

    $mb = new \MessageBroker($this->credentials, $config);
    $mb->publishMessage($payload);
    echo '- logEvent: '   . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $this->statHat->ezCount('mbc-externalApplications-events: logEvent', 1);

  }

  /**
   * countryFromTemplateName(): Extract country code from email template string. The last characters in string are
   * country specific. If last character is "-" the template name is invalid, default to "US" as country.
   *
   * @todo: Move method to MB_Toolbox class.
   *
   * @param string $emailTemplate
   *   The name of the template defined in the message transactional request.
   *
   * @return string $country
   *   A two letter country code.
   */
  protected function countryFromTemplateName($emailTemplate) {

    // Trap NULL values for country code. Ex: "mb-cgg2015-vote-"
    if (substr($emailTemplate, strlen($emailTemplate) - 1) == "-") {
      echo '- WARNING countryFromTemplateName() defaulting to country: US as template name was invalid. $emailTemplate: ' . $emailTemplate, PHP_EOL;
      $country = 'US';
    }
    else {
      $templateBits = explode('-', $emailTemplate);
      $country = $templateBits[count($templateBits) - 1];
    }

    return $country;
  }

}
