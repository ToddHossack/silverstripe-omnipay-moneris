<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
</head>
<body style="font-family: Open Sans Regular,Helvetica,Arial,sans-serif; background-color: #fff;>
	<div style="padding: 40px; color: #222; font-size: 1rem; line-height: 1.6; font-family: Open Sans Regular,Helvetica,Arial,sans-serif; line-height: 1.2">
		<h1 style="margin: 0; padding: 10px 0 20px 0; font-size: 1.6em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">$EmailTitle</h1>
		<table cellspacing="0" cellpadding="0" style="font-family: inherit; border: 1px solid #ccc; border-collapse: collapse;">
			<!-- Result heading -->
			<tr>
				<th colspan="2" style="padding: 20px; text-align: left; background-color: #eee; border-bottom: 1px solid #ccc;"><h2 style="margin: 0; font-size: 1.4em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">$Result.Type: $Result.Title</h2></th>
			</tr>
			<!-- Merchant name -->
			<tr>
				<td colspan="2" style="padding: 10px 20px;">
					<h4 style="margin: 0; font-size: 1.2em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">$MerchantName</h4>
				</td>
			</tr>
			<!-- Merchant address and contact details -->
			<tr>
				<td style="padding: 10px 20px;">
					<% if $MerchantPhysicalAddressHTML %>
					<h5 style="margin: 0 0 .5em 0; font-size: 1em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Physical address</h5>
					<address style="margin: 0 0 .5em 0; padding: 0; font-style: normal; font-size: 1em; line-height: 1.2">$MerchantPhysicalAddressHTML</address>
					<% end_if %>
					<% if $MerchantPostalAddressHTML %>
						<h5 style="margin: 0 0 .5em 0; font-size: 1em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Postal address</h5>
						<address style="margin: 0 0 .5em 0; padding: 0; font-style: normal; font-size: 1em; line-height: 1.2">$MerchantPostalAddressHTML</address>
					<% end_if %>
				</td>
				<td style="padding: 10px 20px;">
					<h5 style="margin: 0 0 .5em 0; font-size: 1em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Contact</h5>
					<% if $MerchantEmail %>Email: $MerchantEmail<br /><% end_if %>
					<% if $MerchantPhone %>Phone: $MerchantPhone<br /><% end_if %>
					<% if $MerchantWebsite %>Website: $MerchantWebsite<% end_if %>
				</td>
			</tr>
			<!-- Payment page link -->
			<tr>
				<td colspan="2" style="padding: 10px 20px 15px 20px;">
					<h5 style="margin: 0 0 .5em 0; font-size: 1em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Payment page</h5>
					<p>$PaymentUrl</p>
				</td>
			</tr>



		<!-- Order Details Section -->
			<tr>
				<th colspan="2" style="padding: 20px; text-align: left; background-color: #eee; border-bottom: 1px solid #ccc;"><h3 style="margin: 0; font-size: 1.4em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Order Details</h3></th>
			</tr>

		<% if $ContactDetails %>
			<tr>
				<td colspan="2" style="padding: 20px 20px 10px 20px;">
					<h4 style="margin: 0; font-size: 1.2em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Contact Details</h4>
				</td>
		    </tr>
			<% loop $ContactDetails %>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;">$Title:</th>
				<td style="width: 50%; padding: 3px 20px;"><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
			</tr>
			<% end_loop %>
			<tr>
				<td colspan="2" style="padding: 5px;"></td>
			</tr>
		<% end_if %>
					
		<% if $OrderData %>
			<tr>
				<td colspan="2" style="padding: 10px 20px">
					<h4 style="margin: 0; font-size: 1.2em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Order</h4>
				</td>
		    </tr>
			<% loop $OrderData %>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;">$Title:</th>
				<td style="width: 50%; padding: 3px 20px;"><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
			</tr>
			<% end_loop %>
			<tr>
				<td colspan="2" style="padding: 5px;"></td>
			</tr>
		<% end_if %>
		
				
		<% if $MailingAddress %>
			<tr>
				<td colspan="2" style="padding: 10px 20px;">
					<h4 style="margin: 0; font-size: 1.2em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Mailing Address</h4>
				</td>
		    </tr>
			<% loop $MailingAddress %>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;">$Title:</th>
				<td style="width: 50%; padding: 3px 20px;"><% if $TranslatedData %>$TranslatedData<% else %>$Data<% end_if %></td>
			</tr>
			<% end_loop %>
			<tr>
				<td colspan="2" style="padding: 5px;"></td>
			</tr>
		<% end_if %>


		<!-- Transaction Details Section -->
			<tr>
				<th colspan="2" style="padding: 20px; text-align: left; background-color: #eee; border-bottom: 1px solid #ccc;"><h3 style="margin: 0; font-size: 1.4em; font-family: Trebuchet MS,Fira Sans Condensed,Arial Narrow,sans-serif; line-height: 1.2; font-weight: 400; color: #003263;">Transaction Details</h3></th>
			</tr>
		<% if $Payment %>
			<tr>
				<th style="width: 50%; padding: 20px 20px 0 20px; text-align: left; font-weight: normal;"><%t Order.OrderNumber 'Order No.' %>:</th>
				<td style="width: 50%; padding: 20px 20px 0 20px;">$Payment.Identifier</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;"><%t PaymentMessage.Created 'Date/time' %>:</th>
				<td style="width: 50%; padding: 3px 20px;">$LastMessage.Created</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;"><%t PaymentMessage.db_Message 'Message' %>:</th>
				<td style="width: 50%; padding: 3px 20px;">$LastMessage.Message</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;"><%t PaymentMessage.Code 'Response Code' %>:</th>
				<td style="width: 50%; padding: 3px 20px;">$LastMessage.Code</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;"><%t Payment.Status 'Status' %>:</th>
				<td style="width: 50%; padding: 3px 20px;">$Payment.TranslatedStatus</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 3px 20px; text-align: left; font-weight: normal;"><%t Payment.db_Reference 'Reference #' %>:</th>
				<td style="width: 50%; padding: 3px 20px;">$Payment.TransactionReference</td>
			</tr>
			<tr>
				<th style="width: 50%; padding: 0 20px 20px 20px; text-align: left; font-weight: normal;"><%t Payment.MoneyAmount 'Amount' %>:</th>
				<td style="width: 50%; padding: 0 20px 20px 20px;">$Payment.Money.Nice</td>
			</tr>
		<% else %>
			<tr>
				<td colspan="2" style="padding: 10px 20px;">
					No transaction details
				</td>
			</tr>
		<% end_if %>

		</table>
	</div>

</body>
</html>