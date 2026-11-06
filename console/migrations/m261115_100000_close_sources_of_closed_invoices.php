<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Invoices written off before closing reached their carried sources still say Carried Forward, pointing at a
 * closed invoice. Repeats so a chain of sources is closed link by link.
 */
final class m261115_100000_close_sources_of_closed_invoices extends Migration
{
    public function safeUp(): void
    {
        do {
            $changed = $this->db->createCommand(
                "UPDATE {{%invoice}} s
                 JOIN {{%invoice_item}} ii ON ii.source_invoice_id = s.id AND ii.item_type = 5
                 JOIN {{%invoice}} t ON t.id = ii.invoice_id
                 SET s.status = 'Refunded', s.updated_at = :now
                 WHERE s.status = 'Carried_Forward' AND t.status = 'Refunded'",
                [':now' => time()],
            )->execute();
        } while ($changed > 0);
        // As closing does now: a coupon on a closed invoice with no money kept is free to be used again.
        $this->execute(
            "DELETE r FROM {{%coupon_redemption}} r
             JOIN {{%invoice}} i ON i.id = r.invoice_id
             WHERE i.status = 'Refunded' AND NOT EXISTS (SELECT 1 FROM {{%transaction}} t WHERE t.invoice_id = i.id AND t.status = 'Captured' AND t.is_unapplied = 0)"
        );
    }

    /** Data only, and which rows changed isn't recorded. */
    public function safeDown(): bool
    {
        return true;
    }
}
