<?php

declare(strict_types=1);

namespace App\Nexora\Helpdesk\Contracts;

use App\Models\HelpdeskTicket;

interface HelpdeskTicketManagerContract
{
    public function create(array $attributes, ?int $actorId = null): HelpdeskTicket;
    public function addMessage(HelpdeskTicket $ticket, string $body, bool $internal, ?int $actorId = null): HelpdeskTicket;
    public function updateState(HelpdeskTicket $ticket, array $attributes, ?int $actorId = null): HelpdeskTicket;
}
