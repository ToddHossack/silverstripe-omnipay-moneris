<div class="PaymentResult__order">
	<table class="PaymentResult__table" cellspacing="0" cellpadding="0">
		<% loop $OrderData %>
		<tr>
			<th>$Title:</th>
			<td><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
		</tr>
		<% end_loop %>
	</table>
</div>