{**
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
 *  @author    Wuunder Nederland BV
 *  @copyright 2015-2020 Wuunder Holding B.V.
 *  @license   LICENSE.txt
 *}

<h2>{$title}</h2>
{foreach from=$errors item=error}
    <div class="alert error"><img src="{$psimg}admin/forbbiden.gif" alt="nok"/>&nbsp;{$error}</div>
{/foreach}
{$postResponse}
<fieldset>
    <legend><img src="{$path}logo.gif" alt=""/> {$carrierStatus}</legend>

    {if $alert}
        <img src="{$psimg}admin/module_install.png"/>
        <strong>{$isConfigured}</strong>
    {else}
        <img src="{$psimg}admin/warn2.png"/>
        <strong>{$isNotConfigured}</strong>
        <br/>
        <img src="{$psimg}admin/warn2.png"/>
        {$pleaseConfigure}
    {/if}

</fieldset>
<div class="clear">&nbsp;</div>
<style>
    #tabList {
        clear: left;
    }

    .tabItem {
        display: block;
        background: #FFFFF0;
        border: 1px solid #CCCCCC;
        padding: 10px;
        padding-top: 20px;
    }
</style>
<div id="tabList">
    <div class="tabItem">
        <form action="{$formAction}" method="post" class="form" id="configForm">

            <fieldset style="border: 0px;">
                <h4>{$generalConf} :</h4>
                <label>{$mycarrier1} : </label>
                <div class="margin-form">
                    <input type="text" size="20" name="mycarrier1_overcost" value="{$mycarrier1value}"/></div>
                <label>{$mycarrier2}' : </label>
                <div class="margin-form">
                    <input type="text" size="20" name="mycarrier2_overcost" value="{$mycarrier2value}"/>
                </div>
    </div>
    <br/><br/>
    </fieldset>
    <div class="margin-form">
        <input class="button" name="submitSave" type="submit">
    </div>
    </form>
</div>
