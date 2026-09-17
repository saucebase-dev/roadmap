<script setup lang="ts">
import SiteLayout from '@/layouts/SiteLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Modal } from '@inertiaui/modal-vue';

import type { RoadmapItemDetail } from '../../types';
import ItemDetail from '../components/ItemDetail.vue';

import IconArrowLeft from '~icons/heroicons/arrow-left';

/**
 * One roadmap item, as a page or as a modal over the board.
 *
 * The URL is the same either way. The controller passes `modal` only when the
 * board asked for one, so a typed or shared link still opens the full page and
 * search engines see ordinary HTML.
 */
defineProps<{
    item: RoadmapItemDetail;
    authenticated: boolean;
    modal?: boolean;
}>();
</script>

<template>
    <Modal v-if="modal" slideover position="right" max-width="2xl">
        <div class="p-6" data-testid="roadmap-item-modal">
            <ItemDetail :item="item" :authenticated="authenticated" />
        </div>
    </Modal>

    <SiteLayout
        v-else
        :title="item.title"
        :description="item.description ?? undefined"
        :canonical="item.url"
        type="article"
    >
        <div class="mx-auto w-full max-w-3xl px-6 py-16">
            <Link
                :href="route('roadmap.index')"
                data-testid="back-to-roadmap"
                class="text-muted-foreground hover:text-foreground mb-8 inline-flex items-center gap-2 text-sm"
            >
                <IconArrowLeft class="size-4" />
                {{ $t('Back to roadmap') }}
            </Link>

            <ItemDetail :item="item" :authenticated="authenticated" />
        </div>
    </SiteLayout>
</template>
