<template>
    <div class="flex flex-col gap-8">
        <div
            v-for="wf in workflows"
            :key="wf.slug"
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
        >
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ t(wf.nameKey) }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ wf.slug }}</p>
                </div>
                <Button variant="secondary" size="sm" @click="download(wf)">
                    {{ t('gallery.download') }}
                </Button>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <WorkflowGraph :nodes="wf.nodes" :edges="wf.edges" />

                <div class="flex flex-col gap-3">
                    <p
                        class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                    >
                        {{ t('gallery.nodes') }}
                    </p>
                    <ol class="flex flex-col gap-2">
                        <li
                            v-for="node in wf.nodes"
                            :key="node.id"
                            class="flex flex-col gap-0.5 rounded-lg bg-gray-50 p-3 dark:bg-gray-900/40"
                        >
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ t(node.labelKey) }}
                                <code
                                    class="ml-1 rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ nodeShortType(node.type) }}
                                </code>
                            </span>
                            <span class="text-xs text-gray-600 dark:text-gray-400">{{
                                t(node.descKey)
                            }}</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { WORKFLOWS, type Workflow } from '../data/workflows';
import Button from './Button.vue';
import WorkflowGraph from './WorkflowGraph.vue';

const { t } = useI18n();
const workflows = WORKFLOWS;

async function download(wf: Workflow) {
    try {
        const res = await fetch(wf.rawUrl);
        if (!res.ok) throw new Error('download_failed');
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${wf.slug}.json`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    } catch {
        // Silently ignore; the button is a convenience, not a critical path.
    }
}
function nodeShortType(type: string): string {
    return type.replace('n8n-nodes-base.', '').replace('n8n-nodes-base', '');
}
</script>
