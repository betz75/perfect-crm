<?php

namespace app\services;

defined('BASEPATH') or exit('No direct script access allowed');

class ShaamService
{
    protected $authCode;
    protected $grantType;
    protected $codeField;

    public function __construct($auth_code, $grant_type = "authorization_code", $code_field = "code")
    {
        $this->authCode = $auth_code;
        $this->grantType = $grant_type;
        $this->codeField = $code_field;
    }
    public function getAccessToekn()
    {
        $redirect_uri = get_option("bounce_url");
        $postdata = http_build_query(
            [
                "redirect_uri" => $redirect_uri,
                $this->codeField => $this->authCode,
                "grant_type" => $this->grantType,
                "client_id" => $this->getClientId(),
                "client_secret" => $this->getSecretKey(),
            ]
        );
        $authorization_token = $this->getAuthorizationToken();
        $end_point_url = $this->getEndPoint();
        $host = $this->getHostName($end_point_url);
        $opts = array(
            'http' =>
            array(
                'method'  => 'POST',
                'header'  => "Host: $host\r\nContent-Type: application/x-www-form-urlencoded\r\nAuthorization: Basic $authorization_token\r\n",
                'content' => $postdata
            )
        );
        $context  = stream_context_create($opts);
        
        $result = file_get_contents($end_point_url, false, $context);
        if ($result === false) {
            return;
        }

        $result = json_decode($result, true);

        $this->saveTokens($result);
    }
    protected function saveTokens($result)
    {
        $CI = get_instance();
        $access_token = $result['access_token'];
        $refresh_token = $result['refresh_token'];
        $post_data = ["settings" => []];
        $post_data['settings']['refresh_token'] = $refresh_token;
        $post_data['settings']['access_token'] = $access_token;
        $CI->load->model('settings_model');
        $CI->settings_model->update($post_data);
    }
    protected function getEndPoint()
    {
        return get_option("sham_auth_end_point") . "/longtimetoken/oauth2/token";
    }
    protected function getClientId()
    {
        return         get_option("sham_api_key");
    }
    protected function getSecretKey()
    {
        return get_option("secret_key");
    }
    protected function getAuthorizationToken()
    {
        $client_api_key = $this->getClientId();
        $client_secret = $this->getSecretKey();
        $encoded_secrets = base64_encode($client_api_key . ":" . $client_secret);
        return $encoded_secrets;
    }
    protected function getHostName($end_point_url)
    {
        return parse_url($end_point_url, PHP_URL_HOST);
    }
}
