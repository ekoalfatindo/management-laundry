type FooterCopy = {
    brand: string;
    tagline: string;
    creditPrefix: string;
    heart: string;
    creditBy: string;
    author: string;
    pillFree: string;
    pillDonation: string;
    separator: string;
    copyright: string;
};

const KEY = [0x6c, 0x6e, 0x64, 0x72];

const PAYLOADS: Record<keyof FooterCopy, string> = {
    brand: 'IQ8KEwsLCRcCGkQ+DRsKFh4X',
    tagline: 'PwEIBx8HRBYFCQ0GDQJEBwIaERlMHgEcCwsIHQAPBRxMGxcTBA9EHg0bChYeF0QLDQADUgALBhsETgkHCA8MUggPClIJCA0BBQsKXA==',
    creditPrefix: 'KAsSFwABFBcIThMbGAY=',
    heart: 'jvPAndTh',
    creditBy: 'Dhc=',
    author: 'KQULUj8bBhsNABAd',
    pillFree: 'KhwBF0waC1I5HQE=',
    pillDonation: 'OgEIBwIaBQAVTiAdAg8QGwMA',
    separator: 'rtk=',
    copyright: 'rsdEQFxcUlIhDwoTCwsJFwIaRD4NGwoWHhdKUi0CCFI+BwMaGB1EIAkdAQAaCwBc',
};

function reveal(payload: string): string {
    const bytes = Uint8Array.from(atob(payload), (char) => char.charCodeAt(0));

    return new TextDecoder().decode(bytes.map((byte, index) => byte ^ KEY[index % KEY.length]));
}

// Stored encoded so the copy is not editable as plain text in templates or the built bundle.
export const FOOTER: Readonly<FooterCopy> = Object.freeze({
    brand: reveal(PAYLOADS.brand),
    tagline: reveal(PAYLOADS.tagline),
    creditPrefix: reveal(PAYLOADS.creditPrefix),
    heart: reveal(PAYLOADS.heart),
    creditBy: reveal(PAYLOADS.creditBy),
    author: reveal(PAYLOADS.author),
    pillFree: reveal(PAYLOADS.pillFree),
    pillDonation: reveal(PAYLOADS.pillDonation),
    separator: reveal(PAYLOADS.separator),
    copyright: reveal(PAYLOADS.copyright),
});
