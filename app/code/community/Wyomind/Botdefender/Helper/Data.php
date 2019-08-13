<?php

class Wyomind_Botdefender_Helper_Data extends Mage_Core_Helper_Data {

    public $_API_ERROR = "BotDefender API is temporary unavailable.";
    public $_CURL_ERROR = "Unable to connect the BotDefender API through CURL.";
    public $_CURL_DISABLED = "CURL must be enabled to allow the API connection!";
    public $_MISSING_CREDENTIALS = "Create your FREE BotDefender account now!";
    public $_AUTHENTIFICATION_ERROR = "API connection failed.<br/> Please check your credentials!";
    public $_BAD_REQUEST = "BAD REQUEST";
    public $_CONNECTION_SUCCEEDED = "Installation complete.";
    public $_BOTDEFENDER_API = "https://bdapi.lokad.com/rest/stub/";
    public $_BOTDEFENDER_URL = "https://botdefender.lokad.com/";
    public $_error = false;

    function apiCall($productPriceId = false, $price = 0) {

        // CURL DISABLED
        if (!function_exists('curl_init')) {
            $this->_error = true;
            return -4;
        }
        $username = trim(Mage::getStoreConfig("botdefender/settings/lokad_user"));
        $password = trim(Mage::getStoreConfig("botdefender/settings/lokad_password__"));
        // NO CREDENTIALS

        if ($username == null || $password == null) {
            $this->_error = true;
            return -2;
        }
        $service_url = $this->_BOTDEFENDER_API;
        if ($productPriceId)
            $service_url .=$productPriceId . "/" . $price;
        else {
            $service_url .= "botdefendertest/1.00";
        }

        $curl = curl_init($service_url);
        curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $info = curl_getinfo($curl);

        //print_r($info);
        //print_r($curl_response);


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

    public function getMessage($status, $testCnx = false) {

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
                if (!$testCnx)
                    return $status;
                else
                    return $this->_CONNECTION_SUCCEEDED;
        }
    }

    function cleanArray($var) {
        if ($var != null && $var != "")
            return $var;
    }

    function getData($htmlOutput, $_id, $_storeId, $_price_id) {
        if (Mage::getStoreConfig("botdefender/settings/enabled")) {
            $_botDefenderId = $_id . "_" . $_storeId . "_" . $_price_id;
            preg_match("/([0-9]|\.|\,){1,16}/", $htmlOutput, $matches);

            $cacheGroup = 'botdefender';
            $useCache = Mage::app()->useCache($cacheGroup);


            $debug = "<div style='border:1px dotted red; color:red;padding:10px;'><b>BotDefender</b><br>";

            $cache = Mage::app()->getCache();
            $data = $cache->load($_botDefenderId);
            if ($useCache)
                $debug .= "Cache is active.<br>";
            else
                $debug .= "Cache is not active.<br>";

            // If cache exists
            if ($data !== false && $useCache) {

                $debug .= "Cache exists.<br>";
                $data = str_replace($matches[0], $data, $htmlOutput);
                // If cache doesn't exixst or is out of date
            } elseif ($data === false) {

                if ($useCache)
                    $debug .= "Cache doesn't exists.<br>";
                $curl_response = $this->apiCall($_botDefenderId, $matches[0]);
                $ips = array_filter(explode(",", Mage::getStoreConfig("botdefender/settings/ips")), array("Wyomind_Botdefender_Helper_Data", "cleanArray"));

                //debug enabled and ips match
                if (!count($ips) || in_array(Mage::helper('core/http')->getRemoteAddr(), $ips))
                    $debug .= $this->_BOTDEFENDER_API . "" . $_botDefenderId . "/" . $matches[0] . "  <br> Response : " . $this->getMessage($curl_response);
                // if error return skip the api response& disable pulugin
                if ($this->_error) {
                    // DISABLE THE MODULE
                    Mage::getConfig()->saveConfig("botdefender/settings/enabled", false, "default", 0);
                    Mage::getConfig()->cleanCache();
                    //log error
                    $debug .= "Default data are used.";
                    if (Mage::getStoreConfig("botdefender/settings/log"))
                        Mage::log("\n>>" . $this->_BOTDEFENDER_API . "\n*ID: " . $_botDefenderId . "\n*Price: " . $matches[0] . "\n*status: " . $curl_response . "\n*message: " . $this->getMessage($curl_response) . "\n\n", null, "BotDefender.log");
                    $data = $htmlOutput;
                } else {
                    $data = str_replace($matches[0], $curl_response, $htmlOutput);
                }

                if ($useCache) {
                    $debug .= "Cache has been created";
                    $cache->save($curl_response, $_botDefenderId, array('BOTDEFENDER'), Mage::getStoreConfig("botdefender/settings/cachelifetime") * 24 * 60 * 60);
                }
            }

            $debug .= "<div>";
            if (Mage::getStoreConfig("botdefender/settings/debug", $_storeId))
                return $debug . $data;
            return $data;
        } else
            return $htmlOutput;
    }

}
