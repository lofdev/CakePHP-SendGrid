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

	private $xSmtpApi;
	private $xSmtpApi_vars; 
	private $xSmtpApi_subs;
	private $hasSendgridCredentials;

	/**
	 *	Copy the configuration information out of the app/config/database.php file 
	 *	Use it to instance the parent component's option values 
	 */
	 
	function __construct() {
		parent::__construct();
		$this->_configurate();
	}
	private function _configurate() {
		
		$this->hasSendgridCredentials = false;
		
		// Setup the Sendgrid X-SMTPAPI Array
		$this->xSmtpApi = new SmtpApiHeader(); 
		$this->xSmtpApi_vars = array();
		$this->xSmtpApi_subs = array();
	}
	
	private function _setupSendGridCredentials() {
		$db = new DATABASE_CONFIG();
		if (isset($db->sendgrid)) {
			$this->smtpOptions = $db->sendgrid;
			$this->hasSendgridCredentials = true;
		}
	}
	
	/*
	 *  This function fixes a duplication of X-SMTPAPI in the header
	 *	of the email that gets sent.  It could be fixed by changing the 
	 *	output of asString() in SendGrid's PHP object, but to keep
	 *	consistent with their code, this function is crucial.
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
	private function _removeDuplicateHeaderLabels($string) {
		return trim(str_replace('X-SMTPAPI:', '', $string));
	}

	function sendEmail($params = null) {
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
		 
		//  Sucks that this doesn't work in the constructor
		if (!$this->hasSendgridCredentials) $this->_setupSendGridCredentials();
		if (!$this->hasSendgridCredentials) return false; //  We cannot do this if no credentials
		
	 	
	 	//  Setup Message Basics
		$this->setTo($params['to']);
		$this->setFrom($params['from']);
		//  If you pass an explicit reply-to address, use that, otherwise the from address
		if (isset($params['reply-to'])) $this->setReplyTo($params['reply-to']);
		else $this->setReplyTo($params['from']);
		$this->setSubject($params['subject']);
		
		//  Setup CakePHP layout and template files for the email
		if (isset($params['layout'])) $this->setLayout($params['layout']);
		if (isset($params['template'])) $this->setTemplate($params['template']);
		
		//  Set sendAs content-type
		if (isset($params['layout-type'])) $this->setSendAs($params['layout-type']);
		else $this->setSendAs('both');
		
		//  Set delivery type
		if (isset($params['delivery-type'])) $this->setDelivery($params['delivery-type']);
		else $this->setDelivery('smtp');
		
		//  Setup SendGrid Unique Message ID
		if (isset($params['unique'])) $this->setSendGridUnique($params['unique']);
		//  Setup Sendgrid Substitution Values
		if (isset($params['merge-values']) && is_array($params['merge-values'])) $this->setSubstitution($params['merge-values']);
		
		//  Send the message
		return $this->send();
	
	}

	
	
	/*  Implementing setter functions for all major elements of email
	 *  NOT strictly needed, but for ease of migration, etc, I am 
	 *  abstracting things.
	 */
	function setTo($address = null) {
		
		if (!$address) return false;
		
		//  It's possible to setup sendgrid to send multiple emails based on a single request
		//  Basically this is like a mail merge
		if (is_array($address)) {
			$this->to = $this->smtpOptions['support_email'];		
			//  Set the SendGrid to value(s)
			$this->xSmtpApi->addTo($address);
		}
		else $this->to = $address; 
	}
	function setSubject($subject = null) {
		$this->subject = $subject;
	}
	function setReplyTo($address = null) {
		$this->replyTo = $address;
	}
	function setFrom($address = null) {
		$this->from = $address;
	}
	function setLayout($layout = null) {
		$this->layout = str_replace('.ctp','',$layout);  //  Just to be nice
	}
	function setTemplate($template = null) {
		$this->template = str_replace('.ctp','',$template);  //  Just to be nice
	}
	function setSendAs($as = 'both') {
		$this->sendAs = $as;
	}
	function setDelivery($type = 'smtp') {
		$this->delivery = $type;
	}
	function setSendGridUnique($unique) {
		$this->xSmtpApi->addFilterSetting('opentrack', 'enable', 1);
		$this->xSmtpApi_vars['messageID'] = $unique;
	}
	function setCategory($category) {
		$this->xSmtpApi_vars['category'] = $category;
	}
	function setSubstitution($subs) {
		$this->xSmtpApi_subs = $subs;
	}
	
	
	
	

	/**
	 *  Send Function
	 *
	 *	Sets the SendGrid X-SMTPAPI Header and sends the email
	 */
	function send() {
		//  Set any unique variables
		$this->xSmtpApi->setUniqueArgs($this->xSmtpApi_vars);
		
		//  Handle any substitutions
		foreach ($this->xSmtpApi_subs as $k => $v) {
			$this->xSmtpApi->addSubVal($k,$v);
		}
		if ($this->xSmtpApi->as_string() != 'X-SMTPAPI: null') 
			//  DO NOT MODIFY THIS LINE
			$this->headers['SMTPAPI'] = $this->_removeDuplicateHeaderLabels($this->xSmtpApi->as_string());
		return parent::send();
	}
	
	
	
	
	
	
	
	
	
	/**
	 * Initialize an Email  - DEPRECATED - DO NOT USE
	 *
	 * Input
	 *	 string $to	      (Email Address)
	 *	 string $from     (Email Address)
	 *	 string $subject  (Subject Line)
	 *	 string $unique	  (Unique Identifier - or NULL/false)
	 *	 string $layout   (Layout File - no .ctp)
	 *	 string $template (Template - no .ctp)
	 *	 string $category (Category for grouping sent statistics)
	 *	 boolean $sendNow (immediately send the message?)
	 */
	function init($to, $from, $subject, $unique = null, $layout = null, $template = null, $category = null, $sendNow = false) {
	
		$params = array(
			'to'			=> $to,
			'from'			=> $from,
			'reply-to'		=> $from,
			'subject'		=> $subject,
			'layout'		=> $layout,
			'template'		=> $template,
			'layout-type'	=> 'both',
			'delivery-type'	=> 'smtp',
			'unique'		=> $unique
		);
		
		return $this->sendEmail($params);
	}

}




?>