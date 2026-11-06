<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

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
        // As closing does now: a coupon on a closed invoice is free to be used again, unless money was kept on that
        // invoice or on one its balance was carried to.
        $redemptions = (new Query())->select(['r.id', 'r.invoice_id'])->from(['r' => '{{%coupon_redemption}}'])
            ->innerJoin(['i' => '{{%invoice}}'], 'i.id = r.invoice_id')->where(['i.status' => 'Refunded'])->all($this->db);
        foreach ($redemptions as $redemption) {
            if (!$this->moneyKeptFrom((int) $redemption['invoice_id'])) {
                $this->delete('{{%coupon_redemption}}', ['id' => $redemption['id']]);
            }
        }
    }

    private function moneyKeptFrom(int $invoiceId): bool
    {
        // Each step goes to a newer invoice, so this can't loop.
        for ($id = $invoiceId; $id !== 0;) {
            if ((new Query())->from('{{%transaction}}')->where(['invoice_id' => $id, 'status' => 'Captured', 'is_unapplied' => 0])->exists($this->db)) {
                return true;
            }
            $id = (int) (new Query())->select('invoice_id')->from('{{%invoice_item}}')->where(['source_invoice_id' => $id, 'item_type' => 5])->orderBy(['id' => SORT_DESC])->scalar($this->db);
        }

        return false;
    }

    /** Data only, and which rows changed isn't recorded. */
    public function safeDown(): bool
    {
        return true;
    }
}
