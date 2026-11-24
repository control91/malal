/**
 * Prepares a back-office form payload: upload fields only send a new File
 * (existing { path, url } descriptors are dropped), booleans stay booleans.
 */
export function cleanPayload(data) {
    const out = {};
    for (const [key, value] of Object.entries(data)) {
        if (value && typeof value === 'object' && !(value instanceof File) && !Array.isArray(value) && 'url' in value) continue;
        if (Array.isArray(value)) {
            out[key] = value.map((item) => (item && typeof item === 'object' && !(item instanceof File) ? cleanPayload(item) : item));
        } else {
            out[key] = value;
        }
    }
    return out;
}
