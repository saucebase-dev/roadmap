<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import { ModalLink } from '@inertiaui/modal-vue';

import type { RoadmapItem } from '../../types';
import VoteButton from './VoteButton.vue';

import IconComments from '~icons/heroicons/chat-bubble-left-right';

defineProps<{
    item: RoadmapItem;
    typeVariant: 'default' | 'destructive' | 'secondary' | 'outline';
    commentsEnabled: boolean;
    /** Own submissions are listed off the board, where the column no longer says the status. */
    showStatus?: boolean;
    votePending?: boolean;
}>();

defineEmits<{ vote: [item: RoadmapItem] }>();
</script>

<template>
    <article
        :data-testid="`roadmap-item-${item.id}`"
        class="bg-card hover:border-primary/40 flex items-start gap-3 rounded-lg border p-3 transition-colors"
    >
        <VoteButton
            :item-id="item.id"
            :votes="item.votes_count"
            :voted="item.has_voted"
            :pending="votePending"
            @vote="$emit('vote', item)"
        />

        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <ModalLink
                navigate
                :href="item.url"
                :data-testid="`roadmap-item-link-${item.id}`"
                class="leading-snug font-semibold hover:underline"
            >
                {{ item.title }}
            </ModalLink>

            <p
                v-if="item.description"
                class="text-muted-foreground line-clamp-2 text-sm"
            >
                {{ item.description }}
            </p>

            <div class="mt-1 flex items-center gap-2">
                <Badge
                    v-if="showStatus"
                    variant="secondary"
                    class="text-xs"
                    :data-testid="`item-status-${item.id}`"
                >
                    {{ item.status_label }}
                </Badge>
                <Badge :variant="typeVariant" class="text-xs">
                    {{ item.type_label }}
                </Badge>
                <ModalLink
                    v-if="commentsEnabled"
                    navigate
                    :href="item.url"
                    :data-testid="`comment-count-${item.id}`"
                    :aria-label="$t('Comments')"
                    class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-xs transition-colors"
                >
                    <IconComments class="size-4" />
                    {{ item.comments_count }}
                </ModalLink>
            </div>
        </div>
    </article>
</template>
