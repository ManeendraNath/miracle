<?php

namespace backend\models\searches;

use common\helpers\GridPaginationHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Transaction;
use common\models\traits\DateRangeSearchTrait;

/**
 * TransactionSearch represents the model behind the search form of `common\models\Transaction`.
 */
class TransactionSearch extends Transaction
{
    use DateRangeSearchTrait;

    // Transient placeholder to handle the composite range string text
    public $date_range;
    public ?string $created_at_range = null;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['id', 'invoice_id', 'gateway_id'], 'integer'],
            [['gateway_payment_id', 'gateway_order_id', 'status', 'user_payment_notes', 'payment_receipt_file', 'raw_payload', 'date_range', 'created_at_range'], 'safe'],
            [['amount', 'gateway_fee'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios(): array
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = Transaction::find()->with('invoice');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => GridPaginationHelper::getPageSize(),
            ],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $gatewayId = $this->gateway_id;
        if ((string) $gatewayId === (string) Transaction::GATEWAY_MANUAL) {
            // Manual entries are stored as 0 or NULL.
            $query->andWhere(['or', ['gateway_id' => Transaction::GATEWAY_MANUAL], ['gateway_id' => null]]);
            $gatewayId = null;
        }
        $query->andFilterWhere([
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'gateway_id' => $gatewayId,
            'amount' => $this->amount,
            'gateway_fee' => $this->gateway_fee,
        ]);

        $query->andFilterWhere(['like', 'gateway_payment_id', $this->gateway_payment_id])
                ->andFilterWhere(['like', 'gateway_order_id', $this->gateway_order_id])
                ->andFilterWhere(['status' => $this->status])
                ->andFilterWhere(['like', 'user_payment_notes', $this->user_payment_notes])
                ->andFilterWhere(['like', 'payment_receipt_file', $this->payment_receipt_file])
                ->andFilterWhere(['like', 'raw_payload', $this->raw_payload]);

        // 📅 DYNAMIC DATE RANGE FILTERING LOGIC
        $this->applyTimestampDateRange($query, 'paid_at', $this->date_range);
        $this->applyTimestampDateRange($query, 'created_at', $this->created_at_range);

        return $dataProvider;
    }
}
