<?php

class PaidEmail extends ProcessedEmail {

	/**
	 * Create a new email.
	 */
	public function __construct(Member $customer, Order $order, $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
	  
	  $siteConfig = SiteConfig::current_site_config();
	  if ($customer->Email) $this->to = $customer->Email; 
	  if ($siteConfig->ReceiptSubject) $this->subject = $siteConfig->PaidSubject;
	  if ($siteConfig->ReceiptBody) $this->body = $siteConfig->PaidBody;
	  if ($siteConfig->ReceiptFrom) $this->from = $siteConfig->PaidFrom;
	  elseif (Email::getAdminEmail()) $this->from = Email::getAdminEmail();
	  if ($siteConfig->EmailSignature) $this->signature = $siteConfig->EmailSignature;

	  //Get css for Email by reading css file and put css inline for emogrification
	  $this->setTemplate('Order_PaidEmail');
	  if (file_exists(Director::getAbsFile($this->ThemeDir().'/css/OrderReport.css'))) {
	    $css = file_get_contents(Director::getAbsFile($this->ThemeDir().'/css/OrderReport.css'));
	  }
	  else {
	    $css = file_get_contents(Director::getAbsFile('simplecart/css/OrderReport.css'));
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
