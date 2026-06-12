<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Placement {
    value: string
    label: string
}

interface Advertisement {
    id: number
    title: string
    image_url: string
    link_url?: string | null
    placement: string
    is_active: boolean
    sort_order: number
}

const props = defineProps<{
    advertisement: Advertisement
    placements: Placement[]
}>()

const form = useForm<{
    _method: 'patch'
    title: string
    image: File | null
    link_url: string
    placement: string
    is_active: boolean
    sort_order: number
}>({
    _method: 'patch',
    title: props.advertisement.title,
    image: null,
    link_url: props.advertisement.link_url ?? '',
    placement: props.advertisement.placement,
    is_active: props.advertisement.is_active,
    sort_order: props.advertisement.sort_order,
})

const previewUrl = ref<string | null>(null)

function onFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null
    form.image = file
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = file ? URL.createObjectURL(file) : null
}

function submit() {
    // Files can't be sent over PATCH, so POST with method spoofing.
    form.transform((data) => ({
        ...data,
        link_url: data.link_url || null,
    })).post(`/admin/advertisements/${props.advertisement.id}`)
}
</script>

<template>
    <AdminLayout>
        <div class="mx-auto max-w-2xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="font-display text-3xl font-black uppercase tracking-wide text-foreground">Edit Advertisement</h1>
                <Link href="/admin/advertisements" class="text-sm font-medium text-muted-foreground hover:text-foreground hover:underline">← Back</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-border bg-card p-6">
                <!-- Title -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Title</label>
                    <input v-model="form.title" type="text" placeholder="Sponsor name / internal label" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-destructive">{{ form.errors.title }}</p>
                </div>

                <!-- Image upload -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Banner image</label>
                    <input
                        type="file"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        @change="onFileChange"
                        class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-lg file:border-0 file:bg-pitch file:px-4 file:py-2 file:text-sm file:font-bold file:text-pitch-foreground hover:file:bg-pitch/90"
                    />
                    <p class="mt-1 text-xs text-muted-foreground/70">Leave empty to keep the current image. JPG, PNG, WEBP or GIF · up to 4&nbsp;MB.</p>
                    <p v-if="form.errors.image" class="mt-1 text-xs text-destructive">{{ form.errors.image }}</p>
                    <div class="mt-3 overflow-hidden rounded-lg border border-border">
                        <img :src="previewUrl ?? advertisement.image_url" alt="Banner" class="h-auto w-full" />
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground/70">{{ previewUrl ? 'New image preview' : 'Current image' }}</p>
                    <div v-if="form.progress" class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                        <div class="h-full bg-pitch transition-all" :style="{ width: `${form.progress.percentage}%` }" />
                    </div>
                </div>

                <!-- Link URL -->
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Link URL <span class="text-muted-foreground/60">(optional)</span></label>
                    <input v-model="form.link_url" type="text" placeholder="https://sponsor.example.com" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                    <p v-if="form.errors.link_url" class="mt-1 text-xs text-destructive">{{ form.errors.link_url }}</p>
                </div>

                <!-- Placement + Order -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Placement</label>
                        <select v-model="form.placement" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none">
                            <option v-for="p in placements" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <p v-if="form.errors.placement" class="mt-1 text-xs text-destructive">{{ form.errors.placement }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sort order</label>
                        <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border border-border bg-muted px-3 py-2 text-sm text-foreground focus:border-pitch focus:outline-none" />
                        <p v-if="form.errors.sort_order" class="mt-1 text-xs text-destructive">{{ form.errors.sort_order }}</p>
                    </div>
                </div>

                <!-- Active -->
                <label class="flex items-center gap-2.5">
                    <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-border text-pitch focus:ring-pitch" />
                    <span class="text-sm font-medium text-foreground">Active <span class="font-normal text-muted-foreground">— show this advertisement on the site</span></span>
                </label>

                <!-- Buttons -->
                <div class="flex gap-3 pt-2">
                    <Link href="/admin/advertisements" class="flex h-11 flex-1 items-center justify-center rounded-lg border border-border text-sm font-medium text-foreground hover:bg-muted transition-colors">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="flex h-11 flex-1 items-center justify-center rounded-lg bg-pitch text-sm font-bold text-pitch-foreground hover:bg-pitch/90 disabled:opacity-50 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
