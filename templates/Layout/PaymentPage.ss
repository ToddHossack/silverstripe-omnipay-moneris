<div class="PaymentPage__layout" role="main">

	<h1>$Title <% if $SubTitle %></h1>

	<div class="typography">
		<% if $Content %>$Content<% end_if %>

		<% if $PaymentErrors.count() %>
			<% include PaymentErrors %>
		<% end_if %>

		$PaymentForm
	</div>
</div>
