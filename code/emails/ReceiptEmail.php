<?php

class ReceiptEmail extends ProcessedEmail {
  
  private $customer;
  
	/**
	 * Create a new email.
	 */
	public function __construct(Member $customer, Order $order, $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
	  
	  $siteConfig = SiteConfig::current_site_config();
	  if ($customer->Email) $this->to = $customer->Email; 
	  if ($siteConfig->ReceiptSubject) $this->subject = $siteConfig->ReceiptSubject;
	  if ($siteConfig->ReceiptBody) $this->body = $siteConfig->ReceiptBody;
	  if ($siteConfig->ReceiptFrom) $this->from = $siteConfig->ReceiptFrom;
	  elseif (Email::getAdminEmail()) $this->from = Email::getAdminEmail();

	  //Get css for Email by reading css file and put css inline for emogrification
	  $this->setTemplate('Order_ReceiptEmail');
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
    	  'InlineCSS' => "<style>$css</style>"
    	)
    );

		parent::__construct($from, null, $subject, $body, $bounceHandlerURL, $cc, $bcc);
	}

}
