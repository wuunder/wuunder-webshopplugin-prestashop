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

class WuunderconnectorwuunderwebhookModuleFrontController extends ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->context = Context::getContext();
        $this->ssl = false;
        $this->logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
        $this->logger->setFilename(_PS_ROOT_DIR_ . ((_PS_VERSION_ < '1.7') ? "/log/wuunder.log" : "/app/logs/wuunder.log"));
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();
        $this->logger->logDebug("Webhook incoming");
        $result = json_decode(Tools::file_get_contents('php://input'), true);
        $this->logger->logDebug("Webhook Data received");
        if (isset($_REQUEST['orderid']) && isset($_REQUEST['wtoken'])) {
            $this->logger->logDebug(json_encode($result));
            if ($result['action'] === "shipment_booked") {
                $result = $result['shipment'];
                $this->logger->logDebug("1st webhook received");
                if ($this->updateLabelUrl($_REQUEST['orderid'], $_REQUEST['wtoken'], $result['id'], $result['label_url'], $result['track_and_trace_url'])) {
                    $history = new OrderHistory();
                    $history->id_order = (int) $_REQUEST['orderid'];
                    $history->changeIdOrderState(Configuration::get('postbookingstatus'), (int) $_REQUEST['orderid']);
                }
            }
        }
        exit;
    }

    private function updateLabelUrl($order_id, $booking_token, $label_id, $label_url, $label_tt_url)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'wuunder_shipments
                    SET label_id = "' . pSQL($label_id) . '", label_url = "' . pSQL($label_url) . '", label_tt_url = "' . pSQL($label_tt_url) . '"
                    WHERE order_id = ' . pSQL($order_id) . ' AND booking_token = "' . pSQL($booking_token) . '"';
        if (Db::getInstance()->Execute($sql)) {
            return true;
        } else {
            $this->logger->logDebug(Db::getInstance()->getMsgError());
            return false;
        }
    }
}
