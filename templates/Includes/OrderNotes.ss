<table class="table table-bordered">
  <tr>
    <th><% _t('OrderNotes.NOTES','Notes') %></th>
  </tr>
  <% loop CustomerUpdates %>
  <tr>
    <td>
      $Note
    </td>
  </tr>
  <% end_loop %>
</table>
