<?php
class YaCassaPaymentAvisoModuleFrontController extends ModuleFrontController
{
 	public $ssl = true;
        
	/**
	 * @see FrontController::postProcess()
	 */        
        public function postProcess()
        {
                $cart = new Cart(Tools::getValue('orderNumber'));

                $total_paid = $cart->getOrderTotal(true, Cart::BOTH);
                $rub_currency_id = Currency::getIdByIsoCode('RUB');
                if($cart->id_currency != $rub_currency_id){
                    $from_currency = new Currency($cart->id_currency);
                    $to_currency = new Currency($rub_currency_id);
                    $total_paid = Tools::convertPriceFull($total_paid, $from_currency, $to_currency);
                }
                
                //check order in database
                $callbackParams = array('action'=>'paymentAviso', 'orderSumAmount'=>$total_paid, 'shopId'=>Configuration::get('YC_SHOPID'),
                                        'password'=>Configuration::get('YC_SHOPPASSWORD'));
                
                require_once dirname(dirname(dirname(__FILE__))).'/lib/YandexMoneyObj.php';
                $YandexMoneyObj = new YandexMoneyObj($this->module, $this->module->demo_mode);
                
                //if Ok  rdate order
                if($YandexMoneyObj->paymentAviso($callbackParams)){                   
                    $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_PAYMENT'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, NULL, array(), NULL, false, $cart->secure_key);
                }
                $YandexMoneyObj->sendPaymentAvisoCode();
        }   
}

