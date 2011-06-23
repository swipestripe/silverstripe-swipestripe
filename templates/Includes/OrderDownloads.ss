<table id="DownloadsTable" class="infotable">
  <thead>     
    <tr class="gap mainHeader">
        <th colspan="10" class="left">Downloads</th>
    </tr>
    <tr>
      <th scope="row" class="twoColHeader">Product</th>
      <th scope="row"  class="twoColHeader">Quantity</th>
      <th scope="row"  class="twoColHeader">Download Limit</th>
      <th scope="row"  class="twoColHeader">Download Link</th>
    </tr>
  </thead>
  <tbody>
    <% control Downloads %>  
      <tr>
        <% control Object %> 
        <td class="productTitle">$Title</td>
        <% end_control %>
        
        <td class="quantity">$Quantity</td>
        <td class="downloadLimit">$DownloadLimit ($RemainingDownloadLimit downloads remaining)</td>
        <td class="downloadLink">
          <% if DownloadLink %>
            <a href="$DownloadLink" target="_blank">Download</a>
            downloaded $DownloadCount time(s)
          <% else %>
          
            <% if RemainingDownloadLimit = 0 %>
              There are no downloads remaining, you have<br /> reached your limit.
            <% else %>
              Download link will appear when payment is complete.
            <% end_if %>
            
          <% end_if %>
        </td>
      </tr>
    <% end_control %>
  </tbody>
</table>
