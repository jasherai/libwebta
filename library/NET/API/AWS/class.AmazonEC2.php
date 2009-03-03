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

	Core::Load("NET/API/AWS/WSSESoapClient");
	
	class DescribeInstancesType
	{
		public $instancesSet;
		public $groupSet;
	};
	
	class DescribeImagesType
	{
		public $executableBySet = null;
		public $imagesSet = null;
		public $ownersSet = null;
	};
	
	class DescribeImagesOwnersType
	{
		public $owner;
	};
	
	class DescribeImageAttributeType
	{
	    public $imageId;
	    public $launchPermission;
	};
	
	class ModifyImageAttributeType
	{
	    public $imageId;
	    public $launchPermission;
	};
	
	class DescribeAddressesType
	{
		public $publicIpsSet;
		
		public function AddAddress($ip_address)
		{
			$item = new stdClass();
			$item->item = new stdClass();
			$item->item->publicIp = $ip_address;
			
			$this->publicIpsSet = $item;
		}
	}
	
	class DescribeKeyPairsType
	{
		public $keySet;
		
		public function AddKey($key_name)
		{
			$item = new stdClass();
			$item->item = new stdClass();
			$item->item->keyName = $key_name;
			
			$this->keySet[] = $item;
		}
	}
	
	class AttachVolumeType
	{
		public $volumeId;
		public $instanceId;
		public $device;
		
		public function __construct($volumeId = null, $instanceId = null, $device = null)
		{
			$this->device = $device;
			$this->instanceId = $instanceId;
			$this->volumeId = $volumeId;
		}
	}
	
	class DetachVolumeType
	{
		public $volumeId;
		public $instanceId;
		public $device;
		public $force;
		
		public function __construct($volumeId = null, $instanceId = null, $device = null, $force = false)
		{
			$this->device = $device;
			$this->instanceId = $instanceId;
			$this->volumeId = $volumeId;
			$this->force = $force;
		}
	}
	
	class CreateVolumeType
	{
		public $size;
		public $snapshotId;
		public $availabilityZone;
		
		public function __construct($size = null, $snapshotId = null, $availabilityZone = null)
		{
			$this->size = $size;
			$this->snapshotId = $snapshotId;
			$this->availabilityZone = $availabilityZone;
		}
	}
	
	class RunInstancesType
	{
	    public $imageId;
	    public $minCount;
	    public $maxCount;
	    public $keyName;
	    public $groupSet;
	    public $additionalInfo = "";
	    public $userData;
	    public $addressingType = "public";
	    public $instanceType = "";
	    public $placement;
	    
	    public function SetAvailabilityZone($zoneName)
	    {
	    	$this->placement = new stdClass();
	    	$this->placement->availabilityZone = $zoneName;
	    }
	    
	    public function AddSecurityGroup($groupName)
	    {
	        if (!$this->groupSet)
	        {
	            $this->groupSet = new stdClass();
	            $this->groupSet->item = array();
	        }
	        
	        array_push($this->groupSet->item, array("groupId" => $groupName));
	    }
	    
	    public function SetUserData($data)
	    {
	        $this->userData = new stdClass();
	        $this->userData->version = "1.0";
	        $this->userData->encoding = "base64";
	        $this->userData->data = base64_encode($data);
	    }
	}
	
	/**
	 * IpPermissionSetType
	 *
	 * @todo $groups not supported yet
	 */
	class IpPermissionSetType
	{
	    public $item = array();
	    
	    public function AddItem($ipProtocol, $fromPort, $toPort, $groups = array(), $ipRanges)
	    {
	        $stdClass = new stdClass();
	        $stdClass->ipProtocol = $ipProtocol;
	        $stdClass->fromPort = $fromPort;
	        $stdClass->toPort = $toPort;
	        $stdClass->groups = new stdClass();
	        
	        if (count($groups) > 0)
	        	$stdClass->groups->item = $groups;
	        	
	        $stdClass->ipRanges = new stdClass();
	        	        
	        if ($ipRanges && count($ipRanges) > 0)
	        {
	        	$stdClass->ipRanges->item = array();
	        	
	        	foreach ($ipRanges as $ipRange)
	           		array_push($stdClass->ipRanges->item, array("cidrIp" => $ipRange));
	        }
	           
	        array_push($this->item, $stdClass);
	    }
	}
	
    /**
     * @name AmazonEC2
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	
	class AmazonEC2 
    {
	    const EC2WSDL = 'http://s3.amazonaws.com/ec2-downloads/2008-12-01.ec2.wsdl';
	    const KEY_PATH = '/etc/awskey.pem';
	    const CERT_PATH = '/etc/awscert.pem';
	    const USER_AGENT = 'Libwebta AWS Client (http://webta.net)';
	    const CONNECTION_TIMEOUT = 15;
	    
		private $EC2SoapClient = NULL;
		
		private static $Instances;
		
		/**
		 * @return AmazonEC2
		 */
		public static function GetInstance($API_URL = 'https://ec2.amazonaws.com/')
		{
			if (!self::$Instances[$API_URL])
				self::$Instances[$API_URL] = new AmazonEC2($API_URL);
			 
			 return self::$Instances[$API_URL];
		}
		
		public function __construct($api_url = 'https://ec2.amazonaws.com/') 
		{
	      	$this->EC2SoapClient  = new WSSESoapClient(AmazonEC2::EC2WSDL, array(
	      		'connection_timeout' => self::CONNECTION_TIMEOUT, 
	      		'trace' => true, 
	      		'exceptions'=> false, 
	      		'user_agent' => AmazonEC2::USER_AGENT)
	      	);

	      	/* Force location path - MUST INCLUDE trailing slash
			BUG in ext/soap that does not automatically add / if URL does not contain path. this causes POST header to be invalid 
			Seems like will be fixed in PHP 5.2 Release*/
	      	
	      	if (substr($api_url, -1) != '/')
	      		$api_url = "{$api_url}/";
	      	
			$this->EC2SoapClient->location = $api_url;
		}		
		
		public function SetAuthKeys($key = null, $cert = null, $isfile = false)
		{
			// Defaultize
			if ($key == null || $cert == null)
				$isfile = true;
				
			$key = $key == null ? self::KEY_PATH : $key;
			$cert = $cert == null ? self::CERT_PATH : $cert;
			
			$this->EC2SoapClient->SetAuthKeys($key, $cert, $isfile);
		}
		
		/*
		 * 
		 * 
		 * Elastic Block Storage (EBS)
		 * 
		 * 
		 */
		
		/**
		 * The CreateVolume operation creates a new Amazon EBS volume to which any 
		 * Amazon EC2 instance can attach within the same availability zone. 
		 *
		 * @param CreateVolumeType $CreateVolumeType
		 * @return stdClass
		 */
		public function CreateVolume(CreateVolumeType $CreateVolumeType)
		{
			try 
			{
				$response = $this->EC2SoapClient->CreateVolume($CreateVolumeType);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteVolume operation deletes an Amazon EBS volume.
		 *
		 * @param string $volumeId
		 * @return stdClass
		 */
    	public function DeleteVolume($volumeId)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->volumeId = $volumeId;
				
				$response = $this->EC2SoapClient->DeleteVolume($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeVolumes operation lists the specified Amazon EBS volumes that you own. 
		 * If you do not specify one or more volume IDs, Amazon EBS lists all volumes that you own.
		 *
		 * @param array $volumeId
		 * @return stdClass
		 */
    	public function DescribeVolumes(array $volumeId = null)
		{
			try 
			{
				$stdClass = new stdClass();
				if ($volumeId)
					$stdClass->volumeSet->item->volumeId = $volumeId;
				else
					$stdClass->volumeSet = null;
				
				$response = $this->EC2SoapClient->DescribeVolumes($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		/**
		 * The AttachVolume operation attaches an Amazon EBS volume to an instance.
		 *
		 * @param AttachVolumeType $AttachVolumeType
		 * @return stdClass
		 */
    	public function AttachVolume(AttachVolumeType $AttachVolumeType)
		{
			try 
			{
				$response = $this->EC2SoapClient->AttachVolume($AttachVolumeType);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DetachVolume operation detaches an Amazon EBS volume from an instance.
		 *
		 * @param DetachVolumeType $DetachVolumeType
		 * @return stdClass
		 */
    	public function DetachVolume(DetachVolumeType $DetachVolumeType)
		{
			try 
			{
				$response = $this->EC2SoapClient->DetachVolume($DetachVolumeType);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The CreateSnapshot operation creates a snapshot of an Amazon EBS volume and stores it 
		 * in Amazon S3. You can use snapshots for backups, to make identical copies of instance 
		 * devices, and to save data before shutting down an instance.
		 *
		 * @param string $volumeId
		 * @return stdClass
		 */
    	public function CreateSnapshot($volumeId)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->volumeId = $volumeId;
				
				$response = $this->EC2SoapClient->CreateSnapshot($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteSnapshot operation deletes a snapshot of an Amazon EBS volume that is stored in Amazon S3.
		 *
		 * @param string $snapshotId
		 * @return stdClass
		 */
    	public function DeleteSnapshot($snapshotId)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->snapshotId = $snapshotId;
				
				$response = $this->EC2SoapClient->DeleteSnapshot($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeSnapshots operation describes the status of Amazon EBS snapshots. 
		 *
		 * @param string $snapshotId
		 * @return stdClass
		 */
    	public function DescribeSnapshots(array $snapshotId = null)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->snapshotSet = null;
				
				if ($snapshotId)
					$stdClass->snapshotSet->item->snapshotId = $snapshotId; 
				
				$response = $this->EC2SoapClient->DescribeSnapshots($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/*
		 * 
		 * 
		 * Elastic IP Addresses
		 * 
		 * 
		 */
		
		/**
		 * The AllocateAddress operation acquires an elastic IP address for use with your account.
		 *
		 * @return stdClass
		 */
		public function AllocateAddress()
		{
			try 
			{
				$response = $this->EC2SoapClient->AllocateAddress();
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeAddresses operation lists elastic IP addresses assigned to your account.
		 *
		 * @param DescribeAddressesType $DescribeAddressesType
		 * @return stdClass
		 */
    	public function DescribeAddresses(DescribeAddressesType $DescribeAddressesType = null)
		{
			try 
			{
				$response = $this->EC2SoapClient->DescribeAddresses($DescribeAddressesType);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The ReleaseAddress operation releases an elastic IP address associated with your account.
		 * 
		 * If you run this operation on an elastic IP address that is already released, the address 
		 * might be assigned to another account which will cause Amazon EC2 to return an error.
		 *
		 * @param string $ip_address
		 * @return stdClass
		 */
    	public function ReleaseAddress($ip_address)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->publicIp = $ip_address;
				
				$response = $this->EC2SoapClient->ReleaseAddress($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The AssociateAddress operation associates an elastic IP address with an instance. 
		 * If the IP address is currently assigned to another instance, the IP address is assigned to 
		 * the new instance. This is an idempotent operation. If you enter it more than once, 
		 * Amazon EC2 does not return an error.
		 *
		 * @param string $instance_id
		 * @param string $public_ip
		 * @return stdClass
		 */
   	 	public function AssociateAddress($instance_id, $public_ip)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->publicIp = $public_ip;
				$stdClass->instanceId = $instance_id;
				
				$response = $this->EC2SoapClient->AssociateAddress($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DisassociateAddress operation disassociates the specified elastic IP address from 
		 * the instance to which it is assigned. This is an idempotent operation. 
		 * If you enter it more than once, Amazon EC2 does not return an error.
		 *
		 * @param string $public_ip
		 * @return stdClass
		 */
    	public function DisassociateAddress($public_ip)
		{
			try 
			{
				$stdClass = new stdClass();
				$stdClass->publicIp = $ip_address;
				
				$response = $this->EC2SoapClient->DisassociateAddress($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		
		
		/**
		 * The GetConsoleOutput operation retrieves console output for the specified instance. 
		 *
		 * @param string $instance_id
		 * @return stdClass
		 */
		public function GetConsoleOutput($instance_id)
		{
			try 
			{
				$stdClass = new stdClass();
			    $stdClass->instanceId = $instance_id;

				$response = $this->EC2SoapClient->GetConsoleOutput($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;	
		}
		
		/**
		 * The DescribeAvailabilityZones operation displays availability zones that are currently available to the account and their states. 
		 *
		 * @return stdClass
		 */
		public function DescribeAvailabilityZones()
		{
			try 
			{
				$response = $this->EC2SoapClient->DescribeAvailabilityZones();
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The RevokeSecurityGroupIngress operation revokes permissions from a security group. 
		 * The permissions used to revoke must be specified using the same values used to grant 
		 * the permissions. 
		 * Permissions are specified by IP protocol (TCP, UDP, or ICMP), the source of the request 
		 * (by IP range or an Amazon EC2 user-group pair), the source and destination port ranges 
		 * (for TCP and UDP), and the ICMP codes and types (for ICMP). 
		 * Permission changes are quickly propagated to instances within the security group. 
		 * However, depending on the number of instances in the group, a small delay is might occur. 
		 *
		 * @param styring $userId
		 * @param string $groupName
		 * @param IpPermissionSetType $ipPermissions
		 * @return bool
		 */
		public function RevokeSecurityGroupIngress($userId, $groupName, IpPermissionSetType $ipPermissions)
		{
			try 
			{
				$stdClass = new stdClass();
			    $stdClass->userId = $userId;
				$stdClass->groupName = $groupName;
				$stdClass->ipPermissions = $ipPermissions;

				$response = $this->EC2SoapClient->RevokeSecurityGroupIngress($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The AuthorizeSecurityGroupIngress operation adds permissions to a security group.
         * Permissions are specified by the IP protocol (TCP, UDP or ICMP), the source of the request (by IP
         * range or an Amazon EC2 user-group pair), the source and destination port ranges (for TCP and UDP),
         * and the ICMP codes and types (for ICMP).
         * Permission changes are propagated to instances within the security group as quickly as possible.
         * However, depending on the number of instances, a small delay might occur.
		 *
		 * @param styring $userId
		 * @param string $groupName
		 * @param IpPermissionSetType $ipPermissions
		 * @return bool
		 */
		public function AuthorizeSecurityGroupIngress($userId, $groupName, IpPermissionSetType $ipPermissions)
		{
		    try 
			{
				$stdClass = new stdClass();
			    $stdClass->userId = $userId;
				$stdClass->groupName = $groupName;
				$stdClass->ipPermissions = $ipPermissions;

				$response = $this->EC2SoapClient->AuthorizeSecurityGroupIngress($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteSecurityGroup operation deletes a security group.
		 *
		 * If you attempt to delete a security group that contains instances, a fault is returned.
		 * 
		 * @param string $groupName
		 * @return bool
		 */
		public function DeleteSecurityGroup($groupName)
		{
		    try 
			{
				$response = $this->EC2SoapClient->DeleteSecurityGroup(array("groupName" => $groupName));
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The CreateSecurityGroup operation creates a new security group.
         * Every instance is launched in a security group. If none is specified as part of the launch request then
         * instances are launched in the default security group. Instances within the same security group have
         * unrestricted network access to one another. Instances will reject network access attempts from other
         * instances in a different security group. As the owner of instances you may grant or revoke specific
         * permissions using the AuthorizeSecurityGroupIngress and RevokeSecurityGroupIngress operations.
		 *
		 * @param string $groupName
		 * @param string $groupDescription
		 * @return boolean
		 */
		public function CreateSecurityGroup($groupName, $groupDescription)
		{
		    try 
			{
				$stdClass = new stdClass();
			    $stdClass->groupName = $groupName;
				$stdClass->groupDescription = $groupDescription;

				$response = $this->EC2SoapClient->CreateSecurityGroup($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DeleteSecurityGroup operation deletes a security group.
		 * 
         * If an attempt is made to delete a security group and any instances exist that are members of that group a
         * fault is returned.
		 *
		 * @param securityGroupSet $securityGroupSet
		 * @return Object
		 */
		public function DescribeSecurityGroups($groupName = false)
		{
		    try 
			{
				if ($groupName)
				{
					$securityGroupSet = new stdClass();
					$securityGroupSet->securityGroupSet = new stdClass();
					$securityGroupSet->securityGroupSet->item = new stdClass();
					$securityGroupSet->securityGroupSet->item->groupName = $groupName;
				}
				else
					$securityGroupSet = null;
				
				$response = $this->EC2SoapClient->DescribeSecurityGroups($securityGroupSet);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The ModifyImageAttribute operation modifies an attribute of an AMI.
		 *
		 * @param string $imageId AMI id
		 * @param string $operation 'add' OR 'delete'
		 * @param array $item (group => groupname) OR (userId => userId)
		 * @return boolean
		 */
		public function ModifyImageAttribute($imageId, $operation, $item)
		{
		    try 
			{
				$ModifyImageAttributeType = new ModifyImageAttributeType();
				$ModifyImageAttributeType->imageId = $imageId;
				$ModifyImageAttributeType->launchPermission = array( $operation => array('item' => $item ));
			    				
			    $response = $this->EC2SoapClient->ModifyImageAttribute($ModifyImageAttributeType);
			    
			    if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeImageAttribute operation returns information about an attribute of an AMI. Only one attribute can be specified per call. 
		 *
		 * @param string $imageId
		 * @return stdClass
		 */
		public function DescribeImageAttribute($imageId)
		{
		    try 
			{
				$DescribeImageAttributeType = new DescribeImageAttributeType();
				$DescribeImageAttributeType->imageId = $imageId;
			    
			    $response = $this->EC2SoapClient->DescribeImageAttribute($DescribeImageAttributeType);
			    
			    if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeImages operation returns information about AMIs, AKIs, and ARIs available to the user. 
		 * Information returned includes image type, product codes, architecture, and kernel and RAM disk IDs. 
		 * Images available to the user include public images available for any user to launch, private images 
		 * owned by the user making the request, and private images owned by other users for which the user has 
		 * explicit launch permissions. 
		 *
		 * @param DescribeImagesType $DescribeImagesType
		 * @return stdClass
		 */
		public function DescribeImages(DescribeImagesType $DescribeImagesType = NULL) 
		{
			try 
			{
				$response = $this->EC2SoapClient->DescribeImages($DescribeImagesType);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
		
		/**
		 * The DescribeInstances operation returns information about instances that you own. 
		 *
		 * @param string $instanceId
		 * @return stdClass
		 */
		public function DescribeInstances($instanceId = NULL) 
		{
	
		    try 
		    {
				if(!empty($instanceId)) 
				{
				    $objInstances->instancesSet = array('item' => array('instanceId' => $instanceId) ); 
				};
								
				$response = $this->EC2SoapClient->DescribeInstances($objInstances);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);				
			} 
			catch (SoapFault $e) 
			{				
				throw new Exception($e->getMessage(), E_ERROR);
			}
	
			return $response;
		}
	
	
		/**
		 * The DescribeKeyPairs operation returns information about key pairs available to you. 
		 * If you specify key pairs, information about those key pairs is returned. 
		 * Otherwise, information for all registered key pairs is returned. 
		 *
		 * @param DescribeKeyPairsType $DescribeKeyPairsType
		 * @return stdClass
		 */
		public function DescribeKeyPairs(DescribeKeyPairsType $DescribeKeyPairsType = null) 
		{
	
            try 
            {
            	if (!$DescribeKeyPairsType)
            		$DescribeKeyPairsType = new DescribeKeyPairsType();
            	            	
            	$response = $this->EC2SoapClient->DescribeKeyPairs($DescribeKeyPairsType);	
            	
            	if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                throw new Exception($e->getMessage(), E_ERROR);
            }
            
            return $response;
		}
	
		/**
		 * The TerminateInstances operation shuts down one or more instances. This operation is idempotent;
         * 
         * if you terminate an instance more than once, each call will succeed.
         * Terminated instances will remain visible after termination (approximately one hour).
		 *
		 * @param array $instances
		 * @return bool
		 */
		public function TerminateInstances($instances) 
		{
			try 
			{
                $instancesSet = new stdClass();
                $instancesSet->instancesSet->item = array();
                foreach ($instances as $instance)
                    array_push($instancesSet->instancesSet->item, array("instanceId" => $instance));
			    
                $response = $this->EC2SoapClient->TerminateInstances($instancesSet);
                
                if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR); 
			}
			
			return $response;
		}
		
		/**
		 * The RebootInstances operation requests a reboot of one or more instances. 
		 * This operation is asynchronous; it only queues a request to reboot the specified instance(s). 
		 * The operation will succeed if the instances are valid and belong to the user. 
		 * Requests to reboot terminated instances are ignored. 
		 *
		 * @param array $instances
		 * @return stdClass
		 */
   	 	public function RebootInstances($instances) 
		{
			try 
			{
                $instancesSet = new stdClass();
                $instancesSet->instancesSet->item = array();
                foreach ($instances as $instance)
                    array_push($instancesSet->instancesSet->item, array("instanceId" => $instance));
			    
                $response = $this->EC2SoapClient->RebootInstances($instancesSet);
                
                if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR); 
			}
			
			return $response;
		}
	
		/**
		 * If Amazon EC2 cannot launch the minimum number AMIs you request, no instances will be launched. If
         * there is insufficient capacity to launch the maximum number of AMIs you request, Amazon EC2
         * launches the minimum number specified for each AMI and allocate the remaining available instances
         * using round robin.
         * In the following example, Libby generates a request to launch two images (database and web_server):
         * 
         * 1. Libby runs the RunInstances operation to launch database instances (min. 10, max. 15) and
         * web_server instances (min. 30, max. 40).
         * Because there are currently 30 instances available and Libby needs a minimum of 40, no instances
         * are launched.
         * 
         * 2. Libby adjusts the number of instances she needs and runs the RunInstances operation to launch
         * database instances (min. 5, max. 10) and web_server instances (min. 20, max. 40).
         * Amazon EC2 launches the minimum number of instances for each AMI (5 database, 20 web_server).
         * The remaining 5 instances are allocated using round robin.
         * 
         * 3. Libby adjusts the number of instances she needs and runs the RunInstances operation again to
         * launch database instances (min. 5, max. 10) and web_server instances (min. 20, max. 40).
		 *
		 * @param RunInstancesType $RunInstancesType
		 * @return object
		 */
		public function RunInstances(RunInstancesType $RunInstancesType) 
		{
            try 
            {
                $response = $this->EC2SoapClient->RunInstances($RunInstancesType);
                
                if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                throw new Exception($e->getMessage(), E_ERROR); 
            }
            
            return $response;
		}
	
		/**
		 * The DeleteKeyPair operation deletes a key pair. 
		 *
		 * @param string $keyName
		 * @return stdClass
		 */
		public function DeleteKeyPair($keyName) 
		{
            try 
            {
                $response = $this->EC2SoapClient->DeleteKeyPair(array('keyName' => $keyName));
                
                if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
            } 
            catch (SoapFault $e) 
            {
                throw new Exception($e->getMessage(), E_ERROR);
            }
            
            return $response;
		}
	
		/**
		 * The CreateKeyPair operation creates a new 2048 bit RSA key pair and returns a unique ID 
		 * that can be used to reference this key pair when launching new instances. 
		 * For more information, see RunInstances. 
		 *
		 * @param string $keyName
		 * @return stdClass
		 */
		public function CreateKeyPair($keyName) 
		{
		    try 
		    {
				$response = $this->EC2SoapClient->CreateKeyPair(array('keyName' => $keyName));
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);	
			}
	
			return $response;
		}
		
		/**
		 * The RegisterImage operation registers an AMI with Amazon EC2. Images must be registered before they can be launched
		 * @param string $imageLocation
		 * @return stdClass
		 */
		public function RegisterImage($imageLocation)
		{
			try 
		    {
				$response = $this->EC2SoapClient->RegisterImage(array('imageLocation' => $imageLocation));
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
				
			} 
			catch (SoapFault $e) 
			{
			    throw new Exception($e->getMessage(), E_ERROR);	
			}
	
			return $response;
		}
    }
?>
