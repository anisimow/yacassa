<?php
/**
 * @since 1.5.0
 */
class YaCassaSuccessModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		parent::initContent();

                $cart = new Cart(Tools::getValue('orderNumber'));
                if (!$this->module->checkCurrency($cart))
                    Tools::redirect('index.php?controller=order');
                
                if(!($ordernumber=Order::getOrderByCartId($cart->id))) {                
                    $this->context->smarty->assign(array(
                            'this_path' => $this->module->getPathUri(),
                            'this_path_bw' => $this->module->getPathUri(),
                            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/',
                            'ordernumber' => $cart->id
                    ));
                    $this->setTemplate('waitingPayment.tpl');
                }else{
                    $customer = new Customer((int)$cart->id_customer);
                    if ($customer->id != $this->context->cookie->id_customer)
                        webmoney::validateAnsver($this->module->l('You are not logged in'));
                    Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation.php?key=' . $customer->secure_key . '&id_cart=' . (int)($cart->id) . '&id_module=' . (int)$this->module->id . '&id_order=' . (int)$ordernumber);
                }
		//$this->setTemplate('payment_success.tpl');
	}
}

