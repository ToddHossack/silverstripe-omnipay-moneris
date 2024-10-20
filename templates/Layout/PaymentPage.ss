<div class="PaymentPage__layout" role="main">

	<h1>$Title <% if $SubTitle %></h1>

	<div class="typography">
		<% if $Content %>$Content<% end_if %>

		<% if $PaymentErrors.count() %>
		<div class="PaymentPage__errors typography">
			<h4><%t PaymentPage.Errors 'Errors' %></h4>
			<ul>
			<% loop $PaymentErrors %>
				<li>$Error</li>
			<% end_loop %>
			</ul>
			<a href="$StartLink" class="PaymentResult_action">Try again</a>
		</div>

		$PaymentForm
	</div>
</div>
