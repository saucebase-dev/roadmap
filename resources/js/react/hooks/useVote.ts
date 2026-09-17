import { useT } from '@/i18n';
import { router } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';

import type { RoadmapItem } from '../../types';

type VotableItem = Pick<RoadmapItem, 'id' | 'has_voted' | 'votes_count'>;

/**
 * Toggles the current user's vote, updating the item before the server answers
 * and putting it back if the request fails.
 *
 * @param only    Which page props the answer has to refresh. The board only
 *                needs the items; the item page only needs the item.
 * @param update  Writes the new vote state into the caller's local copy.
 */
export function useVote(
    authenticated: boolean,
    only: string[],
    update: (id: number, vote: Omit<VotableItem, 'id'>) => void,
) {
    const t = useT();

    // A vote is a toggle, so two clicks racing each other would cancel out and
    // land whichever answer arrives last. One request per item at a time.
    const pendingRef = useRef(new Set<number>());
    const [pending, setPending] = useState<ReadonlySet<number>>(new Set());

    function setItemPending(id: number, isPending: boolean) {
        if (isPending) {
            pendingRef.current.add(id);
        } else {
            pendingRef.current.delete(id);
        }

        setPending(new Set(pendingRef.current));
    }

    function vote(item: VotableItem) {
        if (!authenticated) {
            router.visit(route('login'));

            return;
        }

        if (pendingRef.current.has(item.id)) {
            return;
        }

        const hadVoted = item.has_voted;

        update(item.id, {
            has_voted: !hadVoted,
            votes_count: item.votes_count + (hadVoted ? -1 : 1),
        });
        setItemPending(item.id, true);

        router.post(
            route('roadmap.vote', item.id),
            {},
            {
                preserveScroll: true,
                only,
                onSuccess: () =>
                    hadVoted
                        ? toast.info(t('Vote removed'))
                        : toast.success(t('Upvoted!')),
                onError: () => {
                    update(item.id, {
                        has_voted: hadVoted,
                        votes_count: item.votes_count,
                    });
                    toast.error(t('Your vote did not go through.'));
                },
                onFinish: () => setItemPending(item.id, false),
            },
        );
    }

    return { vote, pending };
}
