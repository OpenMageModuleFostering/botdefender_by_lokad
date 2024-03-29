<?php


class Wyomind_Botdefender_FeedReader extends Mage_AdminNotification_Model_Feed {

    public function getFeedUrl() {

        Mage::getSingleton('admin/session')->getData();
        $url = Mage::getStoreConfig("web/secure/base_url");
        $version = Mage::getConfig()->getNode("modules/Wyomind_Botdefender")->version . '[bdl]';

        $lastcheck = $this->getLastUpdate();

        $rss_url = 'www.wyomind.com';

        return "http://$rss_url/rss/new_releases.php?domain=$url&version=$version&lastcheck=$lastcheck&now=" . time();
    }

    public function getLastUpdate() {
        return Mage::getStoreConfig("botdefender/notifications/lastcheck");
    }

    public function setLastUpdate() {
        Mage::getConfig()->saveConfig("botdefender/notifications/lastcheck", time(), "default", "0");
        Mage::getConfig()->cleanCache();
        return $this;
    }

}

class Wyomind_Botdefender_Item {

    const SEVERITY_CRITICAL = 1;
    const SEVERITY_MAJOR = 2;
    const SEVERITY_MINOR = 3;
    const SEVERITY_NOTICE = 4;

    var $pubDate = 0;
    var $title = "";
    var $description = "";
    var $severity = Wyomind_Botdefender_Item::SEVERITY_NOTICE;
    var $link = "";

    public function __contruct() {
        
    }

    public function Wyomind_Botdefender_Item() {
        $this->__contruct();
    }

    public function setPubDate($date) {
        $this->pubDate = $date;
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function setDescription($desc) {
        $this->description = $desc;
    }

    public function setSeverity($sev) {
        $this->severity = $sev;
    }

    public function setLink($link) {
        $this->link = $link;
    }

    public function getPubDate() {
        return $this->pubDate;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getSeverity() {
        return $this->severity;
    }

    public function getLink() {
        return $this->link;
    }

    public function toNotifier() {
        $notify = Mage::getModel('adminnotification/inbox');
        $notify->setUrl($this->getLink());
        $notify->setDescription($this->getDescription());
        $notify->setTitle($this->getTitle());
        $notify->setSeverity($this->getSeverity());
        $notify->setDateAdded($this->getPubDate());
        $notify->save();
    }

}

class Wyomind_Botdefender_Model_Observer {

    public function checkStatus() {
        
       if (Mage::getStoreConfig("botdefender/settings/alert") == 1) {
            if (Mage::helper("botdefender")->apiCall()) {
                Mage::getConfig()->saveConfig("botdefender/settings/enabled", "1", "default", 0);
                Mage::getConfig()->saveConfig("botdefender/settings/alert", "0", "default", 0);
            }
        }
    }

    public function updateAlert() {
        Mage::getConfig()->saveConfig("botdefender/settings/alert", "0", "default", 0);
    }

    public function observe($user) {

        $model = new Wyomind_Botdefender_FeedReader();

        $date = $model->getLastUpdate();
        $exts = Mage::getStoreConfig("botdefender/notifications/extensions");
        $exts = $exts != null ? explode(',', $exts) : array();

        if ($date != "") {

//$model->checkUpdate();
            $rss = $model->getFeedData();
            if ($rss != NULL) {
                $items = $rss->xpath('/rss/channel/item');
                if ($items) {
                    foreach ($items as $item) {
                        $infos = $item->children();
                        $notification = new Wyomind_Botdefender_Item();
                        $notification->setTitle($infos->title);
                        $notification->setLink($infos->link);
                        $notification->setSeverity($infos->severity);
                        $notification->setDescription($infos->description);
                        $notification->setPubDate(date('Y-m-d H:i:s', (int) $infos->pubDate));

                        if ($infos->identifier == "Global" || (in_array($infos->identifier, $exts) && Mage::getConfig()->getModuleConfig('Wyomind_' . $infos->identifier)->is('active', 'true'))) {
                            $notification->toNotifier();
                        }
                    }
                }
            }
        }

        $model->setLastUpdate();
    }

}
