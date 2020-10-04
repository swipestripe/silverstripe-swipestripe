<?php

namespace SwipeStripe\Core\Order;

use SilverStripe\Core\Extension;

class Payment_ProcessorExtension extends Extension
{
    public function onBeforeRedirect()
    {
        $order = $this->owner->payment->Order();
        if ($order && $order->exists()) {
            $order->onAfterPayment();
        }
    }
}
