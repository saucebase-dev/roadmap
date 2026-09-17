<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
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
import SiteLayout from '@/layouts/SiteLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import type {
    RoadmapColumn,
    RoadmapItem,
    RoadmapTypeOption,
} from '../../types';
import ItemCard from '../components/ItemCard.vue';
import { useVote } from '../composables/useVote';

import IconArrowLeft from '~icons/heroicons/arrow-left';
import IconChevronDown from '~icons/heroicons/chevron-down';
import IconMap from '~icons/heroicons/map';
import IconPlus from '~icons/heroicons/plus';

const props = defineProps<{
    items: RoadmapItem[];
    columns: RoadmapColumn[];
    types: RoadmapTypeOption[];
    sort: string;
    mine: boolean;
    authenticated: boolean;
    comments_enabled: boolean;
}>();

const title = trans('Roadmap');
const description = trans(
    'See what we are building and vote on what matters to you.',
);

const SORT_OPTIONS = [
    { value: 'trending', label: trans('Trending') },
    { value: 'new', label: trans('Newest') },
    { value: 'old', label: trans('Oldest') },
];

const currentSortLabel = computed(
    () =>
        SORT_OPTIONS.find((option) => option.value === props.sort)?.label ??
        SORT_OPTIONS[0].label,
);

/**
 * Sorting and filtering only redraw the backlog, which sits at the bottom of
 * the page, so the scroll position is kept.
 */
function reload(query: { sort?: string; mine?: boolean }) {
    router.get(
        route('roadmap.index'),
        { sort: props.sort, mine: props.mine, ...query },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Voting updates the card before the server answers, so the list is local state.
const localItems = ref(props.items.map((item) => ({ ...item })));

watch(
    () => props.items,
    (items) => {
        localItems.value = items.map((item) => ({ ...item }));
    },
    { deep: true },
);

const board = computed(() =>
    props.columns.map((column) => ({
        ...column,
        items: localItems.value.filter((item) => item.status === column.value),
    })),
);

const backlog = computed(() =>
    localItems.value.filter((item) => item.status === 'backlog'),
);

const { vote, pending } = useVote(props.authenticated, ['items']);

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

function typeVariant(item: RoadmapItem) {
    const type = props.types.find((option) => option.value === item.type);

    return colorToVariant[type?.color ?? 'primary'] ?? 'default';
}

const dialogOpen = ref(false);

const form = useForm({
    title: '',
    description: '',
    type: props.types[0]?.value ?? 'feature',
});

function openDialog() {
    if (!props.authenticated) {
        router.visit(route('login'));

        return;
    }

    form.reset();
    dialogOpen.value = true;
}

function submitSuggestion() {
    form.post(route('roadmap.store'), {
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <SiteLayout
        :title="title"
        :description="description"
        :canonical="route('roadmap.index')"
    >
        <PageHero
            test-id="roadmap-hero"
            :title="$t('Product Roadmap')"
            :description="
                $t(
                    'Everything we are working on, in the open. Upvote the ideas you want next, follow along as they move from planned to shipped, and send us anything that is missing.',
                )
            "
            :icon="IconMap"
        >
            <template #actions>
                <Button
                    data-testid="suggest-btn"
                    variant="secondary"
                    class="w-full sm:w-auto"
                    @click="openDialog"
                >
                    <IconPlus class="size-5" />
                    {{ $t('Submit feedback') }}
                </Button>
            </template>
        </PageHero>

        <div class="mx-auto w-full max-w-6xl px-8 py-16">
            <!-- Sorting and filtering, at the top where they are easy to find -->
            <div
                v-if="localItems.length > 0 || mine"
                class="mb-8 flex flex-wrap items-center gap-2"
            >
                <Button
                    v-if="mine"
                    data-testid="filter-mine-back"
                    variant="ghost"
                    class="gap-2"
                    @click="reload({ mine: false })"
                >
                    <IconArrowLeft class="size-4" />
                    {{ $t('Back to the roadmap') }}
                </Button>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            data-testid="sort-trigger"
                            class="gap-2"
                        >
                            {{ $t('Sort by') }}: {{ currentSortLabel }}
                            <IconChevronDown class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="start">
                        <DropdownMenuItem
                            v-for="option in SORT_OPTIONS"
                            :key="option.value"
                            :data-testid="`sort-${option.value}`"
                            @select="reload({ sort: option.value })"
                        >
                            {{ option.label }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button
                    v-if="authenticated && !mine"
                    data-testid="filter-mine"
                    variant="outline"
                    class="ml-auto"
                    @click="reload({ mine: true })"
                >
                    {{ $t('My feedback') }}
                </Button>
            </div>

            <div
                v-if="localItems.length === 0"
                class="bg-muted/30 flex flex-col items-center justify-center gap-6 rounded-lg py-20 text-center"
            >
                <IconMap class="text-muted-foreground size-14" />
                <p class="text-muted-foreground" data-testid="roadmap-empty">
                    {{
                        mine
                            ? $t('You have not sent us any feedback yet.')
                            : $t(
                                  'No roadmap items yet. Be the first to suggest a feature!',
                              )
                    }}
                </p>
                <button
                    type="button"
                    data-testid="suggest-btn-empty"
                    class="border-secondary text-secondary hover:bg-secondary hover:text-secondary-foreground inline-flex items-center gap-2 rounded-md border px-4 py-1.5 text-sm transition-colors"
                    @click="openDialog"
                >
                    <IconPlus class="size-5" />
                    {{ $t('Submit feedback') }}
                </button>
            </div>

            <!-- Own submissions: a plain list, because one person's items can sit
                 in statuses the board has no column for, review included. -->
            <div
                v-else-if="mine"
                data-testid="roadmap-mine"
                class="grid grid-cols-1 gap-3 lg:grid-cols-2"
            >
                <ItemCard
                    v-for="item in localItems"
                    :key="item.id"
                    :item="item"
                    :type-variant="typeVariant(item)"
                    :comments-enabled="comments_enabled"
                    :vote-pending="pending.has(item.id)"
                    show-status
                    @vote="vote"
                />
            </div>

            <template v-else>
                <!-- One column per board status, stacked on small screens -->
                <div
                    data-testid="roadmap-board"
                    class="grid grid-cols-1 gap-6 md:grid-cols-3"
                >
                    <section
                        v-for="column in board"
                        :key="column.value"
                        :data-testid="`roadmap-column-${column.value}`"
                        class="flex flex-col gap-3"
                    >
                        <h2
                            class="text-muted-foreground flex items-center gap-2 px-1 text-xs font-semibold tracking-wider uppercase"
                        >
                            {{ column.label }}
                            <span class="font-normal"
                                >({{ column.items.length }})</span
                            >
                        </h2>

                        <p
                            v-if="column.items.length === 0"
                            class="text-muted-foreground bg-muted/30 rounded-lg px-3 py-6 text-center text-sm"
                        >
                            {{ $t('Nothing here yet.') }}
                        </p>

                        <ItemCard
                            v-for="item in column.items"
                            :key="item.id"
                            :item="item"
                            :type-variant="typeVariant(item)"
                            :comments-enabled="comments_enabled"
                            :vote-pending="pending.has(item.id)"
                            @vote="vote"
                        />
                    </section>
                </div>

                <!-- Accepted, but not scheduled yet -->
                <section
                    v-if="backlog.length > 0"
                    data-testid="roadmap-backlog"
                    class="mt-14"
                >
                    <div
                        class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <h2 class="text-lg font-semibold">
                            {{ $t('Backlog') }}
                            <span class="text-muted-foreground font-normal"
                                >({{ backlog.length }})</span
                            >
                        </h2>
                    </div>

                    <p class="text-muted-foreground mb-4 text-sm">
                        {{
                            $t(
                                'Ideas we accepted but have not scheduled. Votes help us pick what comes next.',
                            )
                        }}
                    </p>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <ItemCard
                            v-for="item in backlog"
                            :key="item.id"
                            :item="item"
                            :type-variant="typeVariant(item)"
                            :comments-enabled="comments_enabled"
                            :vote-pending="pending.has(item.id)"
                            @vote="vote"
                        />
                    </div>
                </section>
            </template>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ $t('Submit feedback') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            $t(
                                'We review all submissions before publishing them to the roadmap.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitSuggestion" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="text-sm font-medium">{{
                            $t('Type')
                        }}</label>
                        <div class="flex gap-2">
                            <button
                                v-for="type in types"
                                :key="type.value"
                                type="button"
                                :class="[
                                    'rounded-md border px-4 py-1.5 text-sm font-medium transition-colors',
                                    form.type === type.value
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'hover:bg-accent border-border',
                                ]"
                                @click="form.type = type.value"
                            >
                                {{ type.label }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="suggest-title" class="text-sm font-medium">
                            {{ $t('Title') }}
                            <span class="text-destructive">*</span>
                        </label>
                        <input
                            id="suggest-title"
                            data-testid="suggest-title"
                            v-model="form.title"
                            type="text"
                            :placeholder="
                                $t('e.g. Dark mode, login bug, faster search…')
                            "
                            maxlength="255"
                            class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label
                            for="suggest-description"
                            class="text-sm font-medium"
                        >
                            {{ $t('Description') }}
                        </label>
                        <textarea
                            id="suggest-description"
                            data-testid="suggest-description"
                            v-model="form.description"
                            :placeholder="
                                $t(
                                    'What\'s the problem or idea? Any details help.',
                                )
                            "
                            rows="3"
                            maxlength="2000"
                            class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none"
                        />
                        <p
                            v-if="form.errors.description"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            data-testid="suggest-cancel-btn"
                            type="button"
                            variant="outline"
                            @click="dialogOpen = false"
                        >
                            {{ $t('Cancel') }}
                        </Button>
                        <Button
                            data-testid="suggest-submit-btn"
                            type="submit"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? $t('Submitting…')
                                    : $t('Submit')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </SiteLayout>
</template>
