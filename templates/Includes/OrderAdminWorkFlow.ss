
<div id="OrderWorkFlow">

  <div id="OrderPending" class="WorkFlowProcess">
  
    <p id="status" class="field radiobutton ">
		  <input type="radio" class="radio" id="Form_EditForm_status_1" name="status" value="Pending" <% if Status = Pending %>checked="checked"<% end_if %>>
		  <label class="right StatusTitle" for="Form_EditForm_status_1">Pending</label>
		</p>
		<p class="Meaning">
		  Order has been made, waiting for payment to be approved.
		</p>
  
  </div>
  
  <div id="OrderProcessing" class="WorkFlowProcess">
  
    <p id="status" class="field radiobutton ">
      <input type="radio" class="radio" id="Form_EditForm_status_2" name="status" value="Processing" <% if Status = Processing %>checked="checked"<% end_if %>>
      <label class="right StatusTitle" for="Form_EditForm_status_2">Processing</label>
    </p>
    <p class="Meaning">
      Payment has been approved, order is being processed ready for dispatch.
    </p>
    
  </div>
  
  <div id="OrderDispatched" class="WorkFlowProcess">
  
    <p id="status" class="field radiobutton ">
      <input type="radio" class="radio" id="Form_EditForm_status_3" name="status" value="Dispatched" <% if Status = Dispatched %>checked="checked"<% end_if %>>
      <label class="right StatusTitle" for="Form_EditForm_status_3">Dispatched</label>
    </p>
    <p class="Meaning">
      Order has been completed and dispatched to customer.
    </p>
  
  </div>
  
</div>
