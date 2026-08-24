# Nexora Product Release Trains

Release trains are commercial/product checkpoints layered on top of the canonical dependency graph. They do not replace stage IDs or permit skipping dependencies.

## Train 1 — Nexora Builder Beta

### Goal

Ship a secure, self-hostable, extensible CMS/site-builder product that can credibly compete on the core website-building workflow before deeper CRM/enterprise breadth.

### Required capability groups

- runtime and core Admin usability;
- machine-enforced AI governance;
- early security baseline;
- architecture boundary closure;
- current Theme/Extension/Studio/CMS/Media/SEO/Automation workflow closure;
- dynamic content model/custom fields/relations;
- generic taxonomy;
- typed query/archive engine;
- permalink/routing/redirect platform;
- public navigation/menu locations;
- Theme Contract 2.0/template hierarchy;
- Extension SDK 2.0/hooks/slots/runtime APIs;
- Site Builder 2.0;
- Theme Studio/global design system;
- preview/staging/branching/publish/rollback workflow;
- starter templates/patterns/components;
- multilingual baseline 2.0;
- frontend cache/CDN/image pipeline;
- DAM/search/forms;
- privacy/consent controls.

### Beta exit condition

A new operator can install Nexora, create/edit structured content, visually build responsive pages/templates, install a safe theme/extension, preview/stage/publish/rollback, manage SEO/media/forms/locales/consent, and complete those workflows without private implementation shortcuts or unresolved critical security defects.

## Train 2 — Nexora Pro

### Goal

Differentiate Nexora as an AI-native professional web platform rather than another page builder.

### Required capability groups

- AI Kernel and Tool Registry;
- SEO/AEO/AI visibility intelligence;
- public REST/GraphQL/OAuth/headless surfaces;
- configuration as code/import-export;
- external AI-agent interoperability through scoped tools;
- AI content/SEO/media assistance;
- AI Design Professional;
- design/Figma import to structured AST/tokens/components;
- AI developer/package assistant;
- experimentation/A-B testing;
- privacy-safe personalization;
- capability-bounded low-code/full-stack App Runtime;
- migration center;
- mature DX/CLI/SDK/docs.

### Pro exit condition

AI can plan, draft and safely execute bounded site/content/design/development actions through public contracts with approvals, audit and rollback metadata, while external agents can interoperate without privileged direct access.

## Train 3 — Nexora Platform

### Goal

Expand from professional website platform into a broader application/commerce/ecosystem platform after the web-builder kernel is mature.

### Required capability groups

- current Marketplace workflow closure + Marketplace 2.0;
- Commerce foundation closure + Commerce 2.0;
- CRM/Membership/Helpdesk closure;
- customer/member portal builder;
- collaboration/presence/approvals;
- optional Nexora Managed Cloud;
- enterprise/cloud foundation verification;
- Sentinel 2.0 and runtime isolation strategy;
- Enterprise Governance 2.0;
- Operations/Observability Center;
- update/rollback/backup/DR platform.

### Platform exit condition

Nexora can operate as an extensible multi-tenant/managed or self-hosted platform with safe publisher ecosystem, commerce/business modules, advanced governance, observability and recovery.

## Train 4 — Stable Production

### Required gates

- performance/Core Web Vitals certification;
- accessibility/international certification;
- exact-source dependency/security/browser/database/backup/restore/HA/package certification;
- no unresolved release-blocking security findings;
- stable upgrade/rollback evidence;
- documented support/incident/recovery procedures.

### Stable exit condition

`N2-STABLE-100` may be marked complete only after `RELEASE-CERT-100` passes against the intended production release source and real target evidence.

## Commercial sequencing rule

Do not block Builder Beta on optional deep CRM/Helpdesk/enterprise/cloud productization. Do not ship those later systems through private Core shortcuts either. The platform kernel must remain the reusable foundation for every later product family.
