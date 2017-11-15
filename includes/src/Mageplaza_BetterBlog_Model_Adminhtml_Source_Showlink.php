<?php
/**
 * Mageplaza_BetterBlog extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       Mageplaza
 * @package        Mageplaza_BetterBlog
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Admin source yes/no/default model
 *
 * @category    Mageplaza
 * @package     Mageplaza_BetterBlog
 * @author      Sam
 */
class Mageplaza_BetterBlog_Model_Adminhtml_Source_Showlink extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{

    /**
     * get possible values
     *
     * @access public
     * @return array
     * @author Sam
     */
    public function toOptionArray()
    {
        return array(
            array(
                'label' => Mage::helper('mageplaza_betterblog')->__('Top Link'),
                'value' => 'top_link'
            ),
            array(
                'label' => Mage::helper('mageplaza_betterblog')->__('Footer'),
                'value' => 'footer'
            ),
        );
    }

    /**
     * Get list of all available values
     *
     * @access public
     * @return array
     * @author Sam
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
}
