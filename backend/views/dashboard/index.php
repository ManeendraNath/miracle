<?php

use common\models\SupportTicket;
use common\models\Transaction;
use hail812\adminlte3\assets\AdminLteAsset;
use hail812\adminlte3\assets\PluginAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $metrics Summary metrics dictionary evaluated from SiteController query scopes */
/** @var bool $canBilling Admin+ only: Managers get no money figures */
$assetDir = Yii::$app->assetManager->getPublishedUrl('@vendor/hail812/yii2-adminlte3/src/assets');

// This forces Yii to automatically publish and load all AdminLTE 3 CSS, JS, and Plugins
AdminLteAsset::register($this);

// AdminLTE plugin base (FontAwesome itself comes from AppAsset; register() takes no plugin list)
PluginAsset::register($this);

// Chart.js is shipped with AdminLTE but is not loaded automatically.
$chartJsUrl = Yii::$app->assetManager->publish('@vendor/almasaeed2010/adminlte/plugins/chart.js/Chart.min.js')[1];

$this->registerJsFile(
    $chartJsUrl,
    [
            'depends' => [
                \yii\web\JqueryAsset::class,
            ],
        ]
);
$this->title = 'Miracle Corporate Control Center';
?>

<?php
$kpis = [
    ['bg-info', (int) $openSupportTickets, 'Open Support Requests', 'fas fa-headset', 'Review Support', ['/support-ticket/index']],
    ['bg-danger', (int) $highPrioritySupportTickets, 'High Priority Support', 'fas fa-exclamation-triangle', 'Review Priority Tickets', ['/support-ticket/index', 'SupportTicketSearch' => ['priority' => SupportTicket::PRIORITY_HIGH]]],
    ['bg-primary', (int) $metrics['totalClients'], 'Customer Accounts', 'fas fa-users', 'Manage Clients', ['/user/index']],
    ['bg-success', Yii::$app->formatter->asCurrency((float) $metrics['totalRevenueCollected'], 'INR'), 'Total Revenue Settled', 'fas fa-cash-register', 'Review Paid Invoices', ['/invoice/index']],
    ['bg-warning', Yii::$app->formatter->asCurrency((float) $metrics['unpaidBalance'], 'INR'), 'Outstanding Due Balance', 'fas fa-comments-dollar', 'Track Accounts Due', ['/invoice/index']],
    ['bg-secondary', (int) $metrics['totalInvoicesCount'], 'Invoices Generated', 'fas fa-file-invoice-dollar', 'View Billing History', ['/invoice/index']],
];
// Managers don't see billing figures (they can't open those sections either).
if (!$canBilling) {
    $kpis = array_filter($kpis, static fn(array $kpi): bool => $kpi[5][0] !== '/invoice/index');
}
$assets = [
    ['fas fa-globe text-primary', 'Domains', $metrics['activeDomainsCount'], ['/domains/index']],
    ['fas fa-cloud text-success', 'Web Hosting', $metrics['activeHostingCount'], ['/hosting/index']],
    ['fas fa-tools text-warning', 'Maintenance (AMC)', $metrics['underCareAmcCount'], ['/maintenance/index']],
];
?>
<div class="container-fluid">
    <div class="row">
        <?php foreach ($kpis as [$bg, $value, $label, $icon, $linkLabel, $url]): ?>
            <div class="col-lg-4 col-sm-6">
                <div class="small-box <?= $bg ?>">
                    <div class="inner">
                        <h3 class="text-nowrap"><?= Html::encode($value) ?></h3>
                        <p><?= $label ?></p>
                    </div>
                    <div class="icon"><i class="<?= $icon ?>"></i></div>
                    <?= Html::a($linkLabel . ' <i class="fas fa-arrow-circle-right"></i>', $url, ['class' => 'small-box-footer']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($recentSupportTickets)): ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-headset mr-2"></i>Recent Support Requests</h3>
                <div class="card-tools">
                    <?= Html::a('View All', ['/support-ticket/index'], ['class' => 'btn btn-sm btn-outline-info']) ?>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-valign-middle mb-0">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Client</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSupportTickets as $ticket): ?>
                            <tr>
                                <td class="text-nowrap"><?= Html::encode($ticket->ticket_number) ?></td>
                                <td>
                                    <?php if ($ticket->user): ?>
                                        <span title="<?= Html::encode($ticket->user->email) ?>" data-toggle="tooltip"><?= Html::encode($ticket->user->username) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode($ticket->subject) ?></td>
                                <td><?= Html::encode(ucfirst((string) $ticket->priority)) ?></td>
                                <td><?= Html::encode(ucwords(str_replace('_', ' ', (string) $ticket->status))) ?></td>
                                <td class="text-nowrap"><?= Yii::$app->formatter->asDatetime($ticket->updated_at, 'php:d M Y, h:i A') ?></td>
                                <td class="text-right">
                                    <?= Html::a('<i class="fas fa-comments"></i>', ['/support-ticket/view', 'number' => $ticket->ticket_number], [
                                        'class' => 'btn btn-sm btn-outline-primary',
                                        'title' => 'Open Conversation',
                                        'data-toggle' => 'tooltip',
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <?php if ($canBilling): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-wallet text-primary mr-2"></i>Accounts Receivable</h3>
                    <div class="card-tools">
                        <?= Html::a('View Invoices', ['/invoice/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asCurrency((float) $metrics['outstandingReceivables'], 'INR') ?></div>
                            <small class="text-muted">Total outstanding unpaid balance</small>
                        </div>
                        <div class="text-right">
                            <span class="badge badge-danger p-2"><i class="fas fa-exclamation-triangle mr-1"></i><?= (int) $metrics['overdueInvoicesCount'] ?> Overdue</span>
                            <div><small class="text-muted">Past due date</small></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="btn btn-warning btn-sm disabled mr-3"><i class="fas fa-file-invoice-dollar fa-fw"></i></span>
                            <div>
                                <div class="font-weight-bold">Manual UPI Review Requests</div>
                                <small class="text-muted">Payment proofs awaiting verification</small>
                            </div>
                        </div>
                        <?php if ($metrics['pendingUpiVerifications'] > 0): ?>
                            <?= Html::a((int) $metrics['pendingUpiVerifications'] . ' Review Required', ['/transaction/index', 'TransactionSearch' => ['status' => 'Pending', 'gateway_id' => Transaction::GATEWAY_UPI_BANK]], ['class' => 'btn btn-sm btn-warning font-weight-bold']) ?>
                        <?php else: ?>
                            <span class="badge badge-success p-2"><i class="fas fa-check mr-1"></i>All Clear</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-server text-info mr-2"></i>Infrastructure Assets</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-valign-middle mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th class="text-center">Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as [$icon, $label, $count, $url]): ?>
                                <tr>
                                    <td><i class="<?= $icon ?> fa-fw mr-2"></i><?= $label ?></td>
                                    <td class="text-center font-weight-bold"><?= (int) $count ?></td>
                                    <td class="text-right">
                                        <?= Html::a('<i class="fas fa-external-link-alt"></i>', $url, ['class' => 'btn btn-sm btn-default', 'title' => 'Manage ' . $label]) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <?php if ($canBilling): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line text-success mr-2"></i>Revenue</h3>
                    <div class="card-tools">
                        <?= Html::a('View Transactions', ['/transaction/index'], ['class' => 'btn btn-sm btn-outline-success']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asCurrency((float) $metrics['totalRevenueCollected'], 'INR') ?></div>
                            <small class="text-muted">Lifetime revenue from paid invoices</small>
                        </div>
                        <div class="text-right">
                            <div class="h5 mb-0 text-success">+<?= Yii::$app->formatter->asCurrency((float) $metrics['capturedRevenueToday'], 'INR') ?></div>
                            <small class="text-muted">Settled today</small>
                        </div>
                    </div>
                    <div class="position-relative" style="height: 220px;">
                        <canvas id="sales-chart"></canvas>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-envelope-open-text mr-2"></i>Inbound Pipeline</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-id-card fa-fw text-primary mr-2"></i>Pending Audit Requests
                            </div>
                            <?= Html::a((int) $metrics['newInboundLeadsCount'] . ' Pending', ['/audit-request/index', 'AuditRequestSearch' => ['status' => 'Pending']], [
                                'class' => 'badge p-2 ' . ($metrics['newInboundLeadsCount'] > 0 ? 'badge-primary' : 'badge-light'),
                            ]) ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-users-cog fa-fw text-info mr-2"></i>Newsletter Subscribers</div>
                            <span class="font-weight-bold"><?= number_format((int) $metrics['subscribersCount']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-comments fa-fw text-secondary mr-2"></i>Contact Messages</div>
                            <span class="font-weight-bold"><?= number_format((int) $metrics['supportMessagesCount']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($canBilling): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tags text-warning mr-2"></i>Recent Coupons</h3>
                    <div class="card-tools">
                        <?= Html::a('Manage Coupons', ['/coupon/index'], ['class' => 'btn btn-sm btn-outline-warning']) ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-valign-middle mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['recentCoupon'] as $coupon): ?>
                                <tr>
                                    <td class="font-weight-bold"><?= Html::encode($coupon->code) ?></td>
                                    <td><?= $coupon->isTypeP() ? number_format((float) $coupon->amount, 2) . '%' : Yii::$app->formatter->asCurrency((float) $coupon->amount, 'INR') ?> off</td>
                                    <td class="text-center">
                                        <span class="badge <?= $coupon->isIsActiveY() ? 'badge-success' : 'badge-secondary' ?>"><?= $coupon->isIsActiveY() ? 'Active' : 'Paused' ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($metrics['recentCoupon'])): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No coupons created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
// Secure JSON encoding of dynamic database tracking data matrices arrays
$labelsJson = json_encode($metrics['chartLabels']);
$dataThisYearJson = json_encode($metrics['chartDataThisYear']);
$dataLastYearJson = json_encode($metrics['chartDataLastYear']);

$chartScript = <<< JS
$(function () {
  'use strict'

  var ticksStyle = {
    fontColor: '#495057',
    fontStyle: 'bold'
  }

  var mode = 'index'
  var intersect = true

  var \$salesChart = $('#sales-chart')
  
  // Wipe out any old pre-existing mock instance settings to prevent hover conflicts
  if (window.mySalesChartInstance) {
      window.mySalesChartInstance.destroy();
  }

  window.mySalesChartInstance = new Chart(\$salesChart, {
    type: 'bar', // Using grouped bar/line configuration overlays for high performance aesthetics
    data: {
      labels: {$labelsJson},
      datasets: [
        {
          backgroundColor: '#007bff',
          borderColor: '#007bff',
          data: {$dataThisYearJson},
          label: 'This Year'
        },
        {
          backgroundColor: '#ced4da',
          borderColor: '#ced4da',
          data: {$dataLastYearJson},
          label: 'Last Year'
        }
      ]
    },
    options: {
      maintainAspectRatio: false,
      tooltips: {
        mode: mode,
        intersect: intersect,
        callbacks: {
            label: function(tooltipItem, data) {
                return data.datasets[tooltipItem.datasetIndex].label + ": ₹" + tooltipItem.yLabel.toLocaleString('en-IN');
            }
        }
      },
      hover: {
        mode: mode,
        intersect: intersect
      },
      legend: {
        display: true,
        position: 'top'
      },
      scales: {
        yAxes: [{
          gridLines: {
            display: true,
            lineWidth: '4px',
            color: 'rgba(0, 0, 0, .05)',
            zeroLineColor: 'transparent'
          },
          ticks: $.extend({
            beginAtZero: true,
            callback: function (value) {
              if (value >= 1000) {
                value /= 1000
                value += 'k'
              }
              return '₹' + value
            }
          }, ticksStyle)
        }],
        xAxes: [{
          display: true,
          gridLines: {
            display: false
          },
          ticks: ticksStyle
        }]
      }
    }
  })
})
JS;
if ($canBilling) {
    $this->registerJs($chartScript);
}
?>
