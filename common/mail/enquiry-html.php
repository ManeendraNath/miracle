<?php

declare(strict_types=1);

/**
 * A website enquiry (contact form, audit request) sent to us; Reply-To is the visitor.
 *
 * @var yii\web\View $this
 * @var string $eyebrow
 * @var string $title
 * @var array $rows [label, value] pairs; empty values are left out
 * @var string $message what the visitor wrote
 * @var string $replyEmail
 * @var string $replySubject
 */
echo $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => $eyebrow,
    'title' => $title,
    'rows' => array_filter($rows, static fn(array $row): bool => trim((string) $row[1]) !== ''),
    'preformatted' => $message,
    'button' => ['Reply', 'mailto:' . $replyEmail . '?subject=' . rawurlencode('Re: ' . $replySubject)],
]);
