// Shared category/service search-and-filter logic for the order forms
// (Dashboard "Quick Order" widget and the "/orders/new" page). Both pages
// load the same `{ id, name, category: { id, name }, ... }` service shape
// from `/orders/services?platform=...` and need identical search behavior.

export function normalizeQuery(raw) {
    return String(raw ?? '').trim().toLowerCase();
}

export function serviceMatchesQuery(service, query) {
    if (!query) return true;
    const name = String(service?.name ?? '').toLowerCase();
    const categoryName = String(service?.category?.name ?? '').toLowerCase();
    return name.includes(query) || categoryName.includes(query);
}

// Categories present among `services`, restricted to those with at least
// one service matching `query` (by service name or category name).
export function groupCategoriesByQuery(services, query = '') {
    const map = new Map();
    for (const s of services) {
        if (!serviceMatchesQuery(s, query)) continue;
        const cat = s.category ?? { id: 0, name: 'Other' };
        if (!map.has(cat.id)) map.set(cat.id, { ...cat, count: 0 });
        map.get(cat.id).count++;
    }
    return Array.from(map.values()).sort((a, b) => b.count - a.count);
}

export function servicesInCategory(services, categoryId, query = '') {
    if (categoryId == null) return [];
    return services.filter(s => s.category?.id === categoryId && serviceMatchesQuery(s, query));
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
