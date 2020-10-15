<h3 class="payment--method-headline panel--title is--underline">Afterpay</h3>

<div class="panel--body is--wide block-group">
    <label>Kies aanhef:</label><br/>
    <select name="gender" required="required">
        <option value=""> -- Kies aanhef -- </option>
        <option value="m">De heer</option>
        <option value="f">Mevrouw</option>
    </select>
</div>

<div class="panel--body is--wide block-group">
    <label>Geboortedatum:</label><br/>
    <input type="text" name="dob" data-datepicker="true" data-altInput="true" data-maxDate="{$maxdate}" data-defaultDate="{$maxdate}" data-altInputClass="required">
</div>

<div class="panel--body is--wide block-group">
    <label>Telefoonnummer:</label><br/>
    <input type="text" name="phone" required="required" value="{$phone}">
</div>

{if $b2b && $country == 'NL'}
    <div class="panel--body is--wide block-group">
        <label>KvK nummer:</label><br/>
        <input type="text" name="coc" required="required">
    </div>
{/if}

<div class="panel--body is--wide block-group">
    <label>
        <input type="checkbox" required="required"/> Ik ga akkoord met de <a href="{$afterpayUrl}" target="_blank">algemene voorwaarden van Afterpay</a>
    </label>
</div>


