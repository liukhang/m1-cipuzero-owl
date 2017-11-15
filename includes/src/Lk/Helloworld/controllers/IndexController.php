<?php
class Lk_Helloworld_IndexController extends Mage_Core_Controller_Front_Action {        
    public function indexAction() {
        $_products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		foreach($_products as $product) {
        echo json_encode($product->get());
    	}
    }
}