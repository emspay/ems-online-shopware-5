{extends file='frontend/index/index.tpl'}

{* Main content *}
{block name='frontend_index_content'}
    <div class="content custom-page--content" >
        <h1>Your order was canceled</h1>
        <h3>Please choose one of actions</h3>
            <a class="btn"
               href="{url controller=checkout action=cart}"
               title="change cart">change cart
            </a>
            <a class="btn is--primary"
               href="{url controller=checkout action=shippingPayment sTarget=checkout}"
               title="change payment method">change payment method
            </a>
    </div>
{/block}

{block name='frontend_index_actions'}{/block}
