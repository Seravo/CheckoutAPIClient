<?php

// Disable namespacing for now as the custom-settings.php
// cannot declare a project wide top-level 'use CheckoutFinland\CreateMerchant;''
// and thus namespace line here is disabled

// namespace CheckoutFinland;

/**
 * Class CrateMerchant
 * @package CheckoutFinland
 */
class CreateMerchant
{

    protected $username;
    protected $password;

    public function __construct($username, $password) {
      $this->username = $username;
      $this->password = $password;
    }

    /**
     * Posts data, tries to use stream context if allow_url_fopen is on in php.ini or CURL if not. If neither option is available throws exception.
     *
     * @param $url
     * @param $postData
     * @throws \Exception
     */
    public function sendRequest($postData)
    {

        $url = 'https://rpcapi.checkout.fi/reseller/createMerchant';
        $basicAuth = base64_encode($this->username.':'.$this->password);

        if(ini_get('allow_url_fopen'))
        {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => array(
                                  'Authorization: Basic '. $basicAuth,
                                  'Content-Type: application/x-www-form-urlencoded',
                                  'User-Agent: checkout-finland-api-client'
                                ),
                    'content' => http_build_query($postData)
                )
            ));

            $response = file_get_contents($url, false, $context);
            if ($response) {
                $merchant = new SimpleXMLElement($response);
                return $merchant;
            } else {
                throw new \Exception("No response from Checkout.fi RPC API.");
            }

        }
        elseif(in_array('curl', get_loaded_extensions()) )
        {
            $options = array(
                CURLOPT_POST            => 1,
                CURLOPT_HEADER          => 0,
                CURLOPT_URL             => $url,
                CURLOPT_FRESH_CONNECT   => 1,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_FORBID_REUSE    => 1,
                CURLOPT_TIMEOUT         => 4,
                CURLOPT_POSTFIELDS      => http_build_query($postData)
            );

            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result;
        }
        else
        {
            throw new \Exception("No valid method to post data. Set allow_url_fopen setting to On in php.ini file or install curl extension.");
        }
    }
}
