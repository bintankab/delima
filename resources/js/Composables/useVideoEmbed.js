const YOUTUBE_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'];
const VIMEO_HOSTS = ['vimeo.com', 'www.vimeo.com'];
const DRIVE_HOSTS = ['drive.google.com'];
const VIDEO_FILE_EXTENSIONS = /\.(mp4|webm|ogg|ogv|mov|m4v)$/i;

/**
 * Resolve a playable embed for an external video link.
 * Known platforms (YouTube/Vimeo/Google Drive) get rewritten to their
 * embeddable URL; direct video file links play natively; anything else
 * falls back to framing the link as-is (works for most embeddable pages,
 * but a site that blocks framing won't play — the caller should offer
 * an "open in new tab" link alongside it).
 *
 * @return {{ type: 'video' | 'iframe', src: string } | null}
 */
const getEmbed = (url) => {
    if (!url) {
        return null;
    }

    let parsed;

    try {
        parsed = new URL(url);
    } catch {
        return null;
    }

    const host = parsed.hostname;

    if (YOUTUBE_HOSTS.includes(host)) {
        const id = host === 'youtu.be' ? parsed.pathname.slice(1) : parsed.searchParams.get('v');

        return id ? { type: 'iframe', src: `https://www.youtube.com/embed/${id}` } : null;
    }

    if (VIMEO_HOSTS.includes(host)) {
        const id = parsed.pathname.split('/').filter(Boolean)[0];

        return id ? { type: 'iframe', src: `https://player.vimeo.com/video/${id}` } : null;
    }

    if (DRIVE_HOSTS.includes(host)) {
        const match = parsed.pathname.match(/\/file\/d\/([^/]+)/);

        return match ? { type: 'iframe', src: `https://drive.google.com/file/d/${match[1]}/preview` } : null;
    }

    if (VIDEO_FILE_EXTENSIONS.test(parsed.pathname)) {
        return { type: 'video', src: url };
    }

    return { type: 'iframe', src: url };
};

export function useVideoEmbed() {
    return { getEmbed };
}
