<?php
/**
 * Catalog Product Bestseller Block
 *
 * @author Amasty Team
 */
class Mage_Catalog_Block_Product_Bestseller extends Mage_Catalog_Block_Product_Abstract
{
    public function getCollection()
    {
        $storeId = Mage::app()->getStore()->getId();
        $collection = Mage::getResourceModel('reports/product_collection')
            ->addOrderedQty()
            ->setStoreId($storeId)
            ->addStoreFilter($storeId)
            ->setOrder('ordered_qty', 'desc');
        if (Mage::helper('catalog/product_flat')->isEnabled()) {
            $collection->getSelect()
                ->joinInner(array('e2' => 'catalog_product_flat_' . $storeId), 'e2.entity_id = e.entity_id');
        } else {
            $collection->addAttributeToSelect('*')
                ->addAttributeToSelect(array('name', 'price', 'small_image'));
        }
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
        if ($categoryId = $this->getCategory()) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $collection->addCategoryFilter($category);
        }
        $collection->setPage(1, $this->getLimit());
        return $collection;
    }
}