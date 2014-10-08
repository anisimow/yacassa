{*
* @author Anisimow
*}
                               
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="payment_module yandex_cassa">
        <form action="{$action}" method="post">
            <!-- Обязательные поля -->
            <input name="shopId" value="{$shopId}" type="hidden"/>
            <input name="scid" value="{$scid}" type="hidden"/>
            <input name="sum" value="{$sum}" type="hidden">
            <input name="customerNumber" value="{$customerNumber}" type="hidden"/> 

            <!-- Необязательные поля -->
            {foreach from=$paymentTypes key=type item=item}
                {if !empty($item['allow'])}
                    <div class="yacassa-select-method col-xs-6">
                        <input name="paymentType" value="{$type}" type="radio" id="ym_type_{$type}"/>
                        <label for="ym_type_{$type}"><img src="{$this_path}images/{$type}.png"/>
                            <span class="yacassa-payment-method">{$item['method']}</span>
                        </label>
                    </div>               
                {/if}
            {/foreach}

            <input name="orderNumber" value="{$orderNumber}" type="hidden"/>
            <input name="shopSuccessURL" value="{$shopSuccessURL}" type="hidden"/>
            <input name="shopFailURL" value="{$shopFailURL}" type="hidden"/>
            <input name="cps_email" value="{$cps_email}" type="hidden"/>

            <div class="clearfix"></div>
            <input type="submit" class="btn btn-default btn-lg" value="{l s="Pay width yandex" mod='yacassa'}"/>   
            <p></p>
        </form>
        </div>
    </div>
</div>