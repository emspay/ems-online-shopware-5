{extends file="parent:frontend/checkout/finish.tpl"}

{block name='frontend_checkout_finish_information_wrapper'}
    {$smarty.block.parent}
    {if isset($emspayIbanInformation)}
        <<div class="information--panel-item">
            <div class="finish--table product--table">
                 <div class="panel has--border">
                    <div class="panel--body is--rounded">
                        <div class="panel has--border block information--panel finish--details">
                            <div class="panel--title is--underline">
                                IBAN Information
                            </div><br>
                            <p><b>Reference: </b>{$emspayIbanInformation.reference}</p>
                            <p><b>Creditor BIC: </b>{$emspayIbanInformation.creditor_bic}</p>
                            <p><b>Creditor IBAN: </b>{$emspayIbanInformation.creditor_iban}</p>
                            <p><b>Consumer name: </b>{$emspayIbanInformation.consumer_name}</p>
                            <p><b>Creditor account holder city: </b>{$emspayIbanInformation.creditor_account_holder_city}</p>
                            <p><b>Creditor account holder name: </b>{$emspayIbanInformation.creditor_account_holder_name}</p>
                            <p><b>Creditor account holder country: </b>{$emspayIbanInformation.creditor_account_holder_country}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/block}