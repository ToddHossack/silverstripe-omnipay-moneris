<div class="MonerisOrder"
<table class="" cellspacing="0" cellpadding="0">
	<tr>
		<td class="fieldLabel">First name:</td>
		<td class="fieldData">$Order.FirstName</td>
	</tr>
	<tr>
		<td class="fieldLabel">Last name / business name:</td>
		<td class="fieldData">$Order.LastName</td>
	</tr>
	<tr>
		<td class="fieldLabel">Email:</td>
		<td class="fieldData">$Order.Email</td>
	</tr>
	<tr>
		<td class="fieldLabel">Phone:</td>
		<td class="fieldData">$Order.Phone</td>
	</tr>
	<tr>
		<td class="fieldLabel">Department paid:</td>
		<td class="fieldData">$Order.Department</td>
	</tr>
	<tr>
		<td class="fieldLabel">Account / Invoice #:</td>
		<td class="fieldData">$Order.CustomerAccount</td>
	</tr>
	<tr>
		<td class="fieldLabel">Folio Number:</td>
		<td class="fieldData">$FolioNumber</td>
	</tr>
	<tr>
		<td class="fieldLabel">Application Type:</td>
		<td class="fieldData">$Order.ApplicationType</td>
	</tr>
	<tr>
		<td class="fieldLabel">Item paid for:</td>
		<td class="fieldData">$Order.Item</td>
	</tr>
	<tr>
		<td class="fieldLabel">Payment Amount:</td>
		<td class="fieldData">$$Payment.Amount.Nice $Payment.Money.Currency</td>
	</tr>
	<tr>
		<td class="fieldLabel">Comments:</td>
		<td class="fieldData">$Order.Comments</td>
	</tr>
</table>