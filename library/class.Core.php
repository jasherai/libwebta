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
     * @package    Core
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 
    
    /**
     * 
     */
    define("LIBWEBTA_BASE", dirname(__FILE__));	
	define("LIB_BASE", dirname(__FILE__)."/../../Lib");
	
	define("TEMPLATES_PATH", LIBWEBTA_BASE."/../../../templates");
	define("SMARTYBIN_PATH", LIBWEBTA_BASE."/../../../cache/smarty_bin");
	define("SMARTYCACHE_PATH", LIBWEBTA_BASE."/../../../cache/smarty");
	
	/**
     * @name Core
     * @category   LibWebta
     * @package    Core
     * @abstract 
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */	
	abstract class Core
	{
		/**
		 * ADODB Instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $DB;
		
		/**
		 * Shell instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Shell;
		
		/**
		 * Smarty instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Smarty;
		
		/**
		 * Validator instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Validator;
		
		/**
		 * PHPMAiler instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $PHPMailer;
		
		/**
		 * PHPSmartyMailer instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
  		private static $PHPSmartyMailer;
		
		/**
		 * Debug level
		 */
		const DEBUG_LEVEL = 0; // Lowest debug level by default
		
		
		/**
		* Debug Level.
		* @var $DebugLevel
		* @access protected
		* @see RaiseWarning RaiseError
		*/
		protected static $DebugLevel;
		
		/**
		 * Exception class name
		 *
		 * @var string
		 */
		public static $ExceptionClassName = "CustomException"; 
		
		/**
		 * Reflection Exception Class
		 *
		 * @var ReflectionClass
		 */
		public static $ReflectionException;
		
		/**
		* Constructor
		* @access public
		* @return void
		* @ignore 
		*/
		function __construct()
		{
			self::$DebugLevel = defined("CF_DEBUG_LEVEL") ? CF_DEBUG_LEVEL : self::DEBUG_LEVEL;
			self::$ReflectionException = new ReflectionClass(self::$ExceptionClassName);
		}
		
		
		/**
		* Load class or namespace.
		* Priority:
		* 1 - Fully-clarified file name within LIBWEBTA_BASE
		* 2 - Simplified path to a class file
		* 3 - All classes in directory
		* @param string $path Path to load
		* @param string $loadbase Load base
		* @return bool True is loaded succesfull or false if not found
		* @throws Exception
		* @static 
		*/
		public static function Load($path, $loadbase = false)
		{
			$loadbase = $loadbase ? $loadbase : LIBWEBTA_BASE;
			// XSS prevention
			if (strstr($path, ".."))
			    Core::RaiseError(_("Cannot use path traversals while loading namespace from "). LIBWEBTA_BASE, E_ERROR);
							
			$dirname = dirname($path);
			
			// Full path to file specified?
			$fullpath = "{$loadbase}/{$path}";
					
			if (is_file($fullpath))
			{
				require_once($fullpath);
			}
			else
			{
				
				// Full path to class. file specified?
				$basename = basename($path);
				$classpath = "{$loadbase}/{$dirname}/class.{$basename}.php";
	
				if (is_file($classpath))
				{
					require_once($classpath);
				}
				
				// Directory specified. Loading all classes inside
				elseif (is_dir($fullpath))
				{
					$files = (array)scandir($fullpath);
					foreach ($files as $file)
					{
						$basename = basename($file);
						if (substr($basename,0, 6) == "class." && substr($basename,-4) == ".php")
							require_once("{$fullpath}/{$file}");
					}
				}
				else
					Core::RaiseError(sprintf(_("Cannot load %s"), $path), E_ERROR);
				
			}
		}
		
		/**
		 * Universal singleton
		 *
		 * @param string $objectname
		 * @return object
		 * @static 
		 */
		public static function GetInstance($objectname)
		{
			if (!$GLOBALS[$objectname])
			{
				// Check class exists or no
				if (!class_exists($objectname))
					Core::RaiseError(sprintf(_("Cannot find %s declaration. Use Core::Load() to load it."), $objectname), E_ERROR);
					
				// Get Constructor Reflection	
				if (is_callable(array($objectname, "__construct")))
					$reflect = new ReflectionMethod($objectname, "__construct");
				elseif (is_callable(array($objectname, $objectname)))
					$reflect = new ReflectionMethod($objectname, $objectname);
				
				// Delete $objectname from arguments
				$num_args = func_num_args()-1;
				$args = func_get_args();
				array_shift($args);
					
				if ($reflect)
				{
					$required_params = $reflect->getNumberOfRequiredParameters();
					
					if ($required_params > $num_args)
						Core::RaiseError(sprintf(_("Missing some required arguments for %s constructor. Passed: %s, expected: %s."),$objectname, $num_args, $required_params), E_ERROR);							
				}
				
				$reflect = new ReflectionClass($objectname);
				
				if ($reflect && $reflect->isInstantiable())
				{
					if (count($args) > 0)
						$GLOBALS[$objectname] = $reflect->newInstanceArgs($args);
					else 
						$GLOBALS[$objectname] = $reflect->newInstance(true);						
				}
				else 
					Core::RaiseError(_("Object not instantiable."), E_ERROR);							
			}
			
			return $GLOBALS[$objectname];
		}
	
		
		/**
		 * Get PHPSmartyMailer instance
		 * @param string $dsn Email DSN (username:password@host:port)
		 * @return object
		 * @static 
		 */
		public static function GetPHPSmartyMailerInstance($dsn = "")
		{
		    if (!class_exists("PHPSmartyMailer"))
		      Core::Load("NET/Mail/PHPSmartyMailer");
		    
		    if (!class_exists("PHPMailer"))
		      Core::Load("NET/Mail/PHPMailer");
		      
			if (!self::$PHPSmartyMailer)
			{
				if (!$GLOBALS["Mailer"])
				{
				    if ($dsn == "")
					   $dsn = (defined("CF_EMAIL_DSN")) ? CF_EMAIL_DSN : "";
					
					self::$PHPSmartyMailer = new PHPSmartyMailer($dsn);
				}
				else
					self::$PHPSmartyMailer = $GLOBALS["Mailer"];
			}

			return self::$PHPSmartyMailer;
		}

		
		/**
		 * Get PHPMailer instance
		 * @return object
		 * @static 
		 */
		public static function GetPHPMailerInstance()
		{
		    if (!class_exists("PHPMailer"))
		      Core::Load("NET/Mail/PHPMailer");
		    
			if (!self::$PHPMailer)
			{
				if (!$GLOBALS["mail"])
					self::$PHPMailer = new PHPMailer();
				else
					self::$PHPMailer = $GLOBALS["mail"];
			}

			return self::$PHPMailer;
		}
		
		
		/**
		 * Get Validator instance
		 * @return object
		 * @static 
		 */
		public static function GetValidatorInstance()
		{
		    if (!class_exists("Validator"))
		      Core::Load("Data/Validation");
		    
			if (!self::$Validator)
				self::$Validator = new Validator();
			
		    return self::$Validator;
		}
		
		
		/**
		 * Get Smarty instance
		 * @return object
		 * @static 
		 */
		public static function GetSmartyInstance()
		{
		    if (!class_exists("Smarty"))
		    {
                if (self::$ExceptionClassName == 'CustomException')
                    throw new CoreException(_("Cannot find Smarty declaration. Use Core::Load() to load it."), E_ERROR);
                else
                    Core::RaiseError(_("Cannot find Smarty declaration. Use Core::Load() to load it."), E_ERROR);
		    }
		    
			if (!self::$Smarty)
			{
			     if ($GLOBALS["Smarty"])
			         self::$Smarty = $GLOBALS["Smarty"];
			     elseif ($GLOBALS["smarty"])
			         self::$Smarty = $GLOBALS["smarty"];
			     else 
			     {			         
			         self::$Smarty = new Smarty();
			         self::$Smarty->template_dir = defined("CF_TEMPLATES_PATH") ? CF_TEMPLATES_PATH : TEMPLATES_PATH;
			         self::$Smarty->compile_dir = defined("CF_SMARTYBIN_PATH") ? CF_SMARTYBIN_PATH : SMARTYBIN_PATH;
			         self::$Smarty->cache_dir = defined("CF_SMARTYCACHE_PATH") ? CF_SMARTYCACHE_PATH : SMARTYCACHE_PATH;
			     }
			    
			}
			
			return self::$Smarty;
		}
		
		
		/**
		 * Get ADODB instance
		 * @param array $connection_info
		 * @param bool $use_nconnect
		 * @param string $driver
		 * @return object
		 * @static 
		 */
		public static function GetDBInstance($connection_info = NULL, $use_nconnect = false, $driver = 'mysqli')
		{		    
		    if (function_exists("NewADOConnection"))
		    {
		        if (!self::$DB || self::$DB == null || $use_nconnect)
                {                   
                    if ($GLOBALS["db"] && !$use_nconnect)
                        self::$DB = $GLOBALS["db"];
                    else 
                    {
                        if ((!is_array($connection_info) || defined("CF_DB_DSN")) && !$use_nconnect)
                        {
                            $dsn = ($connection_info) ? $connection_info : CF_DB_DSN;
                            self::$DB = &NewADOConnection($dsn);
                        }
                        else 
                        {                            
                            // Connect to database;
                            $host = ($connection_info["host"]) ? $connection_info["host"] : (defined("CF_DB_HOST") ? CF_DB_HOST : "");
                            $user = ($connection_info["user"]) ? $connection_info["user"] : (defined("CF_DB_USER") ? CF_DB_USER : "");
                            $pass = ($connection_info["pass"]) ? $connection_info["pass"] : (defined("CF_DB_PASS") ? CF_DB_PASS : "");
                            $name = ($connection_info["name"]) ? $connection_info["name"] : (defined("CF_DB_NAME") ? CF_DB_NAME : "");
                            
                            if ($host == "")
                                Core::RaiseError("Database host not specified", E_ERROR);
                            
                            try
                            {
                                if (defined("CF_DB_DRIVER"))
                                    $driver = CF_DB_DRIVER;
                                
                        	    self::$DB = &NewADOConnection($driver);
                        	    
                        	    if ($use_nconnect)
                                    self::$DB->NConnect($host, $user, $pass, $name);
                                else 
                                    self::$DB->Connect($host, $user, $pass, $name);
                                    

                            }
                            catch (ADODB_Exception $e)
                            {
                               Core::RaiseError("Cannot connect to database", E_ERROR);
                            }
                                                      	
                        	if (!self::$DB) 
                        	    Core::RaiseError("Cannot connect to database", E_ERROR);
                            
                        	self::$DB->debug = defined("CF_DEBUG_DB") ? CF_DEBUG_DB : false;
                        	self::$DB->cacheSecs = defined("CF_DB_CACHE") ? CF_DB_CACHE : 0;
                        	self::$DB->SetFetchMode(ADODB_FETCH_ASSOC);   
                        }
                    }
                }
                
    			return self::$DB;
		    }
		    else 
                Core::RaiseError(_("Cannot find ADODB declaration. Use Core::Load() to load it."));	
		}
		
		
		/**
		 * Shell Singleton
		 * @deprecated Use System/Independent/ShellFactory instead!
		 */
		public static function GetShellInstance()
		{
			self::RaiseWarning("GetShellInstance() is deprecated. Use System/Independent/ShellFactory instead!");
			if (!self::$Shell)
				self::$Shell = new Shell();

			return self::$Shell;
		}
		
		public static function SetExceptionClassName($name)
		{
		    if (class_exists($name))
		    {
                self::$ExceptionClassName = $name;
                self::$ReflectionException = new ReflectionClass($name);
		    }
		    else 
		      Core::RaiseWarning("Core::SetExceptionClassName failed. Class '{$name}' not found");
		}
		
		/**
		* Raise warning
		* @access public
		* @param string $str Error message
		* @return void
		* @static 
		*/
		public static function RaiseWarning($str, $print = true)
		{
			$GLOBALS["warnings"][] = $str;
			
			try
			{
    			if ( class_exists("Log") && Log::HasLogger("Default"))
     			{
     			    Log::$DoRaiseExceptions = false;
     			    Log::Log("[WARNING] {$str}", E_USER_WARNING, array("useragent" => $_SERVER['HTTP_USER_AGENT'], "ipaddr" => $_SERVER['REMOTE_ADDR']), "Default");
     			}
			}
			catch (Exception $e)
			{
			    // Need to RaiseError?
			}
		}
		
		/**
		 * Clear warnings array
		 *
		 * @return bool
		 * @static 
		 */
		public static function ClearWarnings()
		{
			$GLOBALS["warnings"] = array();
			return true;
		}
		
		/**
		 * Return true if We have at least one warning
		 *
		 * @return bool
		 * @static 
		 */
		public static function HasWarnings()
		{
			return (count($GLOBALS["warnings"]) > 0) ? true : false;
		}
		
		/**
		* Raise fatal error (We're gonna die!)
		* @access public
		* @param string $str Error message
		* @return void
		* @static 
		*/
		public static function RaiseError($str, $code = E_USER_ERROR)
		{		    
		    throw self::$ReflectionException->newInstanceArgs(array($str, $code));
		}
		
		
		/**
		* Return last warning
		* @access public
		* @return string
		* @static 
		*/
		public static function GetLastWarning()
		{
			return $GLOBALS["warnings"][count($GLOBALS["warnings"])-1];
		}
		
		
		/**
		 * Return current timestampt with microseconds
		 *
		 */
		public static function GetTimeStamp()
		{
		    list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
		}
	}

?>