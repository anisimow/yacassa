<?php
/**
 * @since 1.5.0
 */
class YaCassaFailModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

		$cart = $this->context->cart;
		if (!$this->module->checkCurrency($cart))
			Tools::redirect('index.php?controller=order');
 
		$this->context->smarty->assign(array(
			'this_path' => $this->module->getPathUri(),
			'this_path_bw' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
                        'post' => (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_)? $_POST:''
		));

		$this->setTemplate('payment_fail.tpl');
	}
}

