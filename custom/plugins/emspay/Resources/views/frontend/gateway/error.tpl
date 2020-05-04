{extends file="frontend/error/index.tpl"}

{block name="frontend_index_content"}
    <div class="example-content content custom-page--content">
        <div class="example-content--actions">
            <div style="background-color: pink; color: darkred; text-align: center;"><h3>{$error_message}</h3></div>
            <a class="btn is--primary"
               href="{url controller=checkout action=shippingPayment sTarget=checkout}"
               title="change payment method">change payment method
            </a>
        </div>
    </div>
{/block}
