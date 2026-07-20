/**
 * Drop the +55 country code from a pasted international number.
 *
 * The length is what disambiguates, not the prefix: 55 is a real area code
 * (Santa Maria), so "5599999999" must stay as it is, while a national number
 * never runs past 11 digits — anything longer that starts with 55 carries the
 * country code.
 */
function stripCountryCode(digits: string): string {
    return digits.length > 11 && digits.startsWith('55')
        ? digits.slice(2)
        : digits;
}

/**
 * Format a Brazilian phone number for display.
 *
 * Formats progressively so it can run on every keystroke: a 10 digit landline
 * becomes (11) 9999-9999 and an 11 digit mobile becomes (11) 99999-9999.
 *
 * The backend stores and compares digits only (User::normalizePhone), so this
 * is presentation and nothing else — a masked value posts and resolves to the
 * same account as a raw one.
 */
export function formatPhone(value: string): string {
    const digits = stripCountryCode(value.replace(/\D/g, '')).slice(0, 11);

    if (digits.length === 0) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    const areaCode = digits.slice(0, 2);
    const rest = digits.slice(2);

    if (rest.length <= 4) {
        return `(${areaCode}) ${rest}`;
    }

    // Mobile numbers carry a ninth digit, so they split 5-4 instead of 4-4.
    const split = rest.length > 8 ? 5 : 4;

    return `(${areaCode}) ${rest.slice(0, split)}-${rest.slice(split)}`;
}
