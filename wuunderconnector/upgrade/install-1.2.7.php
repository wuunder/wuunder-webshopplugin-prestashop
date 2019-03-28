<?php
  if (!defined('_PS_VERSION_'))
    exit;

  function upgrade_module_1_2_7($object, $install = false)
  {
      Db::getInstance()->execute('
        CREATE INDEX `' . _DB_PREFIX_ . 'order_id`
        ON `wuunder_shipments` (`order_id`);
        
      ');

    return true; //if there were no errors
  }
?>