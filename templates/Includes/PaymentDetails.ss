<div class="PaymentResult__payment">
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<tr>
			<th><%t Order.OrderNumber 'Order No.' %>:</th>
			<td>$Payment.Identifier</td>
		</tr>
		<tr>
			<th><%t PaymentMessage.Created 'Date/time' %>:</th>
			<td>$LastMessage.Created</td>
		</tr>
		<!--<tr>
			<th><%t PaymentMessage.db_Message 'Message' %>:</th>
			<td>$LastMessage.Message</td>
		</tr>-->
		<tr>
			<th><%t Payment.Status 'Status' %>:</th>
			<td>$Payment.TranslatedStatus</td>
		</tr>
		<tr>
			<th><%t Payment.db_Reference 'Reference' %>:</th>
			<td>$Payment.TransactionReference</td>
		</tr>
		<tr>
			<th><%t Payment.MoneyAmount 'Payment Amount' %>:</th>
			<td>$Payment.Money.Nice</td>
		</tr>
	</table>
</div>