<div class="PaymentPage__layout" role="main">

	<h1>$Title</h1>

	complete
	
	<div class="PaymentPage__result PaymentPage__result--complete">
		<h2>###value_result_data_title###<a href="#" class="button1" onClick="window.print()">Print</a></h2>

		<% include PaymentMerchant %>

		<h3>Payment Details</h3>

		<% include PaymentDetails Payment=$Payment %>

	</div>
</div>