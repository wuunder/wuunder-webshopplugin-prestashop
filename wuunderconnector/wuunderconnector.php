<?php
/**

 * NOTICE OF LICENSE

 *

 * This file is licenced under the Software License Agreement.

 * With the purchase or the installation of the software in your application

 * you accept the licence agreement.

 *

 * You must not modify, adapt or create derivative works of this source code

 *

 *  @author    Wuunder Nederland BV

 *  @copyright 2015-2019 Wuunder Holding B.V.

 *  @license   LICENSE.txt

 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (_PS_VERSION_ < '1.7') {
    require_once 'vendor/autoload.php';
}
$modulePath = _PS_MODULE_DIR_ . '/wuunderconnector/';
require_once $modulePath . 'classes/WuunderCarrier.php';

class WuunderConnector extends Module
{
    private $parcelshopcarrier;

    private $hooks = array(
        'actionValidateOrder',
        'displayHeader',
        'displayFooter',
        'actionCarrierUpdate',
    );

    public function __construct()
    {
        $this->name = 'wuunderconnector';
        $this->tab = 'shipping_logistics';

        $this->version = '1.3.1';

        $this->author = 'Wuunder';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '314132fd1611ed03d1a68870a6e94b36';

        parent::__construct();

        if (_PS_VERSION_ > '1.7') {
            $this->autoLoad();
        }
        $this->displayName = $this->l('Wuunder shipping module');
        $this->description = $this->l('Send and receive your shipments easily, personally and efficiently. You can ship via more then 23 carriers. Wuunder takes care of all your transport and warehouse solutions you need.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get($this->name)) {
            $this->warning = $this->l('No name provided');
        }

        $this->parcelshopcarrier = new WuunderCarrier();
    }

    //will not be called in version 1.6
    public function hookActionDispatcherBefore()
    {
        $this->autoLoad();
    }  
    
    public function hookDisplayAfterBodyOpeningTag($params)
    {    
        $this->initJavascriptTemplate($params);
        return $this->display(__FILE__, 'javascript_ini.tpl');
    }

    private function initJavascriptTemplate($params)
    {
        $pickerData = $this->parcelshop_urls();

        $this->context->smarty->assign(
            array(
                'carrier_id' => Configuration::get('MYCARRIER1_CARRIER_ID'),
                'baseApiUrl' => $pickerData['baseApiUrl'],
                'availableCarriers' => $pickerData['availableCarriers'],
                'baseUrl' => $pickerData['baseUrl'],
                'addressId' => $params['cart']->id_address_delivery,
                'version' => floatval(_PS_VERSION_),
            )
        );

        if ($this->context->cookie->parcelId) {
            $this->context->smarty->assign('cookieParcelshopId', $this->context->cookie->parcelId);
            $this->context->smarty->assign('cookieParcelshopAddress', $this->context->cookie->parcelAddress);
        } else {
            $this->context->smarty->assign('cookieParcelshopAddress', false);
            $this->context->smarty->assign('cookieParcelshopId', false);
        }
    }    

    /**
     * Autoload's project files from /src directory ps > 1.7
     */
    private function autoLoad()
    {
        $autoLoadPath = $this->getLocalPath().'vendor/autoload.php';
        require_once $autoLoadPath;
    }

    private function installDB()
    {
        Db::getInstance()->execute('
                       CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wuunder_shipments` (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `order_id` INT(11) UNSIGNED NOT NULL,
                            `booking_url` TEXT NULL,
                            `booking_token` varchar(255) NULL,
                            `label_id` varchar(255) NULL,
                            `label_url` TEXT NULL,
                            `label_tt_url` TEXT NULL,
                            PRIMARY KEY(`id`)
                        )
                        ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET = utf8;'
        );

        Db::getInstance()->execute('
                        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wuunder_order_parcelshop` (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `order_id` INT(11) UNSIGNED NOT NULL,
                            `parcelshop_id` VARCHAR(255),
                            PRIMARY KEY(`id`)
                        )
                        ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET = utf8;'
        );

        $this->addIndexToOrderId();
    }

    private function addIndexToOrderId()
    {

        Db::getInstance()->execute('
                    CREATE INDEX `shipment_order_id`
                    ON `' . _DB_PREFIX_ . 'wuunder_shipments` (`order_id`);'
        );

        Db::getInstance()->execute('
                    CREATE INDEX `parcelshop_order_id`
                    ON `' . _DB_PREFIX_ . 'wuunder_order_parcelshop` (`order_id`);'
        );
    }

    private function uninstallDB()
    {
        Db::getInstance()->execute('
               DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wuunder_shipments`;
               DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wuunder_order_parcelshop`'
        );
    }

    private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $tab_class;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tab_name;
        }
        if ($id_tab_parent) {
            $tab->id_parent = (int) Tab::getIdFromClassName($id_tab_parent);
        } else {
            $tab->id_parent = 0;
        }
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallModuleTab($tab_class)
    {
        $id_tab = Tab::getIdFromClassName($tab_class);
        if ($id_tab != 0) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        Logger::addLog('uninstallation failed 167.');
        return false;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $this->installDB();

        $this->parcelshopcarrier->install();

        if (!parent::install() 
            || !$this->installModuleTab(
                'AdminWuunderConnector', 
                'Wuunder', (_PS_VERSION_ < '1.7') ? 'AdminShipping' : 'AdminParentShipping'
            )
        ) {
            Logger::addLog('Installation failed 183.');
            return false;
        }

        if (_PS_VERSION_ >= '1.7') {
            array_push(
                $this->hooks,
                'displayAfterBodyOpeningTag',
                'actionDispatcherBefore',
                'actionFrontControllerSetMedia'
            );
        }

        foreach ($this->hooks as $hookName) {
            if (!$this->registerHook($hookName)) {
                Logger::addLog('Installation of hook: ' . $hookName . 'failed');
                return false;
            }
        }
        Configuration::updateValue('testmode', '1');

        return true;
    }

    public function uninstall()
    {
        $this->uninstallDB();

        $this->parcelshopcarrier->uninstall();

        if (!parent::uninstall() ||
            !Configuration::deleteByName($this->name) ||
            !$this->uninstallModuleTab('AdminWuunderConnector')
        ) {
            Logger::addLog('uninstallation failed 209.');
            return false;
        }

        foreach ($this->hooks as $hookName) {
            if (!$this->unregisterHook($hookName)) {
                Logger::addLog('Uninstallation of hook: ' . $hookName . 'failed');
                return false;
            }
        }
        return true;
    }

    public function hookActionCarrierUpdate($params)
    {
        if ((int)($params['id_carrier']) == (int)(Configuration::get(
            'MYCARRIER1_CARRIER_ID'
        ))
        ) {
            Configuration::updateValue(
                'MYCARRIER1_CARRIER_ID',
                (int)($params['carrier']->id)
            );
        }
    }

    public function hookDisplayHeader($params)
    {
        if (_PS_VERSION_ < '1.7') {
            if ('order' === $this->context->controller->php_self) {
                $this->context->controller->addCSS($this->_path . 'views/css/hook/parcelshop.css', 'all');
                $this->context->controller->addJS($this->_path . 'views/js/hook/checkoutjavascript1.6.js', 'all');
                $this->initJavascriptTemplate($params);
                return $this->display(__FILE__, 'javascript_ini.tpl');
            }
        } else {
            $this->context->controller->registerJavascript(
                'wuunderconnector',
                '/js/jquery/jquery-1.11.0.min.js',
                array('position' => 'head', 'priority' => 1)
            ); 
            $this->context->controller->registerStylesheet(
                'wuunderconnector',
                'modules/'.$this->name.'/views/css/hook/parcelshop.css',
                [
                  'media' => 'all',
                  'priority' => 200,
                ]
            );

        }
    }

    public function hookActionFrontControllerSetMedia($params)
    {   
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        if (_PS_VERSION_ > '1.7') {
        //if ('order' === $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'wuunderconnector',
                'modules/wuunderconnector/views/js/hook/checkoutjavascript1.7.js',
                [
                'position' => 'bottom',
                'media' => 'all',
                'priority' => 0,
                'attributes' =>'sync'
                ]
            );
        //}
        }
    }

    public function hookActionValidateOrder($params)
    {
        $carrier_id = $params['cart']->id_carrier;
        if (Configuration::get('MYCARRIER1_CARRIER_ID') == $carrier_id) {
            $orderId = $params['order']->id;
            $parcelshopId = $this->context->cookie->parcelId;
            Db::getInstance()->insert(
                'wuunder_order_parcelshop',
                array(
                    'order_id' => (int) $orderId,
                    'parcelshop_id' => pSQL($parcelshopId),
                )
            );
        }
        unset($this->context->cookie->parcelId);
        $this->context->smarty->clearAssign('cookieParcelshopId');
    }

    public function parcelshop_urls()
    {
        $tmpEnvironment = new \Wuunder\Api\Environment((int) Configuration::get('testmode') === 1 ? 'staging' : 'production');

        $baseApiUrl = Tools::substr($tmpEnvironment->getStageBaseUrl(), 0, -3);

        return $pickerData = array(
            'baseApiUrl' => $baseApiUrl,
            'availableCarriers' => preg_replace('/\s+/', '', Configuration::get('available_carriers_locator')),
            'baseUrl' => _PS_BASE_URL_ . __PS_BASE_URI__,
        );
    }

    public function getContent()
    {
        $output = null;
        $fields = array(
            "testmode",
            "live_api_key",
            "test_api_key",
            "company_name",
            "firstname",
            "lastname",
            "email",
            "phonenumber",
            "streetname",
            "housenumber",
            "zipcode",
            "city",
            "country",
            "postbookingstatus",
            "available_carriers_locator",
            "wuunderfilter1carrier",
            "wuunderfilter1filter",
            "wuunderfilter2carrier",
            "wuunderfilter2filter",
            "wuunderfilter3carrier",
            "wuunderfilter3filter",
            "wuunderfilter4carrier",
            "wuunderfilter4filter",
        );

        $success = true;

        $ignoredFields = array(
            "live_api_key",
            "test_api_key",
            "testmode",
            "wuunderfilter1filter",
            "wuunderfilter2filter",
            "wuunderfilter3filter",
            "wuunderfilter4filter",
        );

        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($fields as $field) {
                $field_name = (string)Tools::getValue($field);
                if (!$this->validateForm($field, $field_name)) {
                    $output .= $this->displayError($this->l('Invalid Configuration value: ' . $field));
                    $success = false;
                } elseif ((!$field_name
                    || empty($field_name)
                    || !Validate::isGenericName($field_name))
                    && (!in_array($field, $ignoredFields, false))
                ) {
                    $output .= $this->displayError($this->l('Invalid Configuration value: ' . $field));
                    $success = false;
                } elseif ($field === "available_carriers_locator" && !Configuration::get('available_carriers_locator')) {
                    //Set default if empty string
                    Configuration::updateValue('available_carriers_locator','dpd,dhl,postnl');
                } else {
                    Configuration::updateValue($field, $field_name);
                }
            }
            if ($success) {
                $output .= $this->displayConfirmation("Successfully saved");
            }
        }
        return $output . $this->displayForm();
    }

    private function validateForm($field, $field_name) 
    {
        if ($field == "phonenumber") {
            return Validate::isPhoneNumber($field_name);
        } elseif ($field == "zipcode") {
            return Validate::isPostCode($field_name);
        } elseif ($field == "country") {
            return Validate::isLanguageIsoCode($field_name);
        } elseif ($field == "city") {
            return Validate::isCityName($field_name);
        } elseif ($field == "email") {
            return Validate::isEmail($field_name);
        } else {
            return true;
        }
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Testmode'),
                    'name' => "testmode",
                    'options' => array(
                        'query' => array(array("id" => 1, "name" => "On"), array("id" => 0, "name" => "Off")),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Live API key'),
                    'name' => "live_api_key",
                    'required' => false,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Test API key'),
                    'name' => "test_api_key",
                    'required' => false,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Company'),
                    'name' => "company_name",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Firstname'),
                    'name' => "firstname",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Lastname'),
                    'name' => "lastname",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => "email",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Phonenumber'),
                    'name' => "phonenumber",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Streetname'),
                    'name' => "streetname",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Housenumber'),
                    'name' => "housenumber",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Zipcode'),
                    'name' => "zipcode",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('City'),
                    'name' => "city",
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Country code'),
                    'name' => "country",
                    'required' => true,
                    'placeholder' => $this->l("e.g. NL"),
                ),

                array(
                    'type' => 'select',
                    'label' => $this->l('Order status after booking'),
                    'name' => "postbookingstatus",
                    'options' => array(
                        'query' => OrderState::getOrderStates($this->context->language->id, $this->context->cookie->profile),
                        'id' => 'id_order_state',
                        'name' => 'name',
                    ),
                    'required' => true,
                ),

                array(
                    'type' => 'text',
                    'label' => $this->l('Set carriers for parcelshop locator'),
                    'name' => 'available_carriers_locator',
                    'required' => false,
                    'placeholder' => $this->l('carriers for the parcelshop locator. e.g. dpd, postnl')
                ),

                array(
                    'type' => 'select',
                    'label' => $this->l('Wuunder filter: #1 Carrier'),
                    'name' => "wuunderfilter1carrier",
                    'options' => array(
                        'query' => Carrier::getCarriers($this->context->language->id, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE),
                        'id' => 'id_carrier',
                        'name' => 'name',
                    ),
                    'required' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wuunder filter: #1 Filter'),
                    'name' => "wuunderfilter1filter",
                    'required' => false,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Wuunder filter: #2 Carrier'),
                    'name' => "wuunderfilter2carrier",
                    'options' => array(
                        'query' => Carrier::getCarriers($this->context->language->id, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE),
                        'id' => 'id_carrier',
                        'name' => 'name',
                    ),
                    'required' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wuunder filter: #2 Filter'),
                    'name' => "wuunderfilter2filter",
                    'required' => false,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Wuunder filter: #3 Carrier'),
                    'name' => "wuunderfilter3carrier",
                    'options' => array(
                        'query' => Carrier::getCarriers($this->context->language->id, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE),
                        'id' => 'id_carrier',
                        'name' => 'name',
                    ),
                    'required' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wuunder filter: #3 Filter'),
                    'name' => "wuunderfilter3filter",
                    'required' => false,
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Wuunder filter: #4 Carrier'),
                    'name' => "wuunderfilter4carrier",
                    'options' => array(
                        'query' => Carrier::getCarriers($this->context->language->id, true, false, false, null, PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE),
                        'id' => 'id_carrier',
                        'name' => 'name',
                    ),
                    'required' => false,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Wuunder filter: #4 Filter'),
                    'name' => "wuunderfilter4filter",
                    'required' => false
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right',
            ),
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->identifier = $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true; // false -> remove toolbar
        $helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ),
        );

        // Load current value
        $fields = array(
            "testmode",
            "live_api_key",
            "test_api_key",
            "company_name",
            "firstname",
            "lastname",
            "email",
            "phonenumber",
            "streetname",
            "housenumber",
            "zipcode",
            "city",
            "country",
            "postbookingstatus",
            "available_carriers_locator",
            "wuunderfilter1carrier",
            "wuunderfilter1filter",
            "wuunderfilter2carrier",
            "wuunderfilter2filter",
            "wuunderfilter3carrier",
            "wuunderfilter3filter",
            "wuunderfilter4carrier",
            "wuunderfilter4filter",
        );

        foreach ($fields as $field) {
            $helper->fields_value[$field] = Configuration::get($field);
        }

        return $helper->generateForm($fields_form);
    }
}
