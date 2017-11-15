<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Conf
*/
class Amasty_Conf_Block_Catalog_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{
    protected $_optionProducts;
    const TINY_SIZE = 30;
    const SMALL_SIZE = 50;
    const BIG_SIZE = 100;
    
    protected function _afterToHtml($html)
    {
        $attributeIdsWithImages = Mage::registry('amconf_images_attrids');
        $html = parent::_afterToHtml($html);
        if ('product.info.options.configurable' == $this->getNameInLayout())
        {
            if (Mage::getStoreConfig('amconf/general/hide_dropdowns') )
            {
                if (!empty($attributeIdsWithImages))
                {
                    foreach ($attributeIdsWithImages as $attrIdToHide)
                    {
                        $html = preg_replace('@(id="attribute' . $attrIdToHide . ')(-)?([0-9]*)(")(\s+)(class=")(.*?)(super-attribute-select)(-)?([0-9]*)@', '$1$2$3$4$5$6$7$8$9$10 no-display', $html);
                    }
                }
            }            

            $_useSimplePrice =  (Mage::helper('amconf')->getConfigUseSimplePrice() == 2 ||(Mage::helper('amconf')->getConfigUseSimplePrice() == 1 AND $this->getProduct()->getData('amconf_simple_price')))? true : false;
            
            $simpleProducts = $this->getProduct()->getTypeInstance(true)->getUsedProducts(null, $this->getProduct());
            if ($this->_optionProducts)
            {
                $noimgUrl = Mage::helper('amconf')->getNoimgImgUrl();
                $this->_optionProducts = array_values($this->_optionProducts);
                foreach ($simpleProducts as $simple)
                {
                    /* @var $simple Mage_Catalog_Model_Product */
                    $key = array();
                    for ($i = 0; $i < count($this->_optionProducts); $i++)
                    {
                        foreach ($this->_optionProducts[$i] as $optionId => $productIds)
                        {
                            if (in_array($simple->getId(), $productIds))
                            {
                                $key[] = $optionId;
                            }
                        }
                    }
                    if ($key)
                    {
                        $strKey = implode(',', $key);
                        // @todo check settings:
                        // array key here is a combination of choosen options
                        $confData[$strKey] = array(
                            'short_description' => $this->helper('catalog/output')->productAttribute($simple, nl2br($simple->getShortDescription()), 'short_description'),
                            'description'       => $this->helper('catalog/output')->productAttribute($simple, $simple->getDescription(), 'description'),
                            'not_is_in_stock'       => !$simple->isSaleable(),
                            'attributes'        => Mage::app()->getLayout()->createBlock('catalog/product_view_attributes', 'product.attributes', array('template' => "catalog/product/view/attributes.phtml"))->setProduct($simple)->toHtml()
			                 //'sku'       	=> $simple->getSku(),
                        );
                        $confData[$strKey]['name'] = $simple->getName();
                 
                        
                        if ($_useSimplePrice)
                        {
                            $tierPriceHtml = $this->getTierPriceHtml($simple);
                            $confData[$strKey]['price_html'] = str_replace('product-price-' . $simple->getId(), 'product-price-' . $this->getProduct()->getId(), $this->getPriceHtml($simple) . $tierPriceHtml);
                            $confData[$strKey]['price_clone_html'] = str_replace('product-price-' . $simple->getId(), 'product-price-' . $this->getProduct()->getId(), $this->getPriceHtml($simple, false, '_clone') . $tierPriceHtml);

				            // the price value is required for product list/grid
				            $confData[$strKey]['price'] = $simple->getFinalPrice();
                        }
                        
                        if ($simple->getImage() && $simple->getImage() !="no_selection" && Mage::getStoreConfig('amconf/general/reload_images'))
                        {
				            $confData[$strKey]['media_url'] = $this->getUrl('amconf/media', array('id' => $simple->getId()));
                            if(Mage::getStoreConfig('amconf/general/oneselect_reload')) {
                                $k = $strKey;
                                if(strpos($strKey, ',')){
                                    $k = substr($strKey, 0, strpos($strKey, ','));
                                }
                                if(!(array_key_exists($k, $confData) && array_key_exists('media_url', $confData[$k]))){
                                    $confData[$k]['media_url'] = $confData[$strKey]['media_url']; 
                                }
                            }
                            else{
                                //for changing only after first select 
                            }
                        } elseif ($noimgUrl) 
                        {
                            $confData[$strKey]['noimg_url'] = $noimgUrl;
                        }
                        //for >3
                        if(Mage::getStoreConfig('amconf/general/oneselect_reload')){
                            $pos = strpos($strKey, ",");
                            if($pos){
                                $pos = strpos($strKey, ",", $pos+1);
                                if($pos){
                                    $newKey = substr($strKey, 0, $pos);
                                    $confData[$newKey] =  $confData[$strKey];   
                                }
                            }
                            
                        }
                        
                    }
                }
				if (Mage::getStoreConfig('amconf/general/show_clear'))
				{
					$html = '<a href="#" onclick="javascript: spConfig.clearConfig(); return false;">' . $this->__('Reset Configuration') . '</a>' . $html;
				}
				$html = '<script type="text/javascript">
							var amConfAutoSelectAttribute = ' . intval(Mage::getStoreConfig('amconf/general/auto_select_attribute')) . ';
							confData = new AmConfigurableData(' . Zend_Json::encode($confData) . ');
						    confData.textNotAvailable = "' . $this->__('Choose previous option please...') . '";
						    confData.mediaUrlMain = "' . $this->getUrl('amconf/media', array('id' => $this->getProduct()->getId())) . '";
                            confData.oneAttributeReload = "' . (boolean) Mage::getStoreConfig('amconf/general/oneselect_reload') . '";
						    confData.imageContainer = "' .  Mage::getStoreConfig('amconf/general/image_container') . '";
						    confData.useSimplePrice = "' . intval($_useSimplePrice)  . '";
                    </script>'. $html;
                
                if (Mage::getStoreConfig('amconf/general/hide_dropdowns'))
                {
                    $html .= '<script type="text/javascript">Event.observe(window, \'load\', spConfig.processEmpty);</script>';
                }              
            }
        }
        
        return $html;
    }
    
    protected function getImagesFromProductsAttributes(){
        $collection = Mage::getModel('amconf/product_attribute')->getCollection();
        $collection->addFieldToFilter('use_image_from_product', 1);
        
        $collection->getSelect()->join( array(
            'prodcut_super_attr' => $collection->getTable('catalog/product_super_attribute')),
                'main_table.product_super_attribute_id = prodcut_super_attr.product_super_attribute_id', 
                array('prodcut_super_attr.attribute_id')
            );
        
        $collection->addFieldToFilter('prodcut_super_attr.product_id', $this->getProduct()->getEntityId());
        
        
        $attributes = $collection->getItems();
        $ret = array();
        
        foreach($attributes as $attribute){
            $ret[] = $attribute->getAttributeId();
        }
        
        return $ret;
    }
    
    public function getJsonConfig()
    {
        $attributeIdsWithImages = array();
        $jsonConfig = parent::getJsonConfig();
        $config = Zend_Json::decode($jsonConfig);
        $productImagesAttributes = $this->getImagesFromProductsAttributes();
      
        foreach ($config['attributes'] as $attributeId => $attribute)
        {
            $attr = Mage::getModel('amconf/attribute')->load($attributeId, 'attribute_id');
            if ($attr->getUseImage())
            {
                $attributeIdsWithImages[] = $attributeId;
                $config['attributes'][$attributeId]['use_image'] = 1;
                $config['attributes'][$attributeId]['config'] = $attr->getData();
                

                
                //calvin 20150815 if attribute 134 use tiny size for size images
                if($attributeId == 134) {
                    $smWidth = $attr->getSmallWidth() != "0"? $attr->getSmallWidth(): self::TINY_SIZE;
                    $smHeight = $attr->getSmallHeight()!= "0"? $attr->getSmallHeight(): self::TINY_SIZE;
                } else {
                    $smWidth = $attr->getSmallWidth() != "0"? $attr->getSmallWidth(): self::SMALL_SIZE;
                    $smHeight = $attr->getSmallHeight()!= "0"? $attr->getSmallHeight(): self::SMALL_SIZE;
                }
                
                $bigWidth = $attr->getBigWidth()!= "0"? $attr->getBigWidth(): self::BIG_SIZE;
                $bigHeight = $attr->getBigHeight()!= "0"? $attr->getBigHeight(): self::BIG_SIZE;
            
                foreach ($attribute['options'] as $i => $option)
                {
                    $this->_optionProducts[$attributeId][$option['id']] = $option['products'];
                    if (in_array($attributeId, $productImagesAttributes)){
                        
                        foreach($option['products'] as $product_id){
                               
                            $product = Mage::getModel('catalog/product')->load($product_id);
                            $config['attributes'][$attributeId]['options'][$i]['image'] = 
                                (string)Mage::helper('catalog/image')->init($product, 'image')->resize($smWidth, $smHeight);
                            $config['attributes'][$attributeId]['options'][$i]['bigimage'] = 
                                (string)Mage::helper('catalog/image')->init($product, 'image')->resize($bigWidth, $bigHeight);
                            break;
                        }
                    }
                    else {
                        $config['attributes'][$attributeId]['options'][$i]['image'] = 
                            Mage::helper('amconf')->getImageUrl($option['id'], $smWidth, $smHeight);
                        $config['attributes'][$attributeId]['options'][$i]['bigimage'] = 
                            Mage::helper('amconf')->getImageUrl($option['id'], $bigWidth, $bigHeight);
                    }
                }
            }
        }
        Mage::unregister('amconf_images_attrids');
        Mage::register('amconf_images_attrids', $attributeIdsWithImages, true);

        return Zend_Json::encode($config);
    }
    
    public function getAddToCartUrl($product, $additional = array())
    {
        if ($this->hasCustomAddToCartUrl()) {
            return $this->getCustomAddToCartUrl();
        }
        if ($this->getRequest()->getParam('wishlist_next')){
            $additional['wishlist_next'] = 1;
        }
        $addUrlKey = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;
        $addUrlValue = Mage::getUrl('*/*/*', array('_use_rewrite' => true, '_current' => true));
        $additional[$addUrlKey] = Mage::helper('core')->urlEncode($addUrlValue);
        return $this->helper('checkout/cart')->getAddUrl($product, $additional);
    }
    
    public function isSalable($product = null){
         $salable = parent::isSalable($product);
 
        if ($salable !== false) {
            $salable = false;
            if (!is_null($product)) {
                $this->setStoreFilter($product->getStoreId(), $product);
            }
 
            if (!Mage::app()->getStore()->isAdmin() && $product) {
                $collection = $this->getUsedProductCollection($product)
                    ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->setPageSize(1)
                    ;
                if ($collection->getFirstItem()->getId()) {
                    $salable = true;
                }
            } else {
                foreach ($this->getUsedProducts(null, $product) as $child) {
                    if ($child->isSalable()) {
                        $salable = true;
                        break;
                    }
                }
            }
        }
 
        return $salable;
    }
    
    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = array();
            $allProducts = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
            foreach ($allProducts as $product) {
                /**
                * Should show all products (if setting set to Yes), but not allow "out of stock" to be added to cart
                */
                 if ($product->isSaleable() || Mage::getStoreConfig('amconf/general/out_of_stock') ) {
                    if ($product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                    {
                        $products[] = $product;
                    }
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }
   
}