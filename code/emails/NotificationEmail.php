<?php
/**
 * Sent to website owner when new Order is made.
 * 
 * @author frankmullenger
 *
 */
class NotificationEmail extends ProcessedEmail {

	/**
	 * Create a new email.
	 */
	public function __construct(Member $customer, Order $order, $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
	  
	  $siteConfig = SiteConfig::current_site_config();
	  if ($siteConfig->NotificationTo) $this->to = $siteConfig->NotificationTo; 
	  if ($siteConfig->NotificationSubject) $this->subject = $siteConfig->NotificationSubject . ' - Order #'.$order->ID;
	  if ($siteConfig->NotificationBody) $this->body = $siteConfig->NotificationBody;
	  if (Email::getAdminEmail()) $this->from = Email::getAdminEmail();
	  $this->signature = '';

	  //Get css for Email by reading css file and put css inline for emogrification
	  $this->setTemplate('Order_NotificationEmail');
	  if (file_exists(Director::getAbsFile($this->ThemeDir().'/css/Shop.css'))) {
	    $css = file_get_contents(Director::getAbsFile($this->ThemeDir().'/css/Shop.css'));
	  }
	  else {
	    $css = file_get_contents(Director::getAbsFile('shop/css/Shop.css'));
	  }

    $this->populateTemplate(
    	array(
    		'Message' => $this->Body(),
    		'Order' => $order,
    	  'Customer' => $customer,
    	  'InlineCSS' => "<style>$css</style>",
    	  'Signature' => $this->signature
    	)
    );
		parent::__construct($from, null, $subject, $body, $bounceHandlerURL, $cc, $bcc);
	}
}
