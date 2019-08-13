<?php


class Varien_Data_Form_Element_Botdefender extends Varien_Data_Form_Element_Abstract {

    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('link');
    }

    /**
     * Generates element html
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = $this->getBeforeElementHtml();
        
        
        $apiCall = Mage::helper("botdefender")->apiCall();
        $message = Mage::helper('botdefender')->getMessage($apiCall);
        

        $html .= '<a id="' . $this->getHtmlId() . '" href="' . Mage::helper("botdefender")->_BOTDEFENDER_URL . '" target="_blank">' . $message . "</a>\n";
        $html .= $this->getAfterElementHtml();
      
        
      
        return $html;
    }

    /**
     * Prepare array of anchor attributes
     *
     * @return array
     */
    public function getHtmlAttributes()
    {
        return array('charset', 'coords', 'href', 'hreflang', 'rel', 'rev', 'name',
            'shape', 'target', 'accesskey', 'class', 'dir', 'lang', 'style',
            'tabindex', 'title', 'xml:lang', 'onblur', 'onclick', 'ondblclick',
            'onfocus', 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover',
            'onmouseup', 'onkeydown', 'onkeypress', 'onkeyup');
    }
    

}
