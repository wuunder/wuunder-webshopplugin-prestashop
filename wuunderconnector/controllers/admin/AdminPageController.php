<?php

class AdminPageController extends AdminController
{
    public function initContent()
    {
        parent::initContent();
        $smarty = $this->context->smarty;

        $smarty->assign('test', 'test1');

    }
}