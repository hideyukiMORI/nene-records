<?php

declare(strict_types=1);

namespace NeNeRecords\Analytics;

use Nene2\Http\ClockInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Setting\SettingRepositoryInterface;
use Throwable;

/**
 * Single source of truth for the Path B privacy-first visitor fields (ADR 0006).
 *
 * Both the access-log middleware (fields from request headers/URI) and the LP beacon
 * endpoint (fields from the posted payload) resolve visitor data through here, so the
 * opt-in gate, hash recipe, and allowlists never drift between the two producers.
 *
 * When the owning org has not opted in (`analytics_visitor_tracking`), every field is null
 * — the raw IP/UA/referer/query are never touched, let alone stored.
 */
final readonly class VisitorFieldsResolver
{
    /**
     * @param RequestScopedHolder<int> $orgId
     */
    public function __construct(
        private SettingRepositoryInterface $settings,
        private AnalyticsSaltRepositoryInterface $salts,
        private ClockInterface $clock,
        private RequestScopedHolder $orgId,
    ) {
    }

    public function trackingEnabled(): bool
    {
        try {
            $value = $this->settings->findValueByKey('analytics_visitor_tracking');
        } catch (Throwable) {
            return false;
        }

        return $value !== null && ($value->value === 'true' || $value->value === '1');
    }

    /**
     * @return array{
     *     visitorHash: ?string, refererHost: ?string, utmSource: ?string, utmMedium: ?string,
     *     utmCampaign: ?string, ref: ?string, clientType: ?string, isBot: ?bool
     * }
     */
    public function resolve(string $clientIp, string $referer, string $query, string $userAgent): array
    {
        if (!$this->trackingEnabled()) {
            return self::empty();
        }

        $salt = $this->salts->saltForDate($this->clock->now());
        $attribution = QueryAttribution::fromQueryString($query);
        $ua = UserAgentClassifier::classify($userAgent);

        return [
            'visitorHash' => VisitorHasher::hash($salt, $clientIp, (int) $this->orgId->get()),
            'refererHost' => RefererHost::fromReferer($referer),
            'utmSource' => $attribution['utmSource'],
            'utmMedium' => $attribution['utmMedium'],
            'utmCampaign' => $attribution['utmCampaign'],
            'ref' => $attribution['ref'],
            'clientType' => $ua['type'],
            'isBot' => $ua['isBot'],
        ];
    }

    /**
     * @return array{
     *     visitorHash: null, refererHost: null, utmSource: null, utmMedium: null,
     *     utmCampaign: null, ref: null, clientType: null, isBot: null
     * }
     */
    public static function empty(): array
    {
        return [
            'visitorHash' => null, 'refererHost' => null, 'utmSource' => null,
            'utmMedium' => null, 'utmCampaign' => null, 'ref' => null,
            'clientType' => null, 'isBot' => null,
        ];
    }
}
