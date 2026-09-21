<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Model\LegalGuarantee;

/**
 * Single source of truth for the shipped official EU legal-guarantee labels
 * (Gesetzliche Gewährleistung, EmpCo — mandatory 27 Sep 2026).
 *
 * A "language" here is one shipped official label: its admin display name, the
 * standalone-page copy, and the verbatim Commission SVG. It is NOT tied to any
 * store-view code or locale — which store view shows which label is chosen per
 * store view in admin (Byte8 > Compliance > Legal Guarantee > Official Label
 * Language), so the module is portable across clients whatever their store codes.
 *
 * To add coverage for another EU language: add an entry here, ship its official
 * SVG (view/frontend/web/images/legal-guarantee-<key>.svg) and its i18n. It then
 * appears automatically in the admin select and the standalone-page patch.
 *
 * The label graphic is the official Commission artwork shipped verbatim; its
 * elements are non-editable (Durchführungsverordnung (EU) 2025/1960, Anhang I).
 */
class LabelCatalog
{
    /**
     * Language key => shipped-label metadata. The key doubles as the SVG suffix
     * (legal-guarantee-<key>.svg) and the value stored in the admin config field.
     *
     * NOTE (copy sign-off): the `intro` sentences are Byte8 drafts and should be
     * confirmed by the client's Rechtsanwalt. The label graphic itself is the
     * official, legally-fixed artwork and must not be edited.
     */
    private const LANGUAGES = [
        'de' => [
            'name' => 'German (Deutschland)',
            'title' => 'Gesetzliche Gewährleistung',
            'intro' => 'Als Verkäufer informieren wir Sie mit dem harmonisierten EU-Gewährleistungslabel über Ihre gesetzlichen Rechte. Für Verbrauchsgüter gilt eine gesetzliche Gewährleistung von mindestens zwei Jahren.',
        ],
        'it' => [
            'name' => 'Italian (Italia)',
            'title' => 'Garanzia legale di conformità',
            'intro' => 'In qualità di venditore, vi informiamo dei vostri diritti tramite l\'etichetta UE armonizzata sulla garanzia legale. I beni di consumo beneficiano di una garanzia legale di conformità di almeno due anni.',
        ],
        'nl' => [
            'name' => 'Dutch (Nederland)',
            'title' => 'Wettelijke garantie',
            'intro' => 'Als verkoper informeren wij u met het geharmoniseerde EU-garantielabel over uw wettelijke rechten. Voor consumptiegoederen geldt een wettelijke garantie van ten minste twee jaar.',
        ],
    ];

    /**
     * All shipped language keys (e.g. ['de', 'it', 'nl']).
     *
     * @return string[]
     */
    public function getKeys(): array
    {
        return array_keys(self::LANGUAGES);
    }

    /**
     * True when $key is a shipped label language.
     */
    public function has(string $key): bool
    {
        return $key !== '' && isset(self::LANGUAGES[$key]);
    }

    /**
     * Metadata (name/title/intro) for a shipped language, or null if unknown.
     *
     * @return array{name: string, title: string, intro: string}|null
     */
    public function get(string $key): ?array
    {
        return self::LANGUAGES[$key] ?? null;
    }

    /**
     * Asset filename of the official SVG for a language, e.g. "legal-guarantee-de.svg".
     */
    public function getSvgFilename(string $key): string
    {
        return 'legal-guarantee-' . $key . '.svg';
    }
}
