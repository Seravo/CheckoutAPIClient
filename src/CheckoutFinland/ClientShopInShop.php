<?php

namespace CheckoutFinland;

/**
 * Class Client
 * @package CheckoutFinland
 */
class Client
{

    /**
     * Builds an XML payload that can be passed to Checkout Finland Shop-In-Shop API.
     * Returns the response body that contains payment options xml or error message.
     *
     * @param Payment $payment
     * @throws \Exception
     */
    public function sendPayment(Payment $payment)
    {

        $xml = '<?xml version="1.0"?>
          <checkout xmlns="http://checkout.fi/request"> <!-- draft: 23/01/2010, 15/06/2010, 12/12/2010, 20/12/2010, 22/01/2011 '.time().' -->
            <request type="aggregator" test="true|false">
              <aggregator>'. $payment->getMerchantId() .'</aggregator>
              <version>0002</version>
              <stamp>'. $payment->getStamp() .'</stamp>
              <reference>'. $payment->getReference() .'</reference>
              <description>'. $payment->getMessage() .'</description>
              <device>10</device>
              <content>1</content>
              <type>0</type>
              <algorithm>2</algorithm>
              <currency>'. $payment->getCurrency() .'</currency>
              <items>';

        // foreach $payment->getItems()
          $xml .= '
  <item>
    <code>'. $payment->getReference() .'</code> <!-- product code, not required -->
    <description>'. $payment->getMessage() .'</description> <!-- required -->
    <price currency="'. $payment->getCurrency() .'">'. $payment->getAmount() .'</price>
    <merchant>'. $payment->getItemMerchantId() .'</merchant> <!-- required -->
    <control>'. $payment->getControl() .'</control>
  </item>
          ';

              $xml .= '
                <amount currency="'. $payment->getCurrency() .'">'. $payment->getAmount() .'</amount>
                <!-- has to be exact total from sum of the items prices, in cents -->
              </items>
              <buyer>
                  <company vatid=""></company> <!-- not required -->
                  <firstname>'. $payment->getFirstName() .'</firstname> <!-- not required -->
                  <familyname>'. $payment->getFamilyName() .'</familyname> <!-- not required -->
                  <address><![CDATA[ '. $payment->getAddress() .' ]]></address> <!-- not required -->
                  <postalcode>'. $payment->getPostcode() .'</postalcode> <!-- not required -->
                  <postaloffice>'. $payment->getPostOffice() .'</postaloffice> <!-- not required -->
                  <country>'. $payment->getCountry() .'</country>
                  <email></email> <!-- not required -->
                  <gsm></gsm> <!-- not required -->
                  <language>'. $payment->getLanguage() .'</language>
              </buyer>
              <delivery>
                  <date>'. $payment->getDeliveryDate('Ymd') .'</date>
                  <company vatid=""></company>
                  <firstname></firstname>
                  <familyname></familyname>
                  <address><![CDATA[ ]]></address>
                  <postalcode></postalcode>
                  <postaloffice></postaloffice>
                  <country></country>
                  <email></email>
                  <gsm></gsm>
                  <language></language>
              </delivery>
              <control type="default">
        <!-- @type=default = only online or offline rule is executed -->
                  <return>'. $payment->getReturnUrl() .'</return> <!-- REQUIRED -->
                  <reject>'. $payment->getReturnUrl() .'</reject> <!-- REQUIRED -->
                  <cancel>'. $payment->getReturnUrl() .'</cancel> <!-- REQUIRED -->
              </control>
          </request>
        </checkout>
        ';

        // print_r($xml);

        $xml = base64_encode($xml);
        $mac = strtoupper(md5("{$xml}+". $payment->getMerchantSecret()));

        $postData = array(
            'CHECKOUT_XML' => $xml,
            'CHECKOUT_MAC' => $mac
        );

        return $this->postData("https://payment.checkout.fi", $postData);
    }


    /**
     * Posts data, tries to use stream context if allow_url_fopen is on in php.ini or CURL if not. If neither option is available throws exception.
     *
     * @param $url
     * @param $postData
     * @throws \Exception
     */
    private function postData($url, $postData)
    {
        $data="";
        foreach($postData as $key => $value) {
            $data.=urlencode($key)."=".urlencode($value)."&";
        }

        $fp = fsockopen("ssl://payment.checkout.fi", 443);
        fputs($fp, "POST / HTTP/1.0\r\n");
        fputs($fp, "Host: payment.checkout.fi\r\n");
        fputs($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "User-Agent: Checkout-poster/1.0\r\n");
        fputs($fp, "Content-Length: " . strlen($data) . "\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        while (!feof($fp)) $sent .= fgets($fp,128);
        fclose($fp);
        $tmp=split("\r\n\r\n",$sent,2);
        $return=split("\n",$tmp[1],2);
        return ($return[1]);

    }
}
