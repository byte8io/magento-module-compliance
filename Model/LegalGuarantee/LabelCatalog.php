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
            'identifier' => 'gesetzliche-gewaehrleistung',
            'title' => 'Gesetzliche Gewährleistung',
            'meta_title' => 'Gesetzliche Gewährleistung – Ihre EU-Verbraucherrechte',
            'meta_description' => 'Gesetzliche Gewährleistung nach EU-Recht: Für Verbrauchsgüter gilt eine Gewährleistung von mindestens zwei Jahren. Hier finden Sie das offizielle EU-Gewährleistungslabel und Ihre Rechte.',
            'intro' => 'Als Verkäufer informieren wir Sie mit dem harmonisierten EU-Gewährleistungslabel über Ihre gesetzlichen Rechte. Für Verbrauchsgüter gilt eine gesetzliche Gewährleistung von mindestens zwei Jahren.',
        ],
        'it' => [
            'name' => 'Italian (Italia)',
            'identifier' => 'garanzia-legale-di-conformita',
            'title' => 'Garanzia legale di conformità',
            'meta_title' => 'Garanzia legale di conformità – I tuoi diritti UE',
            'meta_description' => 'Garanzia legale di conformità secondo il diritto UE: i beni di consumo beneficiano di una garanzia di almeno due anni. Qui trovi l\'etichetta UE ufficiale e i tuoi diritti.',
            'intro' => 'In qualità di venditore, vi informiamo dei vostri diritti tramite l\'etichetta UE armonizzata sulla garanzia legale. I beni di consumo beneficiano di una garanzia legale di conformità di almeno due anni.',
        ],
        'nl' => [
            'name' => 'Dutch (Nederland)',
            'identifier' => 'wettelijke-garantie',
            'title' => 'Wettelijke garantie',
            'meta_title' => 'Wettelijke garantie – Uw EU-consumentenrechten',
            'meta_description' => 'Wettelijke garantie volgens EU-recht: voor consumptiegoederen geldt een garantie van ten minste twee jaar. Hier vindt u het officiële EU-garantielabel en uw rechten.',
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
     * Metadata for a shipped language, or null if unknown.
     *
     * @return array{name: string, identifier: string, title: string, meta_title: string, meta_description: string, intro: string}|null
     */
    public function get(string $key): ?array
    {
        return self::LANGUAGES[$key] ?? null;
    }

    /**
     * Localised standalone-page URL key for a language (e.g. "gesetzliche-gewaehrleistung"),
     * or null if the language is unknown. Used both to create the CMS page and to
     * resolve the notice's "Learn more" / footer link, so the two stay in lockstep.
     */
    public function getPageIdentifier(string $key): ?string
    {
        return self::LANGUAGES[$key]['identifier'] ?? null;
    }

    /**
     * Asset filename of the official SVG for a language, e.g. "legal-guarantee-de.svg".
     */
    public function getSvgFilename(string $key): string
    {
        return 'legal-guarantee-' . $key . '.svg';
    }
}
