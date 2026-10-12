<script setup lang="ts">
import IconAltArrowUpBold from '~icons/solar/alt-arrow-up-bold';

defineProps<{
    itemId: number;
    votes: number;
    voted: boolean;
    pending?: boolean;
}>();

defineEmits<{ vote: [] }>();
</script>

<template>
    <button
        type="button"
        :data-testid="`vote-btn-${itemId}`"
        :data-voted="voted"
        :disabled="pending"
        :aria-pressed="voted"
        :aria-label="$t('Upvote')"
        :class="[
            'flex w-14 shrink-0 cursor-pointer flex-col items-center justify-center gap-0.5 rounded-md border py-2 transition-colors disabled:cursor-default disabled:opacity-70',
            voted
                ? 'bg-primary text-primary-foreground border-primary'
                : 'text-muted-foreground hover:bg-accent hover:text-foreground border-border',
        ]"
        @click.prevent="$emit('vote')"
    >
        <IconAltArrowUpBold class="size-5" />
        <span
            :data-testid="`vote-count-${itemId}`"
            class="text-sm font-semibold tabular-nums"
        >
            {{ votes }}
        </span>
    </button>
</template>
