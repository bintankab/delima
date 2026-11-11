export function countWords(html) {
    const text = String(html ?? '')
        .replace(/</g, ' <')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;|&#160;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/\s+/g, ' ')
        .trim();

    return text === '' ? 0 : text.split(' ').length;
}
