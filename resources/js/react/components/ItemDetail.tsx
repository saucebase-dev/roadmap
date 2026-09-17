import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useT } from '@/i18n';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import { useModal } from '@inertiaui/modal-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

import type { RoadmapItemDetail } from '../../types';
import { useVote } from '../hooks/useVote';
import VoteButton from './VoteButton';

function initials(author: string): string {
    return (
        author
            .split(/\s+/)
            // Array.from splits by character, so an emoji or astral script keeps
            // its whole glyph instead of half a surrogate pair.
            .map((part) => Array.from(part)[0])
            .join('')
            .slice(0, 2)
            .toUpperCase()
    );
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

export default function ItemDetail({
    item: initialItem,
    authenticated,
}: {
    item: RoadmapItemDetail;
    authenticated: boolean;
}) {
    const t = useT();

    // Voting updates the count before the server answers, so the item is local state.
    const [item, setItem] = useState(initialItem);

    useEffect(() => setItem(initialItem), [initialItem]);

    const { vote, pending } = useVote(authenticated, ['item'], (_, change) =>
        setItem((current) => ({ ...current, ...change })),
    );

    const form = useForm({ body: '' });

    // React's form has no processing setter, so the slideover request tracks its own.
    const [posting, setPosting] = useState(false);
    const processing = form.processing || posting;

    // Inside a slideover the redirect refreshes the board behind it, so the panel
    // has to ask for its own props again to show the new comment.
    const modal = useModal();

    function submitComment(event: React.FormEvent) {
        event.preventDefault();

        if (modal) {
            // Posting through the panel's own request: an Inertia visit would follow
            // the redirect, navigate the page behind it, and close the panel.
            modal.reload({
                method: 'post',
                data: { body: form.data.body },
                onStart: () => setPosting(true),
                onSuccess: () => form.reset(),
                onError: () => toast.error(t('Your comment was not posted.')),
                onFinish: () => setPosting(false),
            });

            return;
        }

        form.post(route('roadmap.comments.store', item.slug), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            // A rejected body already shows its message under the field; anything
            // else (expired session, rate limit, server error) would be silent.
            onError: (errors) => {
                if (!errors.body) {
                    toast.error(t('Your comment was not posted.'));
                }
            },
        });
    }

    return (
        <div>
            <article className="flex items-start gap-4">
                <VoteButton
                    itemId={item.id}
                    votes={item.votes_count}
                    voted={item.has_voted}
                    pending={pending.has(item.id)}
                    onVote={() => vote(item)}
                />

                <div className="min-w-0 flex-1">
                    <div className="mb-3 flex flex-wrap items-center gap-2">
                        <Badge variant="secondary" data-testid="item-status">
                            {item.status_label}
                        </Badge>
                        <Badge variant="outline">{item.type_label}</Badge>
                        <span className="text-muted-foreground text-xs">
                            {formatDate(item.created_at)}
                        </span>
                    </div>

                    <h1 className="text-2xl font-bold tracking-tight">
                        {item.title}
                    </h1>

                    {item.description && (
                        <p className="text-muted-foreground mt-4 whitespace-pre-line">
                            {item.description}
                        </p>
                    )}
                </div>
            </article>

            {/* The team's answer, pinned above the discussion */}
            {item.official_response && (
                <section
                    data-testid="official-response"
                    className="border-primary bg-primary/5 mt-8 rounded-lg border-l-4 p-5"
                >
                    <p className="text-primary mb-2 text-sm font-semibold">
                        {t('Response from the team')}
                        {item.official_response_at && (
                            <span className="text-muted-foreground font-normal">
                                {' '}
                                · {formatDate(item.official_response_at)}
                            </span>
                        )}
                    </p>
                    <p className="whitespace-pre-line">
                        {item.official_response}
                    </p>
                </section>
            )}

            {item.comments_enabled && (
                <section className="mt-10">
                    <h2 className="mb-6 text-lg font-semibold">
                        {item.comments.length > 0 ? (
                            <>
                                {t('Comments')}{' '}
                                <span className="text-muted-foreground font-normal">
                                    ({item.comments.length})
                                </span>
                            </>
                        ) : (
                            t('No comments yet')
                        )}
                    </h2>

                    <ul className="space-y-6">
                        {item.comments.map((comment) => (
                            <li
                                key={comment.id}
                                data-testid={`comment-${comment.id}`}
                                className="flex gap-3"
                            >
                                <Avatar className="size-8 shrink-0">
                                    <AvatarFallback
                                        className={cn(
                                            'text-xs',
                                            comment.mine &&
                                                'bg-primary text-primary-foreground',
                                        )}
                                    >
                                        {initials(comment.author)}
                                    </AvatarFallback>
                                </Avatar>

                                <div className="min-w-0 flex-1">
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-sm font-semibold">
                                            {comment.author}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {formatDate(comment.created_at)}
                                        </span>
                                    </div>
                                    <div
                                        className={cn(
                                            'mt-1 rounded-lg rounded-tl-none px-3 py-2',
                                            comment.mine
                                                ? 'bg-primary/10 border-primary/30 border'
                                                : 'bg-muted/50',
                                        )}
                                    >
                                        <p className="text-sm whitespace-pre-line">
                                            {comment.body}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>

                    {authenticated ? (
                        <form
                            onSubmit={submitComment}
                            className="mt-8 space-y-2"
                        >
                            <textarea
                                value={form.data.body}
                                onChange={(event) =>
                                    form.setData('body', event.target.value)
                                }
                                data-testid="comment-body"
                                placeholder={t('Add your thoughts…')}
                                rows={3}
                                maxLength={2000}
                                className="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none"
                            />
                            {form.errors.body && (
                                <p className="text-destructive text-xs">
                                    {form.errors.body}
                                </p>
                            )}
                            <div className="flex justify-end">
                                <Button
                                    type="submit"
                                    data-testid="comment-submit"
                                    disabled={processing}
                                >
                                    {processing
                                        ? t('Posting…')
                                        : t('Post comment')}
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <p className="text-muted-foreground mt-8 text-sm">
                            <a
                                href={route('login')}
                                data-testid="comment-login"
                                className="text-primary underline"
                            >
                                {t('Log in')}
                            </a>{' '}
                            {t('to join the discussion.')}
                        </p>
                    )}
                </section>
            )}
        </div>
    );
}
