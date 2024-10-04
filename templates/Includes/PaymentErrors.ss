<div class="PaymentPage__errors typography">
	<h4><%t PaymentPage.Errors 'Errors' %></h4>
	<ul>
	<% loop $PaymentErrors %>
		<li>$Error</li>
	<% end_loop %>
	</ul>
	<a href="$StartLink" class="PaymentResult_action">Try again</a>
</div>