{extends file="parent:frontend/checkout/confirm.tpl"}

{block name='frontend_checkout_confirm_tos_panel'}
    {if isset($warning_message)}
        <div class="tos--panel panel has--border">

            {block name='frontend_checkout_confirm_tos_panel_headline'}
                <div class="panel--title primary is--underline">
                    Warning from payment provider : EMS Online
                </div>
            {/block}

            <div class="panel--body is--wide">
                <div>
                    <b>Reason of return back to the checkout : </b>{$warning_message}
                </div>
            </div>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
