<div class="PaymentResult__order">
	
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<tr>
			<th>First name:</th>
			<td>$Order.FirstName</td>
		</tr>
		<tr>
			<th>Last name / business name:</th>
			<td>$Order.LastName</td>
		</tr>
		<tr>
			<th>Email:</th>
			<td>$Order.Email</td>
		</tr>
		<tr>
			<th>Phone:</th>
			<td>$Order.Phone</td>
		</tr>
		<tr>
			<th>Payment Amount:</th>
			<td>$$Payment.Amount.Nice $Payment.Money.Currency</td>
		</tr>
		<tr>
			<th>Comments:</th>
			<td>$Order.Comments</td>
		</tr>
	</table>
</div>