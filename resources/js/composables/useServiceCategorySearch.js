// Shared category/service search-and-filter logic for the order forms
// (Dashboard "Quick Order" widget and the "/orders/new" page). Both pages
// load the same `{ id, name, category: { id, name }, ... }` service shape
// from `/orders/services?platform=...` and need identical search behavior.

export function normalizeQuery(raw) {
    return String(raw ?? '').trim().toLowerCase();
}

export function serviceMatchesQuery(service, query) {
    const normalized = normalizeQuery(query);
    if (!normalized) return true;
    const name = String(service?.name ?? '').toLowerCase();
    const categoryName = String(service?.category?.name ?? '').toLowerCase();
    const searchableText = `${name} ${categoryName}`;
    return normalized.split(/\s+/).every(term => searchableText.includes(term));
}

// Provider category names are inconsistent and often repeat the platform name.
// Platforms are selected by the cards above the form; this selector groups the
// remaining services by what the customer is actually buying.
const SERVICE_TYPES = [
    ['followers', 'Followers', ['followers', 'follower']],
    ['subscribers', 'Subscribers', ['subscribers', 'subscriber']],
    ['members', 'Members', ['members', 'member']],
    ['likes', 'Likes', ['likes', 'like']],
    ['views', 'Views', ['views', 'view']],
    ['comments', 'Comments', ['comments', 'comment']],
    ['shares', 'Shares', ['shares', 'share', 'repost']],
    ['reactions', 'Reactions', ['reactions', 'reaction', 'emoji']],
    ['saves', 'Saves', ['saves', 'save', 'favorite', 'wishlist']],
    ['plays', 'Plays & Streams', ['plays', 'play', 'streams', 'stream', 'listeners', 'listener']],
    ['votes', 'Votes & Polls', ['votes', 'vote', 'poll']],
    ['boosts', 'Boosts', ['boosts', 'boost']],
    ['traffic', 'Traffic', ['traffic', 'visitors', 'website']],
];

export function serviceTypeCategory(service) {
    const text = `${service?.name ?? ''} ${service?.category?.name ?? ''}`.toLowerCase();
    for (const [id, name, terms] of SERVICE_TYPES) {
        if (terms.some(term => text.includes(term))) return { id: `type:${id}`, name };
    }
    return { id: 'type:other', name: 'Other Services' };
}

// Categories present among `services`, restricted to those with at least
// one service matching `query` (by service name or category name).
export function groupCategoriesByQuery(services, query = '') {
    const map = new Map();
    for (const s of services) {
        if (!serviceMatchesQuery(s, query)) continue;
        const cat = serviceTypeCategory(s);
        if (!map.has(cat.id)) map.set(cat.id, { ...cat, count: 0 });
        map.get(cat.id).count++;
    }
    return Array.from(map.values()).sort((a, b) => b.count - a.count);
}

export function servicesInCategory(services, categoryId, query = '') {
    if (categoryId == null) return [];
    return services.filter(s => serviceTypeCategory(s).id === categoryId && serviceMatchesQuery(s, query));
}

// Given the full service list and the current query, decides the next
// { category, service } selection. Keeps the current category/service if it
// still matches the query; otherwise falls back to the first match (or null
// if nothing matches, which the UI renders as "No services found").
export function resolveSelection({ services, query = '', currentCategoryId = null, currentServiceId = null }) {
    const categories = groupCategoriesByQuery(services, query);

    let category = currentCategoryId != null
        ? categories.find(c => c.id === currentCategoryId) ?? null
        : null;
    if (!category) category = categories[0] ?? null;

    const categoryServiceList = category ? servicesInCategory(services, category.id, query) : [];

    let service = currentServiceId != null
        ? categoryServiceList.find(s => s.id === currentServiceId) ?? null
        : null;
    if (!service) service = categoryServiceList[0] ?? null;

    return { category, service, categories, categoryServiceList };
}
