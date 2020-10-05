<?php

namespace SwipeStripe\Core\Order;

use SilverStripe\Core\Extension;

class PaymentProcessorExtension extends Extension
{
    public function onBeforeRedirect()
    {
        $order = $this->owner->payment->Order();
        if ($order && $order->exists()) {
            $order->onAfterPayment();
        }
    }
}
