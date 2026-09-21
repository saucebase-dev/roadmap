<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useLocalization } from '@/composables/useLocalization';
import { formatDate } from '@js/lib/dates';
import { useForm } from '@inertiajs/vue3';
import { useModal } from '@inertiaui/modal-vue';
import { trans } from 'laravel-vue-i18n';
import { toast } from 'vue-sonner';
import { reactive, watch } from 'vue';

import type { RoadmapItemDetail } from '../../types';
import VoteButton from './VoteButton.vue';
import { useVote } from '../composables/useVote';

const props = defineProps<{
    item: RoadmapItemDetail;
    authenticated: boolean;
}>();

// Voting updates the count before the server answers, so the item is local state.
const item = reactive({ ...props.item });

watch(
    () => props.item,
    (updated) => Object.assign(item, updated),
    { deep: true },
);

const { vote, pending } = useVote(props.authenticated, ['item']);

const form = useForm({ body: '' });

// Inside a slideover the redirect refreshes the board behind it, so the panel
// has to ask for its own props again to show the new comment.
const modal = useModal();

function submitComment() {
    if (modal) {
        // Posting through the panel's own request: an Inertia visit would follow
        // the redirect, navigate the page behind it, and close the panel.
        modal.reload({
            method: 'post',
            data: { body: form.body },
            onStart: () => (form.processing = true),
            onSuccess: () => form.reset(),
            onError: () => toast.error(trans('Your comment was not posted.')),
            onFinish: () => (form.processing = false),
        });

        return;
    }

    form.post(route('roadmap.comments.store', props.item.slug), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        // A rejected body already shows its message under the field; anything
        // else (expired session, rate limit, server error) would be silent.
        onError: (errors) => {
            if (!errors.body) {
                toast.error(trans('Your comment was not posted.'));
            }
        },
    });
}

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

const { language } = useLocalization();
</script>

<template>
    <div>
        <article class="flex items-start gap-4">
            <VoteButton
                :item-id="item.id"
                :votes="item.votes_count"
                :voted="item.has_voted"
                :pending="pending.has(item.id)"
                @vote="vote(item)"
            />

            <div class="min-w-0 flex-1">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <Badge variant="secondary" data-testid="item-status">
                        {{ item.status_label }}
                    </Badge>
                    <Badge variant="outline">{{ item.type_label }}</Badge>
                    <span class="text-muted-foreground text-xs">
                        {{ formatDate(item.created_at, language) }}
                    </span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight">
                    {{ item.title }}
                </h1>

                <p
                    v-if="item.description"
                    class="text-muted-foreground mt-4 whitespace-pre-line"
                >
                    {{ item.description }}
                </p>
            </div>
        </article>

        <!-- The team's answer, pinned above the discussion -->
        <section
            v-if="item.official_response"
            data-testid="official-response"
            class="border-primary bg-primary/5 mt-8 rounded-lg border-l-4 p-5"
        >
            <p class="text-primary mb-2 text-sm font-semibold">
                {{ $t('Response from the team') }}
                <span
                    v-if="item.official_response_at"
                    class="text-muted-foreground font-normal"
                >
                    · {{ formatDate(item.official_response_at, language) }}
                </span>
            </p>
            <p class="whitespace-pre-line">{{ item.official_response }}</p>
        </section>

        <section v-if="item.comments_enabled" class="mt-10">
            <h2 class="mb-6 text-lg font-semibold">
                <template v-if="item.comments.length > 0">
                    {{ $t('Comments') }}
                    <span class="text-muted-foreground font-normal"
                        >({{ item.comments.length }})</span
                    >
                </template>
                <template v-else>{{ $t('No comments yet') }}</template>
            </h2>

            <ul class="space-y-6">
                <li
                    v-for="comment in item.comments"
                    :key="comment.id"
                    :data-testid="`comment-${comment.id}`"
                    class="flex gap-3"
                >
                    <Avatar class="size-8 shrink-0">
                        <AvatarFallback
                            :class="[
                                'text-xs',
                                comment.mine
                                    ? 'bg-primary text-primary-foreground'
                                    : '',
                            ]"
                        >
                            {{ initials(comment.author) }}
                        </AvatarFallback>
                    </Avatar>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <span class="text-sm font-semibold">
                                {{ comment.author }}
                            </span>
                            <span class="text-muted-foreground text-xs">
                                {{ formatDate(comment.created_at, language) }}
                            </span>
                        </div>
                        <div
                            :class="[
                                'mt-1 rounded-lg rounded-tl-none px-3 py-2',
                                comment.mine
                                    ? 'bg-primary/10 border-primary/30 border'
                                    : 'bg-muted/50',
                            ]"
                        >
                            <p class="text-sm whitespace-pre-line">
                                {{ comment.body }}
                            </p>
                        </div>
                    </div>
                </li>
            </ul>
            <form
                v-if="authenticated"
                @submit.prevent="submitComment"
                class="mt-8 space-y-2"
            >
                <textarea
                    v-model="form.body"
                    data-testid="comment-body"
                    :placeholder="$t('Add your thoughts…')"
                    rows="3"
                    maxlength="2000"
                    class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full resize-none rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none"
                />
                <p v-if="form.errors.body" class="text-destructive text-xs">
                    {{ form.errors.body }}
                </p>
                <div class="flex justify-end">
                    <Button
                        type="submit"
                        data-testid="comment-submit"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing
                                ? $t('Posting…')
                                : $t('Post comment')
                        }}
                    </Button>
                </div>
            </form>

            <p v-else class="text-muted-foreground mt-8 text-sm">
                <a
                    :href="route('login')"
                    data-testid="comment-login"
                    class="text-primary underline"
                    >{{ $t('Log in') }}</a
                >
                {{ $t('to join the discussion.') }}
            </p>
        </section>
    </div>
</template>
