import { router, usePage } from '@inertiajs/vue3'

// Every control that narrows or orders the learning path catalog (the search,
// the level filter, the sort) reloads the page through here, so changing one
// carries all the others along instead of silently dropping them. A new
// filter only needs its query parameter added to the list the server echoes
// back in `filters`.
//
// Parameters left empty are dropped from the address, so a catalog with
// nothing narrowing or ordering it is plain /learning-paths.
export function useLearningPathFilters() {
    const page = usePage()

    function currentParams() {
        const search = page.props.search
        const filters = page.props.filters ?? {}

        return {
            q: search?.enabled ? search.query : '',
            level: filters.level,
            sort: filters.sort,
        }
    }

    function visit(changes, { onStart, onFinish } = {}) {
        const params = Object.fromEntries(
            Object.entries({ ...currentParams(), ...changes })
                .filter(([, value]) => value !== null && value !== undefined && value !== ''),
        )

        router.get(route('learning-paths.index'), params, {
            preserveState: true,
            preserveScroll: true,
            onStart,
            onFinish,
        })
    }

    return { visit }
}
