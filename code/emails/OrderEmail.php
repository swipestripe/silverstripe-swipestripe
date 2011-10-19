<?php
/**
 * Sent to website owner when new Order is made.
 * 
 * @author frankmullenger
 *
 */
class OrderEmail extends ProcessedEmail {

	/**
	 * Create a new email.
	 */
	public function __construct(Member $customer, Order $order, $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
	  
	  $siteConfig = SiteConfig::current_site_config();
	  if ($siteConfig->OrderTo) $this->to = $siteConfig->OrderTo; 
	  if ($siteConfig->OrderSubject) $this->subject = $siteConfig->OrderSubject;
	  if ($siteConfig->OrderBody) $this->body = $siteConfig->OrderBody;
	  if (Email::getAdminEmail()) $this->from = Email::getAdminEmail();
	  if ($siteConfig->EmailSignature) $this->signature = $siteConfig->EmailSignature;

	  //Get css for Email by reading css file and put css inline for emogrification
	  $this->setTemplate('Order_ReceiptEmail');
	  if (file_exists(Director::getAbsFile($this->ThemeDir().'/css/StripeyCart.css'))) {
	    $css = file_get_contents(Director::getAbsFile($this->ThemeDir().'/css/StripeyCart.css'));
	  }
	  else {
	    $css = file_get_contents(Director::getAbsFile('stripeycart/css/StripeyCart.css'));
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
