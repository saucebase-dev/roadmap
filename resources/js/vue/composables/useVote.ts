import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

import type { RoadmapItem } from '../../types';

type VotableItem = Pick<RoadmapItem, 'id' | 'has_voted' | 'votes_count'>;

/**
 * Toggles the current user's vote, updating the item before the server answers
 * and putting it back if the request fails.
 *
 * @param only  Which page props the answer has to refresh. The board only needs
 *              the items; the item page only needs the item.
 */
export function useVote(authenticated: boolean, only: string[]) {
    // A vote is a toggle, so two clicks racing each other would cancel out and
    // land whichever answer arrives last. One request per item at a time.
    const pending = ref(new Set<number>());

    function vote(item: VotableItem) {
        if (!authenticated) {
            router.visit(route('login'));

            return;
        }

        if (pending.value.has(item.id)) {
            return;
        }

        const hadVoted = item.has_voted;

        item.has_voted = !hadVoted;
        item.votes_count += hadVoted ? -1 : 1;
        pending.value.add(item.id);

        router.post(
            route('roadmap.vote', item.id),
            {},
            {
                preserveScroll: true,
                only,
                onSuccess: () =>
                    hadVoted
                        ? toast.info(trans('Vote removed'))
                        : toast.success(trans('Upvoted!')),
                onError: () => {
                    item.has_voted = hadVoted;
                    item.votes_count += hadVoted ? 1 : -1;
                    toast.error(trans('Your vote did not go through.'));
                },
                onFinish: () => pending.value.delete(item.id),
            },
        );
    }

    return { vote, pending };
}
