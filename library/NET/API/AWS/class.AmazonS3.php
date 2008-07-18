<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */ 

	
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
			$this->S3SoapClient = new SoapClient(AmazonS3::EC2WSDL, array('trace' => 1, 'exceptions'=> 0, 'user_agent' => AmazonS3::USER_AGENT));
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
        
		/**
		 * Generate signature
		 *
		 * @param string $operation
		 * @param string $time
		 * @return string
		 */
		private function GetSOAPSignature($operation, $time)
		{
            return base64_encode(@hash_hmac(AmazonS3::HASH_ALGO, sprintf(AmazonS3::SIGN_STRING, $operation, $time), $this->AWSAccessKey, 1));
		}
		
		private function GetRESTSignature($data)
		{
			$data_string = implode("\n", $data);
			return base64_encode(@hash_hmac(AmazonS3::HASH_ALGO, $data_string, $this->AWSAccessKey, 1));
		}
		
		/**
		 * Return GMT timestamp for Amazon AWS S3 Requests
		 *
		 * @return unknown
		 */
		private function GetTimestamp($REST_FORMAT = false)
		{
		    date_default_timezone_set("GMT");
		    
		    if (!$REST_FORMAT)
		    	return date("Y-m-d\TH:i:s.B\Z");
		   else
				return date("r");
		}
		
		/**
		 * List all objects on bucket
		 *
		 * @param string $bucket_name
		 * @param string $prefix
		 * @return array
		 */
		public function ListBucket($bucket_name, $prefix = "")
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->ListBucket(
            		                                      array(  
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                      		  "Bucket"		   => $bucket_name,
            		                                      		  "Prefix"		   => $prefix,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("ListBucket", $timestamp)
            		                                           )
            		                                     );
            		                                                 		                                                   
                if (!($res instanceof SoapFault))
                {
                    $retval = $res->ListBucketResponse->Contents;
                    if (!$retval)
                    	return array();
                    else
                    {
                    	if ($retval instanceof stdClass)
                    		$retval = array($retval);
                    		
                    	return $retval;
                    }
                }
                else 
                {
                	throw new Exception($res->faultString ? $res->faultString : $res->getMessage(), E_ERROR);
                }
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
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
            		                                              "Signature"      => $this->GetSOAPSignature("ListAllMyBuckets", $timestamp)
            		                                           )
            		                                     );
            		                                                 		                                                   
                if (!($res instanceof SoapFault))
                {
                    $retval = $res->ListAllMyBucketsResponse->Buckets->Bucket;
                    if ($retval instanceof stdClass)
                    	$retval = array($retval);
                	
                	return $retval;
                }
                else 
                {
                	throw new Exception($res->faultString ? $res->faultString : $res->getMessage(), E_ERROR);
                }
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		public function DownloadObject($object_path, $bucket_name, $out_filename = false)
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp(true);
			
			$data_to_sign = array("GET", "", "", $timestamp, "/{$bucket_name}/{$object_path}");
			$signature = $this->GetRESTSignature($data_to_sign);
			
			$HttpRequest->setUrl("http://{$bucket_name}.s3.amazonaws.com/{$object_path}");
		    $HttpRequest->setMethod(constant("HTTP_METH_GET"));

		    $headers["Date"] = $timestamp;
            $headers["Authorization"] = "AWS {$this->AWSAccessKeyId}:{$signature}";
			                
            $HttpRequest->addHeaders($headers);
			
			try 
            {
                $HttpRequest->send();
                
                $info = $HttpRequest->getResponseInfo();                
                if ($info['response_code'] == 200)
                {
                	if ($out_filename)
                		return (bool)@file_put_contents($out_filename, $HttpRequest->getResponseBody());
                	else
						return $HttpRequest->getResponseBody();
                }
                else
                {
                	$xml = @simplexml_load_string($HttpRequest->getResponseBody());                	
                	return $xml->Message;
                }
            }
            catch (HttpException $e)
            {
                Core::RaiseWarning($e->__toString(), E_ERROR);
		        return false;
            }
		}
		
		/**
		 * Create new object on S3 Bucket
		 *
		 * @param string $object_path
		 * @param string $bucket_name
		 * @param string $filename
		 * @param string $object_content_type
		 * @param string $object_permissions
		 * @return bool
		 */
		public function CreateObject($object_path, $bucket_name, $filename, $object_content_type, $object_permissions = "public-read")
		{
			if (!file_exists($filename))
			{
				Core::RaiseWarning("{$filename} - no such file.");
				return false;
			}
			
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp(true);
			
			$data_to_sign = array("PUT", "", $object_content_type, $timestamp, "x-amz-acl:{$object_permissions}","/{$bucket_name}/{$object_path}");
			$signature = $this->GetRESTSignature($data_to_sign);
			
			$HttpRequest->setUrl("http://{$bucket_name}.s3.amazonaws.com/{$object_path}");
		    $HttpRequest->setMethod(constant("HTTP_METH_PUT"));
		   	 
		    $headers["Content-type"] = $object_content_type;
		    $headers["x-amz-acl"] = $object_permissions;
		    $headers["Date"] = $timestamp;
            $headers["Authorization"] = "AWS {$this->AWSAccessKeyId}:{$signature}";
			                
            $HttpRequest->addHeaders($headers);
            
            $HttpRequest->setPutFile($filename);
            
            try 
            {
                $HttpRequest->send();
                
                $info = $HttpRequest->getResponseInfo();
                
                if ($info['response_code'] == 200)
                	return true;
                else
                {
                	$xml = @simplexml_load_string($HttpRequest->getResponseBody());                	
                	return $xml->Message;
                }
            }
            catch (HttpException $e)
            {
                Core::RaiseWarning($e->__toString(), E_ERROR);
		        return false;
            }
		}
		
		/**
		 * Delete bucket from S3
		 *
		 * @param string $bucket_name
		 * @return boolean
		 */
		public function DeleteBucket($bucket_name)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->DeleteBucket(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("DeleteBucket", $timestamp)
            		                                           )
            		                                     );

				if (!($res instanceof SoapFault))
                    return true;
                else 
                    throw new Exception($res->faultString, E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		/**
		 * Delete object from bucket
		 *
		 * @param string $object_path
		 * @param string $bucket_name
		 * @return bool
		 */
		public function DeleteObject($object_path, $bucket_name)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->DeleteObject(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "Key"	   => $object_path,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("DeleteObject", $timestamp)
            		                                           )
            		                                     );

				if (!($res instanceof SoapFault))
                    return true;
                else 
                    throw new Exception($res->faultString, E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
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
            		                                              "Signature"      => $this->GetSOAPSignature("CreateBucket", $timestamp)
            		                                           )
            		                                     );             
                if (!($res instanceof SoapFault))
                    return true;
                else 
                    throw new Exception($res->getMessage(), E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
    }
?>
