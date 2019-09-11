
<div class="PaymentMerchant clearfix">
	<h4>$MerchantName</h4>
	<% if $MerchantPhysicalAddress %>
	<div class="PaymentMerchant__section">
		<h5>Physical address</h5>
		<address>$MerchantPhysicalAddressHTML</address>
	</div>
	<% end_if %>
	<% if $MerchantPostalAddress %>
	<div class="PaymentMerchant__section">
		<h5>Postal address</h5>
		<address>$MerchantPostalAddressHTML</address>
	</div>
	<% end_if %>
	<div class="PaymentMerchant__section">
		<h5>Contact</h5>
		<% if $MerchantEmail %>Email: $MerchantEmail<br /><% end_if %>
		<% if $MerchantPhone %>Phone: $MerchantPhone<br /><% end_if %>
		<% if $MerchantWebsite %>Website: $MerchantWebsite<% end_if %>
	</div>
</div>