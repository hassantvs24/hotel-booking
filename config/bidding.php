<?php

/*
 | How long each step of an offer (bid) may take.
 |
 |  pending_hours : a new offer waits this long for the owner to answer. It is also how long a guest has to answer
 |                  an owner's counter offer. After that the offer lapses.
 |  payment_hours : once an offer (or a counter) is accepted, the guest has this long to pay.
 */
return [
    'pending_hours' => 8,
    'payment_hours' => 2,
];
