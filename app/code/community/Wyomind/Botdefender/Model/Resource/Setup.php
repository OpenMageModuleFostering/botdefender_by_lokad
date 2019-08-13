<?php

class Wyomind_Botdefender_Model_Resource_Setup extends Mage_Core_Model_Resource_Setup {

    public function applyUpdates() {



        Mage::getConfig()->saveConfig("botdefender/notifications/extensions", "botdefender", "default", "0");


        return parent::applyUpdates();
    }

}