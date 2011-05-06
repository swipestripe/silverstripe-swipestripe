<div class="typography">

<h2>$Title</h2>
$Content

<ul>
<% control Orders %>
  <li>
    <div>
      <h4><a href="$Link">Order #$ID - $Status </a></h4>
      <p>
        Order created: $Created.Format(j M y). <br />
        Total: $Total.Nice. <br />
        
        Items: 
        <% control Products %>
          <a href="$Link" target="_blank">$Title</a><% if Last %>.<% else %>, <% end_if %>
        <% end_control %>
        <br />
        
        <% if Downloads %>
        <em>You can access downloads by <a href="$Link#DownloadsTable">viewing this order</a>.</em>
        <% end_if %>
      </p>
    </div>
  </li>
<% end_control %>
</ul>

</div>