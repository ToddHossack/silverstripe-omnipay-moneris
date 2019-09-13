<div class="PaymentResult__payment">
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<tr>
			<th><%t Payment.Identifier 'Reference No.' %>:</th>
			<td>$Payment.Identifier</td>
		</tr>
		<tr>
			<th><%t Payment.Status 'Status' %>:</th>
			<td>$Payment.TranslatedStatus</td>
		</tr>
		<tr>
			<th><%t Payment.MoneyAmount 'Payment Amount' %>:</th>
			<td>$Payment.Money.Nice</td>
		</tr>
	</table>
</div>