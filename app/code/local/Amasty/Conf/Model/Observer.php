<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Conf
*/
class Amasty_Conf_Model_Observer 
{
    public function onListBlockHtmlBefore($observer)//core_block_abstract_to_html_after    
    {
        if (($observer->getBlock() instanceof Mage_Catalog_Block_Product_List) && Mage::getStoreConfig('amconf/list/enable_list')) {
            $html = $observer->getTransport()->getHtml();
            preg_match_all("/product-price-([0-9]+)/", $html, $productsId) ;
            if(!$productsId[0]){
               preg_match_all("/price-including-tax-([0-9]+)/", $html, $productsId) ;
            }
            
            $productsId[1] = array_unique($productsId[1]);

            foreach ($productsId[1] as $key => $productId){  
                $_product = Mage::getModel('catalog/product')->load($productId);
               
                if($_product->isSaleable() && $_product->isConfigurable()){
                    $addButton = (Mage::getStoreConfig('amconf/product_image_size/have_button'))?"(.*?)/button>":"";
                    $template = '@(product-price-'.$productId.'"(.*?)div>)' . $addButton . '@s';
                    preg_match_all($template, $html, $res);
                    if(!$res[0]){
                         $template = '@(price-including-tax-'.$productId.'"(.*?)div>)' . $addButton . '@s';
                         preg_match_all($template, $html, $res);
                         if(!$res[0]){
                             $template = '@(price-excluding-tax-'.$productId.'"(.*?)div>)' . $addButton . '@s';
                             preg_match_all($template, $html, $res);
                        }
                    }
                    if($res[0]){
                         $replace =  Mage::helper('amconf')->getHtmlBlock($_product, $res[1][0]);
			             unset($_product);
                         if(strpos($html, $replace) === false) {
                            $html= str_replace($res[0][0], $replace, $html);
                         }
			             unset($replace);
                    }
                }
            }
            $observer->getTransport()->setHtml($html);
        }
    }
    
    protected function getSuperProductAttributesJS($product_id){
        $collection = Mage::getModel('amconf/product_attribute')->getCollection();
        
        $collection->getSelect()->join( array(
            'prodcut_super_attr' => $collection->getTable('catalog/product_super_attribute')),
                'main_table.product_super_attribute_id = prodcut_super_attr.product_super_attribute_id', 
                array('prodcut_super_attr.product_id')
            );
        
        $collection->addFieldToFilter('prodcut_super_attr.product_id', $product_id);

        $attributes = $collection->getItems();
        
        $ids = array();

        foreach($attributes as $attribute){
            if ($attribute->getUseImageFromProduct()){
                $ids[] = $attribute->getProductSuperAttributeId();
            }
        }

        $js = '<script>
                Event.observe(window, \'load\', function(){
                    var ids = '.  Zend_Json::encode($ids).';
                    checkUseImageProducts(ids);
                })
            </script>
        ';
        
        return $js;
    }
    
    public function onSuperProductAttributesConfigurationAfter($observer) {//core_block_abstract_to_html_after   
        if (($observer->getBlock() instanceof Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config)){
            $html = $observer->getTransport()->getHtml();
            $product_id = NULL;
            preg_match("/product\/([0-9]+)\//", $html, $productsId) ;
            if (isset($productsId[1])){
                
                $product_id = $productsId[1];

                $from = '<label for="__id___label_use_default">';
                $to = '</label>';

                $fromInd = strpos($html, $from);
                $toInd = strpos($html, $to, $fromInd) + strlen($to);

                $str = substr($html, $fromInd, $toInd - $fromInd);

                $controls = '&nbsp;&nbsp;&nbsp;&nbsp;<input onclick=\'return onUseImageProductClick(this);\' type=\'checkbox\' rel="use_image_from_product" id="__id___use_image_from_product" />&nbsp;'.
                        '<label for="__id___use_image_from_product">'. Mage::helper('amconf')->__('Use image from product') .'</label>';

                $html = str_replace($str, $str.$controls, $html);

                $html .= $this->getSuperProductAttributesJS($product_id);

                $observer->getTransport()->setHtml($html);
            }
        }
    }
    
    public function onSuperProductAttributesPrepareSave($observer){
        
        $configurable_attributes_data = Mage::helper('core')->jsonDecode($observer->getRequest()->getPost('configurable_attributes_data'));
        if (is_array($configurable_attributes_data)){
            foreach($configurable_attributes_data as $attribute){
                
                if ($attribute['id'] !== NULL){
                    $confAttr = Mage::getModel('amconf/product_attribute')->load($attribute['id'], 'product_super_attribute_id');

                    if (!$confAttr->getId())
                    {
                        $confAttr->setProductSuperAttributeId($attribute['id']);
                    }
                    $use_image_from_product  = isset($attribute['use_image_from_product']) ? intval($attribute['use_image_from_product']) : 0;

                    $confAttr->setUseImageFromProduct($use_image_from_product);
                    $confAttr->save();
                }
            }
        }
        
    } 
    
    public function catalogProductAttributeSave($observer)
    {   
        if (Mage::app()->getRequest()->isPost())
        {
            // saving attribute 'use_image' property
            $entityAttributeId = Mage::registry('entity_attribute')? Mage::registry('entity_attribute')->getId() : Mage::app()->getRequest()->getPost('entity_attribute');
            if(!$entityAttributeId) return;
            $confAttr = Mage::getModel('amconf/attribute')->load($entityAttributeId, 'attribute_id');
            if (!$confAttr->getId())
            {
                $confAttr->setAttributeId($entityAttributeId);
            }

            $confAttr->setUseImage(intval(Mage::app()->getRequest()->getPost('amconf_useimages')));
            
            $confAttr->setSmallWidth(intval(Mage::app()->getRequest()->getPost('small_width')));
            $confAttr->setSmallHeight(intval(Mage::app()->getRequest()->getPost('small_height')));
            
            $confAttr->setUseTooltip(intval(Mage::app()->getRequest()->getPost('amconf_usetooltip')));
            
            if(intval(Mage::app()->getRequest()->getPost('amconf_usetooltip'))) {
                $confAttr->setBigWidth(intval(Mage::app()->getRequest()->getPost('big_width')));
                $confAttr->setBigHeight(intval(Mage::app()->getRequest()->getPost('big_height')));
            }
            
            
            
            
            
            $confAttr->setUseTitle(intval(Mage::app()->getRequest()->getPost('amconf_show_title')));
            
            $confAttr->setCatSmallWidth(intval(Mage::app()->getRequest()->getPost('cat_small_width')));
            $confAttr->setCatSmallHeight(intval(Mage::app()->getRequest()->getPost('cat_small_height')));
            
            $confAttr->setCatUseTooltip(intval(Mage::app()->getRequest()->getPost('amconf_use_cat_tooltip')));
            
            if(intval(Mage::app()->getRequest()->getPost('amconf_use_cat_tooltip'))) {
                $confAttr->setCatBigWidth(intval(Mage::app()->getRequest()->getPost('cat_big_width')));
                $confAttr->setCatBigHeight(intval(Mage::app()->getRequest()->getPost('cat_big_height')));
            }
            
            $confAttr->save();
        }
        
        $uploadDir = Mage::getBaseDir('media') . DIRECTORY_SEPARATOR . 
                                                    'amconf' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
                                                    
        /**
        * Deleting
        */
        $toDelete = Mage::app()->getRequest()->getPost('amconf_icon_delete');
        if ($toDelete)
        {
            foreach ($toDelete as $optionId => $del)
            {
                if ($del)
                {
                    @unlink($uploadDir . $optionId . '.jpg');
                }
            }
        }
        
        /**
        * Uploading files
        */
        if (isset($_FILES['amconf_icon']) && isset($_FILES['amconf_icon']['error']))
        {
            foreach ($_FILES['amconf_icon']['error'] as $optionId => $errorCode)
            {
                if (UPLOAD_ERR_OK == $errorCode)
                {
                    move_uploaded_file($_FILES['amconf_icon']['tmp_name'][$optionId], $uploadDir . $optionId . '.jpg');
                    if (!file_exists($uploadDir . $optionId . '.jpg'))
                    {
                        Mage::getSingleton('catalog/session')->addSuccess('File was not uploaded. Please check permissions to folder media/amconf/images(need 0777 recursively)');
                    }                    
                }
            }
        }
    }    
    
   
}
