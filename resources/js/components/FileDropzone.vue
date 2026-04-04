<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref } from 'vue';
import { FileUp, Upload, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    modelValue?: File | null;
    accept?: string;
    title?: string;
    hint?: string;
    disabled?: boolean;
    class?: HTMLAttributes['class'];
}>(), {
    modelValue: null,
    accept: '',
    title: 'Перетягніть файл сюди',
    hint: 'або натисніть, щоб обрати',
    disabled: false,
    class: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', payload: File | null): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

const fileSizeLabel = computed(() => {
    if (!props.modelValue) return '';
    const size = props.modelValue.size;
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
});

const openPicker = () => {
    if (props.disabled) return;
    inputRef.value?.click();
};

const setFile = (file: File | null) => {
    emit('update:modelValue', file);
};

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    setFile(target.files?.[0] ?? null);
};

const onDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (props.disabled) return;
    isDragging.value = true;
};

const onDragLeave = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
};

const onDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
    if (props.disabled) return;
    const file = event.dataTransfer?.files?.[0] ?? null;
    setFile(file);
};

const clearFile = () => {
    setFile(null);
    if (inputRef.value) {
        inputRef.value.value = '';
    }
};
</script>

<template>
    <div
        :class="cn(
            'rounded-lg border border-dashed border-input/80 bg-muted/25 p-4 transition-colors',
            isDragging ? 'border-primary bg-primary/5' : '',
            disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:border-primary/70 hover:bg-muted/40',
            props.class,
        )"
        role="button"
        tabindex="0"
        @click="openPicker"
        @keydown.enter.prevent="openPicker"
        @keydown.space.prevent="openPicker"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
    >
        <input
            ref="inputRef"
            type="file"
            class="hidden"
            :accept="accept"
            :disabled="disabled"
            @change="onFileChange"
        />

        <div class="flex items-start gap-3">
            <div class="rounded-md border border-input/70 bg-background p-2 text-muted-foreground">
                <Upload class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground">{{ title }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>

                <div
                    v-if="modelValue"
                    class="mt-3 flex items-center gap-2 rounded-md border border-input/70 bg-background/80 px-3 py-2 text-xs"
                >
                    <FileUp class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                    <span class="min-w-0 flex-1 truncate">{{ modelValue.name }}</span>
                    <span class="shrink-0 text-muted-foreground">{{ fileSizeLabel }}</span>
                    <button
                        type="button"
                        class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground"
                        @click.stop="clearFile"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
