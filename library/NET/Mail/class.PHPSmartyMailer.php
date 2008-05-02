<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
     * This program is protected by international copyright laws. Any           
	 * use of this program is subject to the terms of the license               
	 * agreement included as part of this distribution archive.                 
	 * Any other uses are strictly prohibited without the written permission    
	 * of "Webta" and all other rights are reserved.                            
	 * This notice may not be removed from this source code file.               
	 * This source file is subject to version 1.1 of the license,               
	 * that is bundled with this package in the file LICENSE.                   
	 * If the backage does not contain LICENSE file, this source file is   
	 * subject to general license, available at http://webta.net/license.html
     *
     * @category   LibWebta
     * @package    NET
     * @subpackage Mail
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */
	
	Core::Load("NET/Mail/PHPMailer");
	
	/**
	 * @name PHPSmartyMailer
	 * @category LibWebta
	 * @package NET
	 * @subpackage Mail
	 * @todo Enable in HTTP Client socket connections if curl functions are disabled
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
	class PHPSmartyMailer extends PHPMailer
	{
		
		 /**
		* Sets the Body of the message.  This can be either an HTML or text body.
		* If HTML then run IsHTML(true).
		* @var string
		*/
		public $Body;
		
		/**
		* Instance of Smarty object
		* @var object
		*/
		private $Smarty;
		
		/**
		* Constructor
		* @access public
		* @param string $smtp_dsn SMTP DSN.
		* @return array Mounts
		*/
		public function __construct($smtp_dsn = false)
		{
			$this->Smarty = Core::GetSmartyInstance();
			$this->Smarty->caching = false;
			
			if (!$smtp_dsn || $smtp_dsn == "")
				$this->Mailer = "sendmail";
			else
			{
				$this->Mailer = "smtp";
				
				//
				// parseDSN
				//
				preg_match_all("/(.+):(.*)@([^:]+):?([0-9]+)?/", $smtp_dsn, $matches);
				
				$this->Host = $matches[3][0];
				$this->Port = $matches[4][0] ? $matches[4][0] : 25;
				$this->Username = $matches[1][0];
				$this->Password = $matches[2][0];
				
				if ($this->Username && $this->Password)
					$this->SMTPAuth = true;
			}
		}
		
		/**
		* Set Smarty variables
		* @access public
		* @param array $vars
		* @return void
		*/
		public function SetTemplateVars($vars)
		{
			$this->Smarty->assign($vars);
		}
		
		public function LoadTemplate($templatename)
		{
			$templ = @file("{$this->Smarty->template_dir}/{$templatename}");
			if (count($templ) > 0)
			{
				$this->Subject = array_shift($templ);
				$this->Body = $this->Smarty->fetch("string:".implode("", $templ));
			}
			else
				RaiseError(_("Cannot read template {$templatename}"));
		}
		
		/**
		* Setter
		* @access public
		* @return array Mounts
		*/
		public function __set($name, $value)
		{
			if ($name == "SmartyBody")
			{
				if (is_array($value))
				{
					$this->Smarty->assign($value[1]);
					$this->Body = $this->Smarty->fetch($value[0]);
				}
				else
					$this->Body = $this->Smarty->fetch($value);
			}
		}
	}
	
?>
