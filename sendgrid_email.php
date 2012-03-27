<?php
/*
 *	Sendgrid Email Component
 *  -----------------------------------------
 *  A CakePHP Component designed to easily integrate CakePHP
 *	with the wonderful service provided by SendGrid.com
 *  -----------------------------------------
 *  Original coding: Dave Loftis (dave@lofdev.com)
 *  -----------------------------------------
 *  Copyright (c) 2011 Dave Loftis.
 *	Licensed under MIT License.
 *
 *	I'd love it if you're credit me, and tell me about your use, too.
 *
 *	Last Update: Feb 14, 2011 - 12:01 MST (GMT-6:00)
 */


/*

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once(dirname(__FILE__) . '/sendgrid_lib/sendgrid_smtp_api_header.php');
App::import('Component', 'Email');

class SendgridEmailComponent extends EmailComponent {

	private $__xSmtpApi;
	private $__xSmtpApi_vars;
	private $__xSmtpApi_subs;
	private $__hasSendgridCredentials;
	private $__smtpOptions = false;

	/**
	 *	Copy the configuration information out of the app/config/database.php file
	 *	Use it to instance the parent component's option values
	 */
	public function __construct() {
		parent::__construct();

		// Setup the Sendgrid X-SMTPAPI Array
		$this->__xSmtpApi = new SmtpApiHeader();
		$this->__xSmtpApi_vars = array();
		$this->__xSmtpApi_subs = array();
	}

	private function __setupSendGridCredentials() {
		$db = new DATABASE_CONFIG();
		if (isset($db->sendgrid)) {
			$this->__smtpOptions = $db->sendgrid;
		} else {
			return false;
		}
	}

	/*
	 *  This function fixes a duplication of X-SMTPAPI in the header
	 *	of the email that gets sent.  It could be fixed by changing the
	 *	output of asString() in SendGrid's PHP object, but to keep
	 *	consistent with their code, this function is crucial.
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function __removeDuplicateHeaderLabels($string) {
		return trim(str_replace('X-SMTPAPI:', '', $string));
	}

	/*
	 *	$params =
	 *	{
	 *		'to' 			=> <recipient address>,  		REQUIRED - may be array
	 *		'subject 		=> <subject>,					REQUIRED
	 *		'from'			=> <sender address>,  			REQUIRED
	 *		'reply-to'		=> <reply to address>,
	 *		'unique'		=> <unique id for x-SMTPAPI>,
	 *		'category'		=> <category for sendgrid reports>,
	 *		'layout'		=> <email layout filename>,
	 *		'template'		=> <template filename>,
	 *		'layout-type'	=> <[text|html|both]>, 			DEFAULT = both
	 *		'delivery-type'	=> <smtp>,						DEFAULT = smtp
	 *		'merge-values' 	=> array(
	 *			<keys> => <values>,
	 *			<keys> => <values>,..    For doing bulk messages with single call
	 *		)
	 *	}
	 */
	public function sendEmail($params = array()) {
		// Setup sendgrid credentials using db config, otherwise fail
		if (!$this->__smtpOptions && !$this->__setupSendGridCredentials()) {
			return false;
		}

		// Bring our params into the local symbol table
		extract($params);

	 	// Setup Message Basics
		$this->setTo($to);
		$this->setFrom($from);

		// If you pass an explicit reply-to address, use that, otherwise the from address
		if (isset($reply-to)) {
			$this->setReplyTo($reply-to);
		} else {
			$this->setReplyTo($from);
		}

		// Set subject
		$this->setSubject($subject);

		// Setup CakePHP layout and template files for the email
		if (isset($layout)) {
			$this->setLayout($layout);
		}

		if (isset($template)) {
			$this->setTemplate($template);
		}

		// Set sendAs content-type
		if (isset($layout-type)) {
			$this->setSendAs($layout-type);
		} else {
			$this->setSendAs('both');
		}

		// Set delivery type
		if (isset($delivery-type)) {
			$this->setDelivery($delivery-type);
		} else {
			$this->setDelivery('smtp');
		}

		// Setup SendGrid Unique Message ID
		if (isset($unique)) {
			$this->setSendGridUnique($unique);
		}

		// Setup Sendgrid Substitution Values
		if (isset($merge-values) && is_array($merge-values)) {
			$this->setSubstitution($merge-values);
		}

		// Send the message
		return $this->send();

	}

	/*  Implementing setter functions for all major elements of email
	 *  NOT strictly needed, but for ease of migration, etc, I am
	 *  abstracting things.
	 */
	public function setTo($address = null) {
		if (!$address) {
			return false;
		}

		// It's possible to setup sendgrid to send multiple emails based on a single request
		// Basically this is like a mail merge
		if (is_array($address)) {
			$this->to = $this->__smtpOptions['support_email'];
			// Set the SendGrid to value(s)
			$this->__xSmtpApi->addTo($address);
		} else {
			$this->to = $address;
		}
	}

	public function setSubject($subject = null) {
		$this->subject = $subject;
	}

	public function setReplyTo($address = null) {
		$this->replyTo = $address;
	}

	public function setFrom($address = null) {
		$this->from = $address;
	}

	public function setLayout($layout = null) {
		$this->layout = str_replace('.ctp', '', $layout);
	}

	public function setTemplate($template = null) {
		$this->template = str_replace('.ctp', '', $template);
	}

	public function setSendAs($as = 'both') {
		$this->sendAs = $as;
	}

	public function setDelivery($type = 'smtp') {
		$this->delivery = $type;
	}

	public function setSendGridUnique($unique) {
		$this->__xSmtpApi->addFilterSetting('opentrack', 'enable', 1);
		$this->__xSmtpApi_vars['messageID'] = $unique;
	}

	public function setCategory($category) {
		$this->__xSmtpApi_vars['category'] = $category;
	}

	public function setSubstitution($subs) {
		$this->__xSmtpApi_subs = $subs;
	}

	/**
	 *  Send Function
	 *
	 *	Sets the SendGrid X-SMTPAPI Header and sends the email
	 */
	public function send() {
		// Set any unique variables
		$this->__xSmtpApi->setUniqueArgs($this->__xSmtpApi_vars);

		// Handle any substitutions
		foreach ($this->__xSmtpApi_subs as $k => $v) {
			$this->__xSmtpApi->addSubVal($k,$v);
		}
		if ($this->__xSmtpApi->as_string() != 'X-SMTPAPI: null') {
			// DO NOT MODIFY THIS LINE
			$this->headers['SMTPAPI'] = $this->__removeDuplicateHeaderLabels($this->__xSmtpApi->as_string());
		}

		return parent::send();
	}

}