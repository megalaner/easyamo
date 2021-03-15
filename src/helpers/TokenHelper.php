<?php

namespace EasyAmo\Helpers;

use League\OAuth2\Client\Token\AccessToken;
use Exception;

if (!defined('TOKEN_FILE'))
    define('TOKEN_FILE', __DIR__ . '/../tmp/token_info.json');

if (!defined('TOKEN_REFRESH_FILE'))
    define('TOKEN_REFRESH_FILE', __DIR__ . '/../tmp/token_refresh.json');

class TokenHelper
{
    public function saveToken($accessToken, string $baseDomain)
    {
        if (
            isset($accessToken)
            && isset($accessToken['access_token'])
            && isset($accessToken['refresh_token'])
            && isset($accessToken['expires_in'])
        ) {

            $data = [
                'accessToken' => $accessToken['access_token'],
                'expires' => $accessToken['expires_in'],
                'refreshToken' => $accessToken['refresh_token'],
                'tokenType' => $accessToken['token_type'],
                'baseDomain' => $baseDomain,
            ];

            $refreshData = [
                'refreshToken' => $accessToken['refresh_token'],
            ];

            if (!file_exists(__DIR__ . '/../tmp'))
                mkdir(__DIR__ . '/../tmp', 0755);


            file_put_contents(TOKEN_FILE, json_encode($data));

            file_put_contents(TOKEN_REFRESH_FILE, json_encode($refreshData));

        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    public function getToken()
    {
        $accessToken = json_decode(file_get_contents(TOKEN_FILE), true);

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    public function getTokenArray()
    {
        $accessToken = json_decode(file_get_contents(TOKEN_FILE), true);

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['baseDomain'])
        ) {
            return [
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ];
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    public function getRefreshToken()
    {
        $jsonDecodedToken = json_decode(file_get_contents(TOKEN_REFRESH_FILE), true);

        return $jsonDecodedToken['refreshToken'];
    }

    public function curlRequest($data, $link)
    {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Exception $e) {

            if($e->getCode() == 400)
                print "Probably OAuth code error, please try to copy new one from your integration and update your auth configuration file. ";

            die('Error: ' . $e->getMessage() . PHP_EOL . 'Error code: ' . $e->getCode());
        }

        return $out;

    }

    public function getCurlRequest($data, $link)
    {

        $request = "";

        foreach ($data as $key => $value):

            $request .= $key . "=" . $value . "&";

        endforeach;

        $link = $link . "?" . $request;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
        curl_setopt($curl, CURLOPT_URL, $link);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch (\Exception $e) {

            die('Error: ' . $e->getMessage() . PHP_EOL . 'Error code: ' . $e->getCode());
        }

        return $out;

    }

    public function printResponse($response)
    {

        echo "<h1>Access key:</h1>";
        echo $response['access_token'];
        echo "<h1>Refresh token:</h1>";
        echo $response['refresh_token'];
        echo "<h1>Token type:</h1>";
        echo $response['token_type'];
        echo "<h1>Token expires in^</h1>";
        echo $response['expires_in'];
    }

    function refreshAndSave($config, $baseDomain) {

        $tokenHelper = new TokenHelper();

        $refreshToken = $tokenHelper->getRefreshToken();

        $data = [
            'client_id' => $config['clientID'],
            'client_secret' => $config['clientSecret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri' => $config['redirect_uri'],
        ];

        $link = 'https://' . $config['domain'] . '/oauth2/access_token?time=' . time();

        $curlResponse = $tokenHelper->curlRequest($data, $link);

        $response = json_decode($curlResponse, true);

        $tokenHelper->saveToken($response, $baseDomain);

        /** Uncomment to debug **/
        // $tokenHelper->printResponse($response);

    }

    public function isTokenFresh()
    {

        $fileCreated = (int)filemtime(TOKEN_FILE);

        $timeStamp = (int)time();

        $differs = $fileCreated + 86400;

        if ($differs >= $timeStamp)
            return false;
        else
            return true;

    }

    public function createConnect($authConfig)
    {
        $link = 'https://' . $authConfig['domain'] . '/oauth2/access_token?time=' . time();

        $data = [
            'client_id' => $authConfig['clientID'],
            'client_secret' => $authConfig['clientSecret'],
            'grant_type' => 'authorization_code',
            'code' => $authConfig['oAuthCode'],
            'redirect_uri' => $authConfig['redirect_uri'],
        ];

        $out = $this->curlRequest($data, $link);

        $response = json_decode($out, true);

        $this->saveToken($response, $authConfig['domain']);

        /** Uncomment to debug **/
        // $this->printResponse($response);

    }

    public function refreshConnect($authConfig)
    {

        $link = 'https://' . $authConfig['domain'] . '/oauth2/access_token?time=' . time();

        $refreshToken = $this->getRefreshToken();

        $data = [
            'client_id' => $authConfig['clientID'],
            'client_secret' => $authConfig['clientSecret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri' => $authConfig['redirect_uri'],
        ];

        $curlResponse = $this->curlRequest($data, $link);

        $response = json_decode($curlResponse, true);

        $this->saveToken($response, $authConfig['domain']);

        /** Uncomment to debug **/
        //$this->printResponse($response);

    }
}
