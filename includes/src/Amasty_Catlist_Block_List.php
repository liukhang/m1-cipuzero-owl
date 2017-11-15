<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Catlist
 */
class Amasty_Catlist_Block_List extends Mage_Core_Block_Template
{
    protected $_categories = array();
    
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('amcatlist/list.phtml');
    }
    
    public function getColumnsNumber()
    {
        $columns = Mage::getStoreConfig('amcatlist/general/columns');
        if (!$columns)
        {
            $columns = 2;
        }
        return $columns;
    }
    
    public function getTitle()
    {
        return Mage::getStoreConfig('amcatlist/general/title');
    }
    
    public function getCategories()
    {
        if (!$this->_categories)
        {
            $product = Mage::registry('current_product');
            if ($product)
            {
                $categoryIds = $product->getCategoryIds();
                if ($categoryIds)
                {
                    $categoryCollection = Mage::getModel('catalog/category')->getCollection();
                    $categoryCollection->addIdFilter($categoryIds);
                    $categoryCollection->addNameToResult();
                    if ($categoryCollection->getSize() > 0)
                    {
                        foreach ($categoryCollection as $cat)
                        {
                            $this->_categories[] = array(
                                'title' => $cat->getName(),
                                'url'   => $cat->getUrl(),
                            );
                        }
                    }
                }
            }
        }
        return $this->_categories;
    }
    
    protected function _toHtml()
    {
        if (!$this->getCategories())
        {
            return '';
        }
        return parent::_toHtml();
    }
}