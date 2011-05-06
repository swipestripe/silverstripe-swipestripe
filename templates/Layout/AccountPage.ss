<div class="typography">

<h2>$Title</h2>
$Content

<ul>
<% control Orders %>
  <li>
    <div>
      <h4><a href="$Link">Order #$ID - $Status </a></h4>
      <p>
        Order created: $Created.Nice. <br />
        Status: $Status. <br />
        Total: $Total.Nice. <br />
      </p>
    </div>
  </li>
<% end_control %>
</ul>

</div>