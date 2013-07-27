<?php
/**
 * A notification email that is sent to an email address specified in {@link ShopConfig}, usually
 * a site administrator or owner. 
 * 
 * @author Frank Mullenger <frankmullenger@gmail.com>
 * @copyright Copyright (c) 2011, Frank Mullenger
 * @package swipestripe
 * @subpackage emails
 */
class NotificationEmail extends ProcessedEmail {

	/**
	 * Create the new notification email.
	 * 
	 * @param Member $customer
	 * @param Order $order
	 * @param String $from
	 * @param String $to
	 * @param String $subject
	 * @param String $body
	 * @param String $bounceHandlerURL
	 * @param String $cc
	 * @param String $bcc
	 */
	public function __construct(Member $customer, Order $order, $from = null, $to = null, $subject = null, $body = null, $bounceHandlerURL = null, $cc = null, $bcc = null) {
		
		$siteConfig = ShopConfig::get()->first();
		if ($siteConfig->NotificationTo) $this->to = $siteConfig->NotificationTo; 
		if ($siteConfig->NotificationSubject) $this->subject = $siteConfig->NotificationSubject . ' - Order #'.$order->ID;
		if ($siteConfig->NotificationBody) $this->body = $siteConfig->NotificationBody;
		
		if ($customer->Email) $this->from = $customer->Email; 
		elseif (Email::getAdminEmail()) $this->from = Email::getAdminEmail();
		else $this->from = 'no-reply@' . $_SERVER['HTTP_HOST'];
		
		$this->signature = '';
		$adminLink = Director::absoluteURL('/admin/shop/');

		//Get css for Email by reading css file and put css inline for emogrification
		$this->setTemplate('Order_NotificationEmail');
		
		if (file_exists(Director::getAbsFile($this->ThemeDir().'/css/ShopEmail.css'))) {
			$css = file_get_contents(Director::getAbsFile($this->ThemeDir().'/css/ShopEmail.css'));
		}
		else {
			$css = file_get_contents(Director::getAbsFile('swipestripe/css/ShopEmail.css'));
		}

		$this->populateTemplate(
			array(
				'Message' => $this->Body(),
				'Order' => $order,
				'Customer' => $customer,
				'InlineCSS' => "<style>$css</style>",
				'Signature' => $this->signature,
				'AdminLink' => $adminLink
			)
		);
		parent::__construct($from, null, $subject, $body, $bounceHandlerURL, $cc, $bcc);
	}
}
