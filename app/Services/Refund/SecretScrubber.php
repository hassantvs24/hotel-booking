<?php

namespace App\Services\Refund;

use Throwable;

/**
 * SSLCommerz wants the store password in the web address (a GET request), and the HTTP client copies the whole address into
 * its error text ("cURL error 28 ... for https://...?store_passwd=XXXX"). Anything that logs or rethrows such an error must
 * go through here first, or the password ends up in the log files.
 */
final class SecretScrubber
{
    public static function scrub(string $text): string
    {
        $text = (string) preg_replace('/((?:store_)?pass(?:wd|word)?|store_pass)=[^&\s)"\']*/i', '$1=***', $text);

        foreach (array_filter([config('sslcommerz.apiCredentials.store_password')]) as $secret) {
            foreach ([(string) $secret, urlencode((string) $secret), rawurlencode((string) $secret)] as $form) {
                $text = $form === '' ? $text : str_replace($form, '***', $text);
            }
        }

        return $text;
    }

    /** One safe line for a log: the kind of error and its scrubbed message */
    public static function describe(Throwable $e): string
    {
        return class_basename($e) . ': ' . self::scrub($e->getMessage());
    }
}
