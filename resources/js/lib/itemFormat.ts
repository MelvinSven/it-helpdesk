export const KODE_BARANG_PREFIX = 'LIX-EL-';

export const KODE_BARANG_PATTERN = /^LIX-EL-[A-Z0-9]+-[A-Z0-9]+$/;

/**
 * Builds a full Kode Barang from what the user typed after the fixed prefix:
 * uppercases, turns any run of spaces/punctuation into a single dash, and
 * caps it at two segments ("laptop 12" → "LIX-EL-LAPTOP-12"). Mirrors
 * Item::formatKodeBarang() on the server.
 */
export function formatKodeBarang(input: string): string {
    let rest = input.toUpperCase();

    // A pasted full code shouldn't end up with the prefix twice.
    while (rest.startsWith(KODE_BARANG_PREFIX)) {
        rest = rest.slice(KODE_BARANG_PREFIX.length);
    }

    const cleaned = rest.replace(/[^A-Z0-9]+/g, '-').replace(/^-/, '');
    const dash = cleaned.indexOf('-');

    if (dash === -1) {
        return KODE_BARANG_PREFIX + cleaned;
    }

    // Keep the dash while typing so the second segment can start; any later
    // separators are dropped rather than opening a third segment.
    return `${KODE_BARANG_PREFIX}${cleaned.slice(0, dash)}-${cleaned
        .slice(dash + 1)
        .replace(/-/g, '')}`;
}

/** The editable part of a code, i.e. everything after the fixed prefix. */
export function kodeBarangSegments(code: string): string {
    return code.startsWith(KODE_BARANG_PREFIX)
        ? code.slice(KODE_BARANG_PREFIX.length)
        : code;
}
