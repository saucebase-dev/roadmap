import { useT } from '@/i18n';
import { cn } from '@/lib/utils';

import IconAltArrowUpBold from '~icons/solar/alt-arrow-up-bold';

export default function VoteButton({
    itemId,
    votes,
    voted,
    pending,
    onVote,
}: {
    itemId: number;
    votes: number;
    voted: boolean;
    pending?: boolean;
    onVote: () => void;
}) {
    const t = useT();

    return (
        <button
            type="button"
            data-testid={`vote-btn-${itemId}`}
            data-voted={voted}
            disabled={pending}
            aria-pressed={voted}
            aria-label={t('Upvote')}
            className={cn(
                'flex w-14 shrink-0 cursor-pointer flex-col items-center justify-center gap-0.5 rounded-md border py-2 transition-colors disabled:cursor-default disabled:opacity-70',
                voted
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'text-muted-foreground hover:bg-accent hover:text-foreground border-border',
            )}
            onClick={(event) => {
                event.preventDefault();
                onVote();
            }}
        >
            <IconAltArrowUpBold className="size-5" />
            <span
                data-testid={`vote-count-${itemId}`}
                className="text-sm font-semibold tabular-nums"
            >
                {votes}
            </span>
        </button>
    );
}
