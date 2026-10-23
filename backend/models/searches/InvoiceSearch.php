<?php

namespace backend\models\searches;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\helpers\GridPaginationHelper;
use common\models\Invoice;
use common\models\traits\DateRangeSearchTrait;

/**
 * InvoiceSearch represents the model behind the search form of `common\models\Invoice`.
 */
class InvoiceSearch extends Invoice
{

    use DateRangeSearchTrait;

    public ?string $due_date_range = null;
    public ?string $created_at_range = null;

    public function rules()
    {
        return [
            [['id', 'user_id', 'id'], 'integer'],
            [['invoice_number', 'client_name', 'client_name', 'code', 'status', 'due_date_range', 'created_at_range'], 'safe'],
            [['subtotal_amount', 'discount_amount', 'total_payable'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
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
        $query = Invoice::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => GridPaginationHelper::getPageSize(),
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ],
            ],
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // Exact matches
        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
        ]);

        // String searches
        $query->andFilterWhere(['like', 'invoice_number', $this->invoice_number])
                ->andFilterWhere(['like', 'client_name', $this->client_name]);

        $this->applySqlDateRange($query, 'invoice.due_date', $this->due_date_range);
        // 📅 DYNAMIC DATE RANGE FILTERING LOGIC
        if (!empty($this->date_range) && strpos($this->date_range, ' - ') !== false) {
            $this->applyTimestampDateRange($query, 'invoice.created_at', $this->created_at_range);
        }

        return $dataProvider;
    }
}
