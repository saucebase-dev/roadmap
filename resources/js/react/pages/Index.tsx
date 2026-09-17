import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { PageHero } from '@/components/ui/saucebase';
import { useT } from '@/i18n';
import SiteLayout from '@/layouts/SiteLayout';
import { cn } from '@/lib/utils';
import { router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import type {
    RoadmapColumn,
    RoadmapItem,
    RoadmapTypeOption,
} from '../../types';
import ItemCard from '../components/ItemCard';
import { useVote } from '../hooks/useVote';

import IconArrowLeft from '~icons/heroicons/arrow-left';
import IconChevronDown from '~icons/heroicons/chevron-down';
import IconMap from '~icons/heroicons/map';
import IconPlus from '~icons/heroicons/plus';

const colorToVariant: Record<
    string,
    'default' | 'destructive' | 'secondary' | 'outline'
> = {
    primary: 'default',
    secondary: 'secondary',
    danger: 'destructive',
    warning: 'secondary',
    success: 'default',
    info: 'secondary',
    gray: 'outline',
};

const inputClass =
    'border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none';

export default function Index({
    items,
    columns,
    types,
    sort,
    mine,
    authenticated,
    comments_enabled,
}: {
    items: RoadmapItem[];
    columns: RoadmapColumn[];
    types: RoadmapTypeOption[];
    sort: string;
    mine: boolean;
    authenticated: boolean;
    comments_enabled: boolean;
}) {
    const t = useT();

    const sortOptions = [
        { value: 'trending', label: t('Trending') },
        { value: 'new', label: t('Newest') },
        { value: 'old', label: t('Oldest') },
    ];

    const currentSortLabel =
        sortOptions.find((option) => option.value === sort)?.label ??
        sortOptions[0].label;

    /**
     * Sorting and filtering only redraw the backlog, which sits at the bottom of
     * the page, so the scroll position is kept.
     */
    function reload(query: { sort?: string; mine?: boolean }) {
        router.get(
            route('roadmap.index'),
            { sort, mine, ...query },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }

    // Voting updates the card before the server answers, so the list is local state.
    const [localItems, setLocalItems] = useState(items);

    useEffect(() => setLocalItems(items), [items]);

    const { vote, pending } = useVote(authenticated, ['items'], (id, change) =>
        setLocalItems((current) =>
            current.map((item) =>
                item.id === id ? { ...item, ...change } : item,
            ),
        ),
    );

    const board = columns.map((column) => ({
        ...column,
        items: localItems.filter((item) => item.status === column.value),
    }));

    const backlog = localItems.filter((item) => item.status === 'backlog');

    function typeVariant(item: RoadmapItem) {
        const type = types.find((option) => option.value === item.type);

        return colorToVariant[type?.color ?? 'primary'] ?? 'default';
    }

    function card(item: RoadmapItem, showStatus = false) {
        return (
            <ItemCard
                key={item.id}
                item={item}
                typeVariant={typeVariant(item)}
                commentsEnabled={comments_enabled}
                votePending={pending.has(item.id)}
                showStatus={showStatus}
                onVote={vote}
            />
        );
    }

    const [dialogOpen, setDialogOpen] = useState(false);

    const form = useForm({
        title: '',
        description: '',
        type: types[0]?.value ?? 'feature',
    });

    function openDialog() {
        if (!authenticated) {
            router.visit(route('login'));

            return;
        }

        form.reset();
        setDialogOpen(true);
    }

    function submitSuggestion(event: React.FormEvent) {
        event.preventDefault();

        form.post(route('roadmap.store'), {
            onSuccess: () => {
                setDialogOpen(false);
                form.reset();
            },
        });
    }

    return (
        <SiteLayout
            title={t('Roadmap')}
            description={t(
                'See what we are building and vote on what matters to you.',
            )}
            canonical={route('roadmap.index')}
        >
            <PageHero
                testId="roadmap-hero"
                title={t('Product Roadmap')}
                description={t(
                    'Everything we are working on, in the open. Upvote the ideas you want next, follow along as they move from planned to shipped, and send us anything that is missing.',
                )}
                icon={IconMap}
                actions={
                    <Button
                        data-testid="suggest-btn"
                        variant="secondary"
                        className="w-full sm:w-auto"
                        onClick={openDialog}
                    >
                        <IconPlus className="size-5" />
                        {t('Submit feedback')}
                    </Button>
                }
            />

            <div className="mx-auto w-full max-w-6xl px-8 py-16">
                {/* Sorting and filtering, at the top where they are easy to find */}
                {(localItems.length > 0 || mine) && (
                    <div className="mb-8 flex flex-wrap items-center gap-2">
                        {mine && (
                            <Button
                                data-testid="filter-mine-back"
                                variant="ghost"
                                className="gap-2"
                                onClick={() => reload({ mine: false })}
                            >
                                <IconArrowLeft className="size-4" />
                                {t('Back to the roadmap')}
                            </Button>
                        )}

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    variant="outline"
                                    data-testid="sort-trigger"
                                    className="gap-2"
                                >
                                    {t('Sort by')}: {currentSortLabel}
                                    <IconChevronDown className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="start">
                                {sortOptions.map((option) => (
                                    <DropdownMenuItem
                                        key={option.value}
                                        data-testid={`sort-${option.value}`}
                                        onSelect={() =>
                                            reload({ sort: option.value })
                                        }
                                    >
                                        {option.label}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {authenticated && !mine && (
                            <Button
                                data-testid="filter-mine"
                                variant="outline"
                                className="ml-auto"
                                onClick={() => reload({ mine: true })}
                            >
                                {t('My feedback')}
                            </Button>
                        )}
                    </div>
                )}

                {localItems.length === 0 ? (
                    <div className="bg-muted/30 flex flex-col items-center justify-center gap-6 rounded-lg py-20 text-center">
                        <IconMap className="text-muted-foreground size-14" />
                        <p
                            className="text-muted-foreground"
                            data-testid="roadmap-empty"
                        >
                            {mine
                                ? t('You have not sent us any feedback yet.')
                                : t(
                                      'No roadmap items yet. Be the first to suggest a feature!',
                                  )}
                        </p>
                        <button
                            type="button"
                            data-testid="suggest-btn-empty"
                            className="border-secondary text-secondary hover:bg-secondary hover:text-secondary-foreground inline-flex items-center gap-2 rounded-md border px-4 py-1.5 text-sm transition-colors"
                            onClick={openDialog}
                        >
                            <IconPlus className="size-5" />
                            {t('Submit feedback')}
                        </button>
                    </div>
                ) : mine ? (
                    // Own submissions: a plain list, because one person's items can sit
                    // in statuses the board has no column for, review included.
                    <div
                        data-testid="roadmap-mine"
                        className="grid grid-cols-1 gap-3 lg:grid-cols-2"
                    >
                        {localItems.map((item) => card(item, true))}
                    </div>
                ) : (
                    <>
                        {/* One column per board status, stacked on small screens */}
                        <div
                            data-testid="roadmap-board"
                            className="grid grid-cols-1 gap-6 md:grid-cols-3"
                        >
                            {board.map((column) => (
                                <section
                                    key={column.value}
                                    data-testid={`roadmap-column-${column.value}`}
                                    className="flex flex-col gap-3"
                                >
                                    <h2 className="text-muted-foreground flex items-center gap-2 px-1 text-xs font-semibold tracking-wider uppercase">
                                        {column.label}
                                        <span className="font-normal">
                                            ({column.items.length})
                                        </span>
                                    </h2>

                                    {column.items.length === 0 && (
                                        <p className="text-muted-foreground bg-muted/30 rounded-lg px-3 py-6 text-center text-sm">
                                            {t('Nothing here yet.')}
                                        </p>
                                    )}

                                    {column.items.map((item) => card(item))}
                                </section>
                            ))}
                        </div>

                        {/* Accepted, but not scheduled yet */}
                        {backlog.length > 0 && (
                            <section
                                data-testid="roadmap-backlog"
                                className="mt-14"
                            >
                                <h2 className="mb-3 text-lg font-semibold">
                                    {t('Backlog')}{' '}
                                    <span className="text-muted-foreground font-normal">
                                        ({backlog.length})
                                    </span>
                                </h2>

                                <p className="text-muted-foreground mb-4 text-sm">
                                    {t(
                                        'Ideas we accepted but have not scheduled. Votes help us pick what comes next.',
                                    )}
                                </p>

                                <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                    {backlog.map((item) => card(item))}
                                </div>
                            </section>
                        )}
                    </>
                )}
            </div>

            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{t('Submit feedback')}</DialogTitle>
                        <DialogDescription>
                            {t(
                                'We review all submissions before publishing them to the roadmap.',
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    <form onSubmit={submitSuggestion} className="space-y-4">
                        <div className="space-y-1.5">
                            <label className="text-sm font-medium">
                                {t('Type')}
                            </label>
                            <div className="flex gap-2">
                                {types.map((type) => (
                                    <button
                                        key={type.value}
                                        type="button"
                                        className={cn(
                                            'rounded-md border px-4 py-1.5 text-sm font-medium transition-colors',
                                            form.data.type === type.value
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : 'hover:bg-accent border-border',
                                        )}
                                        onClick={() =>
                                            form.setData('type', type.value)
                                        }
                                    >
                                        {type.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <label
                                htmlFor="suggest-title"
                                className="text-sm font-medium"
                            >
                                {t('Title')}{' '}
                                <span className="text-destructive">*</span>
                            </label>
                            <input
                                id="suggest-title"
                                data-testid="suggest-title"
                                value={form.data.title}
                                onChange={(event) =>
                                    form.setData('title', event.target.value)
                                }
                                type="text"
                                placeholder={t(
                                    'e.g. Dark mode, login bug, faster search…',
                                )}
                                maxLength={255}
                                className={cn(inputClass, 'h-9 py-1')}
                            />
                            {form.errors.title && (
                                <p className="text-destructive text-xs">
                                    {form.errors.title}
                                </p>
                            )}
                        </div>

                        <div className="space-y-1.5">
                            <label
                                htmlFor="suggest-description"
                                className="text-sm font-medium"
                            >
                                {t('Description')}
                            </label>
                            <textarea
                                id="suggest-description"
                                data-testid="suggest-description"
                                value={form.data.description}
                                onChange={(event) =>
                                    form.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                                placeholder={t(
                                    "What's the problem or idea? Any details help.",
                                )}
                                rows={3}
                                maxLength={2000}
                                className={cn(inputClass, 'resize-none py-2')}
                            />
                            {form.errors.description && (
                                <p className="text-destructive text-xs">
                                    {form.errors.description}
                                </p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button
                                data-testid="suggest-cancel-btn"
                                type="button"
                                variant="outline"
                                onClick={() => setDialogOpen(false)}
                            >
                                {t('Cancel')}
                            </Button>
                            <Button
                                data-testid="suggest-submit-btn"
                                type="submit"
                                disabled={form.processing}
                            >
                                {form.processing
                                    ? t('Submitting…')
                                    : t('Submit')}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </SiteLayout>
    );
}
