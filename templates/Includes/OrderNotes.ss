<table class="table table-bordered">
	<tr>
		<th><% _t('OrderNotes.NOTES','Notes') %></th>
	</tr>
	<% loop CustomerUpdates %>
		<% if Note %>
		<tr>
			<td>
				$Note
			</td>
		</tr>
		<% end_if %>
	<% end_loop %>
</table>
