<% if IncludeFormTag %>
<form $FormAttributes>
<% end_if %>

	<% if Message %>
		<p id="{$FormName}_error" class="message $MessageType">$Message</p>
	<% else %>
		<p id="{$FormName}_error" class="message $MessageType" style="display: none"></p>
	<% end_if %>

	<fieldset>

		<% if PersonalDetailsFields %>
		<section class="personal-details">
			<% loop PersonalDetailsFields %>
				$FieldHolder
			<% end_loop %>
		</section>
		
		<hr />
		<% end_if %>
		
		<section class="order-details">
			<h3><%t CheckoutFormOrder.YOUR_ORDER 'Your Order' %></h3>

			<div id="cart-loading-js" class="cart-loading">
				<div>
					<h4><%t CheckoutFormOrder.LOADING 'Loading...' %></h4>
				</div>
			</div>
			
			<% include OrderFormCart %>
		</section>
	 

		<section class="notes">
			<% loop NotesFields %>
				$FieldHolder
			<% end_loop %>
		</section>
		
		<hr />
	 
		<section class="payment-details">
			<% loop PaymentFields %>
				$FieldHolder
			<% end_loop %>
		</section>

		<div class="clear" />
	</fieldset>

	<% if Cart.Items %>
		<% if Actions %>
		<div class="Actions">
			<div class="loading">
				<img src="swipestripe/images/loading.gif" />
			</div>
			<% loop Actions %>
				$Field
			<% end_loop %>
		</div>
		<% end_if %>
	<% end_if %>
	
<% if IncludeFormTag %>
</form>
<% end_if %>