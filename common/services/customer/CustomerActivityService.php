<?php

declare(strict_types=1);

namespace common\services\customer;

use common\models\CustomerNotification;
use common\models\Invoice;
use common\models\ServiceRenewalHistory;
use common\models\SupportTicket;
use common\models\Transaction;

final class CustomerActivityService
{
    public static function getActivities(int $userId, int $limit = 50): array
    {
        $activities = [];

        $notifications = CustomerNotification::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
        foreach ($notifications as $notification) {
            $activities[] = [
                'icon' => 'fas fa-bell',
                'badgeClass' => 'bg-primary',
                'title' => $notification->title,
                'description' => $notification->message,
                'time' => (int) $notification->created_at,
                'url' => $notification->route ?: '#',
            ];
        }

        foreach (
            Invoice::find()
                ->where(['user_id' => $userId])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->all()
            as $invoice
        ) {
            $activities[] = [
                'icon' => 'fas fa-file-invoice',
                'badgeClass' => 'bg-info',
                'title' => 'Invoice Generated',
                'description' => $invoice->invoice_number,
                'time' => (int) $invoice->created_at,
                'url' => '/invoice/index',
            ];
        }

        foreach (
            Transaction::find()
                ->innerJoinWith('invoice', false)
                ->where(['invoice.user_id' => $userId])
                ->orderBy(['transaction.created_at' => SORT_DESC])
                ->limit($limit)
                ->all()
            as $transaction
        ) {
            $activities[] = [
                'icon' => 'fas fa-rupee-sign',
                'badgeClass' => 'bg-success',
                'title' => 'Payment Submitted',
                'description' => $transaction->gateway_payment_id ?: 'Reference Uploaded',
                'time' => (int) $transaction->created_at,
                'url' => '/payment/index',
            ];
        }

        foreach (
            SupportTicket::find()
                ->where(['user_id' => $userId])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->all()
            as $ticket
        ) {
            $activities[] = [
                'icon' => 'fas fa-life-ring',
                'badgeClass' => 'bg-warning text-dark',
                'title' => 'Support Ticket Created',
                'description' => $ticket->subject,
                'time' => (int) $ticket->created_at,
                'url' => '/support/view?number=' . rawurlencode($ticket->ticket_number),
            ];
        }

        foreach (
            ServiceRenewalHistory::find()
                ->innerJoinWith('invoice', false)
                ->where(['invoice.user_id' => $userId])
                ->orderBy(['service_renewal_history.logged_at' => SORT_DESC])
                ->limit($limit)
                ->all()
            as $renewal
        ) {
            $activities[] = [
                'icon' => 'fas fa-sync-alt',
                'badgeClass' => 'bg-success',
                'title' => 'Service Renewed',
                'description' => ucfirst($renewal->item_type) . ' renewed until ' . $renewal->end_date,
                'time' => (int) $renewal->logged_at,
                'url' => '/renewal/index',
            ];
        }

        usort($activities, static fn(array $a, array $b) => $b['time'] <=> $a['time']);

        return array_slice($activities, 0, $limit);
    }
}
