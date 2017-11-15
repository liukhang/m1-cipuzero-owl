<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Catlist
 */
class Amasty_Catlist_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function show($showHtml = true)
    {
        if (Mage::getStoreConfig('amcatlist/general/enable'))
        {
            $html = Mage::app()->getLayout()->createBlock('amcatlist/list', 'amcatlist_list')->toHtml();
            if ($showHtml)
            {
                Mage::helper('ambase/utils')->_echo($html);
            }
            return $html;
        }
    }
}