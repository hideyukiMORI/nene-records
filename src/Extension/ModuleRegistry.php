<?php

declare(strict_types=1);

namespace NeNeRecords\Extension;

/**
 * Discovers optional/private modules present in the current build.
 *
 * A module activates by PRESENCE: if its entrypoint class is installed (e.g. via a
 * private composer path package — the same mechanism used to inject NENE2), it is
 * composed on top of core; otherwise core runs as plain permissive OSS. There is
 * no config toggle and no obfuscation — see ADR 0005.
 *
 * The default candidates are forward references: a name only resolves to a class
 * when its private package is installed, so a fresh `git clone` discovers nothing.
 */
final class ModuleRegistry
{
    /**
     * Well-known optional-module entrypoints (plain strings, NOT class-string
     * literals, so they don't have to exist for static analysis to pass).
     *
     * @var list<string>
     */
    private const DEFAULT_CANDIDATES = [
        // Commercial layer (billing / plan-based entitlements / account) — private
        // package, present only in the hosted build. See ADR 0005.
        'NeNeRecords\\Commercial\\CommercialModule',
    ];

    /** @var list<string> */
    private array $candidates;

    /** @param list<string>|null $candidates override for tests; null = defaults */
    public function __construct(?array $candidates = null)
    {
        $this->candidates = $candidates ?? self::DEFAULT_CANDIDATES;
    }

    /** @return list<ModuleInterface> */
    public function modules(): array
    {
        $modules = [];

        foreach ($this->candidates as $candidate) {
            // class_exists() narrows $candidate to class-string and triggers the
            // autoloader; a missing private package simply yields false.
            if (!class_exists($candidate)) {
                continue;
            }

            $instance = new $candidate();

            if ($instance instanceof ModuleInterface) {
                $modules[] = $instance;
            }
        }

        return $modules;
    }
}
