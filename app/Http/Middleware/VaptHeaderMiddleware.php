<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VaptHeaderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    private $unwantedHeaderList = [
        'X-Powered-By',
        'Server',
    ];

    public function handle(Request $request, Closure $next): Response
    {

        // return $next($request);

        $this->removeUnwantedHeaders($this->unwantedHeaderList);

        $response = $next($request);

        if (!app()->environment('local')) {

            // this only works with HTTPS
            foreach ($response->headers->getCookies() as $cookie) {
                $response->headers->setCookie($cookie->withSecure(true)->withHttpOnly(true)->withSameSite('lax'));
            }

        }


        // $defaultSrc = "default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://fonts.bunny.net 'unsafe-inline' 'unsafe-eval'";
        // $styleSrc = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net";
        // $fontSrc = "font-src 'self' data: https://fonts.gstatic.com https://fonts.bunny.net";
        // $scriptSrc = "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://www.google.com/recaptcha/api.js https://www.gstatic.com/recaptcha/ https://cdn.jsdelivr.net/npm/chart.js https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js";
        // $imgSrc = "img-src 'self' data: blob: https://*.ap-south-1.amazonaws.com";
        // $connectSrc = "connect-src 'self' ws://127.0.0.1:* http://127.0.0.1:*";
        // $frameSrc = "frame-src 'self' https://*.ap-south-1.amazonaws.com https://agencyportal.irdai.gov.in/PublicAccess/LookUpPAN.aspx https://pos.iib.gov.in https://www.itrex.in/USN/SelfDeactivatePAN.aspx https://www.google.com/ https://www.google.com/recaptcha/";

        // $csp = trim("$defaultSrc; $styleSrc; $fontSrc; $scriptSrc; $imgSrc; $connectSrc; $frameSrc;");
        // $response->headers->set('Content-Security-Policy', $csp);

        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        $response->headers->add([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
        return $response;
    }

    private function removeUnwantedHeaders($headerList)
    {
        foreach ($headerList as $header)
            header_remove($header);
    }
}
