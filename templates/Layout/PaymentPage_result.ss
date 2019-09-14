<div class="PaymentPage__layout" role="main">
	<h1>$Title</h1>
	
	<% if $PaymentErrors %>
	<div class="PaymentPage__errors">
		<h4><%t PaymentPage.Errors 'Errors' %></h4>
		<ul>
		<% loop $PaymentErrors %>
			<li>$Error</li>
		<% end_loop %>
		</ul>
		<div class=""><a href="$StartLink" class="PaymentResult_action">Start again</a></div>
	</div>

	<% else %>
	<div class="PaymentResult PaymentResult--$Result.Code.XML">
		<h2 class="PaymentResult__heading">$Result.Title - $Result.Type
			<a href="#" class="PaymentResult_action" onClick="window.print()">Print</a>
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
		<% if $Payment %>
		<h3 class="PaymentResult__heading">Transaction Details</h3>
		<div class="PaymentResult__section">
			<% include PaymentDetails Payment=$Payment %>
		</div>
		<% end_if %>
	</div>
	<% end_if %>
</div>