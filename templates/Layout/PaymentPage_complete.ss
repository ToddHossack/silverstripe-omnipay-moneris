<div class="PaymentPage__layout" role="main">

	<h1>$Title</h1>

	<div class="PaymentPage__errors">

	</div>
	<div class="PaymentResult PaymentResult--complete">
		<h2 class="PaymentResult__heading">Test<a href="#" class="button button1" onClick="window.print()">Print</a></h2>

		<div class="PaymentResult__section">
			<% include PaymentMerchant %>
			<h5>Payment page</h5>
			<span class="PaymentPage__URL">$AbsoluteLink</span>
		</div>
		
		<h3 class="PaymentResult__heading">Order Details</h3>
		<div class="PaymentResult__section">
			<% include PaymentOrderDetails Order=$Order %>
		</div>

		<h3 class="PaymentResult__heading">Transaction Details</h3>
		<div class="PaymentResult__section">
			<% include PaymentDetails Payment=$Payment %>
		</div>

	</div>
</div>