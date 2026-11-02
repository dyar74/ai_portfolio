/**
 * Static workflow-graph data for the gallery, derived from the n8n JSON exports
 * in /n8n/workflows/<slug>.json. The Vue app never calls n8n directly — it just
 * renders these graphs and links to the downloadable JSON.
 *
 * IMPORTANT: node `label` and `description` are i18n MESSAGE KEYS (not raw text).
 * WorkflowGallery.vue resolves them with t(). Keep keys in sync with locales.
 */

export interface FlowNode {
  id: string;
  /** i18n key for the node label, e.g. 'wf.contact.webhook.label' */
  labelKey: string;
  type: string;
  /** i18n key for the node description */
  descKey: string;
}

export interface FlowEdge {
  id: string;
  source: string;
  target: string;
}

export interface Workflow {
  slug: string;
  /** i18n key for the workflow display name */
  nameKey: string;
  nodes: FlowNode[];
  edges: FlowEdge[];
  rawUrl: string; // endpoint to download the original n8n JSON
}

export const WORKFLOWS: Workflow[] = [
  {
    slug: 'contact-to-telegram',
    nameKey: 'wf.contact.name',
    nodes: [
      { id: 'webhook', labelKey: 'wf.contact.webhook.label', type: 'n8n-nodes-base.webhook', descKey: 'wf.contact.webhook.desc' },
      { id: 'validate', labelKey: 'wf.contact.validate.label', type: 'n8n-nodes-base.if', descKey: 'wf.contact.validate.desc' },
      { id: 'build', labelKey: 'wf.common.buildRequest.label', type: 'n8n-nodes-base.code', descKey: 'wf.contact.buildRequest.desc' },
      { id: 'gemini', labelKey: 'wf.common.gemini.label', type: 'n8n-nodes-base.httpRequest', descKey: 'wf.common.gemini.desc' },
      { id: 'parse', labelKey: 'wf.common.parseGemini.label', type: 'n8n-nodes-base.code', descKey: 'wf.common.parseGemini.desc' },
      { id: 'not-spam', labelKey: 'wf.contact.notSpam.label', type: 'n8n-nodes-base.if', descKey: 'wf.contact.notSpam.desc' },
      { id: 'telegram', labelKey: 'wf.contact.telegram.label', type: 'n8n-nodes-base.telegram', descKey: 'wf.contact.telegram.desc' },
      { id: 'email', labelKey: 'wf.contact.email.label', type: 'n8n-nodes-base.emailSend', descKey: 'wf.contact.email.desc' },
      { id: 'respond', labelKey: 'wf.common.respond.label', type: 'n8n-nodes-base.respondToWebhook', descKey: 'wf.contact.respond.desc' },
      { id: 'respond-invalid', labelKey: 'wf.contact.respondInvalid.label', type: 'n8n-nodes-base.respondToWebhook', descKey: 'wf.contact.respondInvalid.desc' },
    ],
    edges: [
      { id: 'e1', source: 'webhook', target: 'validate' },
      { id: 'e2', source: 'validate', target: 'build' },
      { id: 'e3', source: 'validate', target: 'respond-invalid' },
      { id: 'e4', source: 'build', target: 'gemini' },
      { id: 'e5', source: 'gemini', target: 'parse' },
      { id: 'e6', source: 'parse', target: 'not-spam' },
      { id: 'e7', source: 'not-spam', target: 'telegram' },
      { id: 'e8', source: 'not-spam', target: 'respond' },
      { id: 'e9', source: 'telegram', target: 'email' },
      { id: 'e10', source: 'email', target: 'respond' },
    ],
    rawUrl: '/n8n/workflows/contact-to-telegram.json',
  },
  {
    slug: 'ai-translate-en-uk',
    nameKey: 'wf.translate.name',
    nodes: [
      { id: 'webhook', labelKey: 'wf.translate.webhook.label', type: 'n8n-nodes-base.webhook', descKey: 'wf.translate.webhook.desc' },
      { id: 'build', labelKey: 'wf.common.buildRequest.label', type: 'n8n-nodes-base.code', descKey: 'wf.translate.buildRequest.desc' },
      { id: 'gemini', labelKey: 'wf.common.gemini.label', type: 'n8n-nodes-base.httpRequest', descKey: 'wf.common.gemini.desc' },
      { id: 'parse', labelKey: 'wf.common.parseGemini.label', type: 'n8n-nodes-base.code', descKey: 'wf.translate.parseGemini.desc' },
      { id: 'respond', labelKey: 'wf.common.respond.label', type: 'n8n-nodes-base.respondToWebhook', descKey: 'wf.translate.respond.desc' },
    ],
    edges: [
      { id: 'e1', source: 'webhook', target: 'build' },
      { id: 'e2', source: 'build', target: 'gemini' },
      { id: 'e3', source: 'gemini', target: 'parse' },
      { id: 'e4', source: 'parse', target: 'respond' },
    ],
    rawUrl: '/n8n/workflows/ai-translate-en-uk.json',
  },
  {
    slug: 'url-summarizer',
    nameKey: 'wf.summarizer.name',
    nodes: [
      { id: 'webhook', labelKey: 'wf.summarizer.webhook.label', type: 'n8n-nodes-base.webhook', descKey: 'wf.summarizer.webhook.desc' },
      { id: 'validate-url', labelKey: 'wf.summarizer.validateUrl.label', type: 'n8n-nodes-base.code', descKey: 'wf.summarizer.validateUrl.desc' },
      { id: 'fetch', labelKey: 'wf.summarizer.fetch.label', type: 'n8n-nodes-base.httpRequest', descKey: 'wf.summarizer.fetch.desc' },
      { id: 'extract', labelKey: 'wf.summarizer.extract.label', type: 'n8n-nodes-base.code', descKey: 'wf.summarizer.extract.desc' },
      { id: 'build', labelKey: 'wf.common.buildRequest.label', type: 'n8n-nodes-base.code', descKey: 'wf.summarizer.buildRequest.desc' },
      { id: 'gemini', labelKey: 'wf.common.gemini.label', type: 'n8n-nodes-base.httpRequest', descKey: 'wf.common.gemini.desc' },
      { id: 'parse', labelKey: 'wf.common.parseGemini.label', type: 'n8n-nodes-base.code', descKey: 'wf.summarizer.parseGemini.desc' },
      { id: 'respond', labelKey: 'wf.common.respond.label', type: 'n8n-nodes-base.respondToWebhook', descKey: 'wf.summarizer.respond.desc' },
    ],
    edges: [
      { id: 'e1', source: 'webhook', target: 'validate-url' },
      { id: 'e2', source: 'validate-url', target: 'fetch' },
      { id: 'e3', source: 'fetch', target: 'extract' },
      { id: 'e4', source: 'extract', target: 'build' },
      { id: 'e5', source: 'build', target: 'gemini' },
      { id: 'e6', source: 'gemini', target: 'parse' },
      { id: 'e7', source: 'parse', target: 'respond' },
    ],
    rawUrl: '/n8n/workflows/url-summarizer.json',
  },
  {
    slug: 'lead-scoring',
    nameKey: 'wf.scoring.name',
    nodes: [
      { id: 'webhook', labelKey: 'wf.scoring.webhook.label', type: 'n8n-nodes-base.webhook', descKey: 'wf.scoring.webhook.desc' },
      { id: 'build', labelKey: 'wf.common.buildRequest.label', type: 'n8n-nodes-base.code', descKey: 'wf.scoring.buildRequest.desc' },
      { id: 'gemini', labelKey: 'wf.common.gemini.label', type: 'n8n-nodes-base.httpRequest', descKey: 'wf.common.gemini.desc' },
      { id: 'parse-validate', labelKey: 'wf.scoring.parseValidate.label', type: 'n8n-nodes-base.code', descKey: 'wf.scoring.parseValidate.desc' },
      { id: 'respond', labelKey: 'wf.common.respond.label', type: 'n8n-nodes-base.respondToWebhook', descKey: 'wf.scoring.respond.desc' },
    ],
    edges: [
      { id: 'e1', source: 'webhook', target: 'build' },
      { id: 'e2', source: 'build', target: 'gemini' },
      { id: 'e3', source: 'gemini', target: 'parse-validate' },
      { id: 'e4', source: 'parse-validate', target: 'respond' },
    ],
    rawUrl: '/n8n/workflows/lead-scoring.json',
  },
];
