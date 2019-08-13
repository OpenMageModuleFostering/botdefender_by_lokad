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
    public $_BOTDEFENDER_DOWNTIME = "BotDefender has been disabled due to a BotDefender downtime ";
    public $_error = false;
    public $_data = array();

    function apiCall($productPriceId = false, $price = 0, $multi = false) {

        // CURL DISABLED
        if (!function_exists('curl_init')) {
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
        $prices = array();
        if ($multi !== FALSE) {
            foreach ($multi as $price) {
                if (!in_array($price, $prices))
                    $prices[] = $price;
            }
            $service_url.=$id = $this->urlToHash() . "/?prices=" . implode(";", $prices);
        } else {
            if ($productPriceId)
                $service_url .=$productPriceId . "/" . $price;
            else {
                $service_url .= "botdefendertest/1.00";
            }
        }
        $this->_webservice_url = $service_url;

        $curl = curl_init($service_url);
       
        curl_setopt($curl, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $curl_response = curl_exec($curl);
        $info = curl_getinfo($curl);
      
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
        /* else {
          $res = false;
          foreach ($multi as $i => $m) {
          $urls[$i] = $service_url . $m['id'] . "/" . $m['price'];
          }


          $mh = curl_multi_init();

          foreach ($urls as $i => $url) {
          $conn[$i] = curl_init($url);

          curl_setopt($conn[$i], CURLOPT_USERPWD, $username . ':' . $password);
          curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, true);
          curl_setopt($conn[$i], CURLOPT_HEADER, 0);
          curl_multi_add_handle($mh, $conn[$i]);
          }

          do {
          $status = curl_multi_exec($mh, $active);
          $info = curl_multi_info_read($mh);
          } while ($status === CURLM_CALL_MULTI_PERFORM || $active);

          foreach ($urls as $i => $url) {
          $res[$i] = curl_multi_getcontent($conn[$i]);
          curl_close($conn[$i]);
          }


          return $res;
          } */
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

    function processData() {
        $data = Mage::getSingleton('core/session')->getData('botdefender_data');
        $debug = "<div style='border:1px dotted red; color:red;padding:10px;'><b>BotDefender</b><br>";

        if (Mage::getStoreConfig("botdefender/settings/enabled") && is_array($data)) {
            $cache = Mage::app()->getCache();
            $cacheGroup = 'botdefender';

            $ips = array_filter(explode(",", Mage::getStoreConfig("botdefender/settings/ips")), array("Wyomind_Botdefender_Helper_Data", "cleanArray"));
            $res = $this->apiCall(false, false, $data);

            if (!$this->_error) {
                $debug .= $this->_webservice_url . "<br>";
                $res_array = array_unique(explode("\n", $res));

                foreach ($res_array as $i => $d) {
                    if (trim($d) !== "") {
                        $debug .= $data[$i] . "=>" . ($d) . "<br>";
                        $cache->save($d, $data[$i], array('BOTDEFENDER'), Mage::getStoreConfig("botdefender/settings/cachelifetime") * 60 * 60);
                    }
                }
                $debug .= "<br>Cache has been created";
            } else {
                // DISABLE THE MODULE
                Mage::getConfig()->saveConfig("botdefender/settings/enabled", "0", "default", 0);
                // create the alert flag
                Mage::getConfig()->saveConfig("botdefender/settings/alert", "1", "default", 0);
                // clean config cache
                Mage::getConfig()->cleanCache();
                //log error
                $debug = $this->_BOTDEFENDER_DOWNTIME . "Default data are used.";
            }
        } else {
            $debug.= "Cache already created, no data stored.";
        }
        $debug .= "</div>";
        if (Mage::getStoreConfig("botdefender/settings/debug") && (!count($ips) || in_array(Mage::helper('core/http')->getRemoteAddr(), $ips)))
            echo $debug;
        Mage::getSingleton('core/session')->setData('botdefender_data', null);
    }

    function urlToHash() {
        mt_srand(crc32(Mage::getSingleton('core/url')->parseUrl(Mage::helper('core/url')->getCurrentUrl())->getPath()));
        return mt_rand(0, 100);
    }

    function getData($htmlOutput, $_id, $_storeId, $_price_id) {
        $ips = array_filter(explode(",", Mage::getStoreConfig("botdefender/settings/ips")), array("Wyomind_Botdefender_Helper_Data", "cleanArray"));
        $cache = Mage::app()->getCache();

        if (Mage::getStoreConfig("botdefender/settings/enabled")) {

            preg_match("/([0-9]|\.|\,){1,16}/", $htmlOutput, $matches);

            $cacheGroup = 'botdefender';
            $useCache = Mage::app()->useCache($cacheGroup);

            $_botDefenderId = $matches[0];

            $debug = "<div style='border:1px dotted red; color:red;padding:10px;'><b>BotDefender</b><br>";


            $data = $cache->load($_botDefenderId);
            if ($useCache)
                $debug .= "Cache is active.<br>";
            else
                $debug .= "Cache is not active.<br>";

            if ($data === false)
                $debug .= "Cache doesn't exists.<br>";
            else
                $debug .= "Cache  exists.<br>";

            // If cache exists
            if ($data !== false && $useCache) {
                // Use cache 
                $data = str_replace($matches[0], $data, $htmlOutput);
            }
            // If cache doesn't exist or is out of date 
            elseif ($data === false) {

                // Store the data for a later use
                $price = $matches[0];

                if (!in_array($price, $this->_data)) {
                    $this->_data[] = $price;
                    Mage::getSingleton('core/session')->setData('botdefender_data', $this->_data);
                }

                // Use defautl html 
                $data = $htmlOutput;
            }
            // Use defautl html 
            else {
                $data = $htmlOutput;
            }

            $debug .= "</div>";
            if (Mage::getStoreConfig("botdefender/settings/debug", $_storeId) && (!count($ips) || in_array(Mage::helper('core/http')->getRemoteAddr(), $ips)))
                return $debug . $data;
            return $data;
        } else
        if (Mage::getStoreConfig("botdefender/settings/debug", $_storeId) && (!count($ips) || in_array(Mage::helper('core/http')->getRemoteAddr(), $ips)))
            return "<div style='border:1px dotted red; color:red;padding:10px;'><b>BotDefender is disabled</b><br>" . $htmlOutput . "</div>";
        return $htmlOutput;
    }

}
