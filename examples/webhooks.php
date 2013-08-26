<?php

/**
 * This is a demo of the webhook functionality of GoCardless.
 *
 * You can use this script with the webhook testing tool in the developer tab.
 * At the moment, the best way to learn about the different webhooks is to
 * change the options in the webhook tester and read the annotations that pop
 * up.
 *
 * Webhook documentation:
 * https://sandbox.gocardless.com/docs/web_hooks_guide
 *
 */

// Include library
include_once '../lib/GoCardless.php';

// Sandbox is the default - uncomment to change to production
// GoCardless::$environment = 'production';

// Config vars
$account_details = array(
  'app_id'        => null,
  'app_secret'    => null,
  'merchant_id'   => null,
  'access_token'  => null
);

// Initialize GoCardless
GoCardless::set_account_details($account_details);


$email['to']	= $_SERVER["SERVER_ADMIN"];
$webhook = file_get_contents('php://input');
$w = json_decode($webhook, true);
$webhook_valid = GoCardless::validate_webhook($w['payload']);

if ($webhook_valid == TRUE) {

  	// Send a success header
  header('HTTP/1.1 200 OK');
  
  $email['content'] = print_r($w, TRUE);
  mail($email['to'], 'goCardless webhook', $email['content'], "FROM: " . $email['to']);
  
  switch( $w['payload']['resource_type'] ){
  	case 'bill':
  		$data = $w['payload']['bills'];
  		switch( $w['payload']['action'] ){
  			case 'created': //bill is created automatically under a subscription
  				mail($email['to'], 'goCardless webhook - bill - created', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $bill){
  					//get all fields, not available in webhook
  					$b 	= GoCardless_Bill::find( $bill['id'] );
  				}
  			break;
  			
  			case 'failed': //bill could not be debited from a customer's account
  				mail($email['to'], 'goCardless webhook bill failed (e.g. no funds)', $email['content'], "FROM: " . $email['to']);
  			break;
  			
  			case 'paid': //bill has successfully been debited from a customer's account
  				mail($email['to'], 'goCardless webhook - bill - paid', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $bill){
  					$b 	= GoCardless_Bill::find( $bill['id'] );
  				}
  			break;
  			
  			case 'withdrawn': //paid into merchant's bank
  				mail($email['to'], 'goCardless webhook withdrawn (paid to bank)', $email['content'], "FROM: " . $email['to']);
  			break;
  			
  			case 'refunded': //result of a chargeback that a customer has filed with their bank under the Direct Debit Guarantee
  				mail($email['to'], 'goCardless webhook refunded', $email['content'], "FROM: " . $email['to']);
  			break;
  			
  			case 'retried': //bill is submitted to the bank again after having previously failed
  				mail($email['to'], 'goCardless webhook retried', $email['content'], "FROM: " . $email['to']);
  			break;
  			
  			default: //unexpected action, email
  				mail($email['to'], 'goCardless webhook exception', $email['content'], "FROM: " . $email['to']);
  		}
  	break;
  
  	case 'subscription':
  		$data = $w['payload']['subscriptions'];
  		switch( $w['payload']['action'] ){
  			case 'cancelled': //subscription is cancelled
  				mail($email['to'], 'goCardless webhook - sub - cancelled', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $sub){
  					//get all fields, not available in webhook
  					$b 	= GoCardless_Subscription::find( $sub['id'] );
  				}
  			break;
  			
  			case 'expired': //subscription has reached set expiry date
  				mail($email['to'], 'goCardless webhook - sub - expired', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $sub){
  					//get all fields, not available in webhook
  					$b 	= GoCardless_Subscription::find( $sub['id'] );
  				}
  			break;
  			
  			default: //unexpected action, email
  				mail($email['to'], 'goCardless webhook exception', $email['content'], "FROM: " . $email['to']);
  		}
  	break;
  	
  	case 'pre_authorization':
  		$data = $w['payload']['pre_authorizations'];
  		switch( $w['payload']['action'] ){
  			case 'cancelled': //pre-auth is cancelled
  				mail($email['to'], 'goCardless webhook - pre-auth - cancelled', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $pre){
  					//get all fields, not available in webhook
  					$b 	= GoCardless_PreAuthorization::find( $pre['id'] );
  				}
  			break;
  			
  			case 'expired': //pre-auth has reached set expiry date
  				mail($email['to'], 'goCardless webhook - pre-auth - expired', $email['content'], "FROM: " . $email['to']);
  				foreach($data as $pre){
  					//get all fields, not available in webhook
  					$b 	= GoCardless_PreAuthorization::find( $pre['id'] );
  				}
  			break;
  			
  			default: //unexpected action, email
  				mail($email['to'], 'goCardless webhook exception', $email['content'], "FROM: " . $email['to']);
  		}
  	break;
  	
  	default: //unexpected action, email
  		mail($email['to'], 'goCardless webhook exception', $email['content'], "FROM: " . $email['to']);
  }

} else {

  header('HTTP/1.1 403 Invalid signature');

}
?>
