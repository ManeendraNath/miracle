<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%service_renewal_history}}`.
 */
class m261006_103404_create_service_renewal_history_table extends Migration
{

    private $tableName = '{{%service_renewal_history}}';

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // High-performance collation optimization for enterprise setups
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        if ($this->db->getTableSchema($this->tableName, true) === null) {
            $this->createTable($this->tableName, [
                'id' => $this->primaryKey(),
                'item_type' => "ENUM('domain', 'hosting', 'maintenance') NOT NULL",
                'item_id' => $this->integer(11)->notNull()->comment('The Primary Key ID of the corresponding domain, hosting, or AMC record'),
                'invoice_id' => $this->integer(11)->defaultValue(null)->comment('Link to billing record if applicable'),
                'transaction_id' => $this->integer(11)->defaultValue(null)->comment('Link to transaction log gateway reference'),
                
                // Active variable metrics per cycle
                'billed_amount' => $this->decimal(10, 2)->notNull()->comment('The exact rate charged for this specific year extension'),
                'renewal_period_months' => $this->integer(3)->notNull()->defaultValue(12)->comment('Usually 12 months, or custom adjustments'),
                
                'start_date' => $this->date()->notNull()->comment('Cycle coverage commencement date'),
                'end_date' => $this->date()->notNull()->comment('Calculated new expiry timestamp threshold'),
                
                'logged_at' => $this->integer(11)->notNull()->comment('Timestamp of payment verification execution'),
            ]);

            $this->createIndex('idx-renewal_history-lookup', '{{%service_renewal_history}}', ['item_type', 'item_id']);
            $this->createIndex('idx-renewal_history-dates', '{{%service_renewal_history}}', ['start_date', 'end_date']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }
}
