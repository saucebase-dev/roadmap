import { Badge } from '@/components/ui/badge';
import { useT } from '@/i18n';
import { ModalLink } from '@inertiaui/modal-react';

import type { RoadmapItem } from '../../types';
import VoteButton from './VoteButton';

import IconComments from '~icons/heroicons/chat-bubble-left-right';

export default function ItemCard({
    item,
    typeVariant,
    commentsEnabled,
    showStatus,
    votePending,
    onVote,
}: {
    item: RoadmapItem;
    typeVariant: 'default' | 'destructive' | 'secondary' | 'outline';
    commentsEnabled: boolean;
    /** Own submissions are listed off the board, where the column no longer says the status. */
    showStatus?: boolean;
    votePending?: boolean;
    onVote: (item: RoadmapItem) => void;
}) {
    const t = useT();

    return (
        <article
            data-testid={`roadmap-item-${item.id}`}
            className="bg-card hover:border-primary/40 flex items-start gap-3 rounded-lg border p-3 transition-colors"
        >
            <VoteButton
                itemId={item.id}
                votes={item.votes_count}
                voted={item.has_voted}
                pending={votePending}
                onVote={() => onVote(item)}
            />

            <div className="flex min-w-0 flex-1 flex-col gap-1">
                <ModalLink
                    navigate
                    href={item.url}
                    data-testid={`roadmap-item-link-${item.id}`}
                    className="leading-snug font-semibold hover:underline"
                >
                    {item.title}
                </ModalLink>

                {item.description && (
                    <p className="text-muted-foreground line-clamp-2 text-sm">
                        {item.description}
                    </p>
                )}

                <div className="mt-1 flex items-center gap-2">
                    {showStatus && (
                        <Badge
                            variant="secondary"
                            className="text-xs"
                            data-testid={`item-status-${item.id}`}
                        >
                            {item.status_label}
                        </Badge>
                    )}
                    <Badge variant={typeVariant} className="text-xs">
                        {item.type_label}
                    </Badge>
                    {commentsEnabled && (
                        <ModalLink
                            navigate
                            href={item.url}
                            data-testid={`comment-count-${item.id}`}
                            aria-label={t('Comments')}
                            className="text-muted-foreground hover:text-foreground flex items-center gap-1 text-xs transition-colors"
                        >
                            <IconComments className="size-4" />
                            {item.comments_count}
                        </ModalLink>
                    )}
                </div>
            </div>
        </article>
    );
}
