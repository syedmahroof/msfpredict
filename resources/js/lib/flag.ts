// Flag emoji helper.
//
// Teams are stored with FIFA 3-letter codes (e.g. ENG, BRA, GER), but flag
// emoji are built from ISO 3166-1 alpha-2 codes. This maps FIFA codes to the
// correct flag, handles 2-letter ISO codes directly (e.g. user countries), and
// covers the UK home nations which use special subdivision flags.

const FIFA_TO_ISO2: Record<string, string> = {
    MEX: 'MX', RSA: 'ZA', KOR: 'KR', CZE: 'CZ',
    CAN: 'CA', BIH: 'BA', QAT: 'QA', SUI: 'CH',
    BRA: 'BR', MAR: 'MA', HAI: 'HT',
    USA: 'US', PAR: 'PY', AUS: 'AU', TUR: 'TR',
    GER: 'DE', CUW: 'CW', CIV: 'CI', ECU: 'EC',
    NED: 'NL', JPN: 'JP', SWE: 'SE', TUN: 'TN',
    BEL: 'BE', EGY: 'EG', IRN: 'IR', NZL: 'NZ',
    ESP: 'ES', CPV: 'CV', KSA: 'SA', URU: 'UY',
    FRA: 'FR', SEN: 'SN', IRQ: 'IQ', NOR: 'NO',
    ARG: 'AR', ALG: 'DZ', AUT: 'AT', JOR: 'JO',
    POR: 'PT', COD: 'CD', UZB: 'UZ', COL: 'CO',
    CRO: 'HR', GHA: 'GH', PAN: 'PA',
}

// UK home nations — black-flag tag sequences.
const SPECIAL: Record<string, string> = {
    ENG: '🏴󠁧󠁢󠁥󠁮󠁧󠁿',
    SCO: '🏴󠁧󠁢󠁳󠁣󠁴󠁿',
    WAL: '🏴󠁧󠁢󠁷󠁬󠁳󠁿',
}

function iso2ToEmoji(iso2: string): string {
    return iso2
        .toUpperCase()
        .split('')
        .map((c) => String.fromCodePoint(127397 + c.charCodeAt(0)))
        .join('')
}

export function countryFlag(code?: string | null): string {
    if (!code) {
        return '🏳️'
    }

    const up = code.toUpperCase()

    if (up === 'TBD') {
        return '🏳️'
    }

    if (SPECIAL[up]) {
        return SPECIAL[up]
    }

    const iso2 = up.length === 2 ? up : FIFA_TO_ISO2[up]

    return iso2 ? iso2ToEmoji(iso2) : '🏳️'
}
