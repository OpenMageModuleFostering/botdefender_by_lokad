<?php

class Wyomind_Botdefender_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Notification_Toolbar {

    function __construct() {


        if (version_compare(Mage::getVersion(), '1.4.0', '<')) {

            if ($this->_toHtml(null) != null)
                Mage::getSingleton("core/session")->addNotice($this->_toHtml(null));
        }
    }

    function _toHtml($className = 'notification-global') {
       
        if (Mage::getStoreConfig("botdefender/settings/alert") == "1") {
            $html.= "

            <div class='$className'>
                <span class='f-right'>
                </span>
                    <b>".Mage::helper("botdefender")->_BOTDEFENDER_DOWNTIME."</b>
                     <br><a href='" . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/botdefender") . "'> Re-enable BotDefender</a>
            </div>";
        } else {


            $html = null;
            $status = Mage::helper("botdefender")->apiCall();
            if ($status == -2) {
                $html.= "
            <div class='$className'>
                <span class='f-right'>
                </span>
                    <b>BotDefender</b> : <a target='_blank' href='" . Mage::helper("botdefender")->_BOTDEFENDER_URL . "'>" . Mage::helper("botdefender")->getMessage($status) . "</a>
                     
                     then <a href='" . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/botdefender") . "'> Complete the BotDefender installation</a>
                 </div>";
            } elseif ($status < 0 && Mage::getStoreConfig("botdefender/settings/enabled")) {
                $html.= "
            <div class='$className'>
                <span class='f-right'>
                </span>
                    <b>BotDefender</b> : " . Mage::helper("botdefender")->getMessage($status) . "
                     <br><a href='" . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/botdefender") . "'> Complete the BotDefender installation</a>
            </div>";
            } else if (!Mage::getStoreConfig("botdefender/settings/enabled")) {
                $html.= "
            <div class='$className'>
                <span class='f-right'>
                </span>
                    <b>BotDefender is disabled</b>, your data are not protected!
                     <br><a href='" . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/botdefender") . "'> Enable BotDefender</a>
            </div>";
            } else if (Mage::getStoreConfig("botdefender/settings/debug") && Mage::getStoreConfig("botdefender/settings/enabled")) {
                $html.= "

            <div class='$className'>
                <span class='f-right'>
                </span>
                    <b>BotDefender debugger is active!</b>
                     <br><a href='" . Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/botdefender") . "'> Disable the debugger</a>
            </div>";
            }
        }
        return $html;
    }

}
