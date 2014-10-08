{*
* 2014 Anisimow
* 
*}

<div class="alert alert-info">
    <img src="../modules/yacassa/yacassa.png" style="float:left; margin-right:15px;" width="90">
    <p><strong>{l s="This module allows you to accept secure payments by cassa from yandex.money" mod='yacassa'}</strong></p>
    <p>{l s="First You need left request on https://money.yandex.ru/joinups" mod='yacassa'}</p>
    <p><strong class="bg-warning text-warning">{l s="Then !!! Set up SSL on your server!!! " mod='yacassa'}</strong></p>
    <p>{l s="When you get technical anketa, use this URLs:" mod='yacassa'}</p>
    <p>{l s="checkURL: " mod='yacassa'}{$check}</p>
    <p>{l s="paymentAvisoURL: " mod='yacassa'}{$paymentAviso}</p>
    <p>{l s="successURL: " mod='yacassa'}{$success}</p>
    <p>{l s="failURL: " mod='yacassa'}{$fail}</p>
</div>
