<div class="layout-main__inner" role="main">

	<h1>$Title <% if $SubTitle %><br /><span class="subhead">$SubTitle</span><% end_if %></h1>

	<div class="typography">
		<% if $Content %>$Content<% end_if %>
		
		<!-- ###TEMPLATE_RESULT_HEADER### begin -->
		<div id="PaymentResult">
			<h2>###value_result_title###</h2>
			
		</div>
		complete

		<div id="receipt">
			<h2>###value_result_data_title###<a href="#" class="button1" onClick="window.print()">Print</a></h2>
			<div class="receiptSection clearfix">
				<h4>Regional District Okanagan-Similkameen</h4>
				<p>101 Martin Street<br />Penticton, BC<br />
				V2A 5J9<br />
				Payment page: www.rdos.bc.ca/index.php?id=716</p>
			</div>

			<h3>Payment Details</h3>

			<% include FinanceOrder Order=$Order, Payment=$Payment %>
			
			<h3>Transaction Details</h3>
			<table class="receiptSection" cellspacing="0" cellpadding="0">
				<tr>
					<td class="fieldLabel">Transaction Type:</td>
					<td class="fieldData">###value_trans_name###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Date / Time:</td>
					<td class="fieldData">###value_date_stamp### ###value_time_stamp###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Transaction Amount:</td>
					<td class="fieldData">$###value_charge_total###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Order ID:</td>
					<td class="fieldData">###value_order_id###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Cardholder:</td>
					<td class="fieldData">###value_cardholder###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Card Type:</td>
					<td class="fieldData">###value_card###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Response Code - Message:</td>
					<td class="fieldData">###value_response_code### - ###value_message###</td>
				</tr>
				<tr>
					<td class="fieldLabel">ISO Code:</td>
					<td class="fieldData">###value_iso_code###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Reference Number:</td>
					<td class="fieldData">###value_bank_transaction_id###</td>
				</tr>
				<tr>
					<td class="fieldLabel">Authorization Code:</td>
					<td class="fieldData">###value_bank_approval_code###</td>
				</tr>
			</table>

		</div>
		<!-- ###TEMPLATE_RESULT_BODY### end -->

	</div>
</div>