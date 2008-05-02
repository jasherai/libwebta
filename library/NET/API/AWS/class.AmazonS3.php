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
     * @package    NET_API
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://webta.net/copyright.html
     * @license    http://webta.net/license.html
     */ 

	Core::Load("NET/API/AWS/WSSESoapClient");
	
    /**
     * @name AmazonS3
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	
	class AmazonS3 
    {
	    const EC2WSDL = 'http://s3.amazonaws.com/doc/2006-03-01/AmazonS3.wsdl';
	    const USER_AGENT = 'Libwebta AWS Client (http://webta.net)';
	    const HASH_ALGO = 'SHA1';
	    const SIGN_STRING = 'AmazonS3%s%s';
	    
		private $S3SoapClient = NULL;
		private $AWSAccessKeyId = NULL;
	
		/**
		 * Constructor
		 *
		 * @param string $AWSAccessKeyId
		 * @param string $AWSAccessKey
		 */
		public function __construct($AWSAccessKeyId, $AWSAccessKey) 
		{
			$this->S3SoapClient = new SoapClient(AmazonS3::EC2WSDL, array('trace' => 1, 'exceptions'=> 0, 'user_agent' => AmazonEC2::USER_AGENT));
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                Core::RaiseError("hash_hmac() function not found. Please install HASH Pecl extension.");
		}
        
		/**
		 * Generate signature
		 *
		 * @param string $operation
		 * @param string $time
		 * @return string
		 */
		private function GetSignature($operation, $time)
		{
            return base64_encode(@hash_hmac(AmazonS3::HASH_ALGO, sprintf(AmazonS3::SIGN_STRING, $operation, $time), $this->AWSAccessKey, 1));
		}
		
		/**
		 * Return GMT timestamp for Amazon AWS S3 Requests
		 *
		 * @return unknown
		 */
		private function GetTimestamp()
		{
		    date_default_timezone_set("GMT");
		    return date("Y-m-d\TH:i:s.B\Z");
		}
		
		/**
		 * The ListBuckets operation returns a list of all buckets owned by the sender of the request.
		 *
		 * @return array
		 */
		public function ListBuckets()
		{
		    $timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->ListAllMyBuckets(
            		                                      array(  
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSignature("ListAllMyBuckets", $timestamp)
            		                                           )
            		                                     );
            		                                                 		                                                   
                if (!($res instanceof SoapFault))
                    return $res->ListAllMyBucketsResponse->Buckets;
                else 
                    Core::RaiseError($res->faultString, E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        Core::RaiseError($e->getMessage(), E_ERROR);
		    }
		}
		
		/**
		 * The CreateBucket operation creates a bucket. Not every string is an acceptable bucket name.
		 *
		 * @param string $bucket_name
		 * @return string 
		 */
		public function CreateBucket($bucket_name)
		{
		    $timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->CreateBucket(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSignature("CreateBucket", $timestamp)
            		                                           )
            		                                     );
            		                                     
                if (!($res instanceof SoapFault))
                    return true;
                else 
                    Core::RaiseError($res->faultString, E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        Core::RaiseError($e->getMessage(), E_ERROR);
		    }
		}
    }
?>
