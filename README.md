# CakePHP-SendGrid Component

## Usage (from controller):

<code>$this->SendgridEmail->sendEmail($params);</code>

<code>$params = array(
	'to' 			=> <recipient address>,  		REQUIRED - may be array
	'subject 		=> <subject>,					REQUIRED
	'from'			=> <sender address>,  			REQUIRED
	'reply-to'		=> <reply to address>,
	'unique'		=> <unique id for x-SMTPAPI>,
	'category'		=> <category for sendgrid reports>,
	'layout'		=> <email layout filename>,
	'template'		=> <template filename>,
	'layout-type'	=> <[text|html|both]>, 			DEFAULT = both
	'delivery-type'	=> <smtp>,						DEFAULT = smtp
	'merge-values' 	=> array(
		<keys> => <values>,
		<keys> => <values>,..    For doing bulk messages with single call
	)	
);</code>


## Installation:
Copy entire repository into <code>app/controllers/components/</code> directory

## Configuration:
Add <code>$sendgrid</code> configuration to <code>app/config/database.php</code> (as immediately below)

<code>var $sendgrid = array (
	'port'			=> '25', 
	'timeout'		=> '30',
	'host' 			=> 'smtp.sendgrid.net',
	'username'		=> '<your username>',
	'password'		=> '<your password>',
	'client' 		=> 'smtp_helo_hostname',
	'support_email'	=> 'your_administrative_address@domain.com'
);</code>

The support email address is used as the to-address when sending bulk emails, 
and does not receive anything, but is needed so that CakePHP does not think that
and email without a normal SMTP to: address is invalid.


## More information:

Code written and maintained by Dave Loftis.  I am happy to help when and where I can
but can't promise that I will be able to spend huge amounts of time helping you, 
but I will offer all the support I can.

## Additional features coming soon, including:
* Basic support for SendGrid's EventAPI

## Legacy Users:
Poorly planned <code>init()</code> function remains in place.  New code should use <code>sendEmail();</code>
Also, you should add the <code>support_email</code> element to the <code>$sendgrid</code> database config.