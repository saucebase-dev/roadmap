<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

defineProps<{
    types: { value: string; label: string }[];
}>();

const title = 'Suggest a feature';

const form = useForm({
    title: '',
    description: '',
    type: 'feature',
});

function submit() {
    form.post(route('roadmap.store'));
}
</script>

<template>
    <AppLayout
        :title="title"
        :breadcrumbs="[{ title: 'Roadmap', url: route('roadmap.index') }, { title }]"
    >
        <div class="flex flex-1 flex-col gap-6 p-6 pt-2 max-w-2xl">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ $t('Suggest a feature') }}</h1>
                <p class="text-muted-foreground text-sm mt-1">
                    {{ $t('Share your idea with us. We review all suggestions before publishing them to the roadmap.') }}
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('Your suggestion') }}</CardTitle>
                    <CardDescription>
                        {{ $t('New suggestions are reviewed before becoming visible to others.') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Type -->
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium">{{ $t('Type') }}</label>
                            <div class="flex gap-3">
                                <label
                                    v-for="t in types"
                                    :key="t.value"
                                    :class="[
                                        'flex items-center gap-2 rounded-md border px-4 py-2 cursor-pointer text-sm font-medium transition-colors',
                                        form.type === t.value
                                            ? 'bg-primary text-primary-foreground border-primary'
                                            : 'hover:bg-accent border-border',
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        :value="t.value"
                                        v-model="form.type"
                                        class="sr-only"
                                    />
                                    {{ t.label }}
                                </label>
                            </div>
                            <p v-if="form.errors.type" class="text-destructive text-xs">{{ form.errors.type }}</p>
                        </div>

                        <!-- Title -->
                        <div class="space-y-1.5">
                            <label for="title" class="text-sm font-medium">
                                {{ $t('Title') }} <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                :placeholder="$t('e.g. Dark mode support')"
                                maxlength="255"
                                class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none"
                            />
                            <p v-if="form.errors.title" class="text-destructive text-xs">{{ form.errors.title }}</p>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1.5">
                            <label for="description" class="text-sm font-medium">
                                {{ $t('Description') }}
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                :placeholder="$t('Describe your suggestion in more detail...')"
                                rows="4"
                                maxlength="2000"
                                class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus-visible:ring-1 focus-visible:outline-none resize-none"
                            />
                            <p v-if="form.errors.description" class="text-destructive text-xs">{{ form.errors.description }}</p>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <Button type="submit" :disabled="form.processing">
                                {{ form.processing ? $t('Submitting…') : $t('Submit suggestion') }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                @click="$inertia.visit(route('roadmap.index'))"
                            >
                                {{ $t('Cancel') }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
