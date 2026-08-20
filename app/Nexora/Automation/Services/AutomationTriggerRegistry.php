<?php

declare(strict_types=1);

namespace App\Nexora\Automation\Services;

use InvalidArgumentException;

final class AutomationTriggerRegistry
{
    /** @var array<string,array<string,mixed>> */
    private array $items = [];

    public function __construct()
    {
        foreach ([
            ['key'=>'document.created','label'=>'Document created','group'=>'Content','description'=>'Runs after a Nexora document is created.','fields'=>['document.id','document.type','document.status','document.title','document.slug']],
            ['key'=>'document.updated','label'=>'Document updated','group'=>'Content','description'=>'Runs after a Nexora document changes.','fields'=>['document.id','document.type','document.status','document.title','document.slug']],
            ['key'=>'document.published','label'=>'Document published','group'=>'Content','description'=>'Runs when a document transitions to published.','fields'=>['document.id','document.type','document.title','document.slug']],
            ['key'=>'media.uploaded','label'=>'Media uploaded','group'=>'Media','description'=>'Runs after a Media Library asset is created.','fields'=>['media.id','media.uuid','media.media_type','media.mime_type','media.original_name']],
            ['key'=>'newsletter.subscribed','label'=>'Newsletter subscriber added','group'=>'Distribution','description'=>'Runs when a subscriber is created.','fields'=>['subscriber.id','subscriber.email','subscriber.locale','subscriber.status']],
            ['key'=>'search.zero_results','label'=>'Search returned no results','group'=>'Discovery','description'=>'Runs when public search demand has zero matching results.','fields'=>['search.query','search.locale','search.results_count']],
            ['key'=>'commerce.order.created','label'=>'Commerce order created','group'=>'Commerce','description'=>'Runs when a Nexora Commerce order record is created.','fields'=>['order.id','order.number','order.currency','order.total_minor','order.customer_id']],
            ['key'=>'commerce.order.placed','label'=>'Commerce order placed','group'=>'Commerce','description'=>'Runs when a draft order is placed and awaits payment.','fields'=>['order.id','order.number','order.currency','order.total_minor']],
            ['key'=>'commerce.payment.succeeded','label'=>'Commerce payment succeeded','group'=>'Commerce','description'=>'Runs after a provider-backed payment/capture is recorded as successful.','fields'=>['payment.id','payment.provider','payment.amount_minor','payment.currency','payment.order_id','payment.invoice_id']],
            ['key'=>'commerce.refund.created','label'=>'Commerce refund created','group'=>'Commerce','description'=>'Runs when a refund lifecycle record is created.','fields'=>['refund.id','refund.payment_id','refund.amount_minor','refund.currency','refund.status']],
            ['key'=>'commerce.subscription.updated','label'=>'Commerce subscription updated','group'=>'Commerce','description'=>'Runs when a provider-backed subscription state is recorded.','fields'=>['subscription.id','subscription.status','subscription.provider','subscription.customer_id','subscription.product_id']],
            ['key'=>'crm.organization.created','label'=>'CRM organization created','group'=>'CRM','description'=>'Runs when a CRM organization/company record is created.','fields'=>['organization.id','organization.name','organization.domain','organization.owner_id']],
            ['key'=>'crm.contact.created','label'=>'CRM contact created','group'=>'CRM','description'=>'Runs when a CRM contact is created.','fields'=>['contact.id','contact.display_name','contact.email','contact.organization_id','contact.owner_id']],
            ['key'=>'crm.lead.created','label'=>'CRM lead created','group'=>'CRM','description'=>'Runs when a lead enters CRM.','fields'=>['lead.id','lead.title','lead.status','lead.score','lead.owner_id']],
            ['key'=>'crm.lead.converted','label'=>'CRM lead converted','group'=>'CRM','description'=>'Runs when a lead is converted into an opportunity.','fields'=>['lead.id','lead.title','opportunity.id','opportunity.name','opportunity.stage_id']],
            ['key'=>'crm.opportunity.created','label'=>'CRM opportunity created','group'=>'CRM','description'=>'Runs when an opportunity is created.','fields'=>['opportunity.id','opportunity.name','opportunity.pipeline_id','opportunity.stage_id','opportunity.status','opportunity.amount_minor','opportunity.currency']],
            ['key'=>'crm.opportunity.stage_changed','label'=>'CRM opportunity stage changed','group'=>'CRM','description'=>'Runs after a transaction-safe opportunity stage move.','fields'=>['opportunity.id','opportunity.name','opportunity.stage_id','opportunity.stage','opportunity.status']],
            ['key'=>'crm.opportunity.won','label'=>'CRM opportunity won','group'=>'CRM','description'=>'Runs when an opportunity moves into a won stage.','fields'=>['opportunity.id','opportunity.name','opportunity.amount_minor','opportunity.currency']],
            ['key'=>'crm.opportunity.lost','label'=>'CRM opportunity lost','group'=>'CRM','description'=>'Runs when an opportunity moves into a lost stage.','fields'=>['opportunity.id','opportunity.name','opportunity.amount_minor','opportunity.currency']],
            ['key'=>'crm.activity.created','label'=>'CRM activity created','group'=>'CRM','description'=>'Runs when a call, meeting, task or other CRM activity is recorded.','fields'=>['activity.id','activity.subject_type','activity.subject_id','activity.type','activity.title']],
            ['key'=>'membership.granted','label'=>'Membership granted','group'=>'Membership','description'=>'Runs when a membership is granted directly or created from Commerce.','fields'=>['membership.id','membership.plan_id','membership.user_id','membership.status']],
            ['key'=>'membership.status_changed','label'=>'Membership status changed','group'=>'Membership','description'=>'Runs after a membership status transition.','fields'=>['membership.id','membership.plan_id','membership.user_id','membership.status','event.from','event.to']],
            ['key'=>'helpdesk.ticket.created','label'=>'Helpdesk ticket created','group'=>'Helpdesk','description'=>'Runs when a support ticket is created.','fields'=>['ticket.id','ticket.reference','ticket.subject','ticket.status','ticket.priority','ticket.assigned_to']],
            ['key'=>'helpdesk.reply.added','label'=>'Helpdesk reply added','group'=>'Helpdesk','description'=>'Runs when a public support reply is added.','fields'=>['ticket.id','ticket.reference','ticket.status','ticket.priority']],
            ['key'=>'helpdesk.note.added','label'=>'Helpdesk internal note added','group'=>'Helpdesk','description'=>'Runs when an internal support note is added.','fields'=>['ticket.id','ticket.reference','ticket.status','ticket.priority']],
            ['key'=>'helpdesk.status.changed','label'=>'Helpdesk status changed','group'=>'Helpdesk','description'=>'Runs after a ticket status transition.','fields'=>['ticket.id','ticket.reference','ticket.status','event.from','event.to']],
            ['key'=>'helpdesk.priority.changed','label'=>'Helpdesk priority changed','group'=>'Helpdesk','description'=>'Runs after a ticket priority change and SLA recalculation.','fields'=>['ticket.id','ticket.reference','ticket.priority','event.from','event.to']],
            ['key'=>'helpdesk.assigned_to.changed','label'=>'Helpdesk assignment changed','group'=>'Helpdesk','description'=>'Runs when ticket ownership changes.','fields'=>['ticket.id','ticket.reference','ticket.assigned_to','event.from','event.to']],
            ['key'=>'schedule.hourly','label'=>'Every hour','group'=>'Schedule','description'=>'Runs on Nexora’s hourly workflow tick.','fields'=>['schedule.iso','schedule.hour']],
            ['key'=>'schedule.daily','label'=>'Every day','group'=>'Schedule','description'=>'Runs on Nexora’s daily workflow tick at 00:05 application time.','fields'=>['schedule.iso','schedule.date']],
            ['key'=>'webhook.inbound','label'=>'Inbound webhook received','group'=>'Integration','description'=>'Runs for a verified Nexora inbound webhook endpoint.','fields'=>['webhook.endpoint_id','webhook.endpoint_slug','payload']],
            ['key'=>'manual','label'=>'Manual run','group'=>'System','description'=>'Runs only when an authorized Admin starts it manually.','fields'=>[]],
        ] as $definition) $this->register($definition);
    }

    /** @param array<string,mixed> $definition */
    public function register(array $definition): void
    {
        $key=trim((string)($definition['key']??''));
        if ($key==='' || ! preg_match('/^[a-z0-9][a-z0-9._-]+$/',$key)) throw new InvalidArgumentException('Automation trigger requires a stable dotted key.');
        if (isset($this->items[$key])) throw new InvalidArgumentException('Automation trigger already registered: '.$key);
        $this->items[$key]=$definition+['key'=>$key,'label'=>$key,'group'=>'Extension','description'=>'','fields'=>[]];
    }

    /** @return array<string,array<string,mixed>> */
    public function all(): array { return $this->items; }
    public function has(string $key): bool { return isset($this->items[$key]); }
    public function get(string $key): ?array { return $this->items[$key] ?? null; }
}
