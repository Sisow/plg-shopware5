<h3 class="payment--method-headline panel--title is--underline">iDEAL betaling</h3>

<div class="panel--body is--wide block-group">
    <select id="issuer" name="issuer" required="required">
        <option value="">-- Kies uw bank --</option>
        {foreach from=$issuers key=k item=v}
            <option value="{$k}">{$v}</option>
        {/foreach}
    </select>
</div>