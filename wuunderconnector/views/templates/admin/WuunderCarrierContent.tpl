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
