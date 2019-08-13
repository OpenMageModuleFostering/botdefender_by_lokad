<?php

class Wyomind_Botdefender_Helper_Data extends Mage_Core_Helper_Data {

    public $_API_ERROR = "BotDefender API is temporary unavailable.";
    public $_CURL_ERROR = "Unable to connect the BotDefender API through CURL.";
    public $_CURL_DISABLED = "CURL.dll  must be enabled to allow the API connection!";
    public $_MISSING_CREDENTIALS = "Create your FREE BotDefender account now!";
    public $_AUTHENTIFICATION_ERROR = "API connection failed.<br/> Please check your credentials!";
    public $_BAD_REQUEST = "BAD REQUEST";
    public $_CONNECTION_SUCCEEDED = "Installation complete.";
    public $_BOTDEFENDER_API = "https://bdapi.lokad.com/rest/stub/";
     public $_BOTDEFENDER_URL = "https://botdefender.lokad.com/";
    public $_error = false;

    function apiCall($productPriceId = false, $price = 0) {

        // CURL DISABLED
        if (!function_exists('curl_version')) {
            $this->_error = true;
            return -4;
        }
        $username = trim(Mage::getStoreConfig("botdefender/settings/lokad_user"));
        $password = trim(Mage::getStoreConfig("botdefender/settings/lokad_password"));
        // NO CREDENTIALS

        if ($username == null || $password == null) {
            $this->_error = true;
            return -2;
        }
        $service_url = $this->_BOTDEFENDER_API;
        if ($productPriceId)
            $service_url .=$productPriceId . "/" . $price;
        else
            $this->_BAD_REQUEST = $this->_CONNECTION_SUCCEEDED;

        $curl = curl_init($service_url);
        curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $info = curl_getinfo($curl);

        //print_r($info);
        //echo $curl_response;


        if ($curl_response === false) {
            curl_close($curl);

            $this->_error = true;
            return -3;
        }

        if (!curl_errno($curl)) {
            curl_close($curl);



            switch ($info['http_code']) {
                case "500":
                    $this->_error = true;
                    return -5;
                case "400":
                    // BAD REQUEST
                    $this->_error = true;
                    return 0;
                case "401":
                    // ANTHENTIFICATION ERROR
                    $this->_error = true;
                    return -1;
                    break;
                case "200":
                    // OK
                    $this->_error = false;
                    return $curl_response;
                    break;
            }
        }
    }

    public function getMessage($status) {

        switch ($status) {
            case "-5": return $this->_AUTHENTIFICATION_ERROR;
                break;
            case "-4": return $this->_CURL_ERROR;
                break;
            case "-3": return $this->_CURL_DISABLED;
                break;
            case "-2": return $this->_MISSING_CREDENTIALS;
                break;
            case "-1": return $this->_AUTHENTIFICATION_ERROR;
                break;
            case "0": return $this->_BAD_REQUEST;
                break;
            default :
                return $status;
        }
    }

    function cleanArray($var) {
        if ($var != null && $var != "")
            return $var;
    }

    function getData($htmlOutput, $_id, $_storeId, $_price_id) {
        if (Mage::getStoreConfig("botdefender/settings/enabled")) {
            $_botDefenderId = $_id . "_" . $_storeId . "_" . $_price_id;
            preg_match("/([0-9.,]+)/", $htmlOutput, $matches);
            $curl_response = $this->apiCall($_botDefenderId, $matches[0]);
            $ips = array_filter(explode(",", Mage::getStoreConfig("botdefender/settings/ips")), array("Wyomind_Botdefender_Helper_Data", "cleanArray"));

            //debug enabled and ips match
            if (!count($ips) || in_array(Mage::helper('core/http')->getRemoteAddr(), $ips))
                if (Mage::getStoreConfig("botdefender/settings/debug", $_storeId))
                    echo "<div style='border:1px dotted red; color:red;padding:10px;'><b>BotDefender</b> <br>" . $this->_BOTDEFENDER_API . "" . $_botDefenderId . "/" . $matches[0] . "  <br> Response : " . $this->getMessage($curl_response) . "</div>";
            // if error return skip the api response
            if ($this->_error) {
                //log error
                if (Mage::getStoreConfig("botdefender/settings/log"))
                    Mage::log("\n>>" . $this->_BOTDEFENDER_API . "\n*ID: " . $_botDefenderId . "\n*Price: " . $matches[0] . "\n*status: " . $curl_response . "\n*message: " . $this->getMessage($curl_response) . "\n\n", null, "BotDefender.log");
                return $htmlOutput;
            }
            return str_replace($matches[0], $curl_response, $htmlOutput);
        }
        else
            return $htmlOutput;
    }

}