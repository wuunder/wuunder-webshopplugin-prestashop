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

 *  @author    Wuunder

 *  @copyright 2015-2019 Wuunder Holding B.V.

 *  @license   LICENSE.txt

 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once 'vendor/autoload.php';
require_once 'classes/WuunderCarrier.php';

class WuunderConnector extends Module
{
    private $parcelshopcarrier;

    private $hooks = array(
        'actionValidateOrder',
        'displayHeader',
        'displayFooter',
    );

    public function __construct()
    {
        $this->name = 'wuunderconnector';
        $this->tab = 'Wuunder_booking';
        $this->version = '1.2.7';
        $this->author = 'Wuunder';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Wuunder verzendmodule');
        $this->description = $this->l('Verstuur al je zendingen makkelijk, persoonlijk en efficient. Wij volgen elke zending op de voet en nemen alle communicatie met de vervoerders van je over.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!Configuration::get($this->name)) {
            $this->warning = $this->l('No name provided');
        }

        $this->parcelshopcarrier = new WuunderCarrier();
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
                        ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET = utf8;
            ');

        Db::getInstance()->execute('
                        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wuunder_order_parcelshop` (
                            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                            `order_id` INT(11) UNSIGNED NOT NULL,
                            `parcelshop_id` VARCHAR(255),
                            PRIMARY KEY(`id`)
                        )
                        ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET = utf8;
 ');

        $this->addIndexToOrderId();
    }

    private function addIndexToOrderId()
    {

        Db::getInstance()->execute('
                    CREATE INDEX `shipment_order_id`
                    ON `' . _DB_PREFIX_ . 'wuunder_shipments` (`order_id`);

            ');

        Db::getInstance()->execute('
                    CREATE INDEX `parcelshop_order_id`
                    ON `' . _DB_PREFIX_ . 'wuunder_order_parcelshop` (`order_id`);

            ');
    }

    private function uninstallDB()
    {
        Db::getInstance()->execute('
               DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wuunder_shipments`;
               DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'wuunder_order_parcelshop`

            ');

    }

    private function installModuleTab($tab_class, $tab_name, $id_tab_parent)
    {
//        if (!copy(_PS_MODULE_DIR_ . $this->name . '/logo.png', _PS_IMG_DIR_ . 't/' . $tab_class . '.png')) {
        //            return false;
        //        }
        //        $tab = new Tab();
        //
        //        $languages = Language::getLanguages(false);
        //        foreach ($languages as $language) {
        //            $tab->name[$language['id_lang']] = $tab_name;
        //        }
        //        $tab->class_name = $tab_class;
        //        $tab->module = $this->name;
        //        $tab->id_parent = $id_tab_parent;
        //
        //        if (!$tab->save()) {
        //            return false;
        //        }
        //        return true;
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
        return false;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $this->installDB();

        $this->parcelshopcarrier->install();

        if (!parent::install() ||
            !$this->installModuleTab('AdminWuunderConnector', 'Wuunder', (_PS_VERSION_ < '1.7') ? 'AdminShipping' : 'AdminParentShipping')
        ) {
            return false;
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
            return false;
        }

        return true;
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/admin/parcelshop.css', 'all');
    }

    public function hookDisplayFooter($params)
    {
        $pickerData = $this->parcelshop_urls();

        $this->context->smarty->assign(
            array(
                'carrier_id' => Configuration::get('MYCARRIER1_CARRIER_ID'),
                'baseApiUrl' => $pickerData['baseApiUrl'],
                'availableCarriers' => 'dpd', //$pickerData['availableCarriers']
                'baseUrl' => $pickerData['baseUrl'],
                'addressId' => $params['cart']->id_address_delivery,
            )
        );

        if ($this->context->cookie->parcelId) {
            $this->context->smarty->assign('cookieParcelshopId', $this->context->cookie->parcelId);
            $this->context->smarty->assign('cookieParcelshopAddress', json_encode($this->context->cookie->parcelAddress));
        } else {
            $this->context->smarty->assign('cookieParcelshopAddress', false);
            $this->context->smarty->assign('cookieParcelshopId', false);

        }

        return $this->display(__FILE__, 'checkoutjavascript.tpl');

    }

    public function hookActionValidateOrder($params)
    {
        $carrier_id = $params['cart']->id_carrier;
        if (Configuration::get('MYCARRIER1_CARRIER_ID') == $carrier_id) {
            $orderId = $params['order']->id;
            $parcelshopId = $this->context->cookie->parcelId;
            Db::getInstance()->insert('wuunder_order_parcelshop', array(
                'order_id' => (int) $orderId,
                'parcelshop_id' => pSQL($parcelshopId),
            ));
        }
        unset($this->context->cookie->parcelId);
        $this->context->smarty->clearAssign('cookieParcelshopId');
    }

    public function parcelshop_urls()
    {
        // $pluginPath = dirname(plugin_dir_url(__FILE__));
        // $pluginPathJS = $pluginPath . "/assets/js/parcelshop.js";

        $tmpEnvironment = new \Wuunder\Api\Environment((int) Configuration::get('testmode') === 1 ? 'staging' : 'production');

        $baseApiUrl = Tools::substr($tmpEnvironment->getStageBaseUrl(), 0, -3);
        //$availableCarriers = implode(',', get_option('woocommerce_wuunder_parcelshop_settings')['select_carriers']);

        // echo <<<EOT
        //     <script type="text/javascript" data-cfasync="false" src="$pluginPathJS"></script>
        //     <script type="text/javascript">
        //         initParcelshopLocator("$baseWebshopUrl", "$baseApiUrl", "$availableCarriers");
        //     </script>
        // EOT;
        return $pickerData = array(
            'baseApiUrl' => $baseApiUrl,
            'availableCarriers' => null, //$availableCarriers
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
            "wuunderfilter1carrier",
            "wuunderfilter1filter",
            "wuunderfilter2carrier",
            "wuunderfilter2filter",
            "wuunderfilter3carrier",
            "wuunderfilter3filter",
            "wuunderfilter4carrier",
            "wuunderfilter4filter",
        );

        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($fields as $field) {
                $field_name = (string)Tools::getValue($field);
                if ((!$field_name
                    || empty($field_name)
                    || !Validate::isGenericName($field_name))
                    && ($field !== "live_api_key"
                        && $field !== "test_api_key"
                        && $field !== "testmode"
                        && $field !== "wuunderfilter1filter"
                        && $field !== "wuunderfilter2filter"
                        && $field !== "wuunderfilter3filter"
                        && $field !== "wuunderfilter4filter")
                ) {
                    $output .= $this->displayError($this->l('Invalid Configuration value: ' . $field));
                } else {
                    Configuration::updateValue($field, $field_name);
//                    $output .= $this->displayConfirmation($this->l('Settings updated'));
                }
            }
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
                        'query' => array(array("id" => 1, "name" => "Aan"), array("id" => 0, "name" => "Uit")),
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
                    'label' => $this->l('Bedrijfsnaam'),
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
                    'required' => false,
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
