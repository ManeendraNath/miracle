New Support Ticket

Ticket Number: <?= $ticket->ticket_number ?>

Subject: <?= $ticket->subject ?>

Customer: <?= $ticket->user->username ?? '' ?>

Priority: <?= $ticket->priority ?>

Status: <?= $ticket->status ?>

A customer has opened a new support ticket.