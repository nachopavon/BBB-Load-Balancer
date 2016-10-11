<?php

namespace BBBLoadBalancer\BBBBundle\Service;

class BBBService
{
	protected $salt;

	/**
     * Constructor.
     */
    public function __construct($salt, $logger)
    {
        $this->salt = $salt;
        $this->logger = $logger;

        // Setting for BBB api lib
        ini_set("allow_url_fopen", "On");
    }

	public function getMeetings($server){
        $result = $this->doRequest($server->getUrl()."/bigbluebutton/api/"."getMeetings".'?checksum='.sha1("getMeetings".$this->salt));
        $meetings = array();
        if($result){
            $xml = new \SimpleXMLElement($result);
            if($xml->returncode == "SUCCESS"){
            	if($xml->meetings->meeting->count()) {
	            	foreach($xml->meetings->meeting as $meeting){
	            		$dt = new \DateTime('@' . round($meeting->createTime/1000));
	            		$meetings[] = array(
	            			'id' => $meeting->meetingID->__toString(),
	            			'name' => $meeting->meetingName->__toString(),
	            			'created' => $dt->format('c'),
	            			'running' => $meeting->running->__toString()
	            		);
	            	}
	            }
            }

            return $meetings;
        } else {
            return false;
        }
	}

	public function doRequest($url, $timeout = 2){
	        if (!function_exists('curl_init')){
			throw new \Exception('Sorry cURL is not installed!');
        	}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_USERAGENT, 'Curios');
	        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        	$output = curl_exec($ch);

        	curl_close($ch);

        	$this->logger->debug("Request to BBB Server", array("url" => $url, "output" => $output));

        	return $output;
	}


	public function doPost($url, $data, $timeout = 10) {
	        if (!function_exists('curl_init')){
			throw new \Exception('Sorry cURL is not installed!');
        	}

		$ch = curl_init();

	        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded','Expect:'));
		curl_setopt($ch, CURLOPT_POST, true);

		$data['checksum'] = sha1("setConfigXML".http_build_query($data).$this->salt);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        	$output = curl_exec($ch);

        	curl_close($ch);

        	$this->logger->debug("POST Request to BBB Server", array("url" => $url,"data" => $data,"output" => $output));

        	return $output;
	}


	public function doPost2($url, $request, $timeout = 10) {
	        if (!function_exists('curl_init')){
			throw new \Exception('Sorry cURL is not installed!');
        	}

		$ch = curl_init();

	        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8'));
		curl_setopt($ch, CURLOPT_POST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getContent());

        	$output = curl_exec($ch);

        	curl_close($ch);

        	$this->logger->debug("POST Request to BBB Server", array("url" => $url,"slide" => $request->getContent(),"output" => $output));

        	return $output;
	}


	public function cleanUri($uri){
		// remove dev url
		return str_replace("app_dev.php/", "", $uri);
	}
}
