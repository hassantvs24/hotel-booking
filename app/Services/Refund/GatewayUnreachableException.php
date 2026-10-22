<?php

namespace App\Services\Refund;

use RuntimeException;

/**
 * SSLCommerz did not give a readable answer (no connection, a timeout, a server error). The refund request may or may not
 * have reached it, so a refund that fails like this must be tried again with the SAME transaction id (SSLCommerz then knows
 * it is the same request) and the admin must check the SSLCommerz panel first.
 */
class GatewayUnreachableException extends RuntimeException
{
}
