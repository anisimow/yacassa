<?php

/**
 * Description of YandexMoneyObj
 * 
 * @author Anisimow
 * @manual https://money.yandex.ru/doc.xml?id=526537
 */

class YandexMoneyObj {
	public $test_mode = false;
        public $error = array();
        public $module;
        
        //0 - success; 1- autentification error; 100 - denided order; 200- error request
        public $code = 0;
        
        //data from request
        public $shopId;
        public $invoiceId = 0;
        public $orderSumAmount = 0.0;
        public $orderSumCurrencyPaycash = 0;
        public $orderSumBankPaycash = 0;
        public $customerNumber = 0;
        public $md5;
        public $orderNumber;
        
	/* constructor
        /* $module - YaCassaPaymentModuleFrontController object
         * $test_mode - bool
        */ 
	public function __construct($module, $test_mode=false){

                $this->test_mode = $test_mode;
                $this->module = $module;
                
                $this->shopId = Tools::getValue('shopId'); 
                $this->invoiceId = Tools::getValue('invoiceId',0) ;
                // order price
                $this->orderSumAmount = Tools::getValue('orderSumAmount', 0.00);
                // currency cod
                $this->orderSumCurrencyPaycash = Tools::getValue('orderSumCurrencyPaycash', 0);
                // Код процессингового центра Оператора для суммы заказа
                $this->orderSumBankPaycash = Tools::getValue('orderSumBankPaycash', 0);
                // Customer Id
                $this->customerNumber = Tools::getValue('customerNumber', 0);
                // MD5
                $this->md5 =  Tools::getValue('md5', md5("Yandex.Money demo mode"));
                // order nuber
                $this->orderNumber=Tools::getValue('orderNumber');                
	}

	public static function GetFormUrl($test_mode){
		if ($test_mode){
                    return 'https://demomoney.yandex.ru/eshop.xml';
                } else {
                    return 'https://money.yandex.ru/eshop.xml';
                }
	}

	public function checkSign($callbackParams){
            // Пишем содержимое обратно в файл
		$string = $callbackParams['action'].';'.$this->orderSumAmount.';'.$this->orderSumCurrencyPaycash.';'.$this->orderSumBankPaycash.';'.$callbackParams['shopId'].';'.$this->invoiceId.';'.$this->customerNumber.';'.$callbackParams['password'];
		$md5 = md5($string);
                //file_put_contents(dirname(__FILE__).'/people.txt',$string.'  md5 - '.$this->md5 .'  '.$md5, FILE_APPEND);
		return (strcasecmp($this->md5, $md5) == 0);
	}
        public function checkParams($callbackParams){
		$this->code = 0;
                                
                //check shopId
                if($this->shopId != $callbackParams['shopId']){
                    $this->code = 100;
                    $this->error[] = $this->module->l('Different shopId`s');  
                }
                
                //check paid summa
                if(round($this->orderSumAmount) < round($callbackParams['orderSumAmount'])){
                    $this->code = 100;
                    $this->error[] = $this->module->l('Different total summa');
                }
               
                //check md5
		if (!$this->checkSign($callbackParams)){
                    $this->code = 1;
                    $this->error[] = $this->module->l('Different md5');
		}                
        }
	public function checkOrder($callbackParams, $sendCode=true){ 
            
                $this->checkParams($callbackParams);
                
		if ($sendCode){
			$this->sendCheckOrderCode();	
			
		}else{
			return ($this->code==0);
		}
	}

	public function sendCheckOrderCode(){
                header("Content-type: text/xml; charset=utf-8");
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<checkOrderResponse performedDatetime="'.date("c").'" code="'.$this->code.'" invoiceId="'.$this->invoiceId.'" shopId="'.$this->shopId.'" message="'.substr(implode(', ', $this->error),0,254).'" />';
		echo $xml;
// Пишем содержимое обратно в файл
file_put_contents(dirname(__FILE__).'/people.txt', $xml, FILE_APPEND);
                //Tools::redirect(self::GetFormUrl($this->test_mode), __PS_BASE_URI__, null, "Content-type: text/xml; charset=utf-8");
                exit;
	}
        
	public function paymentAviso($callbackParams, $sendCode=false){ 
		
                $this->checkParams($callbackParams);
                
		if ($sendCode){
			$this->sendPaymentAvisoCode();	
			
		}else{
			return ($this->code==0);
		}
	}   
	public function sendPaymentAvisoCode(){
                header("Content-type: text/xml; charset=utf-8");
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<paymentAvisoResponse performedDatetime="'.date("c").'" code="'.$this->code.'" invoiceId="'.$this->invoiceId.'" shopId="'.$this->shopId.'" />';
		echo $xml;
                //tools::redirect(self::GetFormUrl($this->test_mode), __PS_BASE_URI__, null, "Content-type: text/xml; charset=utf-8");
                exit;
	}        
}
