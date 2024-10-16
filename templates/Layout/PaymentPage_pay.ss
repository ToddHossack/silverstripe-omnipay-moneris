<% if $PaymentErrors %>
	<h1>$Title</h1>
	<div class="PaymentPage__errors typography" >
		<h4><%t PaymentPage.Errors 'Errors' %></h4>
		<ul>
		<% loop $PaymentErrors %>
			<li>$Error</li>
		<% end_loop %>
		</ul>
		<a href="$StartLink" class="PaymentResult_action">Try again</a>
	</div>

	<% else %>

	<script type="text/javascript" src="https://gatewayt.moneris.com/chktv2/js/chkt_v2.00.js"></script>

	<div id="monerisCheckout"></div>


	<script type="text/javascript">//<![CDATA[
		/*
		 * Instantiate checkout
		 */
		var myCheckout = new monerisCheckout();
		myCheckout.setMode('$GatewayMode');
		myCheckout.setCheckoutDiv("monerisCheckout");

		/*
		 * Define callbacks
		 */
		var myPageLoad = function() {
			console.log('myPageLoad',arguments);
		}

		var myCancelTxn = function() {
			console.log('myCancelTxn',arguments);
			myCheckout.closeCheckout(" ");
		}

		var myErrorEvent = function() {
			console.log('myErrorEvent',arguments);
			myCheckout.closeCheckout(" ");
		}

		var myPaymentReceipt = function() {
			console.log('myPaymentReceipt',arguments);
		}

		var myPaymentComplete = function() {
			console.log('myPaymentComplete',arguments);
		}

		var myPageClosed = function() {
			console.log('myPageClosed',arguments);
		}

		var myPaymentSubmitted = function() {
			console.log('myPaymentSubmitted',arguments);
		}

		/*
		 * Set callbacks
		 */
		myCheckout.setCallback("page_loaded", myPageLoad);
		myCheckout.setCallback("cancel_transaction", myCancelTxn);
		myCheckout.setCallback("error_event", myErrorEvent);
		myCheckout.setCallback("payment_receipt", myPaymentReceipt);
		myCheckout.setCallback("payment_complete", myPaymentComplete);
		myCheckout.setCallback("page_closed",myPageClosed);
		myCheckout.setCallback("payment_submitted",myPaymentSubmitted);

		/*
		 * Start
		 */
		myCheckout.startCheckout('$GatewayTicket');

	//]]></script>

<% end_if %>

