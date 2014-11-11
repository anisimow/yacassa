<?php
/*
*
*/

if (!defined('_PS_VERSION_'))
	exit;

require_once 'lib/YandexMoneyObj.php';
    
class YaCassa extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
        
        public function __construct()
	{
		$this->name = 'yacassa';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
		$this->author = 'Anisimow';
		$this->controllers = array('payment', 'check', 'paymentaviso', 'success', 'fail');
		
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$config = Configuration::getMultiple(array('YC_SHOPID', 'YC_SCID', 'YC_SHOPPASSWORD', 'YC_TEST_MODE'));
		if (!empty($config['YC_SHOPID']))
			$this->shopid = $config['YC_SHOPID'];
		if (!empty($config['YC_SCID']))
			$this->scid = $config['YC_SCID'];
		if (!empty($config['YC_SHOPPASSWORD']))
			$this->shoppassword = $config['YC_SHOPPASSWORD'];
                if(!empty($config['YC_DEMO_MODE'])){
                    $this->demo_mode = true;
                }else{
                     $this->demo_mode = false;
                }
                
		$this->bootstrap = true;
		parent::__construct();	

		$this->displayName = $this->l('Yandex cassa');
		$this->description = $this->l('Accept payments for your products via yandex cassa.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		if (!isset($this->shopid) || !isset($this->scid) || !isset($this->shoppassword))
			$this->warning = $this->l('ShopId, SCID and shoppassword must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('YC_SHOPID')
				|| !Configuration::deleteByName('YC_SCID')
				|| !Configuration::deleteByName('YC_SHOPPASSWORD')
                                || !!Configuration::deleteByName('YC_ALLOW_YANDEXMONEY')
                                || !!Configuration::deleteByName('YC_ALLOW_BANK_CARD')
                                || !!Configuration::deleteByName('YC_ALLOW_MOBILE')
                                || !!Configuration::deleteByName('YC_ALLOW_TERMINAL')
                                || !!Configuration::deleteByName('YC_ALLOW_WEBMONEY')
                                || !!Configuration::deleteByName('YC_ALLOW_SBERBANK')
                                || !!Configuration::deleteByName('YC_ALLOW_MPOS')                        
                                || !!Configuration::deleteByName('YC_DEMO_MODE')
				|| !parent::uninstall())
			return false;
		return true;
	}

	private function _postValidation()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			if (!Tools::getValue('YC_SHOPID'))
				$this->_postErrors[] = $this->l('Shop ID are required.');
			elseif (!Tools::getValue('YC_SCID'))
				$this->_postErrors[] = $this->l('SCID is required.');
                        elseif (!Tools::getValue('YC_SHOPPASSWORD'))
				$this->_postErrors[] = $this->l('Shop password is required.');
                        //debug mode
                        if(Tools::getValue('YC_DEMO_MODE')&&(!Validate::isBool(Tools::getValue('YC_DEMO_MODE'))))
                            $this->_postErrors[] = $this->l('Invalid').' '.$this->l('demo mode');
		}
	}

	private function _postProcess()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			Configuration::updateValue('YC_SHOPID', Tools::getValue('YC_SHOPID'));
			Configuration::updateValue('YC_SCID', Tools::getValue('YC_SCID'));
			Configuration::updateValue('YC_SHOPPASSWORD', Tools::getValue('YC_SHOPPASSWORD'));
                        Configuration::updateValue('YC_ALLOW_YANDEXMONEY', Tools::getValue('YC_ALLOW_YANDEXMONEY'));
                        Configuration::updateValue('YC_ALLOW_BANK_CARD', Tools::getValue('YC_ALLOW_BANK_CARD'));
                        Configuration::updateValue('YC_ALLOW_MOBILE', Tools::getValue('YC_ALLOW_MOBILE'));
                        Configuration::updateValue('YC_ALLOW_TERMINAL', Tools::getValue('YC_ALLOW_TERMINAL'));
                        Configuration::updateValue('YC_ALLOW_WEBMONEY', Tools::getValue('YC_ALLOW_WEBMONEY'));
                        Configuration::updateValue('YC_ALLOW_SBERBANK', Tools::getValue('YC_ALLOW_SBERBANK'));
                        Configuration::updateValue('YC_ALLOW_MPOS', Tools::getValue('YC_ALLOW_MPOS'));                              
                        Configuration::updateValue('YC_DEMO_MODE', Tools::getValue('YC_DEMO_MODE'));
		}
		$this->_html .= $this->displayConfirmation($this->l('Settings updated'));
	}

	private function _displayInfo()
	{
                $this->smarty->assign(array(
                        'ssl_enabled' => Configuration::get('PS_SSL_ENABLED'),
			'check' => str_replace('http://', 'https://', $this->context->link->getModuleLink('yacassa', 'check', array(), true)),
			'paymentAviso' => str_replace('http://', 'https://', $this->context->link->getModuleLink('yacassa', 'paymentaviso', array(), true)),
			'success' => $this->context->link->getModuleLink('yacassa', 'success'),
                        'fail' => $this->context->link->getModuleLink('yacassa', 'fail')
		));
		return $this->display(__FILE__, 'infos.tpl');
	}

	public function getContent()
	{
		if (Tools::isSubmit('btnSubmit'))
		{
			$this->_postValidation();
			if (!count($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors as $err)
					$this->_html .= $this->displayError($err);
		}
		else
			$this->_html .= '<br />';
		
		$this->_html .= $this->_displayInfo();
		$this->_html .= $this->renderForm();

		return $this->_html;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;
                
                $cart = $this->context->cart;
                $customer = new Customer($cart->id_customer);
                
                $currency_rub = new Currency(Currency::getIdByIsoCode('RUB'));

                $total_to_pay = $cart->getOrderTotal(true, Cart::BOTH);
                if($cart->id_currency!=$currency_rub->id)
                {
                    //$total_to_pay=$total_to_pay/$currency->conversion_rate*$currency_rub->conversion_rate;
                    $total_to_pay = Tools::convertPriceFull($total_to_pay, $cart->id_currency, $currency_rub->id);
                }
//                if(empty($this->currentOrder)){
//                     $order= new Order();
//                     $this->currentOrder = $order->id;
//                }
		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_bw' => $this->_path,
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
                        'shopId' => Configuration::get('YC_SHOPID'),
                        'scid' => Configuration::get('YC_SCID'),
                        'sum' => $total_to_pay,
                        'customerNumber' => $cart->id_customer,
                        //'orderNumber' => $this->currentOrder,
                        'orderNumber' => $cart->id,
                        //'cps_phone' => 
                        'cps_email' => $customer->email,
                        'shopSuccessURL' => $this->context->link->getModuleLink('yacassa', 'success'),
                        'shopFailURL' => $this->context->link->getModuleLink('yacassa', 'fail'),
                        'paymentTypes' => array(
                                'PC'=>(array('allow'=>Configuration::get('YC_ALLOW_YANDEXMONEY'), 'method' => $this->l('YandexMoney'))),
                                'AC' => (array('allow'=>Configuration::get('YC_ALLOW_BANK_CARD'), 'method' => $this->l('Bank card'))),
                                'MC' => (array('allow'=>Configuration::get('YC_ALLOW_MOBILE'), 'method' => $this->l('Mobile phone'))),
                                'GP' => (array('allow'=>Configuration::get('YC_ALLOW_TERMINAL'), 'method' => $this->l('Therminal'))),
                                'WM' => (array('allow'=>Configuration::get('YC_ALLOW_WEBMONEY'), 'method' => $this->l('Webmoney'))),
                                'SB' => (array('allow'=>Configuration::get('YC_ALLOW_SBERBANK'),'method' => $this->l('Sberbank'))),
                                'MP' => (array('allow'=>Configuration::get('YC_ALLOW_MPOS'), 'method' => $this->l('Mobile terminal')))
                                ),
                        'demo_on' => Configuration::get('YC_DEMO_MODE'),
                        'action' => YandexMoneyObj::GetFormUrl(Configuration::get('YC_DEMO_MODE'))
		));
		return $this->display(__FILE__, 'payment.tpl');
	}
	
	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}
	
	public function renderForm()
        {
		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('The thechnical identificators of your shop: '),
					'icon' => 'icon-envelope'
				),
				'input' => array(
					array(
						'type' => 'text',
						'label' => $this->l('Shop id'),
						'name' => 'YC_SHOPID',
                                                'desc' => $this->l('Shop id. You get when pass registration on yandexmoney')
					),
					array(
						'type' => 'text',
						'label' => $this->l('scid'),
						'name' => 'YC_SCID',
						'desc' => $this->l('SCID. You get when pass registration on yandexmoney')
					),
					array(
						'type' => 'text',
						'label' => $this->l('Is a private password (20 random characters) used to calculate the cryptographic hash'),
						'name' => 'YC_SHOPPASSWORD'
					),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from yandexmoney'),
                                                'name' => 'YC_ALLOW_YANDEXMONEY',
                                                'desc' => $this->l('Turn on to allow payment from yandexmoney'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'yandexmoney_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'yandexmoney_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from bank card'),
                                                'name' => 'YC_ALLOW_BANK_CARD',
                                                'desc' => $this->l('Turn on to allow payment from bank card'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'bank_card_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'bank_card_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from mobile phone'),
                                                'name' => 'YC_ALLOW_MOBILE',
                                                'desc' => $this->l('Turn on to allow payment from mobile phone'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'mobile_phone_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'mobile_phone_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from cassa or terminal'),
                                                'name' => 'YC_ALLOW_TERMINAL',
                                                'desc' => $this->l('Turn on to allow payment from cassa or terminal'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'terminal_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'terminal_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from Webmoney'),
                                                'name' => 'YC_ALLOW_WEBMONEY',
                                                'desc' => $this->l('Turn on to allow payment from Webmoney'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'webmoney_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'webmoney_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from Sberbank'),
                                                'name' => 'YC_ALLOW_SBERBANK',
                                                'desc' => $this->l('Turn on to allow payment from Sberbank'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'sberbank_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'sberbank_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('Allow payment from mobile terminal (mPOS)'),
                                                'name' => 'YC_ALLOW_MPOS',
                                                'desc' => $this->l('Turn on to allow payment from mobile terminal (mPOS)'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'mpos_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'mpos_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        ),                                    
                                        array(
                                                'type' => 'radio',
                                                'label' => $this->l('demo mode'),
                                                'name' => 'YC_DEMO_MODE',
                                                'desc' => $this->l('Turn off for working mode'),
                                                'is_bool' => true,
                                                'values' => array(
                                                    array(
                                                        'id' => 'demo_mode_on',
                                                        'value' => 1,
                                                        'label' => $this->l('Enabled')
                                                    ),
                                                    array(
                                                        'id' => 'demo_mode_off',
                                                        'value' => 0,
                                                        'label' => $this->l('Disabled')
                                                    )
                                                )
                                        )
				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);
		
		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table =  $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm(array($fields_form));
	}
	
	public function getConfigFieldsValues()
	{
		return array(
			'YC_SHOPID' => Tools::getValue('YC_SHOPID', Configuration::get('YC_SHOPID')),
			'YC_SCID' => Tools::getValue('YC_SCID', Configuration::get('YC_SCID')),
			'YC_SHOPPASSWORD' => Tools::getValue('YC_SHOPPASSWORD', Configuration::get('YC_SHOPPASSWORD')),
                        'YC_ALLOW_YANDEXMONEY' => Tools::getValue('YC_ALLOW_YANDEXMONEY', Configuration::get('YC_ALLOW_YANDEXMONEY')),
                        'YC_ALLOW_BANK_CARD' => Tools::getValue('YC_ALLOW_BANK_CARD', Configuration::get('YC_ALLOW_BANK_CARD')),
                        'YC_ALLOW_MOBILE' => Tools::getValue('YC_ALLOW_MOBILE', Configuration::get('YC_ALLOW_MOBILE')),
                        'YC_ALLOW_TERMINAL' => Tools::getValue('YC_ALLOW_TERMINAL', Configuration::get('YC_ALLOW_TERMINAL')),
                        'YC_ALLOW_WEBMONEY' => Tools::getValue('YC_ALLOW_WEBMONEY', Configuration::get('YC_ALLOW_WEBMONEY')),
                        'YC_ALLOW_SBERBANK' => Tools::getValue('YC_ALLOW_SBERBANK', Configuration::get('YC_ALLOW_SBERBANK')),
                        'YC_ALLOW_MPOS' => Tools::getValue('YC_ALLOW_MPOS', Configuration::get('YC_ALLOW_MPOS')),
                        'YC_DEMO_MODE' => Tools::getValue('YC_DEMO_MODE', Configuration::get('YC_DEMO_MODE'))
		);
	}
}
