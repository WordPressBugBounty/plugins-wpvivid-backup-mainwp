<?php
if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

class MainWP_Dropbox_Base{

	const API_URL_V2  = 'https://api.dropboxapi.com/';
    const CONTENT_URL_V2 = 'https://content.dropboxapi.com/2/';
    const API_ID = 'cwn4z5jg8wy7b4u';

    private $access_token;
    private $option;

    public function __construct($option){
        $this -> option = $option;
    	$this -> access_token = $option['token'];
    }

    public function upload($target_path, $file_data, $mode = "add") {
        $endpoint = self::CONTENT_URL_V2."files/upload";
        $headers = array(
            "Content-Type: application/octet-stream",
            "Dropbox-API-Arg: {\"path\": \"$target_path\", \"mode\": \"$mode\"}"       
        );
        
        if (file_exists($file_data))
            $postdata = file_get_contents($file_data);
        else
            $postdata = $file_data;
        
        $returnData = $this ->postRequest($endpoint, $headers, $postdata);
        return $returnData;
    }

    public function upload_session_start() {
        $endpoint = self::CONTENT_URL_V2."files/upload_session/start";
        $headers = array(
            "Content-Type: application/octet-stream",
            "Dropbox-API-Arg: {\"close\": false}"
        );
        $returnData = $this ->postRequest($endpoint, $headers,null);
        return $returnData;
    }

    public function upload_session_append_v2($session_id, $offset, $postdata) {
        $endpoint = self::CONTENT_URL_V2."files/upload_session/append_v2";
        $headers = array(
            "Content-Type: application/octet-stream",
            "Dropbox-API-Arg: {\"cursor\": {\"session_id\": \"$session_id\",\"offset\": $offset},\"close\": false}"
        );

        $returnData = $this ->postRequest($endpoint, $headers, $postdata);
        return $returnData;
    }

    public function upload_session_finish($session_id, $filesize, $path, $mode = 'add') {
        $endpoint = self::CONTENT_URL_V2."files/upload_session/finish";
        $entry = array(
        	'cursor' => array(
        		'session_id' => $session_id,
        		'offset' => $filesize,
        	),
        	'commit' => array(
        		'path' => $path,
        		'mode' => $mode,

        	),
        );
        $headers = array(
            "Content-Type: application/octet-stream",
            "Dropbox-API-Arg: " . wp_json_encode($entry),
        );

        $returnData = $this ->postRequest($endpoint, $headers,null);
        return $returnData;
    }

    public function download($path,$header = array()) {
        $endpoint = "https://content.dropboxapi.com/2/files/download";
        $headers = array(
            "Content-Type: text/plain; charset=utf-8",
            "Dropbox-API-Arg: {\"path\": \"$path\"}"
        );
        $headers = array_merge ($headers,$header);
        $data = $this ->postRequest($endpoint, $headers,null,false);
        return $data;
    }

    public function delete($path) {
        $endpoint = self::API_URL_V2."2/files/delete";
        $headers = array(
            "Content-Type: application/json"
        );
        $postdata = wp_json_encode(array( "path" => $path ));
        $returnData = $this -> postRequest($endpoint, $headers, $postdata);
        return $returnData;
    }

    public function revoke() {
        $endpoint = self::API_URL_V2."2/auth/token/revoke";
        $headers = array();
        $this -> postRequest($endpoint, $headers);
    }

    public function getUsage(){
        $endpoint = self::API_URL_V2."2/users/get_space_usage";
        $headers = array(
            "Content-Type: application/json"
        );
        $postdata = "null";
        $returnData = $this -> postRequest($endpoint, $headers,$postdata);
        return $returnData;
    }

	public static function getUrl($url,$state = ''){
		$params = array(
                    'client_id' => self::API_ID,
                    'response_type' => 'code',
                    'redirect_uri' => $url,
                    'state' => $state,
                );
	    $url = 'https://www.dropbox.com/oauth2/authorize?';
	    $url .= http_build_query($params,'','&');
	    return $url;
	}

    public function postRequest($endpoint, $headers, $data = null,$returnjson = true) {
        $ch = curl_init($endpoint);
        array_push($headers, "Authorization: Bearer " . $this -> access_token);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        //todo delete this code
//        curl_setopt($ch,CURLOPT_PROXY, '127.0.0.1:1080');
        $r = curl_exec($ch);
        $chinfo = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if($r === false){
            $r['error_summary'] = $error;
        }else{
            if($chinfo['http_code'] === 401){
                $r = array();
                $r['error_summary'] = 'Invalid or expired token. Please remove '.$this -> option['name'].' from the storage list and re-authenticate it.';
            }elseif($chinfo['http_code'] !== 200 && $chinfo['http_code'] !== 206){
                $r = json_decode($r,true);
            }
        }
        if($returnjson && !is_array($r))
            $r = json_decode($r,true);

        return $r;
    }
    public function setAccessToken($access_token){
    	$this -> access_token = $access_token;
    }
}