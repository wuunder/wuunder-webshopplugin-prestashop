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

function upgrade_module_1_2_7($object, $install = false)
{
    Db::getInstance()->execute('
        CREATE INDEX `' . _DB_PREFIX_ . 'order_id`
        ON `wuunder_shipments` (`order_id`);

      ');

    return true; //if there were no errors
}
