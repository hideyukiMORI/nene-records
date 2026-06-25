<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

/**
 * Builds the `<head>` analytics tag for public pages: a CSP-nonce'd inline
 * script that installs the Consent Mode v2 *default* (EU-safe `denied` unless
 * the admin opted into `granted`) **before** the GA4 / GTM loader runs, so no
 * storage is read until consent is (later) updated client-side (PR-A2 banner).
 *
 * - GTM container present → load GTM (it orchestrates GA4 and any other tags).
 * - else GA4 id present   → load gtag.js directly and `config` the property.
 *
 * The inline script carries `nonce="..."`; the matching nonce is added to
 * `script-src` by {@see PublicHtmlCsp::build()}. Inputs are pre-validated
 * ({@see WebAnalyticsConfig}: ids are `[A-Za-z0-9_-]`, consent is an enum, the
 * nonce is hex) so verbatim interpolation carries no injection surface.
 */
final class WebAnalyticsHeadSnippet
{
    private function __construct()
    {
    }

    public static function render(WebAnalyticsConfig $config, string $nonce): string
    {
        if (!$config->isEnabled()) {
            return '';
        }

        $consent = $config->consentDefault; // 'denied' | 'granted'
        $nonceAttr = htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8');

        // Consent Mode v2 defaults — applied before any loader fires.
        $bootstrap = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            . "gtag('consent','default',{"
            . "'ad_storage':'{$consent}',"
            . "'ad_user_data':'{$consent}',"
            . "'ad_personalization':'{$consent}',"
            . "'analytics_storage':'{$consent}',"
            . "'functionality_storage':'{$consent}',"
            . "'personalization_storage':'{$consent}',"
            . "'security_storage':'granted',"
            . "'wait_for_update':500"
            . '});'
            . "gtag('set','ads_data_redaction',true);"
            . "gtag('set','url_passthrough',true);";

        if ($config->gtmId !== null) {
            $inline = $bootstrap . self::gtmLoader($config->gtmId);

            return "<script nonce=\"{$nonceAttr}\">{$inline}</script>";
        }

        // Direct GA4 (gtag.js): external loader + inline init. $config->ga4Id is
        // non-null here because isEnabled() held and gtmId is null.
        $inline = $bootstrap . "gtag('js',new Date());gtag('config','{$config->ga4Id}');";

        return "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$config->ga4Id}\" nonce=\"{$nonceAttr}\"></script>"
            . "<script nonce=\"{$nonceAttr}\">{$inline}</script>";
    }

    private static function gtmLoader(string $gtmId): string
    {
        return "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
            . "var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';"
            . "j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;"
            . "f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$gtmId}');";
    }
}
