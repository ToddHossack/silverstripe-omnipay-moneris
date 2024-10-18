<% if $PaymentErrors %><h1>$Title</h1><% end_if %>
	
	<div id="PaymentPageErrors" class="PaymentPage__errors typography" style="display: <% if $PaymentErrors %>block<% else %>none<% end_if %>; margin: 0 auto 10px auto; padding: 15px; background-color: #fff1cb; border: 1px solid #fcc917; width: 75%;">
		<h4 style="margin: 0; padding: 0; color: #b00; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,san-serif; margin: .8em 0 .4em; line-height: 1.2; font-size: 1.2rem; font-weight: 400;"><%t PaymentPage.Errors 'Error' %></h4>
		<% if $PaymentErrors %>
		<ul>
		<% loop $PaymentErrors %>
			<li>$Error</li>
		<% end_loop %>
		</ul>
		<% else %>
		<p id="PaymentPageJsError"></p>
		<% end_if %>
		<a href="$StartLink" class="PaymentResult_action">Try again</a>
	</div>

	<% if $PaymentErrors %>
	<% else %>
	<div id="monerisCheckout"></div>

	
	<script type="text/javascript">//<![CDATA[

		/*
		 * Functions
		 */

		/**
		 * Parses JSON
		 * @param {string} str
		 * @returns {object|array|string}
		 */
		var parseJson = function(str) {
			var result = '';
			try {
				result = JSON.parse(str);
			} catch(e) {
				console.log(e);
				//console.trace();
				result = {
					error: true,
					message: 'Error when parsing JSON response. ' + e
				};
			}
			return result;
		}

		/*
		 * Variables
		 */
		var formUrl = '$FormUrl',
			resultUrl = '$ResultUrl',
			gatewayTicket = '$GatewayTicket',
			gatewayMode = '$GatewayMode',
			responseCodes = parseJson('$ResponseCodes')
			debug = true;

		/**
		 * Extracts response code from json
		 * @param {object} json
		 * @returns {string|null}
		 */
		var getResponseCode = function(response) {
			if(typeof response === 'object' && response.hasOwnProperty('response_code')) {
				return response.response_code;
			}
			return null;
		}

		/**
		 * Finds message corresponding to given response code
		 * @param {object} json
		 * @returns {string|null}
		 */
		var getResponseCodeMessage = function(code) {
			if(typeof responseCodes === 'object' 
			&& responseCodes.hasOwnProperty(code) 
			&& typeof responseCodes[code] === 'object'
			&& responseCodes[code].hasOwnProperty('message')) {
				return responseCodes[code].message;
			}
			return null;
		}
		
		/**
		 * Shows error message
		 * @param {string} err
		 */
		var showError = function(err) {
			try {
				// Use alert over iframe
				if(document.getElementById('monerisCheckout')) {
					window.alert(err + ': Please go back and try again.');
				}
				// Show error callout
				else {
					document.getElementById("PaymentPageJsError").textContent = err || 'Unknown error';
					document.getElementById("PaymentPageErrors").style.display = "block";
				}
			} catch(e) {
				console.log('Could not show error. "'+ e +'"');
			}
		}
		
		/**
		 * Determins if reponse object is considered an error.
		 * @param {object} json
		 * @returns {string|null}
		 */
		var responseIsError = function(response) {
			var code = getResponseCode(response);
			if(typeof responseCodes === 'object' 
			&& responseCodes.hasOwnProperty(code) 
			&& typeof responseCodes[code] === 'object'
			&& !responseCodes[code].hasOwnProperty('error')) {
				return false;
			}
			return true;
		}

		var getResponseErrorMessage = function(response) {
			if(!responseIsError(response)) {
				return null;
			}
			var code = getResponseCode(response);
			if(!code) {
				return 'No response code found';
			}
			return getResponseCodeMessage(code);
		}

		var getResponseHandler = function(response) {
			if(typeof response === 'object' && response.hasOwnProperty('handler')) {
				return response.handler;
			}
			return null;
		}
		/*
		 * Instantiate checkout
		 */
		 try { 
			var myCheckout = new monerisCheckout();
			myCheckout.setMode(gatewayMode);
			myCheckout.setCheckoutDiv("monerisCheckout");
		 } catch(e) {
			showError('Initialization error. "'+ e +'"');
			console.log('Initialization error',e);
		}
		
		/**
		 * Sends user to given URL, or back to form, if no URL provided.
		 * @returns {null}
		 */
		var goToUrl = function(url) {
			if(debug) console.log('goToUrl', url);
			try {
				if(url) {
					window.open(url);
				} else {
					history.back();
				}
			} catch(e) {
				showError('Redirection error. "'+ e +'"');
				if(debug) console.log('goToUrl error',e);
			}
		}

		/**
		 * Callback handler
		 * @param {string} response string
		 * @returns {null}
		 */
		var callbackHandler = function(responseStr) {
			if(debug) console.log('callbackHandler',responseStr);
			var response = parseJson(responseStr);
			if(debug) console.log('callbackHandler',response);
			// Error check
			if(responseIsError(response)) {
				console.log('callbackHandler hasError');
				showError(getResponseErrorMessage(response));
				return;
			}
			// Determine handler and perform relevant action 
			var handler = getResponseHandler(response);
			if(debug) console.log('callbackHandler handler', handler);

			switch(handler) {
				case 'error_event':
					showError(getResponseErrorMessage(response));
				break;
				case 'payment_complete':
				case 'cancel_transaction':
					myCheckout.closeCheckout(" ");
					goToUrl(resultUrl);	// Proceed to result page
				break;
				case 'page_closed':
					if(debug) console.log('handle page_closed');
					myCheckout.closeCheckout(" ");
					goToUrl(formUrl);	// Return to form page
				break;
				case 'page_loaded':
				case 'payment_submitted':
				case 'payment_receipt':
				default:
					// No action
				break;
			}

		}
		
		/*
		 * Test
		 
		var testResponse = '$TestResponse';
		callbackHandler(testResponse);
		*/
		/*
		 * Set callbacks
		 */
		 try { 
			myCheckout.setCallback("page_loaded", callbackHandler);
			myCheckout.setCallback("cancel_transaction", callbackHandler);
			myCheckout.setCallback("error_event", callbackHandler);
			myCheckout.setCallback("payment_receipt", callbackHandler);
			myCheckout.setCallback("payment_complete", callbackHandler);
			myCheckout.setCallback("page_closed",callbackHandler);
			myCheckout.setCallback("payment_submitted",callbackHandler);
		} catch(e) {
			showError('Set callback error. "'+ e +'"');
			if(debug) console.log('Set callback error',e);
		}

		/*
		 * Start
		 */
		try { 
			myCheckout.startCheckout(gatewayTicket);
		} catch(e) {
			showError('Start checkout error. "'+ e +'"');
			if(debug) console.log('Start checkout error',e);
		}
		
		
	//]]></script>
<% end_if %>


