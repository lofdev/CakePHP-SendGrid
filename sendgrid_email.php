<?php
/*
 *	Sendgrid Email Component 
 *  -----------------------------------------
 *  A CakePHP Component designed to easily integrate CakePHP
 *	with the wonderful service provided by SendGrid.com
 *  -----------------------------------------
 *  Original coding: Dave Loftis (dave@lofdev.com)
 *  -----------------------------------------
 *  Copyright (c) 2010 Vacation Rental Partner, Inc.
 *  Licenced under version 2 of the GNU Public License.
 *
 *	Last Update: Nov 2, 2010 - 12:08 MDT (GMT-7:00)
 */
 
 /*
  *  Usage:
  *  ----------------------------------------------
  *  $this->SendgridEmail->init($to, 
  *								$from, 
  *								$subject, 
  *								$unique, 
  *								$layout, 
  *								$template, 
  *								$category, 
  *								$sendNow);
  **/
 
App::import('Component', 'Email');
class SendgridEmailComponent extends EmailComponent {

	/**
	 * I debated making this private, but decided to leave it public
	 * since it's public in the parent.
	 */
	public $smtpOptions = array(
		'port'=>'25', 
		'timeout'=>'30',
		'host' => 'smtp.sendgrid.net',
		'username'=>'r',
		'password'=>'',
		'client' => 'smtp_helo_hostname'
	);
	private $xsmtpapi;




	/**
	 * Initialize an Email
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
	
		$this->to = $to;
		$this->subject = $subject;
		//  Reply to and From are the same by default, but you can change them if you want
		$this->replyTo = $from;
		$this->from = $from;
		//echo 'To: ' . $this->to;
		$this->template = str_replace('.ctp','',$template); 	//  Just to be nice
		$this->sendAs = 'both';									//  Best practice, don't change - unless you want to
		$this->delivery = 'smtp';			
	
		// Setup the Sendgrid X-SMTPAPI Array
		$this->xsmtpapi = new SmtpApiHeader(); 
		$this->xsmtpapi->addTo($to);
		if ($unique) {
			$this->xsmtpapi->addFilterSetting('opentrack', 'enable', 1);
			$this->xsmtpapi->setUniqueArgs(array('messageID' => $unique));
		}
		if ($sendNow) return $this->send();
	}

	/**
	 *  Send Function
	 *
	 *	Sets the SendGrid X-SMTPAPI Header and sends the email
	 */
	function send() {
		//$this->additionalParams = $this->getJsonSGHeader();
		$this->headers['SMTPAPI'] = $this->xsmtpapi->as_string();
		return parent::send();
	}

}



/*  This is in this file simply for ease of distribution  
 *  The following code is a (slightly) modified SendGrid Example, and can be
 *  found here: http://wiki.sendgrid.com/doku.php?id=smtpapiheader.php  
 *
 *  The copyright notice above does not apply to the following code.
 **/
class SmtpApiHeader {
  var $data; 
  function addTo($tos) {
    if (!isset($this->data['to']))  {
      $this->data['to'] = array();
    }
    $this->data['to'] = array_merge($this->data['to'], (array)$tos);
  }
  function addSubVal($var, $val) {
    if (!isset($this->data['sub']))  {
      $this->data['sub'] = array();
    }
 
    if (!isset($this->data['sub'][$var])) {
      $this->data['sub'][$var] = array();
    }
    $this->data['sub'][$var] = array_merge($this->data['sub'][$var], (array)$val);
  }
  function setUniqueArgs($val) {
    if (!is_array($val)) return;
    $diff = array_diff_assoc($val, array_values($val));
    if(((empty($diff)) ? false : true)) {
      $this->data['unique_args'] = $val;
    } 
  }
  function setCategory($cat) {
    $this->data['category'] = $cat;
  }
  function addFilterSetting($filter, $setting, $value) {
    if (!isset($this->data['filters']))  {
      $this->data['filters'] = array();
    }
 
    if (!isset($this->data['filters'][$filter]))  {
      $this->data['filters'][$filter] = array();
    }
 
    if (!isset($this->data['filters'][$filter]['settings']))  {
      $this->data['filters'][$filter]['settings'] = array();
    }
    $this->data['filters'][$filter]['settings'][$setting] = $value;
  }
  function asJSON() {
    $json = json_encode($this->data);
    $json = preg_replace('/(["\]}])([,:])(["\[{])/', '$1$2 $3', $json);
    return $json;
  }
  function as_string() {
    $json = $this->asJSON();
    //  Modified from SendGrid Example to work with CakePHP Email component
    $str = wordwrap($json, 76, "\n   ");
    return $str;
  }
 
}


?>