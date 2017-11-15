<?php
/**
 * @author Liu Khang
 * @copyright Copyright (c) 2017
 * @package MessageSuscess_Lk
 */
class MessageSuscess_Lk_IndexController extends Mage_Core_Controller_Front_Action
{
 /**
 * index action
 */
 public function indexAction()
 {
     $this->loadLayout();
     $this->renderLayout();
 }
}