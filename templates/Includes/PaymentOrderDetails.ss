<div class="PaymentResult__order">
	<% if $ContactDetails %>
	<h4>Contact Details</h4>
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<% loop $ContactDetails %>
		<tr>
			<th>$Title:</th>
			<td><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
		</tr>
		<% end_loop %>
	</table>
	<% end_if %>

	<% if $OrderData %>
	<h4>Order</h4>
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<% loop $OrderData %>
		<tr>
			<th>$Title:</th>
			<td><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
		</tr>
		<% end_loop %>
	</table>
	<% end_if %>

	<% if $MailingAddress %>
	<h4>Mailing Address</h4>
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<% loop $MailingAddress %>
		<tr>
			<th>$Title:</th>
			<td><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
		</tr>
		<% end_loop %>
	</table>
	<% end_if %>
</div>