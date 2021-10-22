<?php
date_default_timezone_set('Asia/Colombo');

define('TOKEN_FILE', __DIR__ . '/token.json');
define('CONFIG_FILE', __DIR__ . '/config.json');
define('TOKEN_GTIME', 5);
define('DATE_FORMAT', 'Y-m-d H:i:s');

function getToken()
{

    $configData = file_get_contents(CONFIG_FILE);
    $config = json_decode($configData, true);
    $clientId = $config['client_id'];
    $client_secret = $config['client_secret'];
    $code = $config['code'];
    $redirectUri = $config['redirect_uri'];   

    $jsonData = file_get_contents(TOKEN_FILE);
    $currentToken = json_decode($jsonData, true);
    if (isset($currentToken) && $currentToken != "") {

        $expire_mins = (strtotime($currentToken['expires_in']) - strtotime(date(DATE_FORMAT))) / 60;
        if ($expire_mins >= TOKEN_GTIME) {
            return $currentToken;
        }

        $refreshToken = isset($currentToken['refresh_token']) ? $currentToken['refresh_token'] : '';
        $newToken = getAccessTokenWithRefreshToken($clientId, $client_secret, $refreshToken);
    } else {
        $newToken = getAccessToken($code, $clientId, $client_secret, $redirectUri);
    }
    return $newToken;
}


/**
 * Initial access token creation
 */
function getAccessToken($code, $clientId, $client_secret, $redirectUri)
{
    $token = [];
    $query = "?grant_type=authorization_code&code=" . $code . "&client_id=" . $clientId . "&redirect_uri=" . $redirectUri . "&client_secret=" . $client_secret;
    $url = 'https://accounts.zoho.com/oauth/v2/token' . $query;
    $response = getAccessTokenAPIConnection($url);
    if (isset($response['access_token'])) {
        $token['access_token'] = isset($response['access_token']) ? $response['access_token'] : '';
        $token['refresh_token'] = isset($response['refresh_token']) ? $response['refresh_token'] : '';
        $token['api_domain'] = isset($response['api_domain']) ? $response['api_domain'] : '';
        $token['expires_in'] = date(DATE_FORMAT, strtotime('+1 hours'));
        file_put_contents(TOKEN_FILE, json_encode($token));
    }

    return $token;
}



/**
 * Access token creation with refresh token
 */
function getAccessTokenWithRefreshToken($clientId, $client_secret, $refreshtoken)
{
    $token = [];
    $query = "?refresh_token=" . $refreshtoken . "&client_id=" . $clientId . "&client_secret=" . $client_secret . "&grant_type=refresh_token";
    $url = 'https://accounts.zoho.com/oauth/v2/token' . $query;

    $response = getAccessTokenAPIConnection($url);
    if (isset($response['access_token'])) {
        $currentTokenData = file_get_contents(TOKEN_FILE);
        $token = json_decode($currentTokenData, true);
        $token['access_token'] = $response['access_token'];
        $token['expires_in'] = date(DATE_FORMAT, strtotime('+1 hours'));
        file_put_contents(TOKEN_FILE, json_encode($token));
    }
    return $token;
}




function getAccessTokenAPIConnection($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);
    return  $response;
}



function storeLeads($data)
{

    $token = getToken();
    $url = 'https://www.zohoapis.com/crm/v2/Leads';
    $headers = array(
        'Content-Type: application/json; utf-8',
        'Accept: application/json',
        'Authorization: Bearer ' . $token['access_token']
    );

    $zoho_data = array();
    $zoho_data['data'][0] = $data;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($zoho_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);

    return $response['data'][0];
}



function storeFiles($id,$fileName,$filePathName)
{
    $filePath =$filePathName.$fileName;
    $token = getToken();
    $curl_pointer = curl_init();
    $curl_options = array();
    $curl_options[CURLOPT_URL] = "https://www.zohoapis.com/crm/v2/Leads/" . $id . "/Attachments";
    $curl_options[CURLOPT_RETURNTRANSFER] = true;
    $curl_options[CURLOPT_HEADER] = 1;
    $curl_options[CURLOPT_CUSTOMREQUEST] = "POST";
    
    $file = fopen($filePath, "rb");
    $fileData = fread($file, filesize($filePath));
    $date = new \DateTime();

    $current_time_long = $date->getTimestamp();
    $lineEnd = "\r\n";
    $hypen = "--";
    $contentDisp = "Content-Disposition: form-data; name=\"" . "file" . "\";filename=\"" . $fileName . "\"" . $lineEnd . $lineEnd;
    $data = utf8_encode($lineEnd);
    $boundaryStart = utf8_encode($hypen . (string)$current_time_long . $lineEnd);
    $data = $data . $boundaryStart;
    $data = $data . utf8_encode($contentDisp);
    $data = $data . $fileData . utf8_encode($lineEnd);
    $boundaryend = $hypen . (string)$current_time_long . $hypen . $lineEnd . $lineEnd;
    $data = $data . utf8_encode($boundaryend);
    
    $curl_options[CURLOPT_POSTFIELDS] = $data;
    $headersArray = array();

    $headersArray = ['ENCTYPE: multipart/form-data', 'Content-Type:multipart/form-data;boundary=' . (string)$current_time_long];
    $headersArray[] = "content-type" . ":" . "multipart/form-data";
    $headersArray[] = "Authorization" . ":" . "Zoho-oauthtoken " . $token['access_token'];

    $curl_options[CURLOPT_HTTPHEADER] = $headersArray;

    curl_setopt_array($curl_pointer, $curl_options);

    $result = curl_exec($curl_pointer);
    $responseInfo = curl_getinfo($curl_pointer);
    curl_close($curl_pointer);
    list($headers, $content) = explode("\r\n\r\n", $result, 2);
    if (strpos($headers, " 100 Continue") !== false) {
        list($headers, $content) = explode("\r\n\r\n", $content, 2);
    }
    $headerArray = (explode("\r\n", $headers, 50));
    $headerMap = array();
    foreach ($headerArray as $key) {
        if (strpos($key, ":") != false) {
            $firstHalf = substr($key, 0, strpos($key, ":"));
            $secondHalf = substr($key, strpos($key, ":") + 1);
            $headerMap[$firstHalf] = trim($secondHalf);
        }
    }
    $jsonResponse = json_decode($content, true);
    if ($jsonResponse == null && $responseInfo['http_code'] != 204) {
        list($headers, $content) = explode("\r\n\r\n", $content, 2);
        $jsonResponse = json_decode($content, true);
    }

    unlink($filePathName,$fileName); 
    // print_r($jsonResponse);
    // exit;
    return $jsonResponse['data'][0];    
}



function uploadfiles($target_dir,$currentInputFile)
{
    
    $fileName = time() . '_' . $currentInputFile["name"];
    $target_file = $target_dir . basename($fileName);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $arr= array();
    if (file_exists($target_file)) {
        $uploadOk = 0;
        $arr['error']="Sorry, file already exists.";
        return $arr;       
    }

    // Check file size
    if ($currentInputFile["size"] > 500000) {
        $arr['error']="Sorry, your file is too large.";
        $uploadOk = 0;
        return $arr;    
    }



    if ($uploadOk == 0) {
        $arr['error']= "Sorry, your file was not uploaded.";
        return $arr;    
    } else {
        if (move_uploaded_file($currentInputFile["tmp_name"], $target_file)) {
            $arr['fileName']= htmlspecialchars(basename($fileName));
            return $arr; 
        } else {
            $arr['error']= "Sorry, there was an error uploading your file.";
            return $arr;    
        }
    }
}
