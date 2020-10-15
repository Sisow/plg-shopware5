{extends file="frontend/index/index.tpl"}

{* Shop header *}
{block name='frontend_index_navigation'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {else}
        {include file="frontend/checkout/header.tpl"}
    {/if}
{/block}

{* Back to the shop button *}
{block name='frontend_index_logo_trusted_shops'}
    {$smarty.block.parent}
    {if $theme.checkoutHeader}
        {s name="FinishButtonBackToShop" namespace="frontend/checkout/finish" assign="snippetFinishButtonBackToShop"}{/s}
        <a href="{url controller='index'}"
           class="btn is--small btn--back-top-shop is--icon-left"
           title="{$snippetFinishButtonBackToShop|escape}">
            <i class="icon--arrow-left"></i>
            {s name="FinishButtonBackToShop" namespace="frontend/checkout/finish"}{/s}
        </a>
    {/if}
{/block}

{* Hide sidebar left *}
{block name='frontend_index_content_left'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}
{/block}

{* Hide breadcrumb *}
{block name='frontend_index_breadcrumb'}{/block}

{* Step box *}
{block name='frontend_index_navigation_categories_top'}
    {if !$theme.checkoutHeader}
        {$smarty.block.parent}
    {/if}

    {include file="frontend/register/steps.tpl" sStepActive="paymentShipping"}
{/block}

{* Footer *}
{block name="frontend_index_footer"}
    {if !$theme.checkoutFooter}
        {$smarty.block.parent}
    {else}
        {block name="frontend_index_checkout_shipping_payment_footer"}
            {include file="frontend/index/footer_minimal.tpl"}
        {/block}
    {/if}
{/block}

{* Main content *}
{block name="frontend_index_content"}
    <div class="content content--confirm product--table" data-ajax-shipping-payment="true">
        {* Error messages *}
        {block name='frontend_account_payment_error_messages'}
            {include file="frontend/register/error_message.tpl" error_messages=$sErrorMessages}
        {/block}

        <div class="confirm--outer-container">
            <form id="shippingPaymentForm" name="shippingPaymentForm" method="post" action="{url controller='Sisow' action='pay'}" class="payment">

                {* Action top *}
                {block name='frontend_checkout_shipping_payment_core_buttons_top'}
                    {block name='frontend_checkout_shipping_payment_core_buttons'}
                        <div class="confirm--actions table--actions block">
                            <button type="submit" class="btn is--primary is--icon-right is--large right main--actions">{s namespace='frontend/checkout/shipping_payment' name='NextButton'}{/s}<i class="icon--arrow-right"></i></button>
                        </div>
                    {/block}
                {/block}

                {* Payment and shipping information *}
                <div class="shipping-payment--information">

                    {* Payment method *}
                    <div class="confirm--inner-container block">
                        <div class="payment--method-list panel has--border is--rounded block">
                            {include file=$template}
                        </div>
                    </div>
                </div>

                {* Action bottom *}
                {block name='frontend_checkout_shipping_payment_core_buttons_bottom'}
                    {block name='frontend_checkout_shipping_payment_core_buttons'}
                        <div class="confirm--actions table--actions block actions--bottom">
                            <button type="submit" form="shippingPaymentForm" class="btn is--primary is--icon-right is--large right main--actions">{s namespace='frontend/checkout/shipping_payment' name='NextButton'}{/s}<i class="icon--arrow-right"></i></button>
                        </div>
                    {/block}
                {/block}
            </form>

            {* Benefit and services footer *}
            {block name="frontend_checkout_footer"}
                {include file="frontend/checkout/table_footer.tpl"}
            {/block}
        </div>
    </div>
{/block}
