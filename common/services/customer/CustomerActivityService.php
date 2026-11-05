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
                // Pending/failed Razorpay rows are unpaid or abandoned checkout orders, not payments.
                ->andWhere(['or',
                    ['not', ['transaction.gateway_id' => Transaction::GATEWAY_RAZORPAY]],
                    ['transaction.gateway_id' => null],
                    ['transaction.status' => [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED]],
                ])
                ->orderBy(['transaction.created_at' => SORT_DESC])
                ->limit($limit)
                ->all()
            as $transaction
        ) {
            $activities[] = [
                'icon' => 'fas fa-rupee-sign',
                'badgeClass' => match ($transaction->status) {
                    Transaction::STATUS_CAPTURED => 'bg-success',
                    Transaction::STATUS_FAILED => 'bg-danger',
                    default => 'bg-secondary',
                },
                'title' => match ($transaction->status) {
                    Transaction::STATUS_CAPTURED => $transaction->isUnapplied() ? 'Payment Not Applied' : 'Payment Confirmed',
                    Transaction::STATUS_REFUNDED => 'Payment Refunded',
                    Transaction::STATUS_FAILED => 'Payment Rejected',
                    default => 'Payment Submitted',
                },
                'description' => $transaction->customerReference() ?: 'Reference Uploaded',
                // When it happened, not when the row was created: a refund comes long after the payment.
                'time' => (int) (match ($transaction->status) {
                    Transaction::STATUS_CAPTURED => $transaction->paid_at,
                    Transaction::STATUS_REFUNDED => $transaction->refunded_at,
                    Transaction::STATUS_FAILED => $transaction->updated_at,
                    default => null,
                } ?: $transaction->created_at),
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
                ->andWhere(['not in', 'service_renewal_history.invoice_id', \common\services\billing\RenewalFulfilmentService::fullyRefundedInvoices()])
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
