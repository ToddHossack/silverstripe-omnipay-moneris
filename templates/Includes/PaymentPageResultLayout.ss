<% if $PaymentErrors %>
<div class="PaymentPage__errors typography">
	<h4><%t PaymentPage.Errors 'Errors' %></h4>
	<ul>
	<% loop $PaymentErrors %>
		<li>$Error</li>
	<% end_loop %>
	</ul>
	<a href="$StartLink" class="PaymentResult_action">Try again</a>
</div>

<% else %>
<div class="PaymentResult PaymentResult--$Result.Code.XML typography">
	<h2 class="PaymentResult__heading">$Result.Type: $Result.Title
		<% if $Result.Code == 'Completed' %>
		<a href="$StartLink" class="PaymentResult_action button success hide-for-print">Done</a>
		<% else %>
		<a href="$StartLink" class="PaymentResult_action button success hide-for-print">Start again</a>
		<% end_if %>
		<a href="#" class="PaymentResult_action button primary hide-for-print" onClick="window.print()">Print</a>
	</h2>

	<div class="PaymentResult__section">
		<% include PaymentMerchant %>
		<h5>Payment page</h5>
		<span class="PaymentPage__URL">$AbsoluteLink</span>
	</div>

	<% if $OrderData %>
	<h3 class="PaymentResult__heading">Order Details</h3>
	<div class="PaymentResult__section">
		<% include PaymentOrderDetails Order=$OrderData %>
	</div>
	<% end_if %>
	<h3 class="PaymentResult__heading">Transaction Details </h3>
	<div class="PaymentResult__section">
		<% if $Payment %>
		<% include PaymentDetails Payment=$Payment %>
		<% else %>
		No transaction details
		<% end_if %>
	</div>
</div>
<% end_if %>
