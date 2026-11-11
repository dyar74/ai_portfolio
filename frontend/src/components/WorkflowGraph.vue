<template>
    <div
        class="relative h-[420px] w-full overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
    >
        <VueFlow
            :nodes="flowNodes"
            :edges="flowEdges"
            :fit-view-on-init="true"
            :nodes-draggable="false"
            :nodes-connectable="false"
            :elements-selectable="false"
            :min-zoom="0.4"
            :max-zoom="1.5"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { VueFlow } from '@vue-flow/core';
import type { Node, Edge } from '@vue-flow/core';
import { useI18n } from 'vue-i18n';
import type { FlowNode, FlowEdge } from '../data/workflows';

/**
 * Graph renderer for a single workflow.
 *
 * Props are the *data* shape from data/workflows.ts (FlowNode/FlowEdge), not
 * resolved strings. Labels come from i18n message keys (labelKey), so the
 * graph is fully translated and never shows raw node ids like "gemini" or
 * "undefined (logic)".
 */
const props = defineProps<{ nodes: FlowNode[]; edges: FlowEdge[] }>();
const { t } = useI18n();

type Category = 'llm' | 'trigger' | 'action' | 'logic' | 'output' | 'http' | 'default';

function nodeType(type: string): Category {
    const s = type.toLowerCase();
    if (
        s.includes('openai') ||
        s.includes('langchain') ||
        s.includes('gemini') ||
        s.includes('llm')
    )
        return 'llm';
    if (s.includes('webhook') && !s.includes('respond')) return 'trigger';
    if (s.includes('telegram') || s.includes('emailsend')) return 'action';
    if (s.includes('respondtowebhook')) return 'output';
    if (s.includes('httprequest')) return 'http';
    if (s.includes('if') || s.includes('switch') || s.includes('code')) return 'logic';
    return 'default';
}

const colorMap: Record<Category, string> = {
    llm: '#7c3aed',
    trigger: '#2563eb',
    action: '#059669',
    logic: '#d97706',
    output: '#dc2626',
    http: '#0891b2',
    default: '#64748b',
};

/** Short, human-readable category label shown in the node pill. */
const categoryLabel: Record<Category, string> = {
    llm: 'AI',
    trigger: 'trigger',
    action: 'action',
    logic: 'logic',
    output: 'output',
    http: 'http',
    default: 'node',
};

function resolveLabel(node: FlowNode): string {
    // Prefer the i18n label; fall back to the node id so the graph is never
    // blank. t() returns the key itself when the key is missing, so guard it.
    const raw = node.labelKey ? t(node.labelKey) : '';
    const label = raw && raw !== node.labelKey ? raw : node.id;
    return label;
}

/**
 * Node width (px). Used to center the column of nodes in the graph viewport.
 * Must match the `width` in the node style below.
 */
const NODE_WIDTH = 200;

const flowNodes = computed<Node[]>(() =>
    props.nodes.map((n, i) => {
        const cat = nodeType(n.type);
        const label = `${resolveLabel(n)} · ${categoryLabel[cat]}`;
        return {
            id: n.id,
            // Center the node column: x=0 with fitViewOnInit=true lets VueFlow
            // compute the correct pan/zoom to center the 200px-wide nodes in
            // whatever viewport width (mobile 375px, desktop, etc.).
            position: { x: 0, y: 60 + i * 90 },
            label,
            data: { label },
            style: {
                background: colorMap[cat],
                color: 'white',
                borderRadius: '10px',
                padding: '10px 14px',
                width: `${NODE_WIDTH}px`,
                fontSize: '12px',
                fontWeight: '600',
            },
        } as Node;
    }),
);

const flowEdges = computed<Edge[]>(() =>
    props.edges.map((e) => ({
        id: e.id,
        source: e.source,
        target: e.target,
        animated: true,
        style: { stroke: '#94a3b8', strokeWidth: 2 },
    })),
);
</script>
