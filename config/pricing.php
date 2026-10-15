<?php

/*
 | VAT and service charge on room prices.
 |
 | These are only the last-resort defaults: the real platform rates live in the admin settings
 | (settings table, group "tax"). A property can override them, and so can a single room.
 */
return [
    'default_vat_percent'            => 15,
    'default_service_charge_percent' => 10,

    // false: VAT and service charge are both worked out on the room price (2,000 -> 2,500).
    // true : service charge first, VAT on price + service charge (2,000 -> 2,530), the usual hotel bill.
    'vat_on_service_charge' => false,
];
